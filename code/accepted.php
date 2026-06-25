<?php
// NextHire - Onboarding & Accepted Candidates Portal

require_once __DIR__ . '/config.php';
$pdo = require __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_auth();

$company_id = $_SESSION['company_id'];
$success_msg = '';

// Handle Status Reset from this board
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_candidate_id'])) {
    $cand_id = intval($_POST['reset_candidate_id']);
    
    // Verify ownership before update
    try {
        $stmt = $pdo->prepare("
            SELECT c.id 
            FROM candidates c 
            JOIN jobs j ON c.job_id = j.id 
            WHERE c.id = ? AND j.company_id = ?
        ");
        $stmt->execute([$cand_id, $company_id]);
        
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE candidates SET status = 'applied' WHERE id = ?");
            $stmt->execute([$cand_id]);
            $success_msg = "Candidate status reset back to 'Applied'.";
        }
    } catch (PDOException $e) {
        // Silently capture
    }
}

// Fetch all accepted candidates for this company
$accepted_candidates = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.*, j.title AS job_title, j.id AS job_id 
        FROM candidates c
        JOIN jobs j ON c.job_id = j.id
        WHERE j.company_id = ? AND c.status = 'accepted'
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$company_id]);
    $accepted_candidates = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

require_once __DIR__ . '/header.php';
?>

<!-- Title Header -->
<div class="mb-4">
    <h1 class="h2 text-light mb-1 brand-font">Onboarding Board</h1>
    <p class="text-secondary mb-0">Track successfully hired and accepted candidates across all company job posts</p>
</div>

<!-- Alert banners -->
<?php if (!empty($success_msg)): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert" style="background-color: rgba(16, 185, 129, 0.15); border-color: rgba(16, 185, 129, 0.3); color: #34d399; border-radius: 10px;">
        <i class="fa-solid fa-circle-check"></i>
        <div><?php echo htmlspecialchars($success_msg); ?></div>
    </div>
<?php endif; ?>

<!-- Central Board Panel -->
<div class="card-premium">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="h5 text-light brand-font mb-0"><i class="fa-solid fa-user-check text-success me-2"></i> Accepted Hires (<?php echo count($accepted_candidates); ?>)</h3>
        <span class="badge bg-secondary"><?php echo htmlspecialchars($_SESSION['company_name']); ?></span>
    </div>
    
    <?php if (empty($accepted_candidates)): ?>
        <div class="text-center py-5">
            <div class="text-secondary mb-3"><i class="fa-solid fa-users-rectangle fa-4x text-muted"></i></div>
            <h5 class="text-light">No Hired Candidates Yet</h5>
            <p class="text-secondary mb-4">When you mark a candidate as 'Accepted' on their profile review panel, they will appear here for onboarding tracking.</p>
            <a href="jobs.php" class="btn btn-premium btn-sm">Browse Job Openings</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-premium align-middle mb-0">
                <thead>
                    <tr>
                        <th>Candidate Info</th>
                        <th>Target Position</th>
                        <th>Match Score</th>
                        <th>Contact Details</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accepted_candidates as $cand): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold text-light"><?php echo htmlspecialchars($cand['name']); ?></div>
                                <small class="text-secondary" style="font-size: 0.75rem;">Date Added: <?php echo date('M d, Y', strtotime($cand['created_at'])); ?></small>
                            </td>
                            <td>
                                <div class="fw-semibold text-light"><?php echo htmlspecialchars($cand['job_title']); ?></div>
                                <a href="job-details.php?id=<?php echo $cand['job_id']; ?>" class="text-secondary text-decoration-none" style="font-size: 0.8rem;">
                                    <i class="fa-solid fa-circle-info me-1"></i> View Screening Board
                                </a>
                            </td>
                            <td>
                                <span class="fw-bold text-success" style="font-size: 1.1rem;"><?php echo $cand['match_score']; ?>%</span>
                            </td>
                            <td>
                                <div class="text-light" style="font-size: 0.85rem;"><i class="fa-regular fa-envelope text-primary me-2"></i><?php echo htmlspecialchars($cand['email']); ?></div>
                                <div class="text-secondary mt-1" style="font-size: 0.85rem;"><i class="fa-solid fa-phone text-primary me-2"></i><?php echo htmlspecialchars($cand['phone']); ?></div>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="candidate.php?id=<?php echo $cand['id']; ?>" class="btn btn-premium btn-sm py-1.5 px-3" style="font-size: 0.8rem;">
                                        Review Profile <i class="fa-solid fa-chevron-right ms-1"></i>
                                    </a>
                                    <form action="accepted.php" method="POST" onsubmit="return confirm('Are you sure you want to reset this hired candidate back to applied? They will be removed from this onboarding board.');">
                                        <input type="hidden" name="reset_candidate_id" value="<?php echo $cand['id']; ?>">
                                        <button type="submit" class="btn btn-premium-outline btn-sm py-1.5" style="font-size: 0.8rem;">
                                            Reset Stage
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
