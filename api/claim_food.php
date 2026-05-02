<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || getRole() !== 'receiver') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $food_id = $_POST['food_id'];
    $user_id = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        // Check if available
        $stmt = $pdo->prepare("SELECT status FROM food_posts WHERE id = ? FOR UPDATE");
        $stmt->execute([$food_id]);
        $post = $stmt->fetch();

        if (!$post || $post['status'] !== 'available') {
            throw new Exception('Item is no longer available.');
        }

        // Check if already claimed by this user
        $stmt = $pdo->prepare("SELECT id FROM claims WHERE food_id = ? AND receiver_id = ? AND status != 'cancelled'");
        $stmt->execute([$food_id, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception('You have already claimed this item.');
        }

        // Update status to reserved
        $stmt = $pdo->prepare("UPDATE food_posts SET status = 'reserved' WHERE id = ?");
        $stmt->execute([$food_id]);

        // Insert claim record
        $stmt = $pdo->prepare("INSERT INTO claims (food_id, receiver_id) VALUES (?, ?)");
        $stmt->execute([$food_id, $user_id]);
        
        // Log activity
        logActivity($pdo, $food_id, $user_id, 'claimed', 'Receiver claimed the item');

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
