<?php
// NextHire - Company Settings and API Configurations

require_once __DIR__ . '/config.php';
$pdo = require __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_auth();

$company_id = $_SESSION['company_id'];
$success_msg = '';
$error_msg = '';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if it's an AJAX request (either via header or post parameter)
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax'] == 1);

    $company_name = trim($_POST['company_name'] ?? '');
    $gemini_key = trim($_POST['gemini_api_key'] ?? '');
    $openai_key = trim($_POST['openai_api_key'] ?? '');
    
    if (empty($company_name)) {
        $error_msg = "Company name cannot be empty.";
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error_msg]);
            exit;
        }
    } else {
        try {
            // Update company settings in database
            $stmt = $pdo->prepare("
                UPDATE companies 
                SET name = ?, gemini_api_key = ?, openai_api_key = ? 
                WHERE id = ?
            ");
            $stmt->execute([$company_name, $gemini_key, $openai_key, $company_id]);
            
            // Update session value
            $_SESSION['company_name'] = $company_name;
            $success_msg = "Settings updated successfully.";
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $success_msg]);
                exit;
            }
        } catch (PDOException $e) {
            $error_msg = "Error saving configurations: " . $e->getMessage();
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $error_msg]);
                exit;
            }
        }
    }
}

// Fetch current company settings
$company = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

require_once __DIR__ . '/header.php';
?>

<!-- Title Header -->
<div class="mb-4 animate-fade-in">
    <h1 class="h2 text-light mb-1 brand-font">Settings & Configurations</h1>
    <p class="text-secondary mb-0">Manage your company profile and configure AI engine API keys</p>
</div>

<!-- Alert banners -->
<div id="alert-wrapper" class="animate-fade-in">
    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert" style="background-color: rgba(16, 185, 129, 0.15); border-color: rgba(16, 185, 129, 0.3); color: #34d399; border-radius: 10px;">
            <i class="fa-solid fa-circle-check text-success fs-5"></i>
            <div><?php echo htmlspecialchars($success_msg); ?></div>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert" style="background-color: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.3); color: #f87171; border-radius: 10px;">
            <i class="fa-solid fa-circle-exclamation text-danger fs-5"></i>
            <div><?php echo htmlspecialchars($error_msg); ?></div>
        </div>
    <?php endif; ?>
</div>

<div class="row g-4 animate-fade-in">
    <!-- Left Column: Settings Form -->
    <div class="col-lg-8">
        <div class="card-premium">
            <h3 class="h5 text-light brand-font mb-4"><i class="fa-solid fa-sliders text-primary me-2"></i> Configuration Control Panel</h3>
            
            <form id="settings-form" action="settings.php" method="POST">
                <!-- Section 1: Company Profile -->
                <h5 class="text-light mb-3 pb-2 border-bottom border-secondary d-flex align-items-center gap-2">
                    <i class="fa-solid fa-building text-secondary fs-6"></i> Company Details
                </h5>
                <div class="mb-4">
                    <label for="company_name" class="form-label form-label-premium">Company / Organization Name</label>
                    <input type="text" name="company_name" id="company_name" class="form-control form-control-premium" value="<?php echo htmlspecialchars($company['name']); ?>" required>
                    <small class="text-secondary" style="font-size: 0.8rem;">Modifying this updates your team's sidebar banner and email signatures.</small>
                </div>
                
                <!-- Section 2: AI Parser Engines -->
                <h5 class="text-light mb-3 pb-2 border-bottom border-secondary d-flex align-items-center gap-2">
                    <i class="fa-solid fa-brain text-secondary fs-6"></i> AI Resume Parsing & Summarization Keys
                </h5>
                <p class="text-secondary mb-4" style="font-size: 0.9rem; line-height: 1.5;">
                    By default, NextHire uses our <strong>built-in high-performance regex parsing engine</strong> to scan, score, and summarize candidates offline. If you want to unlock advanced semantic reasoning, enter an API key below to switch to generative processing:
                </p>
                
                <!-- Gemini API Key -->
                <div class="mb-4">
                    <label for="gemini_api_key" class="form-label form-label-premium">Gemini API Key (Recommended)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-wand-magic-sparkles text-primary"></i></span>
                        <input type="password" name="gemini_api_key" id="gemini_api_key" class="form-control form-control-premium" value="<?php echo htmlspecialchars($company['gemini_api_key']); ?>" placeholder="Enter Google Gemini API Key...">
                        <button type="button" class="btn btn-outline-secondary border-secondary text-secondary toggle-password" data-target="gemini_api_key" title="Toggle visibility">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-premium-outline test-key-btn" data-provider="gemini">
                            <i class="fa-solid fa-bolt me-1"></i> Test Key
                        </button>
                    </div>
                    <small class="text-secondary d-block mt-1" style="font-size: 0.8rem;">Enables high-fidelity data extraction using the Gemini-1.5-Flash model.</small>
                </div>
                
                <!-- OpenAI API Key -->
                <div class="mb-4">
                    <label for="openai_api_key" class="form-label form-label-premium">OpenAI API Key</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-robot text-success"></i></span>
                        <input type="password" name="openai_api_key" id="openai_api_key" class="form-control form-control-premium" value="<?php echo htmlspecialchars($company['openai_api_key']); ?>" placeholder="Enter OpenAI API Key...">
                        <button type="button" class="btn btn-outline-secondary border-secondary text-secondary toggle-password" data-target="openai_api_key" title="Toggle visibility">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-premium-outline test-key-btn" data-provider="openai">
                            <i class="fa-solid fa-bolt me-1"></i> Test Key
                        </button>
                    </div>
                    <small class="text-secondary d-block mt-1" style="font-size: 0.8rem;">Enables GPT-4o-mini structured resume analysis.</small>
                </div>
                
                <div class="mt-4">
                    <button type="submit" id="save-settings-btn" class="btn btn-premium px-4 py-2 d-inline-flex align-items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> <span>Save Configurations</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Right Column: Information/Guidance Card -->
    <div class="col-lg-4">
        <div class="card-premium h-100 d-flex flex-column justify-content-between">
            <div>
                <h3 class="h6 text-light brand-font mb-3 pb-2 border-bottom border-secondary">HOW IT WORKS</h3>
                <p class="text-secondary" style="font-size: 0.85rem; line-height: 1.6;">
                    <strong>1. Offline Mode (Local)</strong><br>
                    If no keys are saved, NextHire runs regular expression parsers to instantly extract qualifications.
                </p>
                <p class="text-secondary" style="font-size: 0.85rem; line-height: 1.6;">
                    <strong>2. Online Mode (AI Upgrade)</strong><br>
                    Saving a Gemini or OpenAI API key instructs the backend to query their completions endpoint to parse candidates with higher semantic accuracy.
                </p>
            </div>
            
            <div class="alert alert-info mt-4 p-3 text-secondary border border-secondary bg-dark" style="font-size: 0.8rem; border-radius: 10px;">
                <i class="fa-solid fa-shield-halved text-info me-1 fs-6"></i>
                API keys are logically separated between organizations in the database. Your keys will never be exposed to other companies.
            </div>
        </div>
    </div>
</div>

<style>
/* Modern styling and animations for Settings */
.animate-fade-in {
    animation: fadeIn 0.5s ease-out forwards;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.toggle-password {
    background-color: var(--bg-secondary);
    transition: all 0.2s ease;
}
.toggle-password:hover {
    background-color: var(--bg-tertiary);
    color: var(--text-primary) !important;
}

.test-key-btn {
    border-top-right-radius: 10px !important;
    border-bottom-right-radius: 10px !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
    border-width: 0.15em;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Password Visibility Toggle
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // 2. Connection Tester
    const testButtons = document.querySelectorAll('.test-key-btn');
    testButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const provider = this.getAttribute('data-provider');
            const inputId = provider === 'gemini' ? 'gemini_api_key' : 'openai_api_key';
            const apiKey = document.getElementById(inputId).value.trim();
            
            if (!apiKey) {
                showToast('Please enter an API key first.', 'danger');
                return;
            }
            
            // Set Loading state
            const originalHtml = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Testing...';
            
            fetch('test-api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    provider: provider,
                    api_key: apiKey
                })
            })
            .then(res => res.json())
            .then(data => {
                // Restore button state
                this.disabled = false;
                this.innerHTML = originalHtml;
                
                if (data.success) {
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message, 'danger');
                }
            })
            .catch(err => {
                this.disabled = false;
                this.innerHTML = originalHtml;
                showToast('Network error occurred while testing key connection.', 'danger');
                console.error(err);
            });
        });
    });

    // 3. AJAX Form Save
    const form = document.getElementById('settings-form');
    const saveBtn = document.getElementById('save-settings-btn');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Set loading state
        const saveBtnText = saveBtn.querySelector('span');
        const saveBtnIcon = saveBtn.querySelector('i');
        const originalText = saveBtnText.innerText;
        const originalIconClass = saveBtnIcon.className;
        
        saveBtn.disabled = true;
        saveBtnText.innerText = 'Saving...';
        saveBtnIcon.className = 'fa-solid fa-circle-notch fa-spin';
        
        const formData = new FormData(form);
        formData.append('ajax', '1');
        
        fetch('settings.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            // Restore button state
            saveBtn.disabled = false;
            saveBtnText.innerText = originalText;
            saveBtnIcon.className = originalIconClass;
            
            if (data.success) {
                showToast(data.message, 'success');
                
                // Update sidebar/header company name display dynamically
                const companyNameBadge = document.querySelector('.fw-bold.text-light + small');
                if (companyNameBadge) {
                    companyNameBadge.innerHTML = '<i class="fa-solid fa-building me-1"></i>' + escapeHtml(document.getElementById('company_name').value);
                }
                const headerUserNameText = document.querySelector('.text-end.d-none.d-sm-block .fw-bold');
                // Session is updated, but client side we also update visual representation
            } else {
                showToast(data.message, 'danger');
            }
        })
        .catch(err => {
            saveBtn.disabled = false;
            saveBtnText.innerText = originalText;
            saveBtnIcon.className = originalIconClass;
            showToast('Network error occurred while saving configurations.', 'danger');
            console.error(err);
        });
    });

    // Toast Alert Helper
    function showToast(message, type) {
        const wrapper = document.getElementById('alert-wrapper');
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const iconClass = type === 'success' ? 'fa-circle-check text-success' : 'fa-circle-exclamation text-danger';
        const colorStyle = type === 'success' ? 
            'background-color: rgba(16, 185, 129, 0.15); border-color: rgba(16, 185, 129, 0.3); color: #34d399;' :
            'background-color: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.3); color: #f87171;';
        
        wrapper.innerHTML = `
            <div class="alert ${alertClass} d-flex align-items-center gap-2 mb-4 animate-fade-in" role="alert" style="${colorStyle} border-radius: 10px;">
                <i class="fa-solid ${iconClass} fs-5"></i>
                <div class="flex-grow-1">${escapeHtml(message)}</div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.75rem; padding: 0.5rem;"></button>
            </div>
        `;
        
        // Auto-dismiss alert after 5 seconds
        setTimeout(() => {
            const alertElement = wrapper.querySelector('.alert');
            if (alertElement) {
                const bsAlert = new bootstrap.Alert(alertElement);
                bsAlert.close();
            }
        }, 5000);
    }

    function escapeHtml(string) {
        return String(string)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
