<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$food_id = $_POST['food_id'] ?? null;
$reviewee_id = $_POST['reviewee_id'] ?? null;
$rating = intval($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if (!$food_id || !$reviewee_id || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Invalid input. Rating must be 1-5.']);
    exit;
}

$reviewer_id = $_SESSION['user_id'];

// Prevent reviewing yourself
if ($reviewer_id == $reviewee_id) {
    echo json_encode(['success' => false, 'error' => 'Cannot review yourself.']);
    exit;
}

// Check if already reviewed this food item
$check = $pdo->prepare("SELECT id FROM reviews WHERE food_id = ? AND reviewer_id = ?");
$check->execute([$food_id, $reviewer_id]);
if ($check->fetch()) {
    echo json_encode(['success' => false, 'error' => 'You have already reviewed this transaction.']);
    exit;
}

// Verify the food item is collected (review only after completion)
$foodCheck = $pdo->prepare("SELECT status FROM food_posts WHERE id = ?");
$foodCheck->execute([$food_id]);
$food = $foodCheck->fetch();
if (!$food || $food['status'] !== 'collected') {
    echo json_encode(['success' => false, 'error' => 'Reviews are only allowed for collected items.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO reviews (food_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$food_id, $reviewer_id, $reviewee_id, $rating, $comment]);

    // Log activity
    $log = $pdo->prepare("INSERT INTO activity_log (food_id, user_id, action, details) VALUES (?, ?, 'reviewed', ?)");
    $log->execute([$food_id, $reviewer_id, "Rated $rating/5"]);

    // Recalculate and cache the reviewee's average rating
    $avgStmt = $pdo->prepare("SELECT ROUND(AVG(rating), 2) as avg_rating FROM reviews WHERE reviewee_id = ?");
    $avgStmt->execute([$reviewee_id]);
    $newAvg = $avgStmt->fetch()['avg_rating'] ?? 0;

    $updateAvg = $pdo->prepare("UPDATE users SET rating_avg = ? WHERE id = ?");
    $updateAvg->execute([$newAvg, $reviewee_id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
?>
