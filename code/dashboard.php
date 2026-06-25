<?php
// NextHire - Recruiter Central Dashboard

require_once __DIR__ . '/config.php';
$pdo = require __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_auth();

$company_id = $_SESSION['company_id'];

// 1. Fetch KPI Metrics
$metrics = [
    'active_jobs' => 0,
    'total_candidates' => 0,
    'shortlisted' => 0,
    'accepted' => 0,
    'avg_score' => 0
];

try {
    // Active jobs
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE company_id = ? AND status = 'open'");
    $stmt->execute([$company_id]);
    $metrics['active_jobs'] = intval($stmt->fetchColumn());
    
    // Total candidates
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates c JOIN jobs j ON c.job_id = j.id WHERE j.company_id = ?");
    $stmt->execute([$company_id]);
    $metrics['total_candidates'] = intval($stmt->fetchColumn());
    
    // Shortlisted
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates c JOIN jobs j ON c.job_id = j.id WHERE j.company_id = ? AND c.status = 'shortlisted'");
    $stmt->execute([$company_id]);
    $metrics['shortlisted'] = intval($stmt->fetchColumn());
    
    // Accepted
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates c JOIN jobs j ON c.job_id = j.id WHERE j.company_id = ? AND c.status = 'accepted'");
    $stmt->execute([$company_id]);
    $metrics['accepted'] = intval($stmt->fetchColumn());
    
    // Average score
    $stmt = $pdo->prepare("SELECT AVG(c.match_score) FROM candidates c JOIN jobs j ON c.job_id = j.id WHERE j.company_id = ?");
    $stmt->execute([$company_id]);
    $metrics['avg_score'] = round($stmt->fetchColumn() ?: 0);
    
} catch (PDOException $e) {
    die("Error fetching dashboard metrics: " . $e->getMessage());
}

// 2. Fetch Recent Job Openings
$recent_jobs = [];
try {
    $stmt = $pdo->prepare("
        SELECT j.*, 
               (SELECT COUNT(*) FROM candidates WHERE job_id = j.id) AS applicant_count,
               (SELECT COUNT(*) FROM candidates WHERE job_id = j.id AND status = 'shortlisted') AS shortlisted_count
        FROM jobs j
        WHERE j.company_id = ?
        ORDER BY j.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$company_id]);
    $recent_jobs = $stmt->fetchAll();
} catch (PDOException $e) {
    // Handle error gracefully
}

// 3. Fetch Top Performing Candidates
$top_candidates = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.*, j.title AS job_title
        FROM candidates c
        JOIN jobs j ON c.job_id = j.id
        WHERE j.company_id = ? AND c.status != 'rejected'
        ORDER BY c.match_score DESC, c.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$company_id]);
    $top_candidates = $stmt->fetchAll();
} catch (PDOException $e) {
    // Handle error gracefully
}

require_once __DIR__ . '/header.php';
?>

<!-- Dashboard Welcome Title -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 text-light mb-1 brand-font">Recruiter Dashboard</h1>
        <p class="text-secondary mb-0">Hiring overview for <strong><?php echo htmlspecialchars($_SESSION['company_name']); ?></strong></p>
    </div>
    <div>
        <a href="jobs.php" class="btn btn-premium"><i class="fa-solid fa-plus me-2"></i> Create Job Posting</a>
    </div>
</div>

<!-- KPI Cards Grid -->
<div class="row g-3 mb-4">
    <!-- Card 1: Active Jobs -->
    <div class="col-md-6 col-xl-3">
        <div class="card-premium h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-secondary text-uppercase fw-bold" style="font-size: 0.75rem;">Active Openings</div>
                    <div class="h2 fw-bold text-light brand-font mt-2 mb-0"><?php echo $metrics['active_jobs']; ?></div>
                </div>
                <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background-color: rgba(99, 102, 241, 0.15) !important;">
                    <i class="fa-solid fa-briefcase text-primary fs-4"></i>
                </div>
            </div>
            <div class="mt-3 text-secondary" style="font-size: 0.8rem;">
                <span class="text-light fw-semibold">Open positions</span> currently accepting applications.
            </div>
        </div>
    </div>
    
    <!-- Card 2: Total Candidates -->
    <div class="col-md-6 col-xl-3">
        <div class="card-premium h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-secondary text-uppercase fw-bold" style="font-size: 0.75rem;">Total Applicants</div>
                    <div class="h2 fw-bold text-light brand-font mt-2 mb-0"><?php echo $metrics['total_candidates']; ?></div>
                </div>
                <div class="bg-info text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background-color: rgba(13, 202, 240, 0.15) !important;">
                    <i class="fa-solid fa-users text-info fs-4"></i>
                </div>
            </div>
            <div class="mt-3 text-secondary" style="font-size: 0.8rem;">
                <span class="text-light fw-semibold">Resumes processed</span> in the company database.
            </div>
        </div>
    </div>

    <!-- Card 3: Shortlisted -->
    <div class="col-md-6 col-xl-3">
        <div class="card-premium h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-secondary text-uppercase fw-bold" style="font-size: 0.75rem;">Smart Shortlisted</div>
                    <div class="h2 fw-bold text-light brand-font mt-2 mb-0"><?php echo $metrics['shortlisted']; ?></div>
                </div>
                <div class="bg-warning text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background-color: rgba(245, 158, 11, 0.15) !important;">
                    <i class="fa-solid fa-user-gear text-warning fs-4"></i>
                </div>
            </div>
            <div class="mt-3 text-secondary" style="font-size: 0.8rem;">
                Candidates identified by <span class="text-light fw-semibold">AI Match scores</span>.
            </div>
        </div>
    </div>

    <!-- Card 4: Average Match Score -->
    <div class="col-md-6 col-xl-3">
        <div class="card-premium h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-secondary text-uppercase fw-bold" style="font-size: 0.75rem;">Average Match Score</div>
                    <div class="h2 fw-bold text-light brand-font mt-2 mb-0"><?php echo $metrics['avg_score']; ?>%</div>
                </div>
                <div class="bg-success text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background-color: rgba(16, 185, 129, 0.15) !important;">
                    <i class="fa-solid fa-chart-simple text-success fs-4"></i>
                </div>
            </div>
            <div class="mt-3 text-secondary" style="font-size: 0.8rem;">
                Average qualifications match rate <span class="text-light fw-semibold">across job criteria</span>.
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Column 1: Recent Job Postings -->
    <div class="col-xl-7">
        <div class="card-premium h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h5 text-light brand-font mb-0"><i class="fa-solid fa-list-check text-primary me-2"></i> Recent Job Postings</h3>
                <a href="jobs.php" class="text-primary text-decoration-none" style="font-size: 0.9rem;">View All <i class="fa-solid fa-angle-right"></i></a>
            </div>
            
            <?php if (empty($recent_jobs)): ?>
                <div class="text-center py-5">
                    <div class="text-secondary mb-3"><i class="fa-solid fa-briefcase fa-3x text-muted"></i></div>
                    <h5 class="text-light">No Jobs Created Yet</h5>
                    <p class="text-secondary mb-4" style="font-size: 0.9rem;">Start screening CVs by creating your first job post opening.</p>
                    <a href="jobs.php" class="btn btn-premium btn-sm">Create Job Posting</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-premium align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Candidates</th>
                                <th>Shortlist Limit</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_jobs as $job): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-light"><?php echo htmlspecialchars($job['title']); ?></div>
                                        <small class="text-secondary" style="font-size: 0.75rem;">Created: <?php echo date('M d, Y', strtotime($job['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $job['applicant_count']; ?> Applicants</span>
                                    </td>
                                    <td>
                                        <span class="text-light"><i class="fa-solid fa-user-tie text-secondary me-1"></i> Top <?php echo $job['interview_limit']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo ($job['status'] === 'open') ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                                            <?php echo ucfirst($job['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn btn-premium-outline btn-sm py-1 px-2" style="font-size: 0.8rem;">
                                            Manage <i class="fa-solid fa-gears ms-1"></i>
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
    
    <!-- Column 2: Top Applicants Feed -->
    <div class="col-xl-5">
        <div class="card-premium h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h5 text-light brand-font mb-0"><i class="fa-solid fa-ranking-star text-warning me-2"></i> Top Candidates</h3>
                <a href="accepted.php" class="text-primary text-decoration-none" style="font-size: 0.9rem;">Accepted <i class="fa-solid fa-angle-right"></i></a>
            </div>
            
            <?php if (empty($top_candidates)): ?>
                <div class="text-center py-5">
                    <div class="text-secondary mb-3"><i class="fa-solid fa-users-slash fa-3x text-muted"></i></div>
                    <h5 class="text-light">No Candidates Found</h5>
                    <p class="text-secondary" style="font-size: 0.9rem;">Upload resumes within a job opening to run match analytics.</p>
                </div>
            <?php else: ?>
                <ul class="list-group list-group-flush bg-transparent" style="border: none;">
                    <?php foreach ($top_candidates as $cand): ?>
                        <li class="list-group-item bg-transparent border-bottom border-secondary px-0 py-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-light mb-1">
                                        <a href="candidate.php?id=<?php echo $cand['id']; ?>" class="text-light text-decoration-none hover-primary">
                                            <?php echo htmlspecialchars($cand['name']); ?>
                                        </a>
                                    </h6>
                                    <small class="text-secondary d-block" style="font-size: 0.8rem;">
                                        <i class="fa-solid fa-briefcase me-1"></i> <?php echo htmlspecialchars($cand['job_title']); ?>
                                    </small>
                                    <div class="mt-2">
                                        <?php if ($cand['status'] === 'applied'): ?>
                                            <span class="badge badge-premium badge-applied">Applied</span>
                                        <?php elseif ($cand['status'] === 'shortlisted'): ?>
                                            <span class="badge badge-premium badge-shortlisted">Shortlisted</span>
                                        <?php elseif ($cand['status'] === 'accepted'): ?>
                                            <span class="badge badge-premium badge-accepted">Accepted</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="h5 fw-bold mb-0 <?php echo ($cand['match_score'] >= 80) ? 'text-success' : (($cand['match_score'] >= 50) ? 'text-warning' : 'text-danger'); ?>">
                                        <?php echo $cand['match_score']; ?>%
                                    </div>
                                    <small class="text-secondary" style="font-size: 0.75rem;">Match score</small>
                                    <div class="mt-2">
                                        <a href="candidate.php?id=<?php echo $cand['id']; ?>" class="btn btn-sm btn-link text-primary p-0 text-decoration-none" style="font-size: 0.8rem;">
                                            View Profile <i class="fa-solid fa-arrow-right-long ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
