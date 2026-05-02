<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

echo "Starting data backfill...\n";

try {
    // 1. Backfill 'collected_at' in claims table for already collected items
    $stmt = $pdo->prepare("UPDATE claims c 
                          JOIN food_posts fp ON c.food_id = fp.id 
                          SET c.collected_at = fp.created_at 
                          WHERE fp.status = 'collected' AND c.collected_at IS NULL");
    $stmt->execute();
    echo "Updated " . $stmt->rowCount() . " claims with timestamps.\n";

    // 2. Backfill activity_log for 'collected' actions
    $stmt = $pdo->query("SELECT id, donor_id, created_at FROM food_posts WHERE status = 'collected'");
    $collectedItems = $stmt->fetchAll();
    
    $logsAdded = 0;
    foreach ($collectedItems as $item) {
        // Check if log already exists
        $check = $pdo->prepare("SELECT id FROM activity_log WHERE food_id = ? AND action = 'collected'");
        $check->execute([$item['id']]);
        if (!$check->fetch()) {
            $log = $pdo->prepare("INSERT INTO activity_log (food_id, user_id, action, details, created_at) VALUES (?, ?, 'collected', 'Retroactive log for past donation', ?)");
            $log->execute([$item['id'], $item['donor_id'], $item['created_at']]);
            $logsAdded++;
        }
    }
    echo "Added " . $logsAdded . " activity log entries for past donations.\n";

    echo "Backfill complete!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
