<?php
// =====================================================================
//  BASE CONTROLLER
//  Every controller extends this class. It provides:
//    - the database connection ($this->db)
//    - login protection (runs automatically when the controller starts)
//    - view() to show a page inside the layout
//    - small helpers to read the request safely
// =====================================================================

abstract class Controller
{
    protected mysqli $db;

    // Set to false in a controller whose pages anyone may open (login)
    protected bool $requiresLogin = true;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
        if ($this->requiresLogin) {
            $this->requireLogin();
        }
    }


    // -----------------------------------------------------------------
    // VIEWS
    // -----------------------------------------------------------------

    // Show app/Views/<name>.php with the given variables.
    //   $layout "app"  = sidebar layout (normal pages)
    //   $layout "auth" = centred sign-in card (login, setup)
    protected function view(string $name, array $data = [], string $layout = "app"): void
    {
        if ($layout === "app") {
            // Number on the sidebar "Expiry" link
            $data["navExpiryCount"] = (new Medicine($this->db))->countUrgentExpiry();
            // Total system notification count & urgent alerts for the top bell
            $notifData = (new Notification($this->db))->getAllNotifications(10);
            $data["topNotifications"] = $notifData["items"];
            $data["topNotifUrgentCount"] = $notifData["urgentCount"];
            $data["topNotifTotal"] = $notifData["total"];
        }

        // Turn ["medicines" => ...] into $medicines inside the view
        extract($data, EXTR_SKIP);

        $views = APP_PATH . "/Views";
        if ($layout === "app") {
            require $views . "/layouts/header.php";
            require $views . "/layouts/navbar.php";
            require $views . "/" . $name . ".php";
            require $views . "/layouts/footer.php";
        } else {
            require $views . "/layouts/auth_header.php";
            require $views . "/" . $name . ".php";
            require $views . "/layouts/auth_footer.php";
        }
    }


    // -----------------------------------------------------------------
    // REQUEST HELPERS
    // -----------------------------------------------------------------

    protected function isPost(): bool
    {
        return ($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST";
    }

    // A text value from the address bar (?name=value), trimmed
    protected function query(string $key, string $default = ""): string
    {
        $v = $_GET[$key] ?? $default;
        return is_string($v) ? trim($v) : $default;
    }

    // A text value from a submitted form, trimmed
    protected function input(string $key, string $default = ""): string
    {
        $v = $_POST[$key] ?? $default;
        return is_string($v) ? trim($v) : $default;
    }


    // -----------------------------------------------------------------
    // ACCESS CONTROL
    // -----------------------------------------------------------------

    // Every protected page runs this first
    protected function requireLogin(): void
    {
        $users = new User($this->db);

        if (!is_logged_in()) {
            // First time ever? Create the admin account.
            if ($users->count() === 0) {
                redirect("setup_admin.php");
            }
            // Remember which page they wanted (normal page visits only)
            $next = $this->isPost() ? "" : "?next=" . urlencode(basename($_SERVER["REQUEST_URI"] ?? ""));
            redirect("login.php" . $next);
        }

        // Sign out automatically after a period of no activity
        if (time() - (int) ($_SESSION["last_activity"] ?? 0) > SESSION_TIMEOUT_MINUTES * 60) {
            logout_user();
            flash("warning", "You were signed out after " . SESSION_TIMEOUT_MINUTES . " minutes of inactivity.");
            redirect("login.php");
        }
        $_SESSION["last_activity"] = time();

        // The account must still exist (e.g. not deleted); refresh name and role
        $fresh = $users->find((int) current_user_id());
        if (!$fresh) {
            logout_user();
            flash("error", "Your account is no longer available. Please contact the administrator.");
            redirect("login.php");
        }
        $_SESSION["user"]["full_name"] = $fresh["full_name"];
        $_SESSION["user"]["role"]      = $fresh["role"];
    }

    // Only administrators may continue
    protected function requireAdmin(string $redirectTo = "dashboard.php"): void
    {
        if (!is_admin()) {
            flash("error", "Only an administrator can do that.");
            redirect($redirectTo);
        }
    }

    // Only accept form submissions (never change data from a plain link)
    protected function requirePost(string $redirectTo): void
    {
        if (!$this->isPost()) {
            redirect($redirectTo);
        }
    }

    // The visitor's IP address (falls back to empty string if unavailable)
    protected function clientIp(): string
    {
        // Respect reverse-proxy headers only if explicitly trusted;
        // for a simple XAMPP / shared-hosting setup, REMOTE_ADDR is safest.
        return trim((string) ($_SERVER["REMOTE_ADDR"] ?? ""));
    }
}
