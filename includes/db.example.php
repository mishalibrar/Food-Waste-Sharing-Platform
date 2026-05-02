<?php
// THIS IS A TEMPLATE FILE. 
// Rename this to db.php and fill in your actual credentials.
// Do NOT upload your real db.php to GitHub!

// Detect environment
$is_localhost = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1');

if ($is_localhost) {
    // Local Settings (XAMPP)
    $host = 'localhost';
    $db   = 'food_waste_sharing';
    $user = 'root';
    $pass = ''; 
} else {
    // Production Settings (InfinityFree)
    $host = 'sqlXXX.epizy.com'; 
    $db   = 'epiz_XXXXXXXX_food_waste'; 
    $user = 'epiz_XXXXXXXX'; 
    $pass = 'YOUR_PASSWORD_HERE'; 
}

$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     if ($is_localhost) {
         throw new \PDOException($e->getMessage(), (int)$e->getCode());
     } else {
         die("Database Connection Error. Please check your production credentials.");
     }
}
?>
