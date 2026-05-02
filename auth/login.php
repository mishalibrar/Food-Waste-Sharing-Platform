<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (isLoggedIn()) redirect('../index.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        
        if ($user['role'] === 'admin') {
            redirect('../dashboard/admin.php');
        } else {
            redirect('../index.php');
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container" style="max-width: 500px; margin-top: 4rem;">
    <div class="glass card" style="padding: 2.5rem;">
        <h2 style="margin-bottom: 2rem; text-align: center;">Welcome Back</h2>
        
        <?php if (isset($_GET['registered'])): ?>
            <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                Registration successful! Please login.
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div style="position: relative;">
                    <input type="password" id="login_pass" name="password" class="form-control" placeholder="••••••••" required style="padding-right: 3rem;">
                    <i class="fas fa-eye" id="loginIcon" 
                       onclick="togglePassword('login_pass', 'loginIcon')" 
                       style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); transition: var(--transition);"
                       onmouseover="this.style.color='var(--primary)'" 
                       onmouseout="this.style.color='var(--text-muted)'"></i>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Login</button>
        </form>
        
        <p style="margin-top: 2rem; text-align: center; color: var(--text-muted);">
            Don't have an account? <a href="register.php" style="color: var(--primary);">Join Now</a>
        </p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
