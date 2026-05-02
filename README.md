<h1 align="center">🍲 Food Waste Sharing Platform</h1>
<h3 align="center">🚀 A scalable web application connecting food donors with receivers to eliminate food waste</h3>

<p align="center">
  <a href="https://github.com/mishalibrar">
    <img src="https://img.shields.io/badge/Developer-Mishal%20Ibrar-blue?style=for-the-badge&logo=github" alt="Developer" />
  </a>
  <a href="mailto:mishalibrar12@gmail.com">
    <img src="https://img.shields.io/badge/Email-Contact-red?style=for-the-badge&logo=gmail" alt="Email" />
  </a>
</p>

---

## 💡 About The Project  

The **Food Waste Sharing Platform** is a complete web-based system designed to tackle food waste by seamlessly connecting food donors (restaurants, individuals, grocery stores) with receivers (charities, individuals in need).  

It features a **premium dark-mode aesthetic**, robust user management, and a comprehensive dashboard system for different user roles (Admin, Donor, Receiver). Built with clean architecture, it emphasizes **user trust, activity tracking, and urgency management** for perishable items.

---

## 💼 Key Features  

- 📱 **Multi-Role Dashboards:** Tailored interfaces with glassmorphic UI for Admins, Donors, and Receivers.
- 🔐 **Secure Authentication:** Complete registration/login flow with password visibility toggles and profile management.
- 🍲 **Listing Management:** Donors can easily list surplus food with detailed descriptions, quantities, and urgency levels.
- 🤝 **Claiming & Tracking:** Receivers can browse, filter, and claim available food listings in real-time.
- ⭐ **Trust & Rating System:** Users can review and rate their experiences, building community trust and accountability.
- 📊 **Activity Logging:** Comprehensive tracking of user actions (logins, claims, listings) for transparency and admin oversight.
- 🎨 **Premium UI/UX:** Responsive, mobile-friendly design featuring a curated dark mode, custom CSS animations, and modern typography.

---

## 🛠️ Tech Stack  

<p align="center">
  <img src="https://skillicons.dev/icons?i=php,mysql,html,css,js,git,github" alt="Tech Stack" />
</p>

- **Frontend:** HTML5, Vanilla CSS (Premium Dark Theme), JavaScript (Fetch API, DOM manipulation)
- **Backend:** PHP (RESTful API endpoints, Session management)
- **Database:** MySQL (Relational schema with triggers and foreign keys)
- **Server:** Apache (via XAMPP)

---

## 🚀 Getting Started  

Follow these steps to set up the project locally:

### 1. Prerequisites
- Install [XAMPP](https://www.apachefriends.org/index.html) (or any AMP stack).
- Ensure Apache and MySQL modules are running.

### 2. Installation
1. Clone the repository into your XAMPP `htdocs` directory:
   ```bash
   cd c:/xampp/htdocs/
   git clone https://github.com/mishalibrar/Foodwastesharingplatform.git
   cd Foodwastesharingplatform
   ```
2. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
3. Create a new database named `food_waste_platform`.
4. Import the provided SQL schemas in this order:
   - `database.sql` (Core tables)
   - `database_migration.sql` (Updates)
   - `database_migration_v2.sql` (Ratings & Reviews)
   - `database_migration_v3.sql` (Admin Logs & Urgency)
5. Configure the database connection in `includes/db.php` if your local MySQL credentials differ from the default (`root` / empty password).

### 3. Run the Application
Open your browser and navigate to:
```
http://localhost/Foodwastesharingplatform/
```

---

## 📂 Project Structure  

- `/api/` - Backend PHP logic and RESTful endpoints (JSON responses)
- `/assets/` - Static assets (CSS, Images, JS scripts)
- `/auth/` - Registration, Login, and Session handling
- `/dashboard/` - Role-specific dashboards (Admin, Donor, Receiver)
- `/includes/` - Reusable PHP components (DB connection, headers, footers)
- `/listings/` - Logic for displaying, creating, and managing food listings

---

## ⚡ Key Strengths  

- ✅ **Clean & Maintainable Code:** Separation of concerns between API logic and frontend views.
- 🎨 **Strong UI/UX:** Built with a graphic design perspective, ensuring the app is not just functional but polished.
- 🧠 **Real-world Problem Solving:** Addresses a critical global issue with a practical, scalable technological solution.

---

## 📫 Contact  

- **Developer:** Mishal Ibrar
- **LinkedIn:** [https://www.linkedin.com/in/mishalibrar/](https://www.linkedin.com/in/mishalibrar/)
- **Email:** mishalibrar12@gmail.com

---

<p align="center">
  ⚡ Building real apps, deploying real websites, solving real problems. ⚡
</p>
