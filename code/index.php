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

<!-- Features Section -->
<section class="py-5 mb-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="h1 mb-3 brand-font">End-to-End Recruitment Automation</h2>
            <p class="text-secondary">NextHire replaces tedious administrative processes with intelligent, structured workflows.</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card-premium h-100">
                    <div class="feature-icon-wrapper">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                    <h3 class="h4 mb-3">Structured CV Storage</h3>
                    <p class="text-secondary">Upload PDF or text resumes directly. The platform extracts content, saving it into a structured, searchable, and secure relational database.</p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card-premium h-100">
                    <div class="feature-icon-wrapper">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <h3 class="h4 mb-3">AI Matching & Ranking</h3>
                    <p class="text-secondary">We score and rank applicants instantly using NLP patterns or generative APIs, evaluating their qualifications against your specific criteria.</p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card-premium h-100">
                    <div class="feature-icon-wrapper">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <h3 class="h4 mb-3">Smart Shortlisting</h3>
                    <p class="text-secondary">Set your interview limits per position. NextHire automatically identifies and flags the top candidates, streamlining your selection stages.</p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card-premium h-100 mt-4">
                    <div class="feature-icon-wrapper">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                    <h3 class="h4 mb-3">Recruiter Chat Board</h3>
                    <p class="text-secondary">Discuss candidate profiles, share feedback, and coordinate interviews in real-time with colleagues directly on the platform.</p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card-premium h-100 mt-4">
                    <div class="feature-icon-wrapper">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3 class="h4 mb-3">Multi-Company Security</h3>
                    <p class="text-secondary">Full logical isolation of applicant databases, dashboards, job posts, and API keys between registered organizations.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card-premium h-100 mt-4">
                    <div class="feature-icon-wrapper">
                        <i class="fa-solid fa-paste"></i>
                    </div>
                    <h3 class="h4 mb-3">Onboarding Board</h3>
                    <p class="text-secondary">Track accepted applicants across all roles in an organized, centralized portal to transition candidates into onboarding.</p>
                </div>
            </div>
        </div>
    </div>
</section>