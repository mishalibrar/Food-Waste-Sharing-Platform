<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

checkRole('donor');

$id = $_GET['id'] ?? null;
if (!$id) redirect('../dashboard/donor.php');

// Fetch existing data
$stmt = $pdo->prepare("SELECT * FROM food_posts WHERE id = ? AND donor_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$post = $stmt->fetch();

if (!$post) redirect('../dashboard/donor.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $food_type = $_POST['food_type'];
    $quantity = $_POST['quantity'];
    $expiry_time = $_POST['expiry_time'];
    $location = $_POST['location'];
    
    $image_path = $post['image_path'];
    
    // Check if a new image URL was provided
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
        $stmt = $pdo->prepare("UPDATE food_posts SET title = ?, description = ?, food_type = ?, quantity = ?, expiry_time = ?, location = ?, image_path = ? WHERE id = ? AND donor_id = ?");
        $stmt->execute([$title, $description, $food_type, $quantity, $expiry_time, $location, $image_path, $id, $_SESSION['user_id']]);
        redirect('../dashboard/donor.php');
    } catch (PDOException $e) {
        $error = "Failed to update listing: " . $e->getMessage();
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container" style="max-width: 800px; margin-top: 2rem;">
    <div class="glass card" style="padding: 2.5rem;">
        <h2 style="margin-bottom: 2rem;"><i class="fas fa-edit" style="color: var(--primary);"></i> Edit Surplus Food Listing</h2>
        
        <?php if ($error): ?>
            <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Food Title</label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($post['title']); ?>" required>
                </div>
                
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($post['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Food Type</label>
                    <select name="food_type" class="form-control" style="background: rgba(15, 23, 42, 0.9);">
                        <?php
                        $types = ['cooked_meal', 'raw_ingredients', 'bakery', 'dairy', 'fruits_vegetables', 'beverages', 'other'];
                        foreach ($types as $type) {
                            $selected = ($post['food_type'] === $type) ? 'selected' : '';
                            echo "<option value='$type' $selected>" . getFoodTypeLabel($type) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Quantity</label>
                    <input type="text" name="quantity" class="form-control" value="<?php echo htmlspecialchars($post['quantity']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Expiry Time</label>
                    <input type="datetime-local" name="expiry_time" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($post['expiry_time'])); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($post['location']); ?>" required>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Update Food Image (Optional)</label>
                    <div style="display: flex; gap: 1.5rem; align-items: start; margin-bottom: 1rem;">
                        <img src="../<?php echo htmlspecialchars($post['image_path']); ?>" style="width: 100px; height: 100px; border-radius: 12px; object-fit: cover; border: 1px solid var(--glass-border);">
                        <div style="flex: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 0.5rem;">Upload New File</span>
                                <input type="file" name="image" class="form-control" accept="image/*">
                            </div>
                            <div>
                                <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 0.5rem;">Or Paste New Image URL</span>
                                <input type="url" name="image_url" class="form-control" placeholder="https://example.com/image.jpg">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Save Changes</button>
                <a href="../dashboard/donor.php" class="btn btn-outline" style="flex: 1;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
