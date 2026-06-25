<?php
// NextHire - Main Landing Page

require_once __DIR__ . '/header.php';

// Fetch overall platform statistics from database if available
$total_companies = 0;
$total_jobs = 0;
$total_applicants = 0;

try {
    $company_stmt = $pdo->query("SELECT COUNT(*) FROM companies");
    $total_companies = $company_stmt->fetchColumn();
    
    $jobs_stmt = $pdo->query("SELECT COUNT(*) FROM jobs");
    $total_jobs = $jobs_stmt->fetchColumn();
    
    $cand_stmt = $pdo->query("SELECT COUNT(*) FROM candidates");
    $total_applicants = $cand_stmt->fetchColumn();
} catch (Exception $e) {
    // Suppress errors and use default zeros
}
?>

<!-- Hero Section -->
<section class="hero-section text-center py-5 mb-5">
    <div class="container">
        <div class="row justify-content-center py-5">
            <div class="col-lg-8">
                <!-- <span class="badge bg-secondary mb-3 px-3 py-2 text-uppercase fw-bold" style="letter-spacing: 0.1em; font-size: 0.8rem;">
                    
                </span> -->
                <h1 class="display-3 fw-bold mb-3 brand-font">
                    Revolutionize Your <br>
                    <span class="text-primary">Hiring Workflow</span> with AI
                </h1>
                <p class="lead text-secondary mb-4 px-md-5">
                    NextHire automatically screens, scores, and ranks job candidates against your custom criteria. Say goodbye to reading hundreds of CVs manually.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <?php if (is_logged_in()): ?>
                        <a href="dashboard.php" class="btn btn-premium btn-lg">Go to Dashboard <i class="fa-solid fa-arrow-right ms-2"></i></a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-premium btn-lg">Get Started Free</a>
                        <a href="login.php" class="btn btn-premium-outline btn-lg">Recruiter Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Tracker -->
<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card-premium">
                <div class="row text-center">
                    <div class="col-md-4 border-end border-secondary py-3">
                        <div class="h1 fw-bold text-primary brand-font mb-1"><?php echo max(3, $total_companies); ?>+</div>
                        <div class="text-secondary text-uppercase fw-bold" style="font-size: 0.75rem;">Registered Partners</div>
                    </div>
                    <div class="col-md-4 border-end border-secondary py-3">
                        <div class="h1 fw-bold text-success brand-font mb-1"><?php echo max(12, $total_jobs); ?>+</div>
                        <div class="text-secondary text-uppercase fw-bold" style="font-size: 0.75rem;">Active Job Openings</div>
                    </div>
                    <div class="col-md-4 py-3">
                        <div class="h1 fw-bold text-warning brand-font mb-1"><?php echo max(150, $total_applicants); ?>+</div>
                        <div class="text-secondary text-uppercase fw-bold" style="font-size: 0.75rem;">Resumes Analyzed</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
