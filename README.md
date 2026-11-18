# Pharmacy Inventory System

A modern, account-based web inventory management system for pharmacies and medical supply stores. It tracks medicines, medical devices, and health products with real-time stock levels, batch tracking, expiry monitoring, automated audit trails, sales & movement reports, and notification alerts.

Built with plain **PHP 8 and MySQL** using a clean **MVC (Model–View–Controller)** architecture without heavy frameworks or Composer dependencies. Runs effortlessly on **XAMPP (local)** and free shared hosting like **InfinityFree (live)**.

---

## Key Features

### 🏢 Multi-Tenant / Account-Based Isolation
* **Independent Pharmacies:** Any user can register an account (`register.php`). Each pharmacy manages their own private inventory, stock history, and activity logs without seeing other users' data.
* **Role-Based Access:** Administrative controls for product deletion, profile management, and account security.

### 📊 Dashboard & Analytics
* **Real-Time Metric Cards:** Total products, active stock units, low-stock warnings, out-of-stock counts, items expiring within warning window, and already-expired stock.
* **Interactive Activity Charts:** 6-month monthly inflow vs. outflow chart powered by Chart.js.
* **Priority Watchlists:** Immediate-action lists for products needing restock and batches expiring earliest.

### 💊 Product Management
* **Diverse Categories & Types:** Medicines, Vitamins & Supplements, Medical Supplies, Personal Care, and Baby Care.
* **Batch & Prescription Controls:** Batch numbers, dosage strengths, prescription (`Rx`) requirements, and optional expiry dates for non-perishable equipment (e.g. digital thermometers).
* **Search & Filter:** Instant search by name, brand, batch number, or supplier, with filters for product type, stock level, and expiry window.

### 📦 Stock Control & Movements
* **Strict Inventory Rules:** Stock cannot go below zero, and expired batches cannot accept new stock.
* **Categorized Movements:** Stock In (deliveries, returns), Stock Out (dispensed/sold, expired/disposed, damaged), and Stock Adjustments (physical count audit corrections).
* **Audit Trail:** Every stock change records quantity before/after, user identity, reason, timestamp, and optional notes.

### ⏳ Expiry Monitoring
* **Urgency Tiers:** Categorized tabs for critical expiry (within 30 days), upcoming expiry (31–90 days), and expired stock.
* **One-Click Disposal:** Fast disposal button that automatically zeroes out stock and logs the movement reason as "Expired - disposed".
* **Printable Reports:** Clean, print-ready view for shelf inspections.

### 🔔 Real-Time Notifications
* **Top Navigation Bell:** Notification drawer showing unread critical expiry alerts, out-of-stock warnings, and recent system transactions.
* **Dedicated Notifications Page (`notifications.php`):** Filter by alert severity (Critical, Warning, Activity).

### 📈 Reports & Logs
* **Comprehensive Reports (`reports.php`):** Inventory valuation, stock turnover, movement summaries, and low-stock replenishment manifests.
* **System Audit Trail (`audit.php`):** Logs all sensitive operations (registrations, logins, stock changes, product additions/edits/deletions) with user IP tracking.

### 🛡️ Security & Health Check
* **Brute-Force Protection:** IP-based login lockout (5 failed attempts pauses login for 5 minutes).
* **Safe Authentication:** Bcrypt one-way password hashing (`PASSWORD_DEFAULT`), session fixation protection, and CSRF token verification across all forms.
* **Automated Repair Tool (`fix_db.php`):** Web-based database diagnostic tool that checks tables, applies schema updates without duplicate key errors, tests queries live, and includes a **Quick Password Reset / Account Unlock** tool.

---

## Tech Stack

| Component | Technology |
|---|---|
| **Back End** | PHP 8 (strict types, mysqli prepared statements, secure sessions) |
| **Database** | MySQL / MariaDB (InnoDB, UTF8mb4) |
| **Front End** | Modern HTML5, Custom CSS3, Vanilla JavaScript (ES6) |
| **UI Framework & CDN** | Bootstrap 5.3, Bootstrap Icons, Chart.js, Google Inter Font |
| **Deployment** | XAMPP (Local Development) & InfinityFree (Cloud Hosting) |

---

## Project Structure (MVC)

```
pharmacy_inventory/
├── app/
│   ├── Core/
│   │   ├── Controller.php          Base controller: auth checks, view extraction, helpers
│   │   └── Model.php               Base model: safe prepared statements (all, one, exec)
│   ├── Models/
│   │   ├── Medicine.php            Product queries, filtering, stock calculation & stats
│   │   ├── StockMovement.php       Stock in/out/adjustment logs and monthly analytics
│   │   ├── User.php                Authentication, user registration, IP lockout tracking
│   │   ├── Notification.php        Real-time expiry, depletion, and activity alerts
│   │   ├── AuditLog.php            Security tracking and administrative event logging
│   │   └── Report.php              Turnover, inventory valuation, and category summaries
│   ├── Controllers/
│   │   ├── DashboardController.php Dashboard KPIs, activity chart, and urgent items
│   │   ├── MedicineController.php  Product catalog: list, view, add, edit, delete
│   │   ├── StockController.php     Stock In/Out transactions and movement logs
│   │   ├── ExpiryController.php    Per-batch expiry classification and disposal
│   │   ├── NotificationController.php Full notifications drawer and log view
│   │   ├── ReportController.php    Analytical reports and printable summaries
│   │   ├── AuditController.php     Security audit logs with filtering by action
│   │   ├── AccountController.php   User profile and password updates
│   │   └── AuthController.php      Sign in, sign out, user registration, admin setup
│   └── Views/                      Clean HTML/PHP templates organized by feature
├── includes/
│   ├── bootstrap.php               Application entry point, timezone, exception handler
│   ├── config.php                  Global constants (thresholds, warning windows, currency)
│   ├── functions.php               Security helpers: e(), csrf_token(), flash messages
│   ├── auth.php                    Safe session management and user session helpers
│   ├── css/style.css               Custom UI stylesheets (variables, cards, components)
│   └── js/                         dashboard.js (Chart.js), stock.js, app.js
├── config/
│   ├── db_connect.example.php      Template for live database connection
│   ├── db_connect.php              Live InfinityFree credentials (not in Git)
│   └── db_local.php                Local XAMPP credentials (not in Git)
├── database/
│   ├── base.sql                    Base medicines table
│   ├── upgrade.sql                 Stock movements, users, description, date_added
│   ├── upgrade_2_products.sql      Product types, unit, Rx, optional expiry
│   ├── upgrade_3_security.sql      IP-based login lockout and unique batch index
│   ├── upgrade_4_audit.sql         Audit logs table and last_updated column
│   └── upgrade_5_multiuser.sql     User ID isolation for account-based inventory
├── fix_db.php                      Database auto-repair & password reset tool
├── deploy.ps1 / deploy.bat         One-click terminal deployment scripts for InfinityFree FTP
└── index.php                       Landing redirector
```

---

## Database Architecture

The database consists of 5 interconnected tables:

1. **`users`**: User accounts with bcrypt password hashes, roles (`admin`, `staff`), and last login timestamps.
2. **`medicines`**: Product catalog scoped by `user_id` so each pharmacy operates independently. Includes batch numbers, stock units, expiry dates, and categories.
3. **`stock_movements`**: Complete audit trail of inventory adjustments, deliveries, and sales.
4. **`login_attempts`**: IP-based rate limiting to protect against brute-force attacks.
5. **`audit_logs`**: System activity history tracking account actions, logins, updates, and removals.

---

## Local Installation (XAMPP)

1. **Clone the repository:**
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/PreshandCode/pharmacy_inventory.git
   ```

2. **Start Services:** Start **Apache** and **MySQL** in the XAMPP Control Panel.

3. **Set up the Database:**
   * Open `http://localhost/phpmyadmin` in your browser.
   * Create a database named **`pharmacy_inventory`**.
   * Run the SQL scripts in order from the `database/` folder (or run `base.sql` then open `http://localhost/pharmacy_inventory/fix_db.php` to auto-build all remaining tables).

4. **Configure Local DB Connection:**
   Create a file `config/db_local.php` (this file is ignored by Git):
   ```php
   <?php
   $host     = "localhost";
   $username = "root";
   $password = "";
   $database = "pharmacy_inventory";

   $conn = new mysqli($host, $username, $password, $database);
   ```

5. **Launch the App:**
   Open: **`http://localhost/pharmacy_inventory/`**

---

## Live Deployment (InfinityFree)

1. **Create an Account:**
   * Create a free hosting account and MySQL database in InfinityFree.
   * Note your database hostname (`sqlXXX.infinityfree.com`), username (`if0_XXXXXXXX`), and password.

2. **Configure Database Credentials:**
   Create or edit `config/db_connect.php`:
   ```php
   <?php
   $host     = "sqlXXX.infinityfree.com";
   $username = "if0_XXXXXXXX";
   $password = "YOUR_ACCOUNT_PASSWORD";
   $database = "if0_XXXXXXXX_pharmacyinventory_db";

   $conn = new mysqli($host, $username, $password, $database);
   ```

3. **Deploy Files to InfinityFree:**
   Run the automated upload script from your terminal:
   ```powershell
   .\deploy.bat
   ```
   *(Or in Git Bash: `./deploy.sh`)*

4. **Run the Auto-Repair / Verification Tool:**
   In your browser, visit:
   👉 **`http://<your-subdomain>.ifree.page/fix_db.php`**
   * This automatically creates all missing tables, adds required columns, and verifies dashboard queries.
   * If you need to reset an account password, use the **Quick Password Reset** form at the bottom of the page.

5. **Done!** Open your live dashboard and start managing your inventory.

---

## System Configuration

Global parameters can be adjusted in `includes/config.php`:

| Setting | Default | Description |
|---|---|---|
| `LOW_STOCK_THRESHOLD` | `20` | Stock level that triggers a "Low Stock" warning |
| `EXPIRY_WARNING_DAYS` | `90` | Window for "Expiring Soon" monitoring |
| `EXPIRY_CRITICAL_DAYS` | `30` | Urgent expiry threshold (red warning badge) |
| `CURRENCY` | `₱` | Currency symbol displayed across tables and reports |
| `MAX_LOGIN_ATTEMPTS` | `5` | Maximum failed passwords before IP lockout |
| `LOGIN_LOCK_MINUTES` | `5` | Duration of login pause after reaching attempt limit |
| `SESSION_TIMEOUT_MINUTES` | `120` | Inactivity limit before automatic sign-out |

---

## License

This project was developed for educational and small business management purposes. Free to use and customize.
