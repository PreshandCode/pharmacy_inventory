<?php
// Load this file only once per request
if (defined("APP_BOOTSTRAPPED")) {
    return;
}
define("APP_BOOTSTRAPPED", true);

// Keep page output in memory until the end, so redirects always work
ob_start();

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/functions.php";

date_default_timezone_set(APP_TIMEZONE);

// Are we running on your own computer (XAMPP) or on InfinityFree?
$serverName = $_SERVER["SERVER_NAME"] ?? "";
define("IS_LOCAL", in_array($serverName, ["localhost", "127.0.0.1", "::1"], true));

// Show detailed errors only on your own computer, never on the live site
if (IS_LOCAL) {
    ini_set("display_errors", "1");
    error_reporting(E_ALL);
} else {
    ini_set("display_errors", "0");
}

// Friendly error page instead of a raw PHP/database error message

set_exception_handler(function ($e) {
    error_log("[Pharmacy] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);

    $friendly = ($e instanceof AppException)
        ? "<p style='background:#fffbeb;color:#92400e;padding:12px;border-radius:8px;margin:0 0 16px'>"
          . htmlspecialchars($e->getMessage()) . "</p>"
        : "";

    // Technical details are shown on localhost OR if ?debug=1 is in the URL
    $technical = "";
    $showDebug = IS_LOCAL || isset($_GET["debug"]) || (isset($_SERVER["QUERY_STRING"]) && strpos($_SERVER["QUERY_STRING"], "debug") !== false);
    if ($showDebug && !($e instanceof AppException)) {
        $technical = "<pre style='white-space:pre-wrap;font-size:13px;background:#f8fafc;padding:12px;border-radius:8px;color:#dc2626;border:1px solid #fecaca;margin-top:12px;overflow-x:auto'>"
            . htmlspecialchars($e->getMessage() . "\n\nFile: " . $e->getFile() . ":" . $e->getLine() . "\n\nStack trace:\n" . $e->getTraceAsString())
            . "</pre>";
    }

    $details = $friendly . $technical;

    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'>"
       . "<title>Something went wrong</title></head>"
       . "<body style='font-family:system-ui,sans-serif;background:#f5f7f9;color:#1e293b;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:16px'>"
       . "<div style='background:#fff;max-width:520px;width:100%;padding:32px;border-radius:16px;box-shadow:0 4px 24px rgba(15,23,42,.08)'>"
       . "<h1 style='font-size:20px;margin:0 0 8px'>Something went wrong</h1>"
       . "<p style='color:#64748b;margin:0 0 16px'>The system could not complete your request. Please try again. If the problem continues, check the database connection.</p>"
       . $details
       . "<a href='dashboard.php' style='display:inline-block;background:#16a34a;color:#fff;padding:10px 18px;border-radius:10px;text-decoration:none'>Back to Dashboard</a>"
       . "</div></body></html>";
    exit;
});

// Secure session

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
        || (($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "") === "https");

    session_name("PHARMASESSID");
    session_set_cookie_params([
        "lifetime" => 0,          // cookie ends when the browser closes
        "path"     => "/",
        "secure"   => $isHttps,   // only sent over HTTPS when available
        "httponly" => true,       // JavaScript cannot read the cookie
        "samesite" => "Lax",
    ]);
    ini_set("session.use_strict_mode", "1");
    session_start();
}

// Database connection
//   On localhost  -> config/db_local.php (if it exists)
//   On InfinityFree -> your original config/db_connect.php (unchanged)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($conn)) {
    $localConfig = __DIR__ . "/../config/db_local.php";

    if (IS_LOCAL && is_file($localConfig)) {
        require $localConfig;
    } else {
        require_once __DIR__ . "/../config/db_connect.php";
    }
}

$conn->set_charset("utf8mb4");

// Make MySQL dates (CURDATE, NOW) match Philippine time
$conn->query("SET time_zone = '" . DB_TIMEZONE_OFFSET . "'");

// Session sign-in helpers (is_logged_in, current_user, ...)

require_once __DIR__ . "/auth.php";

// MVC: load classes automatically from the app/ folder
//   new MedicineController(...)  -> app/Controllers/MedicineController.php
//   new Medicine(...)            -> app/Models/Medicine.php
//   Controller / Model           -> app/Core/...
// Login protection now happens inside each controller (see
// app/Core/Controller.php), so every page that uses a controller is
// protected automatically.
define("APP_PATH", dirname(__DIR__) . "/app");

spl_autoload_register(function (string $class) {
    foreach (["Core", "Models", "Controllers"] as $folder) {
        $file = APP_PATH . "/" . $folder . "/" . $class . ".php";
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});
