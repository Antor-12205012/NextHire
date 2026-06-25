<?php
// NextHire - Recruiter Sign-In

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/header.php';

$error = $_SESSION['auth_error'] ?? '';
unset($_SESSION['auth_error']); // Clear after reading
?>

<div class="container py-5">
    <div class="row justify-content-center py-5">
        <div class="col-md-6 col-lg-5">
            <div class="card-premium p-4 p-sm-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle mb-3" style="width: 60px; height: 60px; background-color: rgba(99, 102, 241, 0.1);">
                        <i class="fa-solid fa-lock fs-3 text-primary"></i>
                    </div>
                    <h2 class="h3 brand-font text-light">Recruiter Sign In</h2>
                    <p class="text-secondary">Access your company's recruitment portal</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2" role="alert" style="background-color: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.3); color: #f87171; border-radius: 10px;">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>
                
                <form action="auth.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="mb-3">
                        <label for="email" class="form-label form-label-premium">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control form-control-premium" placeholder="name@company.com" required>
                    </div>
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <label for="password" class="form-label form-label-premium mb-0">Password</label>
                        </div>
                        <input type="password" name="password" id="password" class="form-control form-control-premium" placeholder="••••••••" required>
                    </div>
                    
                    <button type="submit" class="btn btn-premium w-100 py-2.5 mb-3">Sign In <i class="fa-solid fa-right-to-bracket ms-2"></i></button>
                    
                    <div class="text-center mt-3 text-secondary" style="font-size: 0.9rem;">
                        Need to register? <a href="register.php" class="text-primary text-decoration-none">Create a Company Portal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
