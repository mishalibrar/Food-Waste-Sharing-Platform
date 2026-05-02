<?php require_once 'functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodShare | Reduce Waste, Feed Community</title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/favicon.png">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav>
        <a href="<?php echo BASE_URL; ?>/index.php" class="logo">
            <i class="fas fa-leaf"></i> FoodShare
        </a>
        
        <div class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </div>

        <ul class="nav-links" id="navLinks">
            <li><a href="<?php echo BASE_URL; ?>/index.php"><i class="fas fa-search"></i> Browse</a></li>
            <?php if (isLoggedIn()): ?>
                <?php if (getRole() === 'donor'): ?>
                    <li><a href="<?php echo BASE_URL; ?>/listings/create.php"><i class="fas fa-plus-circle"></i> Post</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/dashboard/donor.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <?php elseif (getRole() === 'receiver'): ?>
                    <li><a href="<?php echo BASE_URL; ?>/dashboard/receiver.php"><i class="fas fa-hand-holding-heart"></i> Claims</a></li>
                <?php elseif (getRole() === 'admin'): ?>
                    <li><a href="<?php echo BASE_URL; ?>/dashboard/admin.php"><i class="fas fa-user-shield"></i> Admin</a></li>
                <?php endif; ?>
                <li class="user-dropdown" id="userDropdown" onclick="this.classList.toggle('open')">
                    <div class="user-chip">
                        <div class="avatar-sm"><?php echo getInitial($_SESSION['name']); ?></div>
                        <?php echo htmlspecialchars($_SESSION['name']); ?> <i class="fas fa-chevron-down" style="font-size: 0.6rem; margin-left: 0.25rem;"></i>
                    </div>
                    <div class="user-dropdown-menu">
                        <a href="<?php echo BASE_URL; ?>/auth/profile.php"><i class="fas fa-cog"></i> Profile Settings</a>
                        <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </li>
            <?php else: ?>
                <li><a href="<?php echo BASE_URL; ?>/auth/login.php">Login</a></li>
                <li><a href="<?php echo BASE_URL; ?>/auth/register.php" class="btn btn-primary" style="padding: 0.5rem 1.5rem;">Join Now</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Toast Container for Notifications -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Custom Modal Overlay -->
    <div id="modalOverlay" class="modal-overlay">
        <div class="modal-box glass">
            <h3 id="modalTitle" class="modal-title"></h3>
            <p id="modalMessage" class="modal-message"></p>
            <div class="modal-footer">
                <button id="modalCancel" class="btn btn-outline" style="font-size: 0.9rem;">Cancel</button>
                <button id="modalConfirm" class="btn btn-primary" style="font-size: 0.9rem;">Confirm</button>
            </div>
        </div>
    </div>
