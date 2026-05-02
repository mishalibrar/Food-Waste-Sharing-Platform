<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

checkRole('donor');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $quantity = $_POST['quantity'];
    $expiry_time = $_POST['expiry_time'];
    $location = $_POST['location'];
    
    // Image handling
    $image_path = 'assets/images/default-food.jpg';
    
    // Check if an image URL was provided
    if (!empty($_POST['image_url'])) {
        $image_path = $_POST['image_url'];
    }

    // File upload takes priority if provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $target = '../assets/images/' . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_path = 'assets/images/' . $filename;
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO food_posts (donor_id, title, description, food_type, quantity, expiry_time, location, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $title, $description, $_POST['food_type'], $quantity, $expiry_time, $location, $image_path]);
        
        $food_id = $pdo->lastInsertId();
        logActivity($pdo, $food_id, $_SESSION['user_id'], 'posted', 'New donation listing created');

        redirect('../dashboard/donor.php');
    } catch (PDOException $e) {
        $error = "Failed to create listing: " . $e->getMessage();
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container" style="max-width: 800px; margin-top: 2rem;">
    <div class="glass card" style="padding: 2.5rem;">
        <h2 style="margin-bottom: 2rem;"><i class="fas fa-plus-circle" style="color: var(--primary);"></i> Post New Surplus Food</h2>
        
        <?php if ($error): ?>
            <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Food Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g., Freshly Baked Bread" required>
                </div>
                
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Tell us more about the food..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Food Type</label>
                    <select name="food_type" class="form-control" style="background: rgba(15, 23, 42, 0.9);">
                        <option value="cooked_meal">Cooked Meal</option>
                        <option value="raw_ingredients">Raw Ingredients</option>
                        <option value="bakery">Bakery & Sweets</option>
                        <option value="dairy">Dairy Products</option>
                        <option value="fruits_vegetables">Fruits & Veggies</option>
                        <option value="beverages">Beverages</option>
                        <option value="other">Other Food</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Quantity</label>
                    <input type="text" name="quantity" class="form-control" placeholder="e.g., 5 loaves, 2kg" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Expiry Time</label>
                    <input type="datetime-local" name="expiry_time" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Location (City/Area)</label>
                    <input type="text" name="location" class="form-control" placeholder="e.g., Downtown, Sector 5" required>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Food Image</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 0.5rem;">Upload File</span>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                        <div>
                            <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 0.5rem;">Or Paste Image URL</span>
                            <input type="url" name="image_url" class="form-control" placeholder="https://example.com/image.jpg">
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Post Listing</button>
                <a href="../dashboard/donor.php" class="btn btn-outline" style="flex: 1;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
