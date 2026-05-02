<?php
// Detect environment
$is_cli = (php_sapi_name() === 'cli');
$is_localhost = $is_cli || (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1'));

if ($is_localhost) {
    // Local Settings (XAMPP)
    $host = 'localhost';
    $db = 'food_waste_sharing';
    $user = 'root';
    $pass = '';
} else {
    // Production Settings (InfinityFree)
    // IMPORTANT: Replace these with your actual InfinityFree MySQL details from the Control Panel
    $host = 'sql112.infinityfree.com'; // Change this
    $db = 'if0_41813744_foodwastesharing'; // Change this
    $user = 'if0_41813744'; // Change this
    $pass = 'Cosc231103104'; // Change this
}

$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    if ($is_localhost) {
        throw new \PDOException($e->getMessage(), (int) $e->getCode());
    } else {
        die("Database Connection Error. Please check your production credentials.");
    }
}
?>