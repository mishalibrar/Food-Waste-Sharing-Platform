<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

checkRole('admin');

$total_users   = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_posts   = $pdo->query("SELECT COUNT(*) FROM food_posts WHERE is_deleted = 0")->fetchColumn();
$active_posts  = $pdo->query("SELECT COUNT(*) FROM food_posts WHERE status = 'available' AND is_deleted = 0")->fetchColumn();
$total_claims  = $pdo->query("SELECT COUNT(*) FROM claims WHERE status = 'collected'")->fetchColumn();

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 100")->fetchAll();
$posts = $pdo->query("SELECT fp.*, fp.donor_id, u.name as donor_name FROM food_posts fp JOIN users u ON fp.donor_id = u.id WHERE fp.is_deleted = 0 ORDER BY fp.created_at DESC LIMIT 100")->fetchAll();

$platform_reviews_count = $pdo->query("SELECT COUNT(*) FROM platform_reviews")->fetchColumn();
$avg_rating = $pdo->query("SELECT ROUND(AVG(rating),1) FROM platform_reviews")->fetchColumn();
?>
<?php include '../includes/header.php'; ?>

<div class="container" style="margin-top: 2rem; margin-bottom: 5rem;">

    <!-- Hero Header -->
    <div class="dash-hero">
        <div class="dash-hero-text">
            <h1>⚙️ Admin Control Center</h1>
            <p>Manage users, moderate content, and monitor platform activity.</p>
        </div>
        <div style="display:flex; gap:1rem; flex-wrap:wrap; align-items:center;">
            <div style="text-align:right;">
                <div style="font-size:0.8rem; color:var(--text-muted);">Platform Rating</div>
                <div style="font-size:1.4rem; font-weight:800; color:var(--secondary);">
                    ⭐ <?php echo $avg_rating ?: 'N/A'; ?> <span style="font-size:0.8rem; font-weight:400; color:var(--text-muted);">/ 5</span>
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:0.8rem; color:var(--text-muted);">Today</div>
                <div style="font-size:1rem; font-weight:600;"><?php echo date('D, M j Y'); ?></div>
            </div>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="stat-cards-v2">
        <div class="stat-card-v2" style="--card-accent:#10b981; --card-icon-bg:rgba(16,185,129,0.12);">
            <div class="stat-icon-wrap"><i class="fas fa-users"></i></div>
            <div>
                <div class="stat-num"><?php echo $total_users; ?></div>
                <div class="stat-lbl">Total Users</div>
            </div>
        </div>
        <div class="stat-card-v2" style="--card-accent:#3b82f6; --card-icon-bg:rgba(59,130,246,0.12);">
            <div class="stat-icon-wrap"><i class="fas fa-list-ul"></i></div>
            <div>
                <div class="stat-num"><?php echo $total_posts; ?></div>
                <div class="stat-lbl">Total Posts</div>
            </div>
        </div>
        <div class="stat-card-v2" style="--card-accent:#f59e0b; --card-icon-bg:rgba(245,158,11,0.12);">
            <div class="stat-icon-wrap"><i class="fas fa-leaf"></i></div>
            <div>
                <div class="stat-num"><?php echo $active_posts; ?></div>
                <div class="stat-lbl">Active Listings</div>
            </div>
        </div>
        <div class="stat-card-v2" style="--card-accent:#a855f7; --card-icon-bg:rgba(168,85,247,0.12);">
            <div class="stat-icon-wrap"><i class="fas fa-handshake"></i></div>
            <div>
                <div class="stat-num"><?php echo $total_claims; ?></div>
                <div class="stat-lbl">Successful Claims</div>
            </div>
        </div>
    </div>

    <!-- Tab Bar -->
    <div class="tab-bar">
        <button class="tab-btn active" onclick="switchTab('users', this)">
            <i class="fas fa-users"></i> User Management
            <span style="background:rgba(255,255,255,0.15); padding:0.1rem 0.5rem; border-radius:20px; font-size:0.75rem;"><?php echo count($users); ?></span>
        </button>
        <button class="tab-btn" onclick="switchTab('content', this)">
            <i class="fas fa-shield-halved"></i> Content Moderation
            <span style="background:rgba(255,255,255,0.15); padding:0.1rem 0.5rem; border-radius:20px; font-size:0.75rem;"><?php echo count($posts); ?></span>
        </button>
    </div>

    <!-- USERS TAB -->
    <div id="tab-users" class="tab-panel active">
        <div class="glass" style="padding:1.5rem; border-radius:20px;">
            <div class="section-heading">
                <i class="fas fa-users" style="color:var(--primary);"></i> Community Members
            </div>
            <div style="display:flex; flex-direction:column; gap:0.25rem;">
                <?php foreach ($users as $u): ?>
                <div id="user-row-<?php echo $u['id']; ?>" class="user-row">
                    <div style="display:flex; align-items:center; gap:1rem;">
                        <div class="avatar-sm"><?php echo getInitial($u['name']); ?></div>
                        <div>
                            <div style="font-weight:600; font-size:0.95rem;">
                                <?php echo htmlspecialchars($u['name']); ?>
                                <?php if ($u['role'] === 'donor') echo getTrustBadge($pdo, $u['id']); ?>
                            </div>
                            <div style="font-size:0.78rem; color:var(--text-muted); display:flex; gap:0.75rem; align-items:center;">
                                <span><?php echo htmlspecialchars($u['email']); ?></span>
                                <span class="badge" style="background:rgba(255,255,255,0.08); color:var(--text-muted); font-size:0.65rem;"><?php echo strtoupper($u['role']); ?></span>
                                <span>Joined <?php echo date('M j, Y', strtotime($u['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php if ($u['role'] !== 'admin'): ?>
                    <button onclick="moderateUser(<?php echo $u['id']; ?>, '<?php echo addslashes($u['name']); ?>')"
                        style="background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2); color:#ef4444; border-radius:10px; padding:0.5rem 1rem; cursor:pointer; font-size:0.8rem; font-weight:600; transition:var(--transition);"
                        onmouseover="this.style.background='rgba(239,68,68,0.2)'" onmouseout="this.style.background='rgba(239,68,68,0.08)'">
                        <i class="fas fa-user-slash"></i> Remove
                    </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- CONTENT TAB -->
    <div id="tab-content" class="tab-panel">
        <div class="section-heading">
            <i class="fas fa-shield-halved" style="color:var(--secondary);"></i> Food Post Moderation
        </div>
        <div class="grid" style="grid-template-columns:repeat(auto-fill, minmax(290px, 1fr));">
            <?php foreach ($posts as $post): ?>
            <div class="listing-card" id="post-card-<?php echo $post['id']; ?>">
                <div class="listing-card-img">
                    <img src="../<?php echo htmlspecialchars($post['image_path']); ?>" alt="Food Image">
                    <div style="position:absolute; top:0.75rem; right:0.75rem;"><?php echo getStatusBadge($post['status']); ?></div>
                </div>
                <div class="listing-card-body">
                    <h3 style="font-size:1.05rem; font-weight:700; margin-bottom:0.5rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?php echo htmlspecialchars($post['title']); ?>
                    </h3>
                    <div style="font-size:0.82rem; color:var(--text-muted); display:flex; flex-direction:column; gap:0.3rem; margin-bottom:1rem;">
                        <span class="donor-info-badge"><i class="fas fa-user" style="color:var(--primary); width:14px;"></i> <?php echo htmlspecialchars($post['donor_name']); ?> <?php echo getTrustBadge($pdo, $post['donor_id']); ?></span>
                        <span><i class="fas fa-calendar" style="color:var(--text-muted); width:14px;"></i> <?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                        <span><i class="fas fa-map-marker-alt" style="color:var(--text-muted); width:14px;"></i> <?php echo htmlspecialchars($post['location'] ?: 'Not specified'); ?></span>
                    </div>
                </div>
                <div class="listing-card-actions">
                    <a href="../listings/detail.php?id=<?php echo $post['id']; ?>" class="btn btn-outline" style="flex:1; font-size:0.82rem; padding:0.55rem;">
                        <i class="fas fa-eye"></i> View
                    </a>
                    <button onclick="moderatePost(<?php echo $post['id']; ?>, '<?php echo addslashes($post['title']); ?>')"
                        style="flex:1.5; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2); color:#ef4444; border-radius:12px; padding:0.55rem; cursor:pointer; font-size:0.82rem; font-weight:600; transition:var(--transition);"
                        onmouseover="this.style.background='rgba(239,68,68,0.2)'" onmouseout="this.style.background='rgba(239,68,68,0.08)'">
                        <i class="fas fa-trash-alt"></i> Remove Post
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<script>
function switchTab(tab, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    btn.classList.add('active');
}

async function moderateUser(id, name) {
    const confirmed = await showModal('Remove User', `Permanently remove "${name}" and all their data? This cannot be undone.`, true);
    if (!confirmed) return;
    const result = await apiCall('../api/moderate_user.php', { user_id: id });
    if (result.success) {
        showToast('User removed successfully.');
        const row = document.getElementById(`user-row-${id}`);
        row.style.opacity = '0'; row.style.transform = 'translateX(-20px)';
        setTimeout(() => row.remove(), 300);
    } else {
        showToast(result.error || 'Failed to remove user.', 'error');
    }
}

async function moderatePost(id, title) {
    const confirmed = await showModal('Remove Post', `Delete the post "${title}"? This cannot be undone.`, true);
    if (!confirmed) return;
    const result = await apiCall('../api/delete_post.php', { id: id });
    if (result.success) {
        showToast('Post deleted.');
        const card = document.getElementById(`post-card-${id}`);
        card.style.opacity = '0'; card.style.transform = 'scale(0.9)';
        setTimeout(() => card.remove(), 300);
    } else {
        showToast(result.error || 'Failed to delete post.', 'error');
    }
}
</script>

<?php include '../includes/footer.php'; ?>
