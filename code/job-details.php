<?php
// NextHire - Job Details & Candidate Screening Panel

require_once __DIR__ . '/config.php';
$pdo = require __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_auth();

$company_id = $_SESSION['company_id'];
$job_id = intval($_GET['id'] ?? 0);

// Validate job ownership
if ($job_id <= 0 || !check_job_ownership($pdo, $job_id, $company_id)) {
    header('Location: dashboard.php');
    exit;
}

$success_msg = '';
$error_msg = '';
$active_tab = $_GET['tab'] ?? 'pool';

// Fetch job criteria
try {
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch();
    
    // Fetch company API keys
    $stmt = $pdo->prepare("SELECT gemini_api_key, openai_api_key FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company_keys = $stmt->fetch();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle CV upload and processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Handle Uploading Candidate Resumes
    if (isset($_POST['action']) && $_POST['action'] === 'upload_cv') {
        $candidate_name_manual = trim($_POST['manual_name'] ?? '');
        $candidate_email_manual = trim($_POST['manual_email'] ?? '');
        $pasted_text = trim($_POST['pasted_cv_text'] ?? '');
        
        $files_uploaded = false;
        $total_processed = 0;
        $total_failed = 0;
        
        // Check if files were uploaded
        if (!empty($_FILES['resumes']['name'][0])) {
            $files = $_FILES['resumes'];
            $file_count = count($files['name']);
            
            for ($i = 0; $i < $file_count; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $files['tmp_name'][$i];
                    $original_name = basename($files['name'][$i]);
                    $file_ext = pathinfo($original_name, PATHINFO_EXTENSION);
                    
                    // Generate a secure, unique filename
                    $unique_filename = uniqid('cv_') . '.' . $file_ext;
                    $destination = UPLOAD_DIR . '/' . $unique_filename;
                    
                    if (move_uploaded_file($tmp_name, $destination)) {
                        // Extract text
                        $extracted_text = extract_text_from_file($destination, $files['type'][$i]);
                        
                        if (!empty($extracted_text)) {
                            // Process candidate matching
                            $match_result = process_candidate_matching($extracted_text, $job, $company_keys);
                            
                            // Save candidate to database
                            try {
                                $stmt = $pdo->prepare("
                                    INSERT INTO candidates 
                                    (job_id, name, email, phone, resume_text, resume_path, match_score, skills_found, experience_years, education_found, summary, status) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'applied')
                                ");
                                $stmt->execute([
                                    $job_id,
                                    $match_result['name'],
                                    $match_result['email'],
                                    $match_result['phone'],
                                    $extracted_text,
                                    $unique_filename,
                                    $match_result['match_score'],
                                    $match_result['skills_found'],
                                    $match_result['experience_years'],
                                    $match_result['education_found'],
                                    $match_result['summary']
                                ]);
                                $total_processed++;
                                $files_uploaded = true;
                            } catch (PDOException $e) {
                                $total_failed++;
                            }
                        } else {
                            $total_failed++;
                        }
                    } else {
                        $total_failed++;
                    }
                }
            }
        }
        
        // Check if copy-pasted text was submitted as an alternative
        if (!$files_uploaded && !empty($pasted_text)) {
            if (empty($candidate_name_manual)) {
                $candidate_name_manual = "Pasted Profile " . date('H:i');
            }
            
            // Process candidate matching on pasted text
            $match_result = process_candidate_matching($pasted_text, $job, $company_keys);
            
            // Override name/email if manual inputs are provided
            if (!empty($candidate_name_manual) && $match_result['name'] === 'Candidate Profile') {
                $match_result['name'] = $candidate_name_manual;
            }
            if (!empty($candidate_email_manual)) {
                $match_result['email'] = $candidate_email_manual;
            }
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO candidates 
                    (job_id, name, email, phone, resume_text, resume_path, match_score, skills_found, experience_years, education_found, summary, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'applied')
                ");
                $stmt->execute([
                    $job_id,
                    $match_result['name'],
                    $match_result['email'],
                    $match_result['phone'],
                    $pasted_text,
                    '',
                    $match_result['match_score'],
                    $match_result['skills_found'],
                    $match_result['experience_years'],
                    $match_result['education_found'],
                    $match_result['summary']
                ]);
                $total_processed++;
            } catch (PDOException $e) {
                $total_failed++;
            }
        }
        
        if ($total_processed > 0) {
            $success_msg = "Successfully processed " . $total_processed . " candidate CVs.";
            // Automatically run shortlisting to keep it updated
            run_smart_shortlisting($pdo, $job_id);
        }
        if ($total_failed > 0) {
            $error_msg = "Failed to process " . $total_failed . " resume files (verify files are unencrypted text or standard PDFs).";
        }
    }
    
    // 2. Handle Smart Shortlisting Request
    elseif (isset($_POST['action']) && $_POST['action'] === 'run_shortlist') {
        run_smart_shortlisting($pdo, $job_id);
        $success_msg = "Smart shortlisting executed. Top " . $job['interview_limit'] . " candidates have been shortlisted based on match scores.";
        $active_tab = 'shortlist';
    }
}

// Fetch applicants
$search_query = trim($_GET['search'] ?? '');
$candidates = [];
try {
    if (!empty($search_query)) {
        $search_wildcard = '%' . $search_query . '%';
        $stmt = $pdo->prepare("
            SELECT * FROM candidates 
            WHERE job_id = ? 
              AND (name LIKE ? OR skills_found LIKE ? OR education_found LIKE ? OR resume_text LIKE ?)
            ORDER BY match_score DESC, created_at ASC
        ");
        $stmt->execute([$job_id, $search_wildcard, $search_wildcard, $search_wildcard, $search_wildcard]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM candidates WHERE job_id = ? ORDER BY match_score DESC, created_at ASC");
        $stmt->execute([$job_id]);
    }
    $candidates = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Error loading applicants: " . $e->getMessage();
}

// Fetch discussion notes
$messages = [];
try {
    $stmt = $pdo->prepare("
        SELECT m.id, m.message, m.created_at, m.user_id, u.name AS sender_name
        FROM messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.job_id = ?
        ORDER BY m.id ASC
    ");
    $stmt->execute([$job_id]);
    $messages = $stmt->fetchAll();
} catch (PDOException $e) {
    // Handle silently
}
$last_msg_id = !empty($messages) ? end($messages)['id'] : 0;

require_once __DIR__ . '/header.php';
?>

<!-- Back & Title Header -->
<div class="mb-4">
    <a href="jobs.php" class="text-primary text-decoration-none d-inline-flex align-items-center gap-1 mb-2">
        <i class="fa-solid fa-arrow-left"></i> Back to Job Postings
    </a>
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1 class="h2 text-light mb-1 brand-font"><?php echo htmlspecialchars($job['title']); ?></h1>
            <div class="d-flex flex-wrap align-items-center gap-3 text-secondary mt-1" style="font-size: 0.9rem;">
                <span><i class="fa-solid fa-building me-1"></i> <?php echo htmlspecialchars($_SESSION['company_name']); ?></span>
                <span><i class="fa-solid fa-calendar me-1"></i> Posted: <?php echo date('M d, Y', strtotime($job['created_at'])); ?></span>
                <span><i class="fa-solid fa-bullseye me-1"></i> Limit: Top <?php echo $job['interview_limit']; ?></span>
            </div>
        </div>
        <div>
            <button class="btn btn-premium-outline btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#uploadCollapse">
                <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Resumes
            </button>
        </div>
    </div>
</div>

<!-- Alert banners -->
<?php if (!empty($success_msg)): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert" style="background-color: rgba(16, 185, 129, 0.15); border-color: rgba(16, 185, 129, 0.3); color: #34d399; border-radius: 10px;">
        <i class="fa-solid fa-circle-check"></i>
        <div><?php echo htmlspecialchars($success_msg); ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($error_msg)): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert" style="background-color: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.3); color: #f87171; border-radius: 10px;">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div><?php echo htmlspecialchars($error_msg); ?></div>
    </div>
<?php endif; ?>

<!-- Collapsible CV Upload Form -->
<div class="collapse mb-4" id="uploadCollapse">
    <div class="card-premium">
        <h3 class="h5 text-light brand-font mb-4"><i class="fa-solid fa-file-arrow-up text-primary me-2"></i> Upload Applicant CVs</h3>
        
        <form action="job-details.php?id=<?php echo $job_id; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_cv">
            
            <div class="row g-4">
                <!-- File Upload -->
                <div class="col-md-6 border-end border-secondary">
                    <label for="resumes" class="form-label form-label-premium">Select Resume Files (PDF / TXT)</label>
                    <input type="file" name="resumes[]" id="resumes" class="form-control form-control-premium" multiple accept=".pdf,.txt">
                    <small class="text-secondary d-block mt-2" style="font-size: 0.8rem;">
                        You can select and upload multiple resume files at once.
                    </small>
                </div>
                
                <!-- Manual copy-paste text block -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="manual_name" class="form-label form-label-premium">Candidate Name (Optional)</label>
                        <input type="text" name="manual_name" id="manual_name" class="form-control form-control-premium" placeholder="e.g. Alice Smith">
                    </div>
                    <div class="mb-3">
                        <label for="manual_email" class="form-label form-label-premium">Candidate Email (Optional)</label>
                        <input type="email" name="manual_email" id="manual_email" class="form-control form-control-premium" placeholder="e.g. alice@example.com">
                    </div>
                    <div>
                        <label for="pasted_cv_text" class="form-label form-label-premium">Or Paste CV Text</label>
                        <textarea name="pasted_cv_text" id="pasted_cv_text" class="form-control form-control-premium" rows="4" placeholder="Paste full resume contents here if you do not have the PDF file..."></textarea>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-premium">Extract & Screen Resumes</button>
                <button type="button" class="btn btn-premium-outline" data-bs-toggle="collapse" data-bs-target="#uploadCollapse">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Tabbed Navigation Structure -->
<ul class="nav nav-tabs border-secondary mb-4" id="jobDetailsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link text-light bg-transparent border-0 border-bottom border-3 <?php echo ($active_tab === 'pool') ? 'active border-primary fw-bold text-primary' : 'text-secondary'; ?>" id="pool-tab" data-bs-toggle="tab" data-bs-target="#pool" type="button" role="tab" aria-controls="pool" aria-selected="true" onclick="history.replaceState(null, '', '?id=<?php echo $job_id; ?>&tab=pool')">
            <i class="fa-solid fa-users me-2"></i> Applicant Pool (<?php echo count($candidates); ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link text-light bg-transparent border-0 border-bottom border-3 <?php echo ($active_tab === 'shortlist') ? 'active border-warning fw-bold text-warning' : 'text-secondary'; ?>" id="shortlist-tab" data-bs-toggle="tab" data-bs-target="#shortlist" type="button" role="tab" aria-controls="shortlist" aria-selected="false" onclick="history.replaceState(null, '', '?id=<?php echo $job_id; ?>&tab=shortlist')">
            <i class="fa-solid fa-star me-2"></i> Smart Shortlist (Top <?php echo $job['interview_limit']; ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link text-light bg-transparent border-0 border-bottom border-3 <?php echo ($active_tab === 'chat') ? 'active border-success fw-bold text-success' : 'text-secondary'; ?>" id="chat-tab" data-bs-toggle="tab" data-bs-target="#chat" type="button" role="tab" aria-controls="chat" aria-selected="false" onclick="history.replaceState(null, '', '?id=<?php echo $job_id; ?>&tab=chat'); scrollChatToBottom();">
            <i class="fa-solid fa-comments me-2"></i> Team Discussion Chat
        </button>
    </li>
</ul>

<div class="tab-content" id="jobDetailsTabsContent">
    <!-- Tab 1: Applicant Pool -->
    <div class="tab-pane fade <?php echo ($active_tab === 'pool') ? 'show active' : ''; ?>" id="pool" role="tabpanel" aria-labelledby="pool-tab">
        <div class="card-premium">
            
            <!-- Search & Filters -->
            <form action="job-details.php" method="GET" class="mb-4">
                <input type="hidden" name="id" value="<?php echo $job_id; ?>">
                <input type="hidden" name="tab" value="pool">
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control form-control-premium" placeholder="Search by name, skills (e.g. React), education (e.g. Master)..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="btn btn-premium">Search</button>
                    <?php if (!empty($search_query)): ?>
                        <a href="job-details.php?id=<?php echo $job_id; ?>&tab=pool" class="btn btn-premium-outline">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
            
            <?php if (empty($candidates)): ?>
                <div class="text-center py-5">
                    <div class="text-secondary mb-3"><i class="fa-solid fa-users-viewfinder fa-3x text-muted"></i></div>
                    <h5 class="text-light">No Applicants Found</h5>
                    <p class="text-secondary">Click "Upload Resumes" at the top right to parse applicant documents.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-premium align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Candidate Info</th>
                                <th>Match Score</th>
                                <th>Experience</th>
                                <th>Education</th>
                                <th>Stage</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($candidates as $cand): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-light"><?php echo htmlspecialchars($cand['name']); ?></div>
                                        <div class="text-secondary" style="font-size: 0.8rem;"><?php echo htmlspecialchars($cand['email']); ?></div>
                                        <?php if (!empty($cand['skills_found'])): ?>
                                            <div class="mt-1">
                                                <small class="text-muted" style="font-size: 0.75rem;">Skills: </small>
                                                <?php 
                                                $cand_skills = explode(',', $cand['skills_found']);
                                                foreach (array_slice($cand_skills, 0, 5) as $skill) {
                                                    echo '<span class="badge bg-dark text-secondary me-1" style="font-size: 0.7rem; border: 1px solid var(--border-color);">' . htmlspecialchars(trim($skill)) . '</span>';
                                                }
                                                if (count($cand_skills) > 5) echo '<span class="text-muted" style="font-size: 0.7rem;">+ ' . (count($cand_skills) - 5) . ' more</span>';
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-bold <?php echo ($cand['match_score'] >= 80) ? 'text-success' : (($cand['match_score'] >= 50) ? 'text-warning' : 'text-danger'); ?>" style="font-size: 1.1rem;">
                                                <?php echo $cand['match_score']; ?>%
                                            </span>
                                            <div class="progress" style="width: 50px; height: 6px; background-color: var(--bg-tertiary);">
                                                <div class="progress-bar <?php echo ($cand['match_score'] >= 80) ? 'bg-success' : (($cand['match_score'] >= 50) ? 'bg-warning' : 'bg-danger'); ?>" role="progressbar" style="width: <?php echo $cand['match_score']; ?>%" aria-valuenow="<?php echo $cand['match_score']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-light fw-medium"><?php echo $cand['experience_years']; ?> years</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($cand['education_found']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($cand['status'] === 'applied'): ?>
                                            <span class="badge badge-premium badge-applied">Applied</span>
                                        <?php elseif ($cand['status'] === 'shortlisted'): ?>
                                            <span class="badge badge-premium badge-shortlisted">Shortlisted</span>
                                        <?php elseif ($cand['status'] === 'accepted'): ?>
                                            <span class="badge badge-premium badge-accepted">Accepted</span>
                                        <?php else: ?>
                                            <span class="badge badge-premium badge-rejected">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="candidate.php?id=<?php echo $cand['id']; ?>" class="btn btn-premium btn-sm py-1 px-2" style="font-size: 0.8rem;">
                                            Review Profile <i class="fa-solid fa-chevron-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Tab 2: Smart Shortlist -->
    <div class="tab-pane fade <?php echo ($active_tab === 'shortlist') ? 'show active' : ''; ?>" id="shortlist" role="tabpanel" aria-labelledby="shortlist-tab">
        <div class="card-premium mb-4">
            <h3 class="h5 text-light brand-font mb-3"><i class="fa-solid fa-wand-magic-sparkles text-warning me-2"></i> Algorithmic Smart Shortlisting</h3>
            <p class="text-secondary mb-4">
                The smart shortlisting system automatically filters, ranks, and isolates the top candidates based on their multi-dimensional match scores. It will select candidates up to your defined interview limit of <strong>Top <?php echo $job['interview_limit']; ?></strong> applicants.
            </p>
            
            <form action="job-details.php?id=<?php echo $job_id; ?>" method="POST">
                <input type="hidden" name="action" value="run_shortlist">
                <button type="submit" class="btn btn-premium"><i class="fa-solid fa-gears me-2"></i> Execute Smart Shortlist</button>
            </form>
        </div>
        
        <div class="card-premium">
            <h4 class="h6 text-light brand-font mb-3">CURRENTLY SHORTLISTED CANDIDATES</h4>
            
            <?php 
            $shortlisted_candidates = array_filter($candidates, function($c) {
                return $c['status'] === 'shortlisted';
            });
            ?>
            
            <?php if (empty($shortlisted_candidates)): ?>
                <div class="text-center py-5">
                    <div class="text-warning mb-3"><i class="fa-solid fa-triangle-exclamation fa-3x"></i></div>
                    <h5 class="text-light">No Shortlisted Candidates Yet</h5>
                    <p class="text-secondary">Click "Execute Smart Shortlist" above to automatically select and mark the top applicants.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-premium align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Name</th>
                                <th>Match Score</th>
                                <th>Experience</th>
                                <th>Education</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            foreach ($shortlisted_candidates as $cand): 
                            ?>
                                <tr>
                                    <td>
                                        <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 0.85rem;">
                                            #<?php echo $rank++; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-light"><?php echo htmlspecialchars($cand['name']); ?></div>
                                        <div class="text-secondary" style="font-size: 0.8rem;"><?php echo htmlspecialchars($cand['email']); ?></div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success" style="font-size: 1.05rem;"><?php echo $cand['match_score']; ?>%</span>
                                    </td>
                                    <td>
                                        <span class="text-light"><?php echo $cand['experience_years']; ?> years</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($cand['education_found']); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <a href="candidate.php?id=<?php echo $cand['id']; ?>" class="btn btn-premium-outline btn-sm py-1 px-2" style="font-size: 0.8rem;">
                                            Review Profile <i class="fa-solid fa-chevron-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Tab 3: Team Discussion Chat -->
    <div class="tab-pane fade <?php echo ($active_tab === 'chat') ? 'show active' : ''; ?>" id="chat" role="tabpanel" aria-labelledby="chat-tab">
        <div class="card-premium">
            <h3 class="h5 text-light brand-font mb-3"><i class="fa-solid fa-comments text-success me-2"></i> HR Team Collaboration Forum</h3>
            <p class="text-secondary mb-4">
                Use this board to discuss candidate qualifications, coordinate interview dates, and align on hiring decisions with other members of your organization's recruiting team.
            </p>
            
            <!-- Message Feed container -->
            <div class="chat-container mb-4" id="chat-container" data-job-id="<?php echo $job_id; ?>" data-last-msg-id="<?php echo $last_msg_id; ?>">
                <?php if (empty($messages)): ?>
                    <div class="text-center text-secondary py-5" id="chat-empty-state">
                        <i class="fa-regular fa-comment-dots fa-3x mb-3 text-muted"></i>
                        <h5>No discussion notes posted yet.</h5>
                        <p class="small">Be the first to post a note about the candidates for this job opening!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): 
                        $is_self = $msg['user_id'] == $_SESSION['user_id'];
                    ?>
                        <div class="chat-bubble <?php echo $is_self ? 'chat-bubble-self' : 'chat-bubble-other'; ?>">
                            <div class="fw-bold" style="font-size: 0.8rem; margin-bottom: 0.2rem;">
                                <?php echo htmlspecialchars($msg['sender_name']); ?>
                            </div>
                            <div>
                                <?php echo htmlspecialchars($msg['message']); ?>
                            </div>
                            <div class="chat-meta <?php echo $is_self ? 'chat-meta-self' : ''; ?>">
                                <?php echo date('M d, g:i a', strtotime($msg['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Message Submit Form -->
            <form action="auth.php" method="POST" id="chat-form">
                <input type="hidden" name="action" value="post_message">
                <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                <div class="input-group">
                    <input type="text" name="message" class="form-control form-control-premium" placeholder="Type your comment, interview question, or feedback note..." required autocomplete="off">
                    <button type="submit" class="btn btn-premium">Post Note <i class="fa-solid fa-paper-plane ms-1"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
