<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$rating = intval($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Rating must be between 1 and 5.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if user already left a platform review
$check = $pdo->prepare("SELECT id FROM platform_reviews WHERE user_id = ?");
$check->execute([$user_id]);
if ($check->fetch()) {
    // Update existing review
    $stmt = $pdo->prepare("UPDATE platform_reviews SET rating = ?, comment = ?, created_at = NOW() WHERE user_id = ?");
    $stmt->execute([$rating, $comment, $user_id]);
    echo json_encode(['success' => true, 'updated' => true]);
} else {
    // Insert new review
    $stmt = $pdo->prepare("INSERT INTO platform_reviews (user_id, rating, comment) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $rating, $comment]);
    echo json_encode(['success' => true, 'updated' => false]);
}
?>
