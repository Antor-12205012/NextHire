<?php
// NextHire - Job Management Panel

require_once __DIR__ . '/config.php';
$pdo = require __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_auth();

$company_id = $_SESSION['company_id'];
$success_msg = '';
$error_msg = '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action'])) {
    
    // 1. Create Job Posting
    if ($_POST['form_action'] === 'create_job') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $required_skills = trim($_POST['required_skills'] ?? '');
        $required_experience = intval($_POST['required_experience'] ?? 0);
        $required_education = $_POST['required_education'] ?? 'Bachelor';
        $interview_limit = intval($_POST['interview_limit'] ?? 5);
        
        if (empty($title) || empty($description) || empty($required_skills)) {
            $error_msg = "Please fill in all required fields (Job Title, Description, and Required Skills).";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO jobs (company_id, title, description, required_skills, required_experience, required_education, interview_limit, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'open')
                ");
                $stmt->execute([$company_id, $title, $description, $required_skills, $required_experience, $required_education, $interview_limit]);
                $success_msg = "Job posting created successfully.";
            } catch (PDOException $e) {
                $error_msg = "Error creating job: " . $e->getMessage();
            }
        }
    }
}

// Handle GET-based quick actions (Toggle status / Delete)
if (isset($_GET['action'])) {
    $job_id = intval($_GET['id'] ?? 0);
    
    if ($job_id > 0 && check_job_ownership($pdo, $job_id, $company_id)) {
        
        // A. Toggle Status
        if ($_GET['action'] === 'toggle_status') {
            try {
                $stmt = $pdo->prepare("SELECT status FROM jobs WHERE id = ?");
                $stmt->execute([$job_id]);
                $current_status = $stmt->fetchColumn();
                $new_status = ($current_status === 'open') ? 'closed' : 'open';
                
                $stmt = $pdo->prepare("UPDATE jobs SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $job_id]);
                $success_msg = "Job status updated to " . $new_status . ".";
            } catch (PDOException $e) {
                $error_msg = "Error updating job status: " . $e->getMessage();
            }
        }
        
        // B. Delete Job
        elseif ($_GET['action'] === 'delete') {
            try {
                // Delete job (foreign key constraints handle candidates, messages deletion)
                $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
                $stmt->execute([$job_id]);
                $success_msg = "Job posting deleted successfully.";
            } catch (PDOException $e) {
                $error_msg = "Error deleting job: " . $e->getMessage();
            }
        }
    }
}

// Fetch all jobs for the company
$jobs = [];
try {
    $stmt = $pdo->prepare("
        SELECT j.*, 
               (SELECT COUNT(*) FROM candidates WHERE job_id = j.id) AS applicant_count,
               (SELECT COUNT(*) FROM candidates WHERE job_id = j.id AND status = 'shortlisted') AS shortlisted_count,
               (SELECT COUNT(*) FROM candidates WHERE job_id = j.id AND status = 'accepted') AS accepted_count
        FROM jobs j
        WHERE j.company_id = ?
        ORDER BY j.created_at DESC
    ");
    $stmt->execute([$company_id]);
    $jobs = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Error loading jobs: " . $e->getMessage();
}

require_once __DIR__ . '/header.php';
?>

<!-- Title Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 text-light mb-1 brand-font">Job Openings</h1>
        <p class="text-secondary mb-0">Define job requirements and manage applicant screening pools</p>
    </div>
    <button class="btn btn-premium" type="button" data-bs-toggle="collapse" data-bs-target="#createJobCollapse" aria-expanded="false" aria-controls="createJobCollapse">
        <i class="fa-solid fa-square-plus me-2"></i> Create New Posting
    </button>
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

<!-- Collapsible Create Job Form -->
<div class="collapse mb-4" id="createJobCollapse">
    <div class="card-premium">
        <h3 class="h5 text-light brand-font mb-4"><i class="fa-solid fa-pen-to-square text-primary me-2"></i> Define New Job Criteria</h3>
        
        <form action="jobs.php" method="POST">
            <input type="hidden" name="form_action" value="create_job">
            
            <div class="row g-3">
                <!-- Title -->
                <div class="col-md-6">
                    <label for="title" class="form-label form-label-premium">Job Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control form-control-premium" placeholder="e.g. Senior Software Engineer" required>
                </div>
                
                <!-- Smart Shortlisting limit -->
                <div class="col-md-6">
                    <label for="interview_limit" class="form-label form-label-premium">Smart Shortlist Limit <span class="text-danger">*</span></label>
                    <input type="number" name="interview_limit" id="interview_limit" class="form-control form-control-premium" value="5" min="1" max="50" required>
                    <small class="text-secondary" style="font-size: 0.8rem;">How many top candidates to automatically select for the interview shortlist.</small>
                </div>
                
                <!-- Required Skills -->
                <div class="col-12">
                    <label for="required_skills" class="form-label form-label-premium">Required Skills (Comma Separated) <span class="text-danger">*</span></label>
                    <input type="text" name="required_skills" id="required_skills" class="form-control form-control-premium" placeholder="e.g. PHP, Laravel, JavaScript, MySQL, Git" required>
                    <small class="text-secondary" style="font-size: 0.8rem;">Separate each technology or skill with a comma. Capitalization is ignored during candidate matching.</small>
                </div>
                
                <!-- Experience -->
                <div class="col-md-6">
                    <label for="required_experience" class="form-label form-label-premium">Minimum Experience (Years)</label>
                    <input type="number" name="required_experience" id="required_experience" class="form-control form-control-premium" value="0" min="0" max="30">
                </div>
                
                <!-- Education -->
                <div class="col-md-6">
                    <label for="required_education" class="form-label form-label-premium">Minimum Education Requirement</label>
                    <select name="required_education" id="required_education" class="form-select form-control-premium">
                        <option value="High School / Other">High School / Other</option>
                        <option value="Bachelor" selected>Bachelor's Degree</option>
                        <option value="Master">Master's Degree</option>
                        <option value="PhD">Doctorate (PhD)</option>
                    </select>
                </div>
                
                <!-- Job Description -->
                <div class="col-12">
                    <label for="description" class="form-label form-label-premium">Job Description <span class="text-danger">*</span></label>
                    <textarea name="description" id="description" class="form-control form-control-premium" rows="5" placeholder="Outline job responsibilities, day-to-day duties, and additional benefits..." required></textarea>
                </div>
            </div>
            
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-premium">Post Job Opening</button>
                <button type="button" class="btn btn-premium-outline" data-bs-toggle="collapse" data-bs-target="#createJobCollapse">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Job Postings List Grid -->
<div class="row g-3">
    <?php if (empty($jobs)): ?>
        <div class="col-12">
            <div class="card-premium text-center py-5">
                <div class="text-secondary mb-3"><i class="fa-solid fa-briefcase fa-4x text-muted"></i></div>
                <h3 class="h4 text-light">No Job Positions Created</h3>
                <p class="text-secondary mb-4">Post your first job opening to start matching uploaded candidate resumes.</p>
                <button class="btn btn-premium" data-bs-toggle="collapse" data-bs-target="#createJobCollapse">Create Job Post</button>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($jobs as $job): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card-premium h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h3 class="h5 text-light mb-0"><?php echo htmlspecialchars($job['title']); ?></h3>
                            <span class="badge rounded-pill <?php echo ($job['status'] === 'open') ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>" style="font-size: 0.75rem;">
                                <?php echo ucfirst($job['status']); ?>
                            </span>
                        </div>
                        
                        <div class="text-secondary mb-3" style="font-size: 0.8rem;">
                            Posted: <?php echo date('M d, Y', strtotime($job['created_at'])); ?>
                        </div>
                        
                        <p class="text-secondary text-truncate-3" style="font-size: 0.9rem; line-height: 1.5; margin-bottom: 1.25rem;">
                            <?php echo htmlspecialchars($job['description']); ?>
                        </p>
                        
                        <!-- Criteria summary tags -->
                        <div class="mb-3">
                            <div class="text-secondary mb-1" style="font-size: 0.75rem; font-weight: 500;">MATCH CRITERIA:</div>
                            <span class="badge bg-dark border border-secondary text-light me-1 mb-1">Exp: <?php echo $job['required_experience']; ?>+ yrs</span>
                            <span class="badge bg-dark border border-secondary text-light me-1 mb-1">Edu: <?php echo htmlspecialchars($job['required_education']); ?></span>
                            <span class="badge bg-dark border border-secondary text-light mb-1">Shortlist: Top <?php echo $job['interview_limit']; ?></span>
                        </div>
                        
                        <!-- Metrics -->
                        <div class="row text-center bg-dark rounded-3 border border-secondary p-2 mb-3 g-2">
                            <div class="col-4">
                                <div class="fw-bold text-light" style="font-size: 1rem;"><?php echo $job['applicant_count']; ?></div>
                                <div class="text-secondary" style="font-size: 0.65rem; text-uppercase: uppercase;">Applied</div>
                            </div>
                            <div class="col-4 border-start border-secondary">
                                <div class="fw-bold text-warning" style="font-size: 1rem;"><?php echo $job['shortlisted_count']; ?></div>
                                <div class="text-secondary" style="font-size: 0.65rem; text-uppercase: uppercase;">Shortlist</div>
                            </div>
                            <div class="col-4 border-start border-secondary">
                                <div class="fw-bold text-success" style="font-size: 1rem;"><?php echo $job['accepted_count']; ?></div>
                                <div class="text-secondary" style="font-size: 0.65rem; text-uppercase: uppercase;">Accepted</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="d-flex gap-2 pt-2 border-top border-secondary">
                        <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn btn-premium btn-sm flex-grow-1 text-center py-1.5">
                            Manage Candidates <i class="fa-solid fa-arrow-trend-up ms-1"></i>
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-premium-outline btn-sm dropdown-toggle py-1.5" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end" style="border: 1px solid var(--border-color); background-color: var(--bg-secondary);">
                                <li>
                                    <a class="dropdown-item" href="jobs.php?action=toggle_status&id=<?php echo $job['id']; ?>">
                                        <i class="fa-solid <?php echo ($job['status'] === 'open') ? 'fa-folder-closed' : 'fa-folder-open'; ?> me-2"></i>
                                        <?php echo ($job['status'] === 'open') ? 'Close Position' : 'Open Position'; ?>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider" style="background-color: var(--border-color);"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="jobs.php?action=delete&id=<?php echo $job['id']; ?>" onclick="return confirm('Are you sure you want to delete this job posting? This will permanently delete all applicants, summaries, and comments associated with this position.');">
                                        <i class="fa-solid fa-trash-can me-2"></i> Delete Posting
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
