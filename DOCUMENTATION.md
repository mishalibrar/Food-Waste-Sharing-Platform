# Food Waste Sharing Platform - Technical Documentation

## 1. Project Overview
The **Food Waste Sharing Platform** is a high-fidelity web application designed to reduce food waste by connecting donors (restaurants, households) with receivers (individuals, NGOs). The platform uses a modern "Glassmorphism" design aesthetic and real-time urgency features to ensure surplus food is collected efficiently.

---

## 2. Technology Stack
- **Backend**: PHP 8.x with PDO (Secure database interactions)
- **Frontend**: 
  - HTML5 & CSS3 (Vanilla CSS with Custom Properties/Variables)
  - JavaScript (ES6+, Fetch API for AJAX, Custom Toast & Modal systems)
  - FontAwesome 6 (Icons)
- **Database**: MySQL 8.x
- **Design System**: Glassmorphism (Backdrop blur, semi-transparent layers, vibrant gradients)

---

## 3. Project Structure
```
/api                # AJAX endpoints for dynamic platform interactions
/assets             # Static assets
  /css              # Core styles (style.css - 1000+ lines of premium design)
  /js               # Core logic (main.js - AJAX handlers, UI logic)
/auth               # Authentication pages (Login, Register, Profile)
/dashboard          # Role-specific analytics panels
/includes           # Reusable components (Header, Footer, DB Config, Functions)
/listings           # Food post management (Create, Edit, Detail)
index.php           # The main marketplace / landing page
database.sql        # Initial schema and seed data
```

---

## 4. Database Schema
### `users`
| Column | Type | Description |
| --- | --- | --- |
| id | INT (PK) | Unique user identifier |
| name | VARCHAR | Full name |
| email | VARCHAR | Unique email address |
| phone | VARCHAR | Optional contact number |
| password | VARCHAR | BCRYPT hashed password |
| role | ENUM | 'donor', 'receiver', 'admin' |
| created_at | TIMESTAMP | Join date |

### `food_posts`
| Column | Type | Description |
| --- | --- | --- |
| id | INT (PK) | Unique post identifier |
| donor_id | INT (FK) | Reference to `users.id` |
| title | VARCHAR | Title of the food item |
| description | TEXT | Detailed info |
| food_type | VARCHAR | Category (e.g., bakery, cooked_meal) |
| quantity | VARCHAR | Amount (e.g., "5 boxes") |
| expiry_time | DATETIME | When the food expires |
| location | VARCHAR | Pickup address |
| image_path | VARCHAR | Path to uploaded image |
| status | ENUM | 'available', 'reserved', 'collected', 'expired' |

### `claims`
| Column | Type | Description |
| --- | --- | --- |
| id | INT (PK) | Unique claim identifier |
| food_id | INT (FK) | Reference to `food_posts.id` |
| receiver_id | INT (FK) | Reference to `users.id` |
| status | ENUM | 'pending', 'collected', 'cancelled' |
| claimed_at | TIMESTAMP | Date of claim |

---

## 5. Key Features

### 🔐 Authentication & RBAC
- **Registration**: Allows choosing between 'Donor' and 'Receiver' roles.
- **Profile Management**: Identity header with initial-based avatars, activity metrics, and account settings.
- **Access Control**: `checkRole()` function ensures only authorized users access specific dashboards.

### 🍱 Marketplace (index.php)
- **Advanced Filtering**: Search by title, location, and food category.
- **Urgency Engine**: Live countdown timers on all cards. "Urgent" badges appear for food expiring in < 2 hours.
- **Glassmorphic Search**: A refined hero section with integrated search tools.

### 🛡️ Admin Dashboard
- **System Metrics**: Real-time stats for users, posts, and successful claims.
- **Moderation**: Tabbed interface to manage users and delete inappropriate food posts.
- **Analytics**: Displays overall platform rating from community feedback.

### 🌱 Donor Experience
- **Post Management**: Create/Edit listings with image uploads and expiry tracking.
- **Donation Tracking**: Stats for collected vs. active items.
- **Collection Workflow**: Donors can mark an item as "Collected" once picked up.

### 📦 Receiver Experience
- **One-Click Claiming**: AJAX-powered claiming from the marketplace.
- **Claim History**: Track pending and collected items with donor details.
- **Cancellation**: Ability to release a claim if unable to pick up.

---

## 6. UI/UX Systems

### 🎨 Design Tokens
- **Primary**: `#10b981` (Emerald Green)
- **Secondary**: `#f59e0b` (Amber)
- **Background**: `#0f172a` (Deep Navy)
- **Surface**: `rgba(30, 41, 59, 0.7)` with `backdrop-filter: blur(12px)`

### 🛠️ Components
- **Toasts**: Custom non-blocking notification system.
- **Modals**: Custom promise-based confirmation dialogs.
- **Avatars**: Global `getInitial($name)` helper with randomized/gradient backgrounds.
- **Password Toggle**: Security feature for all password inputs.

---

## 7. API Documentation (AJAX)

- `POST /api/claim_food.php`: Claim an available food item.
- `POST /api/cancel_claim.php`: Cancel a pending claim.
- `POST /api/update_status.php`: Mark food as collected.
- `POST /api/delete_post.php`: Permanently remove a food post.
- `POST /api/platform_review.php`: Submit platform-wide feedback.

---

## 8. Setup Instructions
1. Import `database.sql` into MySQL.
2. Update `/includes/db.php` with your credentials.
3. Ensure the root directory has write permissions for image uploads.
4. Access via `http://localhost/Foodwastesharingplatform`.
