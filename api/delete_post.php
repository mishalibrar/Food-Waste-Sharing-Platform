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
        if ($role === 'admin') {
            $stmt = $pdo->prepare("DELETE FROM food_posts WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM food_posts WHERE id = ? AND donor_id = ?");
            $stmt->execute([$id, $user_id]);
        }

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Could not delete listing.');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
