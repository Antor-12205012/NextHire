<?php
// NextHire - Candidate Profile and Analytics Board

require_once __DIR__ . '/config.php';
$pdo = require __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_auth();

$company_id = $_SESSION['company_id'];
$candidate_id = intval($_GET['id'] ?? 0);

// Fetch candidate details
try {
    $stmt = $pdo->prepare("
        SELECT c.*, j.title AS job_title, j.id AS job_id, j.required_skills, j.required_experience, j.required_education
        FROM candidates c
        JOIN jobs j ON c.job_id = j.id
        WHERE c.id = ? AND j.company_id = ?
    ");
    $stmt->execute([$candidate_id, $company_id]);
    $candidate = $stmt->fetch();
    
    if (!$candidate) {
        header('Location: dashboard.php');
        exit;
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$success_msg = '';
$error_msg = '';

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_status'])) {
    $new_status = $_POST['new_status'];
    $allowed_statuses = ['applied', 'shortlisted', 'accepted', 'rejected'];
    
    if (in_array($new_status, $allowed_statuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE candidates SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $candidate_id]);
            $candidate['status'] = $new_status; // Update local array for display
            $success_msg = "Candidate status updated to " . ucfirst($new_status) . ".";
            
            // If status changed, we can optionally re-run shortlisting or just proceed
        } catch (PDOException $e) {
            $error_msg = "Error updating candidate status: " . $e->getMessage();
        }
    }
}

// Calculate individual sub-scores for breakdown visualization
// (Same weights as used in helpers.php: 50% Skills, 35% Exp, 15% Edu)
$skills_matched = [];
$skills_score = 100;
$req_skills_str = $candidate['required_skills'];
$req_skills = array_map('trim', explode(',', strtolower($req_skills_str)));
$req_skills = array_filter($req_skills);

if (!empty($req_skills)) {
    $candidate_skills = array_map('trim', explode(',', strtolower($candidate['skills_found'])));
    $match_count = 0;
    foreach ($req_skills as $req_skill) {
        if (in_array($req_skill, $candidate_skills) || preg_match("/\b" . preg_quote($req_skill, '/') . "\b/i", $candidate['resume_text'])) {
            $match_count++;
            $skills_matched[] = $req_skill;
        }
    }
    $skills_score = round(($match_count / count($req_skills)) * 100);
}

$req_experience = intval($candidate['required_experience']);
$cand_experience = intval($candidate['experience_years']);
$experience_score = 100;
if ($req_experience > 0) {
    $experience_score = ($cand_experience >= $req_experience) ? 100 : round(($cand_experience / $req_experience) * 100);
}

$edu_weights = ['PhD' => 4, 'Master' => 3, 'Bachelor' => 2, 'High School / Other' => 1];
$candidate_edu_val = $edu_weights[$candidate['education_found']] ?? 1;
$req_edu_val = $edu_weights[$candidate['required_education']] ?? 2;
$education_score = ($candidate_edu_val >= $req_edu_val) ? 100 : round(($candidate_edu_val / $req_edu_val) * 100);

require_once __DIR__ . '/header.php';
?>

<!-- Back & Title Header -->
<div class="mb-4">
    <a href="job-details.php?id=<?php echo $candidate['job_id']; ?>" class="text-primary text-decoration-none d-inline-flex align-items-center gap-1 mb-2">
        <i class="fa-solid fa-arrow-left"></i> Back to Candidate Screening Board
    </a>
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1 class="h2 text-light mb-1 brand-font"><?php echo htmlspecialchars($candidate['name']); ?></h1>
            <p class="text-secondary mb-0">Candidate Profile for position: <strong><?php echo htmlspecialchars($candidate['job_title']); ?></strong></p>
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

<div class="row g-4">
    <!-- Left Column: Score Ring and Contact Details -->
    <div class="col-lg-4">
        <!-- Match Score Ring Card -->
        <div class="card-premium text-center mb-4">
            <h3 class="h6 text-secondary text-uppercase fw-bold mb-4">Overall Match Score</h3>
            
            <div class="score-circle-container mb-3">
                <svg class="score-circle" width="120" height="120">
                    <circle class="score-circle-bg" cx="60" cy="60" r="50"></circle>
                    <circle class="score-circle-bar" cx="60" cy="60" r="50" data-score="<?php echo $candidate['match_score']; ?>"></circle>
                </svg>
                <div class="score-circle-text"><?php echo $candidate['match_score']; ?>%</div>
            </div>
            
            <div class="mt-2">
                <?php if ($candidate['match_score'] >= 80): ?>
                    <span class="badge bg-success-subtle text-success px-3 py-2 fw-semibold"><i class="fa-solid fa-circle-check me-1"></i> Strong Match</span>
                <?php elseif ($candidate['match_score'] >= 50): ?>
                    <span class="badge bg-warning-subtle text-warning px-3 py-2 fw-semibold"><i class="fa-solid fa-circle-minus me-1"></i> Moderate Match</span>
                <?php else: ?>
                    <span class="badge bg-danger-subtle text-danger px-3 py-2 fw-semibold"><i class="fa-solid fa-circle-xmark me-1"></i> Poor Match</span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Contact Details Card -->
        <div class="card-premium mb-4">
            <h3 class="h6 text-light brand-font mb-3 pb-2 border-bottom border-secondary">CONTACT DETAILS</h3>
            
            <ul class="list-unstyled text-secondary mb-0">
                <li class="mb-3">
                    <div class="text-secondary small fw-bold" style="font-size: 0.75rem;">EMAIL ADDRESS</div>
                    <div class="text-light fw-medium truncate">
                        <a href="mailto:<?php echo htmlspecialchars($candidate['email']); ?>" class="text-light text-decoration-none">
                            <i class="fa-regular fa-envelope me-2 text-primary"></i><?php echo htmlspecialchars($candidate['email']); ?>
                        </a>
                    </div>
                </li>
                <li class="mb-3">
                    <div class="text-secondary small fw-bold" style="font-size: 0.75rem;">PHONE NUMBER</div>
                    <div class="text-light fw-medium">
                        <i class="fa-solid fa-phone me-2 text-primary"></i><?php echo htmlspecialchars($candidate['phone']); ?>
                    </div>
                </li>
                <li class="mb-3">
                    <div class="text-secondary small fw-bold" style="font-size: 0.75rem;">DATE APPLIED</div>
                    <div class="text-light fw-medium">
                        <i class="fa-regular fa-calendar me-2 text-primary"></i><?php echo date('M d, Y', strtotime($candidate['created_at'])); ?>
                    </div>
                </li>
                <li>
                    <div class="text-secondary small fw-bold" style="font-size: 0.75rem;">CURRENT HIRING STAGE</div>
                    <div class="mt-2">
                        <?php if ($candidate['status'] === 'applied'): ?>
                            <span class="badge badge-premium badge-applied"><i class="fa-solid fa-file-invoice me-1"></i> Applied</span>
                        <?php elseif ($candidate['status'] === 'shortlisted'): ?>
                            <span class="badge badge-premium badge-shortlisted"><i class="fa-solid fa-star me-1"></i> Shortlisted</span>
                        <?php elseif ($candidate['status'] === 'accepted'): ?>
                            <span class="badge badge-premium badge-accepted"><i class="fa-solid fa-circle-check me-1"></i> Accepted (Hired!)</span>
                        <?php else: ?>
                            <span class="badge badge-premium badge-rejected"><i class="fa-solid fa-circle-xmark me-1"></i> Rejected</span>
                        <?php endif; ?>
                    </div>
                </li>
            </ul>
        </div>
        
        <!-- Hiring Actions Panel -->
        <div class="card-premium">
            <h3 class="h6 text-light brand-font mb-3 pb-2 border-bottom border-secondary">MANAGE APPLICATION STAGE</h3>
            
            <form action="candidate.php?id=<?php echo $candidate_id; ?>" method="POST" class="d-grid gap-2">
                <button type="submit" name="new_status" value="shortlisted" class="btn btn-premium-outline btn-sm text-start py-2 <?php echo ($candidate['status'] === 'shortlisted') ? 'active bg-warning text-dark border-warning' : ''; ?>">
                    <i class="fa-solid fa-star me-2 text-warning"></i> Move to Shortlist
                </button>
                <button type="submit" name="new_status" value="accepted" class="btn btn-premium-outline btn-sm text-start py-2 <?php echo ($candidate['status'] === 'accepted') ? 'active bg-success text-white border-success' : ''; ?>">
                    <i class="fa-solid fa-circle-check me-2 text-success"></i> Accept Candidate / Hire
                </button>
                <button type="submit" name="new_status" value="rejected" class="btn btn-premium-outline btn-sm text-start py-2 <?php echo ($candidate['status'] === 'rejected') ? 'active bg-danger text-white border-danger' : ''; ?>">
                    <i class="fa-solid fa-circle-xmark me-2 text-danger"></i> Reject Application
                </button>
                <?php if ($candidate['status'] !== 'applied'): ?>
                    <button type="submit" name="new_status" value="applied" class="btn btn-link text-secondary text-decoration-none py-2 text-center" style="font-size: 0.85rem;">
                        Reset back to 'Applied' stage
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <!-- Right Column: AI Analysis, Matching Matrices, and CV Inspector -->
    <div class="col-lg-8">
        <!-- AI/Rule Summary card -->
        <div class="card-premium mb-4">
            <h3 class="h5 text-light brand-font mb-3"><i class="fa-solid fa-robot text-primary me-2"></i> AI Resume Summary</h3>
            <p class="text-secondary mb-0" style="font-size: 1rem; line-height: 1.6; font-style: italic;">
                "<?php echo htmlspecialchars($candidate['summary']); ?>"
            </p>
        </div>
        
        <!-- Score Breakdown progress indicators -->
        <div class="card-premium mb-4">
            <h3 class="h5 text-light brand-font mb-4"><i class="fa-solid fa-chart-bar text-info me-2"></i> Qualification Breakdown</h3>
            
            <!-- Skills subscore -->
            <div class="mb-4">
                <div class="d-flex justify-content-between mb-1" style="font-size: 0.9rem;">
                    <span class="text-light fw-medium">Skills Match (50% weighting)</span>
                    <span class="text-secondary"><?php echo $skills_score; ?>%</span>
                </div>
                <div class="progress" style="height: 8px; background-color: var(--bg-tertiary);">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $skills_score; ?>%" aria-valuenow="<?php echo $skills_score; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-secondary d-block mt-1" style="font-size: 0.75rem;">
                    Matches extracted technologies against job's required tags: <strong><?php echo htmlspecialchars($candidate['required_skills']); ?></strong>.
                </small>
            </div>
            
            <!-- Experience subscore -->
            <div class="mb-4">
                <div class="d-flex justify-content-between mb-1" style="font-size: 0.9rem;">
                    <span class="text-light fw-medium">Experience Match (35% weighting)</span>
                    <span class="text-secondary"><?php echo $experience_score; ?>%</span>
                </div>
                <div class="progress" style="height: 8px; background-color: var(--bg-tertiary);">
                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $experience_score; ?>%" aria-valuenow="<?php echo $experience_score; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-secondary d-block mt-1" style="font-size: 0.75rem;">
                    Evaluates parsed experience (<strong><?php echo $candidate['experience_years']; ?> years</strong>) against required minimum (<strong><?php echo $candidate['required_experience']; ?> years</strong>).
                </small>
            </div>
            
            <!-- Education subscore -->
            <div>
                <div class="d-flex justify-content-between mb-1" style="font-size: 0.9rem;">
                    <span class="text-light fw-medium">Education Match (15% weighting)</span>
                    <span class="text-secondary"><?php echo $education_score; ?>%</span>
                </div>
                <div class="progress" style="height: 8px; background-color: var(--bg-tertiary);">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $education_score; ?>%" aria-valuenow="<?php echo $education_score; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-secondary d-block mt-1" style="font-size: 0.75rem;">
                    Matches candidate's highest degree (<strong><?php echo htmlspecialchars($candidate['education_found']); ?></strong>) against target minimum (<strong><?php echo htmlspecialchars($candidate['required_education']); ?></strong>).
                </small>
            </div>
        </div>
        
        <!-- Criteria comparison table -->
        <div class="card-premium mb-4">
            <h3 class="h5 text-light brand-font mb-3"><i class="fa-solid fa-code-compare text-warning me-2"></i> Side-by-Side Criteria Analysis</h3>
            
            <div class="table-responsive">
                <table class="table table-bordered table-dark align-middle mb-0" style="border-color: var(--border-color); background-color: transparent;">
                    <thead>
                        <tr style="background-color: var(--bg-secondary);">
                            <th style="width: 30%;">Metric</th>
                            <th style="width: 35%;">Job Requirement</th>
                            <th style="width: 35%;">Parsed Applicant Profile</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-semibold text-light">Required Skills</td>
                            <td>
                                <?php 
                                foreach (explode(',', $candidate['required_skills']) as $skill) {
                                    echo '<span class="badge bg-dark border border-secondary text-light me-1 mb-1">' . htmlspecialchars(trim($skill)) . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if (!empty($candidate['skills_found'])) {
                                    foreach (explode(',', $candidate['skills_found']) as $skill) {
                                        $trimmed = trim($skill);
                                        $is_matched = in_array(strtolower($trimmed), $req_skills);
                                        $badge_class = $is_matched ? 'bg-success-subtle text-success border-success' : 'bg-dark text-secondary border-secondary';
                                        echo '<span class="badge ' . $badge_class . ' border me-1 mb-1">' . htmlspecialchars($trimmed) . '</span>';
                                    }
                                } else {
                                    echo '<span class="text-muted">No key technologies identified.</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-light">Years of Experience</td>
                            <td>
                                <span class="text-light fw-medium"><?php echo $candidate['required_experience']; ?>+ years</span>
                            </td>
                            <td>
                                <span class="fw-bold <?php echo ($cand_experience >= $req_experience) ? 'text-success' : 'text-warning'; ?>">
                                    <?php echo $candidate['experience_years']; ?> years
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-light">Highest Education</td>
                            <td>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($candidate['required_education']); ?></span>
                            </td>
                            <td>
                                <span class="badge <?php echo ($candidate_edu_val >= $req_edu_val) ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'; ?>">
                                    <?php echo htmlspecialchars($candidate['education_found']); ?>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- CV Text Inspector -->
        <div class="card-premium">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h5 text-light brand-font mb-0"><i class="fa-solid fa-file-lines text-secondary me-2"></i> Extracted Resume Content</h3>
                <?php if (!empty($candidate['resume_path'])): ?>
                    <a href="uploads/<?php echo $candidate['resume_path']; ?>" target="_blank" class="btn btn-premium-outline btn-sm py-1" style="font-size: 0.8rem;">
                        <i class="fa-solid fa-file-pdf me-1"></i> View Raw Document
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="bg-dark rounded-3 border border-secondary p-3" style="max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 0.85rem; line-height: 1.6; color: #cbd5e1; white-space: pre-wrap;">
                <?php echo htmlspecialchars($candidate['resume_text']); ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
