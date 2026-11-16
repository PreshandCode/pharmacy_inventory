# Pharmacy Inventory System

A web-based inventory management system for small pharmacies. It keeps track of medicines, stock levels and expiry dates, warns you about low stock and medicines that are about to expire, and records every stock change in a history log.

Built with plain **PHP and MySQL** using an **MVC (Model–View–Controller)** structure. No frameworks or Composer packages, so it runs on free shared hosting such as **InfinityFree** as well as on **XAMPP**.

---

## Features

**Dashboard**
- Key numbers: total medicines, units available, low stock, out of stock, expiring soon, expired
- "Needs restock" and "Expiring or expired" lists
- Monthly stock activity chart (units in / out, last 6 months)
- Recently added medicines

**Medicines**
- Add, view, edit and delete medicines
- Search by name, brand, batch number or supplier
- Filter by category, stock level and expiry status
- Sortable columns and pagination
- Form validation (required fields, number ranges, real dates, duplicate name + batch check)

**Stock management**
- Stock in (deliveries, returns), stock out (dispensed, expired, damaged …) and count corrections
- A reason is required for every change
- Live preview of the new quantity before saving
- Stock can never go below zero, and no stock can be added to an expired batch
- Full history of every change: before/after quantity, reason, who did it and when

**Expiry monitoring**
- Tabs: needs attention, expired, within 30 days, 31–90 days, all
- Expiry dates highlighted by urgency
- "Dispose" button records the removal of expired stock
- Printable list

**Accounts and security**
- Login with admin and staff roles (only admins can delete medicines)
- First-time setup page to create the admin account
- Passwords stored as secure hashes; login is paused after 5 wrong attempts
- Automatic sign-out after 120 minutes of inactivity
- Prepared statements on every query (SQL injection protection)
- CSRF tokens on every form, escaped output (XSS protection)
- `.htaccess` files block direct browser access to code and configuration folders

---

## Tech stack

| Part | Used |
|---|---|
| Back end | PHP 8 (mysqli, sessions) |
| Database | MySQL / MariaDB |
| Front end | HTML, CSS, JavaScript |
| UI libraries (CDN) | Bootstrap 5.3, Bootstrap Icons, Chart.js, Inter font |
| Hosting | XAMPP (local), InfinityFree (live) |

---

## Project structure (MVC)

```
pharmacy_inventory/
├── app/
│   ├── Core/
│   │   ├── Controller.php      Base controller: login check, view(), request helpers
│   │   └── Model.php           Base model: safe prepared-statement helpers
│   ├── Models/
│   │   ├── Medicine.php        Medicines: lists, filters, validation, saving, stock rules
│   │   ├── StockMovement.php   Stock history
│   │   └── User.php            Login accounts
│   ├── Controllers/
│   │   ├── DashboardController.php
│   │   ├── MedicineController.php
│   │   ├── StockController.php
│   │   ├── ExpiryController.php
│   │   ├── AccountController.php
│   │   └── AuthController.php  Login, logout, first-time setup
│   └── Views/                  HTML only (layouts, partials and one folder per page)
├── includes/
│   ├── bootstrap.php           Starts the session, connects to the database, loads classes
│   ├── config.php              Settings (low-stock level, expiry warning days, currency …)
│   ├── functions.php           Helpers (escaping, formatting, flash messages, CSRF, pagination)
│   ├── auth.php                Session sign-in helpers
│   ├── css/style.css
│   └── js/                     app.js, dashboard.js, stock.js
├── config/
│   ├── db_connect.example.php  Template for the live database connection (no real password)
│   ├── db_connect.php          Your real live connection - not in Git (see "Database credentials")
│   └── db_local.php            Your XAMPP connection - not in Git
├── database/
│   └── upgrade.sql             Adds the tables and columns the system needs
├── dashboard.php, medicine.php, stock.php, expiry.php, …   Entry points (one per page)
└── index.php                   Redirects to the dashboard
```

### How a request works

Each page in the main folder is a small entry point. For example, `medicine.php`:

```php
require_once __DIR__ . "/includes/bootstrap.php";
(new MedicineController($conn))->index();
```

1. The **controller** checks that the user is signed in and reads the request (search text, filters, form data).
2. The **model** reads or changes data in MySQL using prepared statements.
3. The controller passes the results to a **view**, which outputs the HTML.

Because every page keeps its own `.php` address, no URL rewriting is needed and links stay the same.

| Page | Controller → method | Purpose |
|---|---|---|
| `dashboard.php` | `DashboardController->index()` | Overview |
| `medicine.php` | `MedicineController->index()` | Medicine list |
| `view_medicine.php?id=` | `MedicineController->show()` | Medicine details and history |
| `add_medicine.php` | `MedicineController->create()` | Add a medicine |
| `edit_medicine.php?id=` | `MedicineController->edit()` | Edit form |
| `update_medicine.php` | `MedicineController->update()` | Save the edit form |
| `delete_medicine.php` | `MedicineController->destroy()` | Delete (admins only) |
| `stock.php` | `StockController->index()` / `store()` | Update stock and view history |
| `expiry.php` | `ExpiryController->index()` | Expiry monitoring |
| `account.php` | `AccountController->index()` | Change name and password |
| `login.php`, `logout.php`, `setup_admin.php` | `AuthController` | Sign in, sign out, first admin |

---

## Database

Three tables:

| Table | Contents |
|---|---|
| `medicines` | medicine_id, medicine_name, category, description, brand, batch_number, quantity, unit_price, expiry_date, supplier, date_added |
| `stock_movements` | Every stock change: medicine, type (in / out / adjust), quantity, before, after, note, user, date |
| `users` | username, full_name, password_hash, role (admin / staff), created_at, last_login |

---

## Installation (local, with XAMPP)

**Requirements:** XAMPP with PHP 8.0 or newer and MySQL/MariaDB.

1. **Copy the project** into `C:\xampp\htdocs\pharmacy_inventory`.

2. **Start Apache and MySQL** in the XAMPP Control Panel.

3. **Create the database.** Open `http://localhost/phpmyadmin`, create a database named `pharmacy_inventory`, then run the SQL below in its **SQL** tab.

   *Fresh install only* — create the base `medicines` table first:

   ```sql
   CREATE TABLE medicines (
     medicine_id   INT(11)       NOT NULL AUTO_INCREMENT,
     medicine_name VARCHAR(100)  NOT NULL,
     category      VARCHAR(50)   NOT NULL,
     brand         VARCHAR(100)  DEFAULT NULL,
     batch_number  VARCHAR(50)   DEFAULT NULL,
     quantity      INT(11)       NOT NULL DEFAULT 0,
     unit_price    DECIMAL(10,2) NOT NULL,
     expiry_date   DATE          NOT NULL,
     supplier      VARCHAR(100)  DEFAULT NULL,
     PRIMARY KEY (medicine_id)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
   ```

   Then run **`database/upgrade.sql`** (once). It adds the `description` and `date_added` columns and the `stock_movements` and `users` tables.

4. **Create `config/db_local.php`** (this file is ignored by Git and only used on `localhost`):

   ```php
   <?php
   $host     = "localhost";
   $username = "root";
   $password = "";
   $database = "pharmacy_inventory";

   $conn = new mysqli($host, $username, $password, $database);
   ```

5. **Open** `http://localhost/pharmacy_inventory/`. The first visit shows **First-time setup**, where you create the administrator account.

---

## Deployment (InfinityFree)

1. In the InfinityFree control panel, create a MySQL database and note its host, username, password and database name.
2. In **phpMyAdmin**, select the database and run the SQL from step 3 above (`CREATE TABLE` for a fresh install, then `database/upgrade.sql`).
3. Copy `config/db_connect.example.php` to `config/db_connect.php` and fill in your InfinityFree database details (see below).
4. Upload the project into `htdocs`, including the `app/`, `includes/`, `config/` folders and the `.htaccess` files. Do **not** upload `config/db_local.php` or `config/db_connect.example.php`.
5. Open your site and create the administrator account.
6. Optional: delete `setup_admin.php` from the server afterwards. It already refuses to work once an account exists.

The system picks the database automatically: `config/db_local.php` on `localhost`, otherwise `config/db_connect.php`.

### Database credentials

Real passwords are never stored in Git:

| File | In Git? | Purpose |
|---|---|---|
| `config/db_connect.example.php` | Yes | Template with placeholder values |
| `config/db_connect.php` | **No** (in `.gitignore`) | Your real InfinityFree connection |
| `config/db_local.php` | **No** (in `.gitignore`) | Your XAMPP connection |

After cloning the project, copy the template to `config/db_connect.php` and fill in your own details:

```php
<?php
$host     = "sqlXXX.infinityfree.com";
$username = "if0_XXXXXXXX";
$password = "YOUR_DATABASE_PASSWORD";
$database = "if0_XXXXXXXX_pharmacyinventory_db";

$conn = new mysqli($host, $username, $password, $database);
```

---

## Configuration

Edit `includes/config.php`:

| Setting | Default | Meaning |
|---|---|---|
| `APP_NAME`, `APP_TAGLINE` | Pharmacy, Inventory System | Name shown in the sidebar and browser tab |
| `LOW_STOCK_THRESHOLD` | 20 | Below this many units = low stock |
| `EXPIRY_WARNING_DAYS` | 90 | Expiring within this many days = expiring soon |
| `EXPIRY_CRITICAL_DAYS` | 30 | Expiring within this many days = critical |
| `CURRENCY` | ₱ | Currency symbol |
| `APP_TIMEZONE` / `DB_TIMEZONE_OFFSET` | Asia/Manila / +08:00 | Time zone for dates |
| `ROWS_PER_PAGE` | 10 | Rows per table page |
| `SESSION_TIMEOUT_MINUTES` | 120 | Automatic sign-out |
| `MAX_LOGIN_ATTEMPTS`, `LOGIN_LOCK_MINUTES` | 5, 5 | Login lockout |
| `MIN_PASSWORD_LENGTH` | 8 | Minimum password length |

---

## User roles

| Role | Can do |
|---|---|
| **Admin** | Everything, including deleting medicines |
| **Staff** | View, add and edit medicines, update stock, check expiry, manage own account |

---

## Troubleshooting

| Problem | Solution |
|---|---|
| "No connection could be made because the target machine actively refused it" | MySQL is not running. Start it in the XAMPP Control Panel. |
| "Please run database/upgrade.sql in phpMyAdmin first" | The `users` table is missing. Run `database/upgrade.sql`. |
| MySQL in XAMPP will not start after a power cut or shutdown | Its system tables may be damaged. Always click **Stop** for MySQL before shutting down. Back up `C:\xampp\mysql\data` before attempting a repair. |
| Changes to CSS/JS don't appear | Refresh with Ctrl + F5. |
| 403 Forbidden on `app/`, `config/` or `includes/*.php` | Expected. These folders are protected on purpose. |

---

## License

This project was created for educational purposes.
