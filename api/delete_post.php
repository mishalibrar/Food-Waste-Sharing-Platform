<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $user_id = $_SESSION['user_id'];
    $role = getRole();

    try {
        // Soft delete: mark as is_deleted = 1 instead of permanently removing
        if ($role === 'admin') {
            $stmt = $pdo->prepare("UPDATE food_posts SET is_deleted = 1 WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            $stmt = $pdo->prepare("UPDATE food_posts SET is_deleted = 1 WHERE id = ? AND donor_id = ?");
            $stmt->execute([$id, $user_id]);
        }

        if ($stmt->rowCount() > 0) {
            // Log the soft-delete action
            $log = $pdo->prepare("INSERT INTO activity_log (food_id, user_id, action, details) VALUES (?, ?, 'cancelled', 'Post soft-deleted')");
            $log->execute([$id, $user_id]);

            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Could not delete listing.');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
