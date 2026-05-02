USE food_waste_sharing;

-- Add phone column to users table
ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL;

-- Platform reviews (general site feedback, not per-food-item)
CREATE TABLE IF NOT EXISTS platform_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
