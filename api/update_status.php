<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || getRole() !== 'donor') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $food_id = $_POST['id'];
    $user_id = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        // Update food post
        $stmt = $pdo->prepare("UPDATE food_posts SET status = 'collected' WHERE id = ? AND donor_id = ?");
        $stmt->execute([$food_id, $user_id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Listing not found or already updated.');
        }

        // Update associated claims
        $stmt = $pdo->prepare("UPDATE claims SET status = 'collected', collected_at = NOW() WHERE food_id = ? AND status = 'pending'");
        $stmt->execute([$food_id]);

        // Log activity
        logActivity($pdo, $food_id, $user_id, 'collected', 'Donor marked as collected');

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
