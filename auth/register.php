<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (isLoggedIn()) redirect('../index.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $password, $role]);
        redirect('login.php?registered=1');
    } catch (PDOException $e) {
        $error = "Email already exists or registration failed.";
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container" style="max-width: 500px; margin-top: 4rem;">
    <div class="glass card" style="padding: 2.5rem;">
        <h2 style="margin-bottom: 2rem; text-align: center;">Join FoodShare</h2>
        
        <?php if ($error): ?>
            <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" placeholder="John Doe" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" placeholder="e.g. +1234567890">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div style="position: relative;">
                    <input type="password" id="reg_pass" name="password" class="form-control" placeholder="••••••••" required style="padding-right: 3rem;">
                    <i class="fas fa-eye" id="regIcon" 
                       onclick="togglePassword('reg_pass', 'regIcon')" 
                       style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); transition: var(--transition);"
                       onmouseover="this.style.color='var(--primary)'" 
                       onmouseout="this.style.color='var(--text-muted)'"></i>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">I want to...</label>
                <select name="role" class="form-control" style="background: rgba(15, 23, 42, 0.9);">
                    <option value="receiver">Receive Food</option>
                    <option value="donor">Donate Surplus Food</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Create Account</button>
        </form>
        
        <p style="margin-top: 2rem; text-align: center; color: var(--text-muted);">
            Already have an account? <a href="login.php" style="color: var(--primary);">Login here</a>
        </p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
