<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

checkRole('donor');

$user_id = $_SESSION['user_id'];
$name    = $_SESSION['name'];

$stats = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
    SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) as reserved,
    SUM(CASE WHEN status = 'collected' THEN 1 ELSE 0 END) as collected
    FROM food_posts WHERE donor_id = ?");
$stats->execute([$user_id]);
$counts = $stats->fetch();

$stmt = $pdo->prepare("SELECT fp.*, c.receiver_id, u.name as receiver_name 
    FROM food_posts fp 
    LEFT JOIN claims c ON fp.id = c.food_id AND c.status != 'cancelled'
    LEFT JOIN users u ON c.receiver_id = u.id
    WHERE fp.donor_id = ? 
    ORDER BY fp.created_at DESC");
$stmt->execute([$user_id]);
$listings = $stmt->fetchAll();
?>
<?php include '../includes/header.php'; ?>

<div class="container" style="margin-top:2rem; margin-bottom:5rem;">

    <!-- Hero Banner -->
    <div class="dash-hero">
        <div class="dash-hero-text">
            <h1>🌱 Welcome back, <?php echo htmlspecialchars(explode(' ', $name)[0]); ?>!</h1>
            <p>Track your donations and make a difference in the community.</p>
        </div>
        <a href="../listings/create.php" class="btn btn-primary" style="padding:0.9rem 2rem; font-size:1rem; white-space:nowrap;">
            <i class="fas fa-plus-circle"></i> Post New Item
        </a>
    </div>

    <!-- Stat Cards -->
    <div class="stat-cards-v2">
        <div class="stat-card-v2" style="--card-accent:#94a3b8; --card-icon-bg:rgba(148,163,184,0.1);">
            <div class="stat-icon-wrap"><i class="fas fa-layer-group"></i></div>
            <div>
                <div class="stat-num"><?php echo $counts['total']; ?></div>
                <div class="stat-lbl">Total Posts</div>
            </div>
        </div>
        <div class="stat-card-v2" style="--card-accent:#10b981; --card-icon-bg:rgba(16,185,129,0.12);">
            <div class="stat-icon-wrap"><i class="fas fa-check-circle"></i></div>
            <div>
                <div class="stat-num"><?php echo $counts['available']; ?></div>
                <div class="stat-lbl">Available</div>
            </div>
        </div>
        <div class="stat-card-v2" style="--card-accent:#f59e0b; --card-icon-bg:rgba(245,158,11,0.12);">
            <div class="stat-icon-wrap"><i class="fas fa-clock"></i></div>
            <div>
                <div class="stat-num"><?php echo $counts['reserved']; ?></div>
                <div class="stat-lbl">Reserved</div>
            </div>
        </div>
        <div class="stat-card-v2" style="--card-accent:#3b82f6; --card-icon-bg:rgba(59,130,246,0.12);">
            <div class="stat-icon-wrap"><i class="fas fa-handshake"></i></div>
            <div>
                <div class="stat-num"><?php echo $counts['collected']; ?></div>
                <div class="stat-lbl">Collected</div>
            </div>
        </div>
    </div>

    <!-- Listings Grid -->
    <?php if (empty($listings)): ?>
    <div class="glass" style="padding:4rem; text-align:center; border-radius:24px;">
        <i class="fas fa-box-open" style="font-size:3.5rem; color:var(--text-muted); display:block; margin-bottom:1rem;"></i>
        <h3 style="margin-bottom:0.75rem; color:var(--text-muted);">No Donations Yet</h3>
        <p style="color:var(--text-muted); margin-bottom:2rem;">Start making a difference by posting surplus food.</p>
        <a href="../listings/create.php" class="btn btn-primary">Post Your First Item</a>
    </div>
    <?php else: ?>
    <div class="section-heading">
        <i class="fas fa-list" style="color:var(--primary);"></i> My Food Listings
        <span style="font-size:0.85rem; font-weight:400; color:var(--text-muted); margin-left:auto;"><?php echo count($listings); ?> items</span>
    </div>
    <div class="grid" style="grid-template-columns:repeat(auto-fill, minmax(290px, 1fr));">
        <?php foreach ($listings as $item): ?>
        <div class="listing-card" id="row-<?php echo $item['id']; ?>">
            <!-- Image -->
            <div class="listing-card-img">
                <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="Food">
                <div style="position:absolute; top:0.75rem; left:0.75rem;"><?php echo getStatusBadge($item['status']); ?></div>
                <?php
                    $expiry = strtotime($item['expiry_time']);
                    $now = time();
                    $diff = $expiry - $now;
                    $hours = floor($diff / 3600);
                    $urgent = $diff < 14400 && $diff > 0;
                    $expired = $diff <= 0;
                ?>
                <?php if (!$expired): ?>
                <div style="position:absolute; bottom:0.75rem; right:0.75rem;">
                    <span class="expiry-chip <?php echo $urgent ? 'urgent' : ''; ?>">
                        <i class="fas fa-clock"></i>
                        <?php echo $urgent ? $hours.'h left' : date('M j', $expiry); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Body -->
            <div class="listing-card-body">
                <h3 style="font-size:1.05rem; font-weight:700; margin-bottom:0.4rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?php echo htmlspecialchars($item['title']); ?>
                </h3>
                <div style="font-size:0.82rem; color:var(--text-muted); display:flex; flex-direction:column; gap:0.25rem; margin-bottom:1rem;">
                    <span><i class="fas fa-tag" style="color:var(--primary); width:14px;"></i> <?php echo getFoodTypeLabel($item['food_type']); ?></span>
                    <span><i class="fas fa-box" style="width:14px;"></i> <?php echo htmlspecialchars($item['quantity'] ?: 'N/A'); ?></span>
                    <?php if ($item['receiver_name']): ?>
                    <span style="color:var(--primary);"><i class="fas fa-user-check" style="width:14px;"></i> Claimed by <?php echo htmlspecialchars($item['receiver_name']); ?></span>
                    <?php else: ?>
                    <span><i class="fas fa-user-slash" style="width:14px;"></i> Not yet claimed</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions -->
            <div class="listing-card-actions">
                <?php if ($item['status'] === 'reserved'): ?>
                <button onclick="markAsCollected(<?php echo $item['id']; ?>)" 
                    class="btn btn-primary" style="flex:1.5; font-size:0.8rem; padding:0.55rem; border-radius:10px;">
                    <i class="fas fa-check"></i> Collected
                </button>
                <?php endif; ?>
                <a href="../listings/detail.php?id=<?php echo $item['id']; ?>" class="btn btn-outline" style="flex:1; font-size:0.8rem; padding:0.55rem; border-radius:10px;">
                    <i class="fas fa-eye"></i>
                </a>
                <a href="../listings/edit.php?id=<?php echo $item['id']; ?>" class="btn btn-outline" style="flex:1; font-size:0.8rem; padding:0.55rem; border-radius:10px; border-color:var(--text-muted); color:var(--text-muted);">
                    <i class="fas fa-edit"></i>
                </a>
                <button onclick="deleteListing(<?php echo $item['id']; ?>)"
                    style="flex:1; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2); color:#ef4444; border-radius:10px; padding:0.55rem; cursor:pointer; font-size:0.85rem; transition:var(--transition);"
                    onmouseover="this.style.background='rgba(239,68,68,0.2)'" onmouseout="this.style.background='rgba(239,68,68,0.08)'">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
async function markAsCollected(id) {
    const confirmed = await showModal('Mark Collected', 'Confirm that this item has been picked up successfully?', true);
    if (!confirmed) return;
    const result = await apiCall('../api/update_status.php', { id: id });
    if (result.success) {
        showToast('Status updated to Collected!');
        setTimeout(() => location.reload(), 1200);
    } else {
        showToast(result.error || 'Failed to update status', 'error');
    }
}

async function deleteListing(id) {
    const confirmed = await showModal('Delete Listing', 'Are you sure? This will permanently remove your donation post.', true);
    if (!confirmed) return;
    const result = await apiCall('../api/delete_post.php', { id: id });
    if (result.success) {
        showToast('Listing removed.');
        const card = document.getElementById(`row-${id}`);
        card.style.opacity = '0'; card.style.transform = 'scale(0.9)';
        setTimeout(() => card.remove(), 300);
    } else {
        showToast(result.error || 'Failed to delete listing', 'error');
    }
}
</script>

<?php include '../includes/footer.php'; ?>
