<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Utility function - can be called from other PHP files
function logActivity($pdo, $food_id, $user_id, $action, $details = '') {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_log (food_id, user_id, action, details) VALUES (?, ?, ?, ?)");
        $stmt->execute([$food_id, $user_id, $action, $details]);
    } catch (PDOException $e) {
        // Silently fail - activity logging shouldn't break main flow
    }
}
?>
