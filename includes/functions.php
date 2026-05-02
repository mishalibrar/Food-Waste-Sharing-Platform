<?php
session_start();

// Define Base URL for links and assets
$is_localhost = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1');
define('BASE_URL', $is_localhost ? '/Foodwastesharingplatform' : '');

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function getRole() {
    return $_SESSION['role'] ?? null;
}

function checkRole($role) {
    if (!isLoggedIn() || getRole() !== $role) {
        redirect('../auth/login.php');
    }
}

function formatDate($date) {
    return date('M j, Y, g:i a', strtotime($date));
}

function getStatusBadge($status) {
    $colors = [
        'available' => '#10b981',
        'reserved' => '#f59e0b',
        'collected' => '#3b82f6',
        'expired' => '#ef4444',
        'pending' => '#f59e0b',
        'cancelled' => '#ef4444'
    ];
    $color = $colors[$status] ?? '#64748b';
    return "<span class='badge' style='background: " . $color . "22; color: " . $color . ";'>" . ucfirst($status) . "</span>";
}

function getInitial($name) {
    if (empty($name)) return '?';
    return strtoupper(substr($name, 0, 1));
}

function getFoodTypeLabel($type) {
    $types = [
        'cooked_meal' => 'Cooked Meal',
        'raw_ingredients' => 'Raw Ingredients',
        'bakery' => 'Bakery & Sweets',
        'dairy' => 'Dairy Products',
        'fruits_vegetables' => 'Fruits & Veggies',
        'beverages' => 'Beverages',
        'other' => 'Other Food'
    ];
    return $types[$type] ?? 'Unknown';
}

function updateExpiredListings($pdo) {
    $stmt = $pdo->prepare("UPDATE food_posts SET status = 'expired' WHERE expiry_time < NOW() AND status = 'available' AND is_deleted = 0");
    $stmt->execute();
}

function getTrustBadge($pdo, $donor_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as collected FROM food_posts WHERE donor_id = ? AND status = 'collected' AND is_deleted = 0");
    $stmt->execute([$donor_id]);
    $count = $stmt->fetch()['collected'];

    if ($count >= 50) return '<span class="trust-badge trust-platinum">🏆 Platinum</span>';
    if ($count >= 20) return '<span class="trust-badge trust-gold">🥇 Gold</span>';
    if ($count >= 10) return '<span class="trust-badge trust-silver">🥈 Silver</span>';
    if ($count >= 3) return '<span class="trust-badge trust-bronze">🥉 Bronze</span>';
    return '';
}

function getTrustBadgeFromCount($count) {
    if ($count >= 50) return '<span class="trust-badge trust-platinum">🏆 Platinum</span>';
    if ($count >= 20) return '<span class="trust-badge trust-gold">🥇 Gold</span>';
    if ($count >= 10) return '<span class="trust-badge trust-silver">🥈 Silver</span>';
    if ($count >= 3) return '<span class="trust-badge trust-bronze">🥉 Bronze</span>';
    return '';
}

function logActivity($pdo, $food_id, $user_id, $action, $details = '') {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_log (food_id, user_id, action, details) VALUES (?, ?, ?, ?)");
        $stmt->execute([$food_id, $user_id, $action, $details]);
    } catch (PDOException $e) {
        // Silently fail - activity logging shouldn't break main flow
    }
}
?>
