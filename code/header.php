<?php
// NextHire - Reusable Header Shell

require_once __DIR__ . '/config.php';
if (!isset($pdo)) {
    $pdo = require __DIR__ . '/db.php';
}
require_once __DIR__ . '/helpers.php';

$is_auth = is_logged_in();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - AI-Powered Recruitment</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Style -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-premium sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center gap-2 brand-font fs-4" href="index.php">
                <i class="fa-solid fa-wand-magic-sparkles text-primary"></i>
                Next<span class="text-primary">Hire</span>
            </a>
            
            <?php if ($is_auth): ?>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarUserContent">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarUserContent">
                    <div class="ms-auto d-flex align-items-center gap-3 mt-2 mt-lg-0">
                        <div class="text-end d-none d-sm-block">
                            <div class="fw-bold text-light"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                            <small class="text-secondary" style="font-size: 0.8rem;">
                                <i class="fa-solid fa-building me-1"></i><?php echo htmlspecialchars($_SESSION['company_name']); ?>
                            </small>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle bg-transparent border-0 d-flex align-items-center gap-2" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                                </div>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" aria-labelledby="userMenuButton" style="border: 1px solid var(--border-color); background-color: var(--bg-secondary);">
                                <li><a class="dropdown-item" href="settings.php"><i class="fa-solid fa-gears me-2"></i>Settings</a></li>
                                <li><a class="dropdown-item" href="feedback.php"><i class="fa-solid fa-comment-dots me-2"></i>Feedback</a></li>
                                <li><hr class="dropdown-divider" style="background-color: var(--border-color);"></li>
                                <li><a class="dropdown-item text-danger" href="auth.php?action=logout"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="ms-auto d-flex align-items-center gap-3">
                    <a href="login.php" class="btn btn-link text-light text-decoration-none fw-500">Sign In</a>
                    <a href="register.php" class="btn btn-premium">Register Company</a>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Layout Container -->
    <div class="container-fluid">
        <div class="row">
            <?php if ($is_auth): ?>
                <!-- Recruiter Sidebar Navigation -->
                <nav class="col-md-3 col-lg-2 d-md-block sidebar-premium collapse p-3">
                    <div class="position-sticky pt-2">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link-premium <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                                    <i class="fa-solid fa-chart-line"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link-premium <?php echo ($current_page === 'jobs.php' || $current_page === 'job-details.php') ? 'active' : ''; ?>" href="jobs.php">
                                    <i class="fa-solid fa-briefcase"></i>
                                    <span>Job Openings</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link-premium <?php echo ($current_page === 'accepted.php') ? 'active' : ''; ?>" href="accepted.php">
                                    <i class="fa-solid fa-user-check"></i>
                                    <span>Onboarding Board</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link-premium <?php echo ($current_page === 'settings.php') ? 'active' : ''; ?>" href="settings.php">
                                    <i class="fa-solid fa-sliders"></i>
                                    <span>API Settings</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link-premium <?php echo ($current_page === 'feedback.php') ? 'active' : ''; ?>" href="feedback.php">
                                    <i class="fa-solid fa-face-smile"></i>
                                    <span>Feedback System</span>
                                </a>
                            </li>
                        </ul>
                        
                        <div class="border-top border-secondary mt-4 pt-4 px-2">
                            <div class="text-secondary" style="font-size: 0.75rem;">Logged in as:</div>
                            <div class="fw-bold text-light truncate" style="font-size: 0.85rem;"><?php echo htmlspecialchars($_SESSION['user_email']); ?></div>
                            <div class="badge bg-secondary mt-2"><?php echo ucfirst(htmlspecialchars($_SESSION['user_role'])); ?></div>
                        </div>
                    </div>
                </nav>
                <!-- Main Authenticated Content Panel -->
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4" style="min-height: calc(100vh - 56px);">
            <?php else: ?>
                <!-- Unauthenticated Content Panel -->
                <main class="col-12" style="min-height: calc(100vh - 56px);">
            <?php endif; ?>
