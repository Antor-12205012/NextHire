<?php
// NextHire - Platform Feedback and Ratings System

require_once __DIR__ . '/config.php';
$pdo = require __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_auth();

$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'];
$success_msg = '';
$error_msg = '';

// Handle Feedback Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_feedback') {
    $rating = intval($_POST['rating'] ?? 5);
    $suggestion = trim($_POST['suggestion'] ?? '');
    
    if ($rating < 1 || $rating > 5 || empty($suggestion)) {
        $error_msg = "Please select a rating and provide a suggestion.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO feedbacks (user_id, rating, suggestion) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $rating, $suggestion]);
            $success_msg = "Thank you for your feedback! Your submission has been saved.";
        } catch (PDOException $e) {
            $error_msg = "Error submitting feedback: " . $e->getMessage();
        }
    }
}

// Fetch past feedbacks from the company team members to display in a feed
$team_feedbacks = [];
try {
    $stmt = $pdo->prepare("
        SELECT f.*, u.name AS user_name, u.role AS user_role
        FROM feedbacks f
        JOIN users u ON f.user_id = u.id
        WHERE u.company_id = ?
        ORDER BY f.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$company_id]);
    $team_feedbacks = $stmt->fetchAll();
} catch (PDOException $e) {
    // Suppress
}

require_once __DIR__ . '/header.php';
?>

<!-- Title Header -->
<div class="mb-4">
    <h1 class="h2 text-light mb-1 brand-font">Feedback System</h1>
    <p class="text-secondary mb-0">Help us improve NextHire by submitting ratings and suggestions for optimization</p>
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

<div class="row g-4">
    <!-- Left Column: Submit Feedback Form -->
    <div class="col-lg-6">
        <div class="card-premium h-100">
            <h3 class="h5 text-light brand-font mb-4"><i class="fa-solid fa-pen-nib text-primary me-2"></i> Submit System Review</h3>
            
            <form action="feedback.php" method="POST">
                <input type="hidden" name="action" value="submit_feedback">
                
                <!-- Star Rating selector -->
                <div class="mb-4">
                    <label class="form-label form-label-premium d-block mb-3">Overall Platform Rating</label>
                    <div class="d-flex align-items-center gap-3">
                        <select name="rating" id="rating" class="form-select form-control-premium" style="max-width: 150px; font-size: 1.1rem; font-weight: 600;">
                            <option value="5" selected>★★★★★ (5)</option>
                            <option value="4">★★★★☆ (4)</option>
                            <option value="3">★★★☆☆ (3)</option>
                            <option value="2">★★☆☆☆ (2)</option>
                            <option value="1">★☆☆☆☆ (1)</option>
                        </select>
                        <span class="text-secondary" style="font-size: 0.9rem;">Rate your experience out of 5 stars.</span>
                    </div>
                </div>
                
                <!-- Suggestions input -->
                <div class="mb-4">
                    <label for="suggestion" class="form-label form-label-premium">How can we improve the screening experience?</label>
                    <textarea name="suggestion" id="suggestion" class="form-control form-control-premium" rows="6" placeholder="Describe additional features, UI improvements, or parsing recommendations for Team Innovex..." required></textarea>
                </div>
                
                <button type="submit" class="btn btn-premium w-100 py-2.5"><i class="fa-solid fa-paper-plane me-2"></i> Send Review</button>
            </form>
        </div>
    </div>
    
    <!-- Right Column: Company Feedback Feed -->
    <div class="col-lg-6">
        <div class="card-premium h-100">
            <h3 class="h5 text-light brand-font mb-4"><i class="fa-solid fa-comments text-success me-2"></i> Recent Feedback from Your Team</h3>
            
            <?php if (empty($team_feedbacks)): ?>
                <div class="text-center py-5 text-secondary">
                    <i class="fa-regular fa-comment-dots fa-3x mb-3 text-muted"></i>
                    <h5>No feedback submitted yet</h5>
                    <p class="small">Your team's submissions will appear here once registered.</p>
                </div>
            <?php else: ?>
                <div style="max-height: 450px; overflow-y: auto; padding-right: 5px;">
                    <?php foreach ($team_feedbacks as $fb): ?>
                        <div class="p-3 mb-3 bg-dark rounded-3 border border-secondary">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="fw-bold text-light" style="font-size: 0.9rem;"><?php echo htmlspecialchars($fb['user_name']); ?></div>
                                    <span class="badge bg-secondary" style="font-size: 0.7rem;"><?php echo ucfirst(htmlspecialchars($fb['user_role'])); ?></span>
                                </div>
                                <div class="text-end">
                                    <div class="text-warning" style="font-size: 0.95rem;">
                                        <?php echo str_repeat('★', $fb['rating']) . str_repeat('☆', 5 - $fb['rating']); ?>
                                    </div>
                                    <small class="text-secondary" style="font-size: 0.75rem;">
                                        <?php echo date('M d, Y', strtotime($fb['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                            <p class="text-secondary mb-0" style="font-size: 0.9rem; line-height: 1.5; font-style: italic;">
                                "<?php echo htmlspecialchars($fb['suggestion']); ?>"
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
