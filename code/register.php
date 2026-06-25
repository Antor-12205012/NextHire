<?php
// NextHire - Recruiter Registration

require_once __DIR__ . '/config.php';
$pdo = require __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

// Fetch all registered companies to allow joining
$companies = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM companies ORDER BY name ASC");
    $companies = $stmt->fetchAll();
} catch (Exception $e) {
    // If table doesn't exist, we don't display companies
}

require_once __DIR__ . '/header.php';

$error = $_SESSION['auth_error'] ?? '';
unset($_SESSION['auth_error']);
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card-premium p-4 p-sm-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle mb-3" style="width: 60px; height: 60px; background-color: rgba(99, 102, 241, 0.1);">
                        <i class="fa-solid fa-building-user fs-3 text-primary"></i>
                    </div>
                    <h2 class="h3 brand-font text-light">Create Recruiter Portal</h2>
                    <p class="text-secondary">Register yourself and connect your organization</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2" role="alert" style="background-color: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.3); color: #f87171; border-radius: 10px;">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>
                
                <form action="auth.php" method="POST" id="registerForm">
                    <input type="hidden" name="action" value="register">
                    
                    <h5 class="text-light mb-3 pb-2 border-bottom border-secondary">1. Personal Account Details</h5>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label form-label-premium">Your Full Name</label>
                        <input type="text" name="name" id="name" class="form-control form-control-premium" placeholder="John Doe" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label form-label-premium">Professional Email</label>
                        <input type="email" name="email" id="email" class="form-control form-control-premium" placeholder="john.doe@company.com" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label form-label-premium">Secure Password</label>
                        <input type="password" name="password" id="password" class="form-control form-control-premium" placeholder="••••••••" required>
                    </div>
                    
                    <h5 class="text-light mb-3 pb-2 border-bottom border-secondary">2. Company Affiliation</h5>
                    
                    <div class="mb-3">
                        <label class="form-label form-label-premium d-block">Select Option</label>
                        <div class="form-check form-check-inline me-4">
                            <input class="form-check-input" type="radio" name="company_selection" id="select_new" value="new" checked>
                            <label class="form-check-label text-light" for="select_new">Register a New Company</label>
                        </div>
                        <?php if (!empty($companies)): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="company_selection" id="select_join" value="join">
                                <label class="form-check-label text-light" for="select_join">Join an Existing Company</label>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Box 1: Register New Company -->
                    <div class="mb-4" id="company_name_wrapper">
                        <label for="company_name" class="form-label form-label-premium">Company / Organization Name</label>
                        <input type="text" name="company_name" id="company_name" class="form-control form-control-premium" placeholder="Acme Corporation">
                        <small class="text-secondary" style="font-size: 0.8rem;">Registering a new company makes you the Administrative Account owner for that organization.</small>
                    </div>
                    
                    <!-- Box 2: Join Existing Company -->
                    <?php if (!empty($companies)): ?>
                        <div class="mb-4 d-none" id="join_company_wrapper">
                            <label for="join_company_id" class="form-label form-label-premium">Choose Registered Organization</label>
                            <select name="join_company_id" id="join_company_id" class="form-select form-control-premium">
                                <option value="" selected disabled>Select organization...</option>
                                <?php foreach ($companies as $comp): ?>
                                    <option value="<?php echo $comp['id']; ?>"><?php echo htmlspecialchars($comp['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-secondary" style="font-size: 0.8rem;">You will register as a hiring Recruiter under the selected company portal.</small>
                        </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-premium w-100 py-2.5">Create Recruiter Account <i class="fa-solid fa-user-plus ms-2"></i></button>
                    
                    <div class="text-center mt-3 text-secondary" style="font-size: 0.9rem;">
                        Already have an account? <a href="login.php" class="text-primary text-decoration-none">Sign In here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectNew = document.getElementById('select_new');
    const selectJoin = document.getElementById('select_join');
    const companyNameWrapper = document.getElementById('company_name_wrapper');
    const joinCompanyWrapper = document.getElementById('join_company_wrapper');
    
    const companyNameInput = document.getElementById('company_name');
    const joinCompanySelect = document.getElementById('join_company_id');
    
    if (selectNew && selectJoin) {
        selectNew.addEventListener('change', toggleCompanyFields);
        selectJoin.addEventListener('change', toggleCompanyFields);
    }
    
    function toggleCompanyFields() {
        if (selectNew.checked) {
            companyNameWrapper.classList.remove('d-none');
            joinCompanyWrapper.classList.add('d-none');
            
            companyNameInput.setAttribute('required', 'required');
            joinCompanySelect.removeAttribute('required');
        } else if (selectJoin && selectJoin.checked) {
            companyNameWrapper.classList.add('d-none');
            joinCompanyWrapper.classList.remove('d-none');
            
            companyNameInput.removeAttribute('required');
            joinCompanySelect.setAttribute('required', 'required');
        }
    }
    
    // Initial trigger
    toggleCompanyFields();
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
