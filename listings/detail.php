<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

updateExpiredListings($pdo);

$id = $_GET['id'] ?? null;
if (!$id) redirect('../index.php');

// Get food item with donor info
$stmt = $pdo->prepare("SELECT fp.*, u.name as donor_name, u.phone as donor_phone, u.id as donor_user_id, u.created_at as donor_joined, u.rating_avg as donor_rating_avg
                      FROM food_posts fp 
                      JOIN users u ON fp.donor_id = u.id 
                      WHERE fp.id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) redirect('../index.php');

// Get claim info (who claimed it)
$claimStmt = $pdo->prepare("SELECT c.*, u.name as receiver_name, u.phone as receiver_phone FROM claims c JOIN users u ON c.receiver_id = u.id WHERE c.food_id = ? AND c.status != 'cancelled' LIMIT 1");
$claimStmt->execute([$id]);
$claim = $claimStmt->fetch();

// Get donor stats
$donorStats = $pdo->prepare("SELECT COUNT(*) as total_posts FROM food_posts WHERE donor_id = ?");
$donorStats->execute([$item['donor_user_id']]);
$donorTotalPosts = $donorStats->fetch()['total_posts'];

$donorCollected = $pdo->prepare("SELECT COUNT(*) as collected FROM food_posts WHERE donor_id = ? AND status = 'collected' AND is_deleted = 0");
$donorCollected->execute([$item['donor_user_id']]);
$donorCollectedCount = $donorCollected->fetch()['collected'];

// Get donor average rating
$avgRating = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE reviewee_id = ?");
$avgRating->execute([$item['donor_user_id']]);
$donorRating = $avgRating->fetch();
$avgScore = round($donorRating['avg_rating'] ?? 0, 1);
$totalReviews = $donorRating['total_reviews'];

// Get reviews for this food item
$reviewsStmt = $pdo->prepare("SELECT r.*, u.name as reviewer_name FROM reviews r JOIN users u ON r.reviewer_id = u.id WHERE r.food_id = ? ORDER BY r.created_at DESC");
$reviewsStmt->execute([$id]);
$reviews = $reviewsStmt->fetchAll();

// Get activity timeline
$timelineStmt = $pdo->prepare("SELECT al.*, u.name as user_name FROM activity_log al JOIN users u ON al.user_id = u.id WHERE al.food_id = ? ORDER BY al.created_at ASC");
$timelineStmt->execute([$id]);
$timeline = $timelineStmt->fetchAll();

// Check if current user can review
$canReview = false;
$reviewTarget = null;
if (isLoggedIn() && $item['status'] === 'collected') {
    $existingReview = $pdo->prepare("SELECT id FROM reviews WHERE food_id = ? AND reviewer_id = ?");
    $existingReview->execute([$id, $_SESSION['user_id']]);
    if (!$existingReview->fetch()) {
        if (getRole() === 'receiver' && $claim && $claim['receiver_id'] == $_SESSION['user_id']) {
            $canReview = true;
            $reviewTarget = $item['donor_user_id'];
        } elseif (getRole() === 'donor' && $item['donor_id'] == $_SESSION['user_id'] && $claim) {
            $canReview = true;
            $reviewTarget = $claim['receiver_id'];
        }
    }
}

// Determine trust badge
$trustBadge = getTrustBadgeFromCount($donorCollectedCount);
?>

<?php include '../includes/header.php'; ?>

<div class="container" style="max-width: 1100px; margin-top: 3rem;">
    <a href="../index.php" style="color: var(--text-muted); text-decoration: none; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 2rem; transition: var(--transition);" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">
        <i class="fas fa-arrow-left"></i> Back to Marketplace
    </a>

    <!-- Main Layout -->
    <div class="detail-grid" style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 3rem;">
        <!-- Left Column -->
        <div>
            <!-- Hero Image -->
            <div class="glass" style="padding: 1rem; border-radius: 24px; position: relative; margin-bottom: 2rem;">
                <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" style="width: 100%; border-radius: 16px; object-fit: cover; max-height: 500px;">
                <div style="position: absolute; top: 2rem; right: 2rem; display: flex; flex-direction: column; gap: 0.75rem; align-items: flex-end;">
                    <span class="urgent-badge" style="display: none;">Urgent</span>
                    <span class="countdown" data-expiry="<?php echo $item['expiry_time']; ?>" style="font-size: 1.1rem; padding: 0.5rem 1rem;">--h --m --s</span>
                </div>
            </div>

            <!-- Delivery / Collection Status Timeline -->
            <div class="glass" style="padding: 2rem; border-radius: 20px; margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-route" style="color: var(--primary);"></i> Activity Timeline
                </h3>
                
                <!-- Status Steps -->
                <div class="timeline">
                    <!-- Posted -->
                    <div class="timeline-step completed">
                        <div class="timeline-dot"><i class="fas fa-plus"></i></div>
                        <div class="timeline-content">
                            <strong>Posted</strong>
                            <span><?php echo formatDate($item['created_at']); ?></span>
                        </div>
                    </div>

                    <!-- Claimed -->
                    <div class="timeline-step <?php echo $claim ? 'completed' : ''; ?>">
                        <div class="timeline-dot"><i class="fas fa-hand-holding-heart"></i></div>
                        <div class="timeline-content">
                            <strong><?php echo $claim ? 'Claimed by ' . htmlspecialchars($claim['receiver_name']) : 'Awaiting Claim'; ?></strong>
                            <span><?php echo $claim ? formatDate($claim['claimed_at']) : 'Pending'; ?></span>
                        </div>
                    </div>

                    <!-- Collected -->
                    <div class="timeline-step <?php echo $item['status'] === 'collected' ? 'completed' : ''; ?>">
                        <div class="timeline-dot"><i class="fas fa-check-double"></i></div>
                        <div class="timeline-content">
                            <strong><?php echo $item['status'] === 'collected' ? 'Donated Successfully ✅' : 'Awaiting Collection'; ?></strong>
                            <span><?php echo ($claim && $claim['collected_at']) ? formatDate($claim['collected_at']) : 'Pending'; ?></span>
                        </div>
                    </div>

                    <!-- Reviewed -->
                    <div class="timeline-step <?php echo count($reviews) > 0 ? 'completed' : ''; ?>">
                        <div class="timeline-dot"><i class="fas fa-star"></i></div>
                        <div class="timeline-content">
                            <strong><?php echo count($reviews) > 0 ? 'Reviewed (' . count($reviews) . ')' : 'Awaiting Review'; ?></strong>
                            <span><?php echo count($reviews) > 0 ? formatDate($reviews[0]['created_at']) : 'After collection'; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Extra activity log entries -->
                <?php if (count($timeline) > 0): ?>
                    <div style="margin-top: 1.5rem; border-top: 1px solid var(--glass-border); padding-top: 1.5rem;">
                        <h4 style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 1px;">Detailed Log</h4>
                        <?php foreach ($timeline as $log): ?>
                            <div style="display: flex; align-items: center; gap: 1rem; padding: 0.5rem 0; font-size: 0.85rem;">
                                <span style="color: var(--primary); min-width: 80px;"><?php echo date('M j, g:ia', strtotime($log['created_at'])); ?></span>
                                <span style="color: var(--text-muted);"><?php echo htmlspecialchars($log['user_name']); ?> — <?php echo ucfirst($log['action']); ?><?php echo $log['details'] ? ': ' . htmlspecialchars($log['details']) : ''; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column -->
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            <!-- Item Info -->
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <?php echo getStatusBadge($item['status']); ?>
                    <span style="color: var(--primary); font-weight: 600;"><i class="fas fa-tag"></i> <?php echo getFoodTypeLabel($item['food_type']); ?></span>
                </div>
                <h1 style="font-size: 2.5rem; line-height: 1.2; margin-bottom: 1rem;"><?php echo htmlspecialchars($item['title']); ?></h1>
                <p style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.7;">
                    <?php echo nl2br(htmlspecialchars($item['description'])); ?>
                </p>
            </div>

            <!-- Quick Info Grid -->
            <div class="glass" style="padding: 1.5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div>
                    <label style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 1px;">Quantity</label>
                    <span style="font-weight: 600; color: var(--secondary);"><i class="fas fa-shopping-basket"></i> <?php echo htmlspecialchars($item['quantity']); ?></span>
                </div>
                <div>
                    <label style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 1px;">Location</label>
                    <span style="font-weight: 600;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['location']); ?></span>
                </div>
                <div>
                    <label style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 1px;">Expires On</label>
                    <span style="font-weight: 600;"><i class="fas fa-clock"></i> <?php echo formatDate($item['expiry_time']); ?></span>
                </div>
                <div>
                    <label style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 1px;">Status</label>
                    <span style="font-weight: 600; text-transform: capitalize;"><?php echo $item['status']; ?></span>
                </div>
            </div>

            <!-- Donor Profile Card -->
            <div class="glass" style="padding: 1.5rem; display: flex; align-items: center; gap: 1.5rem;">
                <div style="width: 60px; height: 60px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: white; flex-shrink: 0;">
                    <i class="fas fa-user"></i>
                </div>
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.25rem;">
                        <strong style="font-size: 1.1rem;"><?php echo htmlspecialchars($item['donor_name']); ?></strong>
                        <?php if ($trustBadge): ?>
                            <?php echo $trustBadge; ?>
                        <?php endif; ?>
                    </div>
                    <!-- Star Rating Display -->
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
                        <?php
                        $cachedAvg = floatval($item['donor_rating_avg'] ?? 0);
                        $displayAvg = $cachedAvg > 0 ? $cachedAvg : $avgScore;
                        for ($s = 1; $s <= 5; $s++):
                            if ($s <= floor($displayAvg)):
                        ?>
                            <i class="fas fa-star" style="color: var(--secondary); font-size: 0.95rem;"></i>
                        <?php elseif ($s - $displayAvg < 1 && $s - $displayAvg > 0): ?>
                            <i class="fas fa-star-half-alt" style="color: var(--secondary); font-size: 0.95rem;"></i>
                        <?php else: ?>
                            <i class="far fa-star" style="color: var(--text-muted); font-size: 0.95rem;"></i>
                        <?php endif; endfor; ?>
                        <span style="font-weight: 700; color: var(--secondary); font-size: 0.95rem;"><?php echo $displayAvg > 0 ? number_format($displayAvg, 1) : 'N/A'; ?></span>
                        <span style="color: var(--text-muted); font-size: 0.8rem;">(<?php echo $totalReviews; ?> review<?php echo $totalReviews != 1 ? 's' : ''; ?>)</span>
                    </div>
                    <div style="display: flex; gap: 1.5rem; color: var(--text-muted); font-size: 0.85rem;">
                        <span><i class="fas fa-box"></i> <?php echo $donorTotalPosts; ?> posts</span>
                        <span><i class="fas fa-check-circle"></i> <?php echo $donorCollectedCount; ?> donated</span>
                    </div>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">Joined <?php echo date('M Y', strtotime($item['donor_joined'])); ?></span>
                </div>
            </div>

            <!-- Communication / Contact Details (Shown if claimed) -->
            <?php if ($claim && isLoggedIn()): ?>
                <?php 
                $showContact = false;
                $contactName = '';
                $contactPhone = '';
                $contactRole = '';
                
                if (getRole() === 'receiver' && $claim['receiver_id'] == $_SESSION['user_id']) {
                    $showContact = true;
                    $contactName = $item['donor_name'];
                    $contactPhone = $item['donor_phone'];
                    $contactRole = 'Donor';
                } elseif (getRole() === 'donor' && $item['donor_id'] == $_SESSION['user_id']) {
                    $showContact = true;
                    $contactName = $claim['receiver_name'];
                    $contactPhone = $claim['receiver_phone'];
                    $contactRole = 'Receiver';
                }
                ?>
                
                <?php if ($showContact): ?>
                    <div class="glass" style="padding: 1.5rem; border-color: var(--secondary); background: rgba(245, 158, 11, 0.05);">
                        <h4 style="margin-bottom: 1rem; color: var(--secondary); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;">
                            <i class="fas fa-comments"></i> Contact Details
                        </h4>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 40px; height: 40px; background: var(--secondary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo $contactRole; ?>: <strong><?php echo htmlspecialchars($contactName); ?></strong></div>
                                <?php if ($contactPhone): ?>
                                    <a href="tel:<?php echo htmlspecialchars($contactPhone); ?>" style="color: var(--text-main); font-weight: 700; font-size: 1.1rem; text-decoration: none;">
                                        <?php echo htmlspecialchars($contactPhone); ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-style: italic; font-size: 0.9rem;">No phone number provided</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p style="margin-top: 1rem; font-size: 0.8rem; color: var(--text-muted);">
                            <i class="fas fa-info-circle"></i> Use this number to coordinate pickup/delivery details.
                        </p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Claim Action -->
            <div>
                <?php if (isLoggedIn()): ?>
                    <?php if (getRole() === 'receiver' && $item['status'] === 'available'): ?>
                        <button onclick="claimFood(<?php echo $item['id']; ?>)" class="btn btn-primary" style="width: 100%; padding: 1.2rem; font-size: 1.1rem;">
                            <i class="fas fa-hand-holding-heart"></i> Claim This Item Now
                        </button>
                    <?php elseif ($item['status'] === 'reserved' && $claim): ?>
                        <div class="glass" style="padding: 1.5rem; text-align: center;">
                            <i class="fas fa-handshake" style="font-size: 1.5rem; color: var(--secondary);"></i>
                            <p style="margin-top: 0.5rem;">Reserved by <strong><?php echo htmlspecialchars($claim['receiver_name']); ?></strong></p>
                            <p style="color: var(--text-muted); font-size: 0.85rem;">Awaiting pickup</p>
                        </div>
                    <?php elseif ($item['status'] === 'collected'): ?>
                        <div class="glass" style="padding: 1.5rem; text-align: center; border-color: var(--primary);">
                            <i class="fas fa-check-circle" style="font-size: 1.5rem; color: var(--primary);"></i>
                            <p style="margin-top: 0.5rem; color: var(--primary); font-weight: 600;">Donated Successfully!</p>
                            <?php if ($claim): ?>
                                <p style="color: var(--text-muted); font-size: 0.85rem;">Collected by <?php echo htmlspecialchars($claim['receiver_name']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($item['status'] === 'expired'): ?>
                        <div class="glass" style="padding: 1.5rem; text-align: center;">
                            <i class="fas fa-clock" style="font-size: 1.5rem; color: #ef4444;"></i>
                            <p style="margin-top: 0.5rem; color: #ef4444; font-weight: 600;">This item has expired</p>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: var(--text-muted); font-size: 0.9rem;">
                            Only receiver accounts can claim food items.
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn btn-primary" style="width: 100%; padding: 1.2rem; display: block; text-align: center;"><i class="fas fa-sign-in-alt"></i> Login to Claim</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <div style="margin-top: 4rem;">
        <h2 style="margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-star" style="color: var(--secondary);"></i> Reviews & Feedback
        </h2>

        <!-- Review Form (shown only if eligible) -->
        <?php if ($canReview): ?>
            <div class="glass" style="padding: 2rem; border-radius: 20px; margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem;">Leave a Review</h3>
                <div id="reviewForm">
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem;" id="starRating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span onclick="setRating(<?php echo $i; ?>)" class="star-btn" data-star="<?php echo $i; ?>" style="font-size: 2rem; cursor: pointer; color: var(--text-muted); transition: var(--transition);">
                                <i class="far fa-star"></i>
                            </span>
                        <?php endfor; ?>
                    </div>
                    <div class="form-group">
                        <textarea id="reviewComment" class="form-control" rows="3" placeholder="Share your experience..." style="resize: vertical;"></textarea>
                    </div>
                    <button onclick="submitReview(<?php echo $item['id']; ?>, <?php echo $reviewTarget; ?>)" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Review
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Existing Reviews -->
        <?php if (count($reviews) > 0): ?>
            <div style="display: grid; gap: 1.5rem;">
                <?php foreach ($reviews as $review): ?>
                    <div class="glass" style="padding: 1.5rem; display: flex; gap: 1.5rem; align-items: flex-start;">
                        <div style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-user" style="color: var(--primary);"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <strong><?php echo htmlspecialchars($review['reviewer_name']); ?></strong>
                                <span style="color: var(--text-muted); font-size: 0.8rem;"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></span>
                            </div>
                            <div style="margin-bottom: 0.5rem;">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <i class="<?php echo $s <= $review['rating'] ? 'fas' : 'far'; ?> fa-star" style="color: var(--secondary); font-size: 0.9rem;"></i>
                                <?php endfor; ?>
                            </div>
                            <?php if ($review['comment']): ?>
                                <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="glass" style="padding: 3rem; text-align: center; color: var(--text-muted);">
                <i class="far fa-comment-dots" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                <p>No reviews yet. Reviews can be submitted after food has been collected.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
let selectedRating = 0;

function setRating(stars) {
    selectedRating = stars;
    document.querySelectorAll('.star-btn').forEach((btn, index) => {
        const icon = btn.querySelector('i');
        if (index < stars) {
            icon.className = 'fas fa-star';
            btn.style.color = 'var(--secondary)';
        } else {
            icon.className = 'far fa-star';
            btn.style.color = 'var(--text-muted)';
        }
    });
}

async function submitReview(foodId, revieweeId) {
    if (selectedRating === 0) {
        showToast('Please select a rating (1-5 stars)', 'error');
        return;
    }
    const comment = document.getElementById('reviewComment').value;
    
    const result = await apiCall('../api/submit_review.php', {
        food_id: foodId,
        reviewee_id: revieweeId,
        rating: selectedRating,
        comment: comment
    });
    
    if (result.success) {
        showToast('Review submitted! Thank you.');
        setTimeout(() => location.reload(), 1500);
    } else {
        showToast(result.error || 'Failed to submit review.', 'error');
    }
}

async function claimFood(id) {
    const confirmed = await showModal('Claim Food', 'Are you sure you want to claim this item?', true);
    if (!confirmed) return;
    
    const result = await apiCall('../api/claim_food.php', { food_id: id });
    if (result.success) {
        showToast('Item claimed successfully!');
        setTimeout(() => location.reload(), 1500);
    } else {
        showToast(result.error || 'Failed to claim item.', 'error');
    }
}
</script>

<?php include '../includes/footer.php'; ?>
