<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || getRole() !== 'receiver') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $claim_id = $_POST['id'];
    $user_id = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        // Get food_id from claim
        $stmt = $pdo->prepare("SELECT food_id FROM claims WHERE id = ? AND receiver_id = ? AND status = 'pending'");
        $stmt->execute([$claim_id, $user_id]);
        $claim = $stmt->fetch();

        if (!$claim) {
            throw new Exception('Claim not found or cannot be cancelled.');
        }

        $food_id = $claim['food_id'];

        // Update claim status
        $stmt = $pdo->prepare("UPDATE claims SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$claim_id]);

        // Revert food post status to available
        $stmt = $pdo->prepare("UPDATE food_posts SET status = 'available' WHERE id = ?");
        $stmt->execute([$food_id]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
