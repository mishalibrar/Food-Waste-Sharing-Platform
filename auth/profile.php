<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('login.php');

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch activity stats
if ($user['role'] === 'donor') {
    $stats = $pdo->prepare("SELECT COUNT(*) FROM food_posts WHERE donor_id = ?");
} else {
    $stats = $pdo->prepare("SELECT COUNT(*) FROM claims WHERE receiver_id = ?");
}
$stats->execute([$user_id]);
$activity_count = $stats->fetchColumn();

// Fetch recent transactions
if ($user['role'] === 'donor') {
    $transactionsStmt = $pdo->prepare("SELECT fp.*, c.status as claim_status, u.name as receiver_name 
                                     FROM food_posts fp 
                                     LEFT JOIN claims c ON fp.id = c.food_id 
                                     LEFT JOIN users u ON c.receiver_id = u.id 
                                     WHERE fp.donor_id = ? 
                                     ORDER BY fp.created_at DESC LIMIT 10");
} else {
    $transactionsStmt = $pdo->prepare("SELECT fp.*, c.status as claim_status, u.name as donor_name 
                                     FROM claims c 
                                     JOIN food_posts fp ON c.food_id = fp.id 
                                     JOIN users u ON fp.donor_id = u.id 
                                     WHERE c.receiver_id = ? 
                                     ORDER BY c.claimed_at DESC LIMIT 10");
}
$transactionsStmt->execute([$user_id]);
$transactions = $transactionsStmt->fetchAll();

// Fetch existing platform review
$prStmt = $pdo->prepare("SELECT * FROM platform_reviews WHERE user_id = ?");
$prStmt->execute([$user_id]);
$existingPlatformReview = $prStmt->fetch();

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $new_password = $_POST['new_password'];
    
    try {
        // Check if email is already taken by another user
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkStmt->execute([$email, $user_id]);
        if ($checkStmt->fetch()) {
            $error = "This email is already in use by another account.";
        } else {
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $email, $phone, $hashed_password, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->execute([$name, $email, $phone, $user_id]);
            }
            
            $_SESSION['name'] = $name;
            $success = "Profile updated successfully!";
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        }
    } catch (PDOException $e) {
        $error = "Failed to update profile.";
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container" style="max-width: 1100px; margin-top: 3rem; margin-bottom: 5rem;">
    <!-- Profile Header Card -->
    <div class="glass" style="padding: 3rem; border-radius: 24px; margin-bottom: 2rem; display: flex; align-items: center; gap: 3rem; flex-wrap: wrap;">
        <div class="avatar-lg" style="width: 150px; height: 150px; font-size: 4rem; border: 4px solid rgba(16, 185, 129, 0.2);">
            <?php echo getInitial($user['name']); ?>
        </div>
        <div style="flex: 1;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                <h1 style="margin: 0; font-size: 2.5rem;"><?php echo htmlspecialchars($user['name']); ?></h1>
                <span class="badge" style="background: var(--primary); color: white; padding: 0.4rem 1rem; text-transform: uppercase; letter-spacing: 1px; font-size: 0.7rem;">
                    <?php echo $user['role']; ?>
                </span>
            </div>
            <p style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 2rem;">
                <i class="fas fa-calendar-alt"></i> Member since <?php echo date('M Y', strtotime($user['created_at'])); ?>
            </p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem;">
                <div class="glass" style="padding: 1.25rem; border-radius: 16px; background: rgba(255,255,255,0.03); display: flex; flex-direction: column; gap: 0.4rem; text-align: center; align-items: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Email Address</div>
                    <div style="font-weight: 600; font-size: 0.95rem; color: var(--text-main); width: 100%; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($user['email']); ?>">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </div>
                </div>
                <div class="glass" style="padding: 1.25rem; border-radius: 16px; background: rgba(255,255,255,0.03); display: flex; flex-direction: column; gap: 0.4rem; text-align: center; align-items: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Phone Number</div>
                    <div style="font-weight: 600; font-size: 0.95rem; color: var(--text-main);">
                        <?php echo $user['phone'] ? htmlspecialchars($user['phone']) : '<span style="color:var(--text-muted); font-weight:400; font-style:italic;">Not provided</span>'; ?>
                    </div>
                </div>
                <div class="glass" style="padding: 1.25rem; border-radius: 16px; background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.1); display: flex; flex-direction: column; gap: 0.2rem; text-align: center; align-items: center;">
                    <div style="font-size: 0.75rem; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px;">
                        <?php echo $user['role'] === 'donor' ? 'Total Donations' : 'Total Claims'; ?>
                    </div>
                    <div style="font-size: 1.75rem; font-weight: 800; color: var(--primary); line-height: 1;"><?php echo $activity_count; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem; align-items: start;">
        <!-- Left Column: Transactions -->
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            <div class="glass" style="padding: 2.5rem; border-radius: 24px;">
                <h3 style="margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
                    <i class="fas fa-history" style="color: var(--primary);"></i> Recent Activity
                </h3>
                
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php if (empty($transactions)): ?>
                        <div style="text-align: center; padding: 3rem; background: rgba(255,255,255,0.02); border-radius: 16px; border: 1px dashed var(--glass-border);">
                            <i class="fas fa-box-open" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem; display: block;"></i>
                            <p style="color: var(--text-muted);">No activity found yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($transactions as $tx): ?>
                            <div class="glass" style="padding: 1.25rem; border-radius: 16px; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; transition: var(--transition); border: 1px solid transparent;" onmouseover="this.style.borderColor='rgba(16, 185, 129, 0.3)'; this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.borderColor='transparent'; this.style.background='transparent'">
                                <div style="display: flex; align-items: center; gap: 1.25rem; flex: 1; overflow: hidden;">
                                    <img src="../<?php echo htmlspecialchars($tx['image_path']); ?>" style="width: 60px; height: 60px; border-radius: 12px; object-fit: cover;">
                                    <div style="overflow: hidden;">
                                        <div style="font-weight: 600; font-size: 1.05rem; margin-bottom: 0.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars($tx['title']); ?>
                                        </div>
                                        <div style="font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.5rem;">
                                            <i class="fas fa-user-circle"></i>
                                            <?php echo $user['role'] === 'donor' ? 'To: ' . htmlspecialchars($tx['receiver_name'] ?? 'Pending') : 'From: ' . htmlspecialchars($tx['donor_name']); ?>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.75rem;">
                                    <?php echo getStatusBadge($tx['status']); ?>
                                    <a href="../listings/detail.php?id=<?php echo $tx['id']; ?>" class="btn btn-outline" style="padding: 0.4rem 1rem; font-size: 0.8rem; border-radius: 8px;">
                                        <?php echo ($tx['status'] === 'collected') ? 'Leave Review' : 'View Details'; ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Settings & Feedback -->
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            <!-- Settings Card -->
            <div class="glass" style="padding: 2.5rem; border-radius: 24px;">
                <h3 style="margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
                    <i class="fas fa-user-cog" style="color: var(--primary);"></i> Account Settings
                </h3>

                <?php if ($success): ?>
                    <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.9rem;">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+1234567890">
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <div style="position: relative;">
                            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Leave blank to keep current" style="padding-right: 3rem;">
                            <i class="fas fa-eye" id="toggleIcon" 
                               onclick="togglePassword('new_password', 'toggleIcon')" 
                               style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); transition: var(--transition);"
                               onmouseover="this.style.color='var(--primary)'" 
                               onmouseout="this.style.color='var(--text-muted)'"></i>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem; margin-top: 1rem;">
                        Update Account
                    </button>
                </form>
            </div>

            <!-- Platform Feedback Card -->
            <div class="glass" style="padding: 2.5rem; border-radius: 24px;">
                <h3 style="margin-bottom: 0.5rem; display: flex; align-items: center; gap: 1rem;">
                    <i class="fas fa-star" style="color: var(--secondary);"></i> Platform Feedback
                </h3>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 2rem;">Help us improve FoodShare!</p>

                <div id="platformReviewContainer">
                    <?php if ($existingPlatformReview): ?>
                        <div id="reviewViewMode">
                            <div style="display: flex; gap: 0.4rem; margin-bottom: 1.25rem;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?php echo ($i <= $existingPlatformReview['rating']) ? 'fas' : 'far'; ?> fa-star" style="font-size: 1.25rem; color: var(--secondary);"></i>
                                <?php endfor; ?>
                            </div>
                            <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); padding: 1.25rem; border-radius: 16px; margin-bottom: 1.5rem; font-style: italic; color: var(--text-muted); line-height: 1.6;">
                                "<?php echo htmlspecialchars($existingPlatformReview['comment']); ?>"
                            </div>
                            <button onclick="toggleEditReview(true)" class="btn btn-outline" style="width: 100%; padding: 0.75rem;">
                                <i class="fas fa-edit"></i> Edit Feedback
                            </button>
                        </div>
                    <?php endif; ?>

                    <div id="platformReviewForm" style="<?php echo $existingPlatformReview ? 'display: none;' : ''; ?>">
                        <div style="display: flex; justify-content: center; gap: 0.75rem; margin-bottom: 1.5rem;" id="platformStarRating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span onclick="setPlatformRating(<?php echo $i; ?>)" class="platform-star" 
                                      style="font-size: 2rem; cursor: pointer; color: <?php echo ($existingPlatformReview && $i <= $existingPlatformReview['rating']) ? 'var(--secondary)' : 'var(--text-muted)'; ?>;">
                                    <i class="<?php echo ($existingPlatformReview && $i <= $existingPlatformReview['rating']) ? 'fas' : 'far'; ?> fa-star"></i>
                                </span>
                            <?php endfor; ?>
                        </div>
                        <div class="form-group">
                            <textarea id="platformComment" class="form-control" rows="3" placeholder="Share your thoughts..." style="resize: none;"><?php echo $existingPlatformReview ? htmlspecialchars($existingPlatformReview['comment']) : ''; ?></textarea>
                        </div>
                        <div style="display: flex; gap: 1rem;">
                            <button onclick="submitPlatformReview()" class="btn btn-primary" style="flex: 2;">Submit</button>
                            <?php if ($existingPlatformReview): ?>
                                <button onclick="toggleEditReview(false)" class="btn btn-outline" style="flex: 1;">Cancel</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleEditReview(show) {
    const viewMode = document.getElementById('reviewViewMode');
    const formMode = document.getElementById('platformReviewForm');
    if (show) {
        if (viewMode) viewMode.style.display = 'none';
        formMode.style.display = 'block';
    } else {
        if (viewMode) viewMode.style.display = 'block';
        formMode.style.display = 'none';
    }
}

let platformRating = <?php echo $existingPlatformReview ? $existingPlatformReview['rating'] : 0; ?>;
const ratingLabels = ['', 'Poor', 'Fair', 'Good', 'Great', 'Excellent'];

function setPlatformRating(stars) {
    platformRating = stars;
    document.querySelectorAll('.platform-star i').forEach((icon, index) => {
        if (index < stars) {
            icon.className = 'fas fa-star';
            icon.parentElement.style.color = 'var(--secondary)';
        } else {
            icon.className = 'far fa-star';
            icon.parentElement.style.color = 'var(--text-muted)';
        }
    });
}

async function submitPlatformReview() {
    if (platformRating === 0) {
        showToast('Please select a rating', 'error');
        return;
    }
    const result = await apiCall('../api/platform_review.php', {
        rating: platformRating,
        comment: document.getElementById('platformComment').value
    });
    if (result.success) {
        showToast('Thank you for your feedback!');
        setTimeout(() => location.reload(), 1500);
    } else {
        showToast(result.error || 'Failed to submit', 'error');
    }
}
</script>

<?php include '../includes/footer.php'; ?>
