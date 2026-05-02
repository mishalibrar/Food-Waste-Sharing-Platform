USE food_waste_sharing;

-- Add food_type column to food_posts
ALTER TABLE food_posts 
ADD COLUMN food_type ENUM('cooked_meal', 'raw_ingredients', 'bakery', 'dairy', 'fruits_vegetables', 'beverages', 'other') 
DEFAULT 'other' AFTER description;

-- Update existing claims status to match new workflow requirements if necessary
-- ALTER TABLE claims MODIFY COLUMN status ENUM('pending', 'collected', 'cancelled') DEFAULT 'pending';
