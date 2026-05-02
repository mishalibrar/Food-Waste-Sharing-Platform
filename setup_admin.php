<?php
require_once 'includes/db.php';

$name = "Mishal Ibrar";
$email = "mishalibrar12@gmail.com";
$password = "12345678";
$role = "admin";

$hashed_password = password_hash($password, PASSWORD_BCRYPT);

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Update existing user to Admin
        $stmt = $pdo->prepare("UPDATE users SET name = ?, password = ?, role = ? WHERE email = ?");
        $stmt->execute([$name, $hashed_password, $role, $email]);
        echo "<h3>✅ Admin Account Updated!</h3>";
    } else {
        // Create new Admin user
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashed_password, $role]);
        echo "<h3>✅ Admin Account Created Successfully!</h3>";
    }
    
    echo "<p>Name: $name</p>";
    echo "<p>Email: $email</p>";
    echo "<p>Password: $password</p>";
    echo "<p><a href='auth/login.php'>Click here to Login</a></p>";

} catch (PDOException $e) {
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
}
?>
