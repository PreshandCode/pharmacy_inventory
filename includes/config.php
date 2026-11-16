<?php
// =====================================================================
//  APPLICATION SETTINGS
//  Change these values to adjust how the system behaves.
// =====================================================================

// Name shown in the sidebar and browser tab
define("APP_NAME", "Pharmacy");
define("APP_TAGLINE", "Inventory System");

// A medicine with quantity BELOW this number (but above 0) is "Low Stock"
define("LOW_STOCK_THRESHOLD", 20);

// A medicine expiring within this many days is "Expiring Soon"
define("EXPIRY_WARNING_DAYS", 90);

// Expiring within this many days is shown as "Critical" (most urgent)
define("EXPIRY_CRITICAL_DAYS", 30);

// Currency symbol used for prices
define("CURRENCY", "₱");

// Your local time zone (Philippines). The database uses the same offset.
define("APP_TIMEZONE", "Asia/Manila");
define("DB_TIMEZONE_OFFSET", "+08:00");

// Number of rows per page in tables
define("ROWS_PER_PAGE", 10);

// ---------------------------------------------------------------------
// LOGIN SECURITY
// ---------------------------------------------------------------------

// Sign the user out after this many minutes without any activity
define("SESSION_TIMEOUT_MINUTES", 120);

// After this many wrong passwords, block login attempts for a while
define("MAX_LOGIN_ATTEMPTS", 5);
define("LOGIN_LOCK_MINUTES", 5);

// Minimum password length for new accounts / password changes
define("MIN_PASSWORD_LENGTH", 8);

// ---------------------------------------------------------------------
// MEDICINE LIMITS (match the database column sizes)
// ---------------------------------------------------------------------
define("MED_MAX_QUANTITY", 1000000);
define("MED_MAX_PRICE", 99999999.99);
