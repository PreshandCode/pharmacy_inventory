<?php
// =====================================================================
//  SIGN-IN SESSION HELPERS
//  Only reads and writes the PHP session - no database work here.
//  (Database work for users is in app/Models/User.php, and the page
//  protection check is in app/Core/Controller.php.)
// =====================================================================


// Is somebody signed in?
function is_logged_in(): bool
{
    return !empty($_SESSION["user"]["user_id"]);
}

// The signed-in user: ["user_id", "username", "full_name", "role"] or null
function current_user(): ?array
{
    return is_logged_in() ? $_SESSION["user"] : null;
}

function is_admin(): bool
{
    return is_logged_in() && ($_SESSION["user"]["role"] ?? "") === "admin";
}

// The signed-in user's ID (or null). Used when recording stock history.
function current_user_id(): ?int
{
    return is_logged_in() ? (int) $_SESSION["user"]["user_id"] : null;
}

// "Maria Santos" -> "MS"  (for the round avatar)
function user_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = $parts[0] ?? "";
    $last  = count($parts) > 1 ? end($parts) : "";
    $initials = mb_substr($first, 0, 1) . mb_substr($last, 0, 1);
    return mb_strtoupper($initials !== "" ? $initials : "?");
}


// ---------------------------------------------------------------------
// Sign in: save the user in the session
// ---------------------------------------------------------------------
function login_user(array $user): void
{
    // New session ID after login (prevents session fixation attacks)
    session_regenerate_id(true);

    $_SESSION["user"] = [
        "user_id"   => (int) $user["user_id"],
        "username"  => $user["username"],
        "full_name" => $user["full_name"],
        "role"      => $user["role"],
    ];
    $_SESSION["last_activity"] = time();
    unset($_SESSION["csrf_token"], $_SESSION["login_fails"], $_SESSION["login_locked_until"]);
}


// ---------------------------------------------------------------------
// Sign out: clear everything in the session
// ---------------------------------------------------------------------
function logout_user(): void
{
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), "", [
            "expires"  => time() - 42000,
            "path"     => $p["path"],
            "domain"   => $p["domain"],
            "secure"   => $p["secure"],
            "httponly" => $p["httponly"],
            "samesite" => $p["samesite"] ?? "Lax",
        ]);
    }
    session_destroy();

    // Start a fresh, empty session so a "signed out" message can be shown
    session_start();
    session_regenerate_id(true);
}


// Only allow redirects to our own pages, e.g. "medicine.php?search=x"
// (stops attackers from using ?next= to send users to another website)
function safe_next_page(?string $next): string
{
    $next = (string) $next;
    if (preg_match('/^[a-z0-9_]+\.php(\?[A-Za-z0-9_=&%.+\-]*)?$/', $next)
        && !preg_match('/^(login|logout|setup_admin)\.php/', $next)) {
        return $next;
    }
    return "dashboard.php";
}
