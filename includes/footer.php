    <footer style="margin-top: 5rem; padding: 5rem 5% 3rem; background: rgba(15, 23, 42, 0.5); border-top: 1px solid var(--glass-border);">
        <div style="max-width: 1400px; margin: 0 auto;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 4rem; margin-bottom: 4rem;">
                <!-- Brand Section -->
                <div>
                    <a href="<?php echo BASE_URL; ?>/index.php" class="logo" style="font-size: 1.5rem; display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
                        <i class="fas fa-leaf" style="color: var(--primary);"></i> FoodShare
                    </a>
                    <p style="color: var(--text-muted); line-height: 1.8; font-size: 0.95rem;">
                        Connecting surplus food from restaurants and homes to those who need it most. 
                        Join our mission to reduce waste and nourish communities.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 style="color: var(--text-main); margin-bottom: 1.5rem; font-size: 1.1rem;">Quick Links</h4>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.75rem;">
                        <li><a href="<?php echo BASE_URL; ?>/index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: var(--transition);" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Browse Food</a></li>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="<?php echo BASE_URL; ?>/auth/profile.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: var(--transition);" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">My Profile</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo BASE_URL; ?>/auth/login.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: var(--transition);" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Login</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 style="color: var(--text-main); margin-bottom: 1.5rem; font-size: 1.1rem;">Contact Us</h4>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 1rem;">
                        <li style="display: flex; align-items: center; gap: 0.75rem; color: var(--text-muted); font-size: 0.9rem;">
                            <i class="fas fa-envelope" style="color: var(--primary);"></i> support@foodshare.com
                        </li>
                        <li style="display: flex; align-items: center; gap: 0.75rem; color: var(--text-muted); font-size: 0.9rem;">
                            <i class="fas fa-phone" style="color: var(--primary);"></i> +123 456 7890
                        </li>
                        <li style="display: flex; align-items: center; gap: 0.75rem; color: var(--text-muted); font-size: 0.9rem;">
                            <i class="fas fa-location-dot" style="color: var(--primary);"></i> 123 Community Lane, Zero Waste City
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div style="border-top: 1px solid var(--glass-border); padding-top: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <p style="color: var(--text-muted); font-size: 0.85rem;">
                    &copy; <?php echo date('Y'); ?> FoodShare Platform. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
