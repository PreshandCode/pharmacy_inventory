# Pharmacy Inventory System

A web-based inventory management system for pharmacies and medical supply stores. It manages medicines and health products with real-time stock levels, batch tracking, expiry monitoring, audit logging, sales and movement reports, and notification alerts.

Built with PHP 8 and MySQL using a Model-View-Controller (MVC) architecture without external frameworks or dependencies. It runs on local environments like XAMPP and shared hosting platforms like InfinityFree.

---

## Features

- **Multi-Tenant Accounts:** Each user or pharmacy registers their own account (`register.php`) with isolated products, stock movements, and activity logs.
- **Dashboard & KPIs:** Displays active inventory counts, available units, low stock alerts, depleted stock, and expiring products alongside a 6-month inflow/outflow activity chart.
- **Product Management:** Supports categorization (Medicines, Supplements, Supplies, Personal Care, Baby Care), batch numbers, dosage strengths, prescription flags, and optional expiry dates for equipment.
- **Stock Control:** Tracks Stock In, Stock Out, and Adjustments with required reasons, live preview calculations, and zero-floor restrictions.
- **Expiry Monitoring:** Groups products into Critical (under 30 days), Upcoming (31-90 days), and Expired tiers with one-click disposal logging.
- **Notifications:** Top navigation alert drawer for critical expiry warnings, stock depletions, and recent activities.
- **Reports & Audit Trail:** Generates inventory valuations, stock turnover, and replenishment lists. Logs sensitive operations (logins, registrations, stock changes) with IP address tracking.
- **Security & Auto-Repair:** Built-in IP-based login lockout, CSRF protection, secure password hashing, and a database diagnostic tool (`fix_db.php`) for automatic schema verification and password resets.

---

## Tech Stack

- **Back End:** PHP 8 (mysqli, prepared statements, sessions)
- **Database:** MySQL / MariaDB (InnoDB, UTF-8)
- **Front End:** HTML5, CSS3, JavaScript (ES6)
- **UI Libraries:** Bootstrap 5.3, Bootstrap Icons, Chart.js

---

## Project Structure

```
pharmacy_inventory/
├── app/
│   ├── Core/           Base Controller and Model classes
│   ├── Models/         Medicine, StockMovement, User, Notification, AuditLog, Report
│   ├── Controllers/    Page controllers handling requests and business logic
│   └── Views/          HTML templates organized by module
├── includes/           Bootstrap, configuration, session auth, and asset files
├── config/             Database connection configurations (local and live)
├── database/           Base schema and sequential migration SQL files
├── fix_db.php          Database diagnostic, migration, and password reset tool
├── deploy.bat          Deployment script for InfinityFree FTP (PowerShell wrapper)
└── index.php           Root redirector to dashboard
```

---

## Database Tables

The application utilizes 5 tables:

1. `users`: Account credentials, hashed passwords, roles (admin/staff), and login tracking.
2. `medicines`: Product catalog scoped by `user_id`, including pricing, batches, and expiry dates.
3. `stock_movements`: Historical records of all inventory transactions.
4. `login_attempts`: IP-based rate limiting to protect against brute-force attacks.
5. `audit_logs`: System activity and compliance tracking.

---

## Installation (Local XAMPP)

1. Clone or copy the project into your local server root:
   ```bash
   C:\xampp\htdocs\pharmacy_inventory
   ```

2. Start Apache and MySQL in the XAMPP Control Panel.

3. Open `http://localhost/phpmyadmin`, create a database named `pharmacy_inventory`, and import `database/base.sql`.

4. Run `http://localhost/pharmacy_inventory/fix_db.php` in your browser to automatically apply remaining migrations and verify tables.

5. Create `config/db_local.php` (ignored by Git):
   ```php
   <?php
   $host     = "localhost";
   $username = "root";
   $password = "";
   $database = "pharmacy_inventory";

   $conn = new mysqli($host, $username, $password, $database);
   ```

6. Open `http://localhost/pharmacy_inventory/` and register or sign in.

---

## Live Deployment (InfinityFree)

1. Create a MySQL database in the InfinityFree Control Panel.

2. Configure `config/db_connect.php` with your cloud database credentials:
   ```php
   <?php
   $host     = "sqlXXX.infinityfree.com";
   $username = "if0_XXXXXXXX";
   $password = "YOUR_ACCOUNT_PASSWORD";
   $database = "if0_XXXXXXXX_pharmacyinventory_db";

   $conn = new mysqli($host, $username, $password, $database);
   ```

3. Upload the files to the `htdocs` folder using FTP or the automated deploy script:
   ```powershell
   .\deploy.bat
   ```

4. Open `http://<your-domain>/fix_db.php` in your browser to verify table structures and run initial tests.

5. Open your live website and register your pharmacy account.

---

## Configuration

Global settings can be modified in `includes/config.php`:

| Setting | Default | Description |
|---|---|---|
| `LOW_STOCK_THRESHOLD` | `20` | Units remaining before triggering a low stock warning |
| `EXPIRY_WARNING_DAYS` | `90` | Window for upcoming expiry warnings |
| `EXPIRY_CRITICAL_DAYS` | `30` | Critical expiry threshold for urgent alerts |
| `CURRENCY` | `₱` | Currency symbol displayed in tables and reports |
| `MAX_LOGIN_ATTEMPTS` | `5` | Failed attempts permitted before IP lockout |
| `LOGIN_LOCK_MINUTES` | `5` | Duration of login pause after lockout |
| `SESSION_TIMEOUT_MINUTES` | `120` | Inactivity window before automatic session termination |

---

## License

This project is available for educational and commercial inventory management purposes.
