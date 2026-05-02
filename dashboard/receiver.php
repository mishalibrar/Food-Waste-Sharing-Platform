<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

checkRole('receiver');

$user_id = $_SESSION['user_id'];
$name    = $_SESSION['name'];

// Handle stats
$stats = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'collected' THEN 1 ELSE 0 END) as collected
    FROM claims WHERE receiver_id = ?");
$stats->execute([$user_id]);
$counts = $stats->fetch();

$stmt = $pdo->prepare("SELECT c.*, fp.title, fp.image_path, fp.location, fp.quantity, fp.food_type, fp.donor_id, u.name as donor_name 
                      FROM claims c 
                      JOIN food_posts fp ON c.food_id = fp.id 
                      JOIN users u ON fp.donor_id = u.id 
                      WHERE c.receiver_id = ? AND c.status != 'cancelled' AND fp.is_deleted = 0
                      ORDER BY c.claimed_at DESC");
$stmt->execute([$user_id]);
$claims = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container" style="margin-top:2rem; margin-bottom:5rem;">

    <!-- Hero Banner -->
    <div class="dash-hero">
        <div class="dash-hero-text">
            <h1>🍱 Hello, <?php echo htmlspecialchars(explode(' ', $name)[0]); ?>!</h1>
            <p>Manage your food claims and community impact.</p>
        </div>
        <a href="../index.php" class="btn btn-primary" style="padding:0.9rem 2rem; font-size:1rem; white-space:nowrap;">
            <i class="fas fa-search"></i> Browse Food
        </a>
    </div>

    <!-- Stat Cards -->
    <div class="stat-cards-v2">
        <div class="stat-card-v2" style="--card-accent:#3b82f6; --card-icon-bg:rgba(59,130,246,0.1);">
            <div class="stat-icon-wrap"><i class="fas fa-shopping-bag"></i></div>
            <div>
                <div class="stat-num"><?php echo $counts['total']; ?></div>
                <div class="stat-lbl">Total Claims</div>
            </div>
        </div>
        <div class="stat-card-v2" style="--card-accent:#f59e0b; --card-icon-bg:rgba(245,158,11,0.12);">
            <div class="stat-icon-wrap"><i class="fas fa-clock"></i></div>
            <div>
                <div class="stat-num"><?php echo $counts['pending']; ?></div>
                <div class="stat-lbl">Pending Pickup</div>
            </div>
        </div>
        <div class="stat-card-v2" style="--card-accent:#10b981; --card-icon-bg:rgba(16,185,129,0.12);">
            <div class="stat-icon-wrap"><i class="fas fa-check-circle"></i></div>
            <div>
                <div class="stat-num"><?php echo $counts['collected']; ?></div>
                <div class="stat-lbl">Collected</div>
            </div>
        </div>
    </div>

    <!-- Claims Grid -->
    <?php if (empty($claims)): ?>
    <div class="glass" style="padding:4rem; text-align:center; border-radius:24px;">
        <i class="fas fa-utensils" style="font-size:3.5rem; color:var(--text-muted); display:block; margin-bottom:1rem;"></i>
        <h3 style="margin-bottom:0.75rem; color:var(--text-muted);">No Claims Yet</h3>
        <p style="color:var(--text-muted); margin-bottom:2rem;">Browse the marketplace to find and claim surplus food.</p>
        <a href="../index.php" class="btn btn-primary">Start Browsing</a>
    </div>
    <?php else: ?>
    <div class="section-heading">
        <i class="fas fa-shopping-basket" style="color:var(--primary);"></i> My Claims History
    </div>
    <div class="grid" style="grid-template-columns:repeat(auto-fill, minmax(300px, 1fr));">
        <?php foreach ($claims as $claim): ?>
            <div class="claim-card" id="claim-<?php echo $claim['id']; ?>">
                <div class="claim-card-img">
                    <img src="../<?php echo htmlspecialchars($claim['image_path']); ?>" alt="Food">
                    <div class="claim-overlay">
                        <div style="display:flex; justify-content:space-between; align-items:flex-end;">
                            <span class="badge <?php echo $claim['status'] === 'collected' ? 'bg-success' : 'bg-warning'; ?>" style="padding:0.4rem 0.8rem; border-radius:10px;">
                                <i class="fas <?php echo $claim['status'] === 'collected' ? 'fa-check' : 'fa-clock'; ?>"></i> 
                                <?php echo ucfirst($claim['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="claim-card-body">
                    <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:0.75rem;"><?php echo htmlspecialchars($claim['title']); ?></h3>
                    <div style="display:flex; flex-direction:column; gap:0.6rem; margin-bottom:1.5rem;">
                        <div style="display:flex; align-items:center; gap:0.6rem; font-size:0.85rem; color:var(--text-muted);">
                            <i class="fas fa-user-circle" style="color:var(--primary);"></i>
                            <span class="donor-info-badge">From: <strong><?php echo htmlspecialchars($claim['donor_name']); ?></strong> <?php echo getTrustBadge($pdo, $claim['donor_id']); ?></span>
                        </div>
                        <div style="display:flex; align-items:center; gap:0.6rem; font-size:0.85rem; color:var(--text-muted);">
                            <i class="fas fa-map-marker-alt" style="color:#ef4444;"></i>
                            <span><?php echo htmlspecialchars($claim['location']); ?></span>
                        </div>
                        <div style="display:flex; align-items:center; gap:0.6rem; font-size:0.85rem; color:var(--text-muted);">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Claimed on <?php echo date('M j, Y', strtotime($claim['claimed_at'])); ?></span>
                        </div>
                    </div>
                    
                    <div style="display:flex; gap:0.75rem; margin-top:auto;">
                        <a href="../listings/detail.php?id=<?php echo $claim['food_id']; ?>" class="btn btn-primary" style="flex:1; font-size:0.85rem; padding:0.65rem;">
                            <i class="fas fa-info-circle"></i> Details
                        </a>
                        <?php if ($claim['status'] === 'pending'): ?>
                        <button onclick="cancelClaim(<?php echo $claim['id']; ?>)" class="btn btn-outline" 
                            style="flex:1; border-color:rgba(239,68,68,0.3); color:#ef4444; font-size:0.85rem; padding:0.65rem;">
                            Cancel
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
async function cancelClaim(id) {
    const confirmed = await showModal('Cancel Claim', 'Are you sure? This will make the food available for others again.', true);
    if (!confirmed) return;
    const result = await apiCall('../api/cancel_claim.php', { id: id });
    if (result.success) {
        showToast('Claim cancelled successfully.');
        const card = document.getElementById(`claim-${id}`);
        card.style.opacity = '0'; card.style.transform = 'scale(0.9)';
        setTimeout(() => card.remove(), 300);
    } else {
        showToast(result.error || 'Failed to cancel claim', 'error');
    }
}
</script>

<?php include '../includes/footer.php'; ?>
