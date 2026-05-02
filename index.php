<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

updateExpiredListings($pdo);

$search = $_GET['search'] ?? '';
$location = $_GET['location'] ?? '';
$food_type = $_GET['food_type'] ?? '';
$expiring_soon = isset($_GET['expiring_soon']);

$query = "SELECT fp.*, u.name as donor_name, fp.donor_id 
          FROM food_posts fp 
          JOIN users u ON fp.donor_id = u.id 
          WHERE fp.status = 'available' AND fp.expiry_time > NOW() AND fp.is_deleted = 0";

$params = [];
if ($search) {
    $query .= " AND (fp.title LIKE ? OR fp.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($location) {
    $query .= " AND fp.location LIKE ?";
    $params[] = "%$location%";
}
if ($food_type) {
    $query .= " AND fp.food_type = ?";
    $params[] = $food_type;
}
if ($expiring_soon) {
    $query .= " AND fp.expiry_time < DATE_ADD(NOW(), INTERVAL 4 HOUR)";
}

// Location-based prioritization: exact matches first, then partial matches, then the rest
if ($location) {
    $query .= " ORDER BY 
        CASE 
            WHEN fp.location = ? THEN 0
            WHEN fp.location LIKE ? THEN 1
            ELSE 2 
        END, fp.created_at DESC";
    $params[] = $location;
    $params[] = "%$location%";
} else {
    $query .= " ORDER BY fp.created_at DESC";
}
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$listings = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<section class="hero">
    <h1>Share Food, <br><span style="color: var(--primary);">Spread Happiness</span></h1>
    <p>Connecting surplus food from restaurants and homes to those who need it most. <br>Join the movement to end food waste today.</p>
    
    <div class="glass" style="padding: 2.5rem; border-radius: 30px; max-width: 1100px; width: 100%; border: 1px solid rgba(255,255,255,0.15); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
        <form action="index.php" method="GET" style="display: flex; flex-direction: column; gap: 1.5rem; width: 100%;">
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem; width: 100%;">
                <div style="position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: var(--primary);"></i>
                    <input type="text" name="search" class="form-control" placeholder="What are you looking for?" style="padding-left: 3rem; background: rgba(255,255,255,0.03); border-radius: 16px;" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div style="position: relative;">
                    <i class="fas fa-location-dot" style="position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: #ef4444;"></i>
                    <input type="text" name="location" class="form-control" placeholder="Location" style="padding-left: 3rem; background: rgba(255,255,255,0.03); border-radius: 16px;" value="<?php echo htmlspecialchars($location); ?>">
                </div>
                <div>
                    <select name="food_type" class="form-control" style="background: rgba(15, 23, 42, 0.95); border-radius: 16px; cursor: pointer;">
                        <option value="">All Categories</option>
                        <option value="cooked_meal" <?php echo $food_type === 'cooked_meal' ? 'selected' : ''; ?>>Cooked Meals</option>
                        <option value="raw_ingredients" <?php echo $food_type === 'raw_ingredients' ? 'selected' : ''; ?>>Groceries</option>
                        <option value="bakery" <?php echo $food_type === 'bakery' ? 'selected' : ''; ?>>Bakery</option>
                        <option value="dairy" <?php echo $food_type === 'dairy' ? 'selected' : ''; ?>>Dairy</option>
                        <option value="fruits_vegetables" <?php echo $food_type === 'fruits_vegetables' ? 'selected' : ''; ?>>Fresh Produce</option>
                        <option value="beverages" <?php echo $food_type === 'beverages' ? 'selected' : ''; ?>>Beverages</option>
                        <option value="other" <?php echo $food_type === 'other' ? 'selected' : ''; ?>>Miscellaneous</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 0.5rem; border-top: 1px solid rgba(255,255,255,0.05);">
                <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; color: var(--text-muted); font-size: 0.95rem; font-weight: 500;">
                    <div style="position: relative; width: 1.2rem; height: 1.2rem; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                        <input type="checkbox" name="expiring_soon" <?php echo $expiring_soon ? 'checked' : ''; ?> style="position: absolute; opacity: 0; cursor: pointer; width: 100%; height: 100%; z-index: 2;">
                        <i class="fas fa-check" style="font-size: 0.7rem; color: var(--primary); display: <?php echo $expiring_soon ? 'block' : 'none'; ?>;"></i>
                    </div>
                    Urgent Pickup (Expiring Soon)
                </label>
                <button type="submit" class="btn btn-primary" style="padding: 0.85rem 3rem; border-radius: 16px; font-size: 1rem; box-shadow: 0 10px 20px -10px var(--primary);">
                    <i class="fas fa-search" style="margin-right: 0.5rem;"></i> Find Food
                </button>
            </div>
        </form>
    </div>
</section>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2 style="font-size: 1.75rem;">Available Surplus</h2>
        <span style="color: var(--text-muted);"><?php echo count($listings); ?> items found</span>
    </div>

    <?php if (empty($listings)): ?>
        <div class="glass card" style="padding: 4rem; text-align: center;">
            <i class="fas fa-box-open" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1.5rem;"></i>
            <p style="font-size: 1.2rem; color: var(--text-muted);">No surplus food available at the moment. Check back later!</p>
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($listings as $item): ?>
                <div class="glass card" onclick="window.location.href='listings/detail.php?id=<?php echo $item['id']; ?>'" style="cursor: pointer;">
                    <div style="position: relative;">
                        <img src="<?php echo htmlspecialchars($item['image_path']); ?>" class="card-img" alt="Food Image">
                        <div style="position: absolute; top: 1rem; right: 1rem; display: flex; flex-direction: column; gap: 0.5rem; align-items: flex-end;">
                            <span class="urgent-badge" style="display: none;">Urgent</span>
                            <span class="countdown" data-expiry="<?php echo $item['expiry_time']; ?>">--h --m --s</span>
                        </div>
                    </div>
                    <div class="card-content">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <div>
                                <h3 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                                <span style="font-size: 0.8rem; color: var(--primary);"><i class="fas fa-tag"></i> <?php echo getFoodTypeLabel($item['food_type']); ?></span>
                            </div>
                            <?php echo getStatusBadge($item['status']); ?>
                        </div>
                        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem; height: 3.2rem; overflow: hidden;">
                            <?php echo htmlspecialchars(substr($item['description'], 0, 80)) . (strlen($item['description']) > 80 ? '...' : ''); ?>
                        </p>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1.5rem;">
                            <span style="font-size: 0.85rem; color: var(--secondary);"><i class="fas fa-shopping-basket"></i> Qty: <?php echo htmlspecialchars($item['quantity']); ?></span>
                            <span style="font-size: 0.85rem;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['location']); ?></span>
                            <span class="donor-info-badge" style="font-size: 0.85rem; color: var(--text-muted);"><i class="fas fa-user"></i> <?php echo htmlspecialchars($item['donor_name']); ?> <?php echo getTrustBadge($pdo, $item['donor_id']); ?></span>
                        </div>
                        
                        <?php if (isLoggedIn()): ?>
                            <?php if (getRole() === 'receiver'): ?>
                                <button onclick="event.stopPropagation(); claimFood(<?php echo $item['id']; ?>)" class="btn btn-primary" style="width: 100%;">Claim Item</button>
                            <?php else: ?>
                                <p style="font-size: 0.8rem; text-align: center; color: var(--text-muted);">Only Receivers can claim food</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="auth/login.php" onclick="event.stopPropagation();" class="btn btn-outline" style="width: 100%;">Login to Claim</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Fetch recently collected/delivered food
$collectedStmt = $pdo->query("SELECT fp.*, fp.donor_id, u.name as donor_name, ru.name as receiver_name 
    FROM food_posts fp 
    JOIN users u ON fp.donor_id = u.id 
    LEFT JOIN claims c ON fp.id = c.food_id AND c.status = 'collected'
    LEFT JOIN users ru ON c.receiver_id = ru.id
    WHERE fp.status = 'collected' AND fp.is_deleted = 0
    ORDER BY fp.created_at DESC LIMIT 6");
$collected = $collectedStmt->fetchAll();

// Fetch recent reviews
$reviewStmt = $pdo->query("SELECT r.*, u.name as reviewer_name, fp.title as food_title, ru.name as reviewee_name 
    FROM reviews r 
    JOIN users u ON r.reviewer_id = u.id 
    JOIN users ru ON r.reviewee_id = ru.id
    JOIN food_posts fp ON r.food_id = fp.id 
    ORDER BY r.created_at DESC LIMIT 6");
$recentReviews = $reviewStmt->fetchAll();

// Fetch platform reviews for homepage
$platformRevStmt = $pdo->query("SELECT pr.*, u.name as reviewer_name, u.role as reviewer_role 
    FROM platform_reviews pr 
    JOIN users u ON pr.user_id = u.id 
    ORDER BY pr.created_at DESC LIMIT 6");
$platformReviews = $platformRevStmt->fetchAll();

?>

<!-- Successfully Delivered Section -->
<?php if (!empty($collected)): ?>
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2 style="font-size: 1.75rem;"><i class="fas fa-check-circle" style="color: var(--primary);"></i> Successfully Donated</h2>
    </div>
    <div class="grid">
        <?php foreach ($collected as $item): ?>
            <div class="glass card" onclick="window.location.href='listings/detail.php?id=<?php echo $item['id']; ?>'" style="cursor: pointer;">
                <div style="position: relative;">
                    <img src="<?php echo htmlspecialchars($item['image_path']); ?>" class="card-img" alt="Food Image" style="filter: brightness(0.85);">
                    <div style="position: absolute; top: 1rem; right: 1rem;">
                        <span class="badge bg-info"><i class="fas fa-check"></i> Delivered</span>
                    </div>
                </div>
                <div class="card-content">
                    <h3 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                    <div style="display: flex; flex-direction: column; gap: 0.4rem; margin-top: 0.75rem; font-size: 0.85rem;">
                        <span style="color: var(--primary);"><i class="fas fa-tag"></i> <?php echo getFoodTypeLabel($item['food_type']); ?></span>
                        <span class="donor-info-badge" style="color: var(--text-muted);"><i class="fas fa-user"></i> <?php echo htmlspecialchars($item['donor_name']); ?> <?php echo getTrustBadge($pdo, $item['donor_id']); ?></span>
                        <?php if ($item['receiver_name']): ?>
                            <span style="color: var(--secondary);"><i class="fas fa-hand-holding-heart"></i> Received by: <?php echo htmlspecialchars($item['receiver_name']); ?></span>
                        <?php endif; ?>
                        <span style="color: var(--text-muted);"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['location']); ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Community Reviews Section -->
<div class="container" style="margin-top: 3rem; padding-bottom: 4rem;">
    <div style="text-align: center; margin-bottom: 3rem;">
        <h2 style="font-size: 2rem; margin-bottom: 0.75rem;"><i class="fas fa-star" style="color: var(--secondary);"></i> Community Reviews</h2>
        <p style="color: var(--text-muted); max-width: 600px; margin: 0 auto;">See what our community members are saying about their food sharing experiences.</p>
    </div>

    <?php if (!empty($recentReviews)): ?>
        <div class="grid">
            <?php foreach ($recentReviews as $review): ?>
                <div class="glass" style="padding: 2rem; border-radius: 20px;">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div class="avatar-sm" style="width: 48px; height: 48px; font-size: 1.1rem;">
                            <?php echo getInitial($review['reviewer_name']); ?>
                        </div>
                        <div>
                            <strong><?php echo htmlspecialchars($review['reviewer_name']); ?></strong>
                            <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></div>
                        </div>
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <i class="<?php echo $s <= $review['rating'] ? 'fas' : 'far'; ?> fa-star" style="color: var(--secondary); font-size: 0.9rem;"></i>
                        <?php endfor; ?>
                    </div>
                    <?php if ($review['comment']): ?>
                        <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1rem;">"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                    <?php endif; ?>
                    <div style="font-size: 0.8rem; color: var(--text-muted); border-top: 1px solid var(--glass-border); padding-top: 0.75rem;">
                        <i class="fas fa-utensils" style="color: var(--primary);"></i> <?php echo htmlspecialchars($review['food_title']); ?> · 
                        Reviewed <strong><?php echo htmlspecialchars($review['reviewee_name']); ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="glass" style="padding: 4rem; text-align: center; border-radius: 20px;">
            <i class="far fa-comment-dots" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem; display: block;"></i>
            <p style="color: var(--text-muted); font-size: 1.1rem;">No reviews yet. Be the first to donate and get reviewed!</p>
        </div>
    <?php endif; ?>
</div>

<!-- Platform Reviews Section -->
<?php if (!empty($platformReviews)): ?>
<div class="container" style="padding-bottom: 4rem;">
    <div style="text-align: center; margin-bottom: 3rem;">
        <h2 style="font-size: 2rem; margin-bottom: 0.75rem;"><i class="fas fa-heart" style="color: #ef4444;"></i> What Our Users Say</h2>
        <p style="color: var(--text-muted); max-width: 600px; margin: 0 auto;">Platform feedback from our community of donors and receivers.</p>
    </div>
    <div class="grid">
        <?php foreach ($platformReviews as $pr): ?>
            <div class="glass" style="padding: 2rem; border-radius: 20px;">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                    <div class="avatar-sm" style="width: 48px; height: 48px; font-size: 1.1rem;">
                        <?php echo getInitial($pr['reviewer_name']); ?>
                    </div>
                    <div>
                        <strong><?php echo htmlspecialchars($pr['reviewer_name']); ?></strong>
                        <div style="font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.5rem;">
                            <span class="badge" style="background: rgba(255,255,255,0.1); font-size: 0.65rem; padding: 0.1rem 0.4rem;"><?php echo ucfirst($pr['reviewer_role']); ?></span>
                            <?php echo date('M j, Y', strtotime($pr['created_at'])); ?>
                        </div>
                    </div>
                </div>
                <div style="margin-bottom: 0.75rem;">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                        <i class="<?php echo $s <= $pr['rating'] ? 'fas' : 'far'; ?> fa-star" style="color: var(--secondary); font-size: 0.9rem;"></i>
                    <?php endfor; ?>
                </div>
                <?php if ($pr['comment']): ?>
                    <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6;">"<?php echo htmlspecialchars($pr['comment']); ?>"</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
async function claimFood(id) {
    const confirmed = await showModal('Claim Food', 'Are you sure you want to claim this item?', true);
    if (!confirmed) return;
    
    const result = await apiCall('api/claim_food.php', { food_id: id });
    if (result.success) {
        showToast('Item claimed successfully! Check your dashboard.');
        setTimeout(() => location.reload(), 1500);
    } else {
        showToast(result.error || 'Failed to claim item.', 'error');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
