<?php
// =====================================================================
//  AUTH CONTROLLER - pages anyone may open (no sign-in needed)
//    login()   login.php        sign in, with lockout after wrong passwords
//    logout()  logout.php       sign out (Sign out button only)
//    setup()   setup_admin.php  create the first admin (only while no accounts exist)
// =====================================================================

class AuthController extends Controller
{
    protected bool $requiresLogin = false;

    // A real password hash used when the username doesn't exist, so the
    // response time does not reveal which usernames are real.
    private const DUMMY_HASH = '$2y$10$00qttlRJoB/bpnJdJRUj8ORhypA0l5i8UqA0VDGf/zhSUk86Vcbu2';

    // -----------------------------------------------------------------
    // SIGN IN
    // -----------------------------------------------------------------
    public function login(): void
    {
        $users = new User($this->db);
        $next  = safe_next_page($_GET["next"] ?? $_POST["next"] ?? "");

        if (is_logged_in()) {
            redirect($next);
        }
        if ($users->count() === 0) {
            redirect("setup_admin.php");
        }

        $error    = "";
        $username = "";
        $lockedFor = max(0, (int) ($_SESSION["login_locked_until"] ?? 0) - time());

        if ($this->isPost()) {
            csrf_check("login.php");

            $username = strtolower($this->input("username"));
            $password = (string) ($_POST["password"] ?? "");

            if ($lockedFor > 0) {
                $error = "Too many failed attempts. Please wait " . ceil($lockedFor / 60) . " minute(s) and try again.";
            } elseif ($username === "" || $password === "") {
                $error = "Please enter your username and password.";
            } else {
                $user = $users->findForLogin($username);
                $passwordOk = password_verify($password, $user["password_hash"] ?? self::DUMMY_HASH);

                if ($user && $passwordOk) {
                    // Upgrade the stored hash if PHP's recommended method changed
                    if (password_needs_rehash($user["password_hash"], PASSWORD_DEFAULT)) {
                        $users->updatePassword((int) $user["user_id"], $password);
                    }
                    login_user($user);
                    $users->touchLastLogin((int) $user["user_id"]);
                    flash("success", "Welcome back, " . $user["full_name"] . "!");
                    redirect($next);
                }

                // Wrong username or password
                $_SESSION["login_fails"] = (int) ($_SESSION["login_fails"] ?? 0) + 1;
                $left = MAX_LOGIN_ATTEMPTS - $_SESSION["login_fails"];

                if ($left <= 0) {
                    $_SESSION["login_locked_until"] = time() + LOGIN_LOCK_MINUTES * 60;
                    $_SESSION["login_fails"] = 0;
                    $lockedFor = LOGIN_LOCK_MINUTES * 60;
                    $error = "Too many failed attempts. Login is paused for " . LOGIN_LOCK_MINUTES . " minutes.";
                } else {
                    $error = "Incorrect username or password. " . $left . " attempt(s) left.";
                }
            }
        }

        $this->view("auth/login", [
            "pageTitle" => "Sign in",
            "error"     => $error,
            "username"  => $username,
            "next"      => $next,
            "lockedFor" => $lockedFor,
        ], "auth");
    }

    // -----------------------------------------------------------------
    // SIGN OUT
    // -----------------------------------------------------------------
    public function logout(): void
    {
        if (!$this->isPost()) {
            redirect(is_logged_in() ? "dashboard.php" : "login.php");
        }
        csrf_check("dashboard.php");

        logout_user();
        flash("success", "You have been signed out.");
        redirect("login.php");
    }

    // -----------------------------------------------------------------
    // FIRST-TIME SETUP
    // -----------------------------------------------------------------
    public function setup(): void
    {
        $users = new User($this->db);

        // Already set up? Then this page is closed.
        if ($users->count() > 0) {
            flash("info", "The administrator account already exists. Please sign in.");
            redirect("login.php");
        }

        $errors   = [];
        $fullName = "";
        $username = "";

        if ($this->isPost()) {
            csrf_check("setup_admin.php");

            $fullName = $this->input("full_name");
            $username = strtolower($this->input("username"));
            $password = (string) ($_POST["password"] ?? "");
            $confirm  = (string) ($_POST["confirm_password"] ?? "");

            if ($fullName === "" || mb_strlen($fullName) > 100) {
                $errors[] = "Please enter your full name (up to 100 characters).";
            }
            if (!preg_match('/^[a-z0-9._]{3,50}$/', $username)) {
                $errors[] = "Username must be 3–50 characters: letters, numbers, dot or underscore.";
            }
            if (strlen($password) < MIN_PASSWORD_LENGTH) {
                $errors[] = "Password must be at least " . MIN_PASSWORD_LENGTH . " characters.";
            }
            if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
                $errors[] = "Password must contain at least one letter and one number.";
            }
            if ($password !== $confirm) {
                $errors[] = "The two passwords do not match.";
            }

            if (!$errors) {
                // Check again in case two people submit at the same time
                if ($users->count() > 0) {
                    flash("info", "The administrator account already exists. Please sign in.");
                    redirect("login.php");
                }

                $newId = $users->create($username, $fullName, $password, "admin");
                login_user(["user_id" => $newId, "username" => $username, "full_name" => $fullName, "role" => "admin"]);
                $users->touchLastLogin($newId);

                flash("success", "Welcome, " . $fullName . "! Your administrator account is ready.");
                redirect("dashboard.php");
            }
        }

        $this->view("auth/setup", [
            "pageTitle" => "First-time setup",
            "errors"    => $errors,
            "fullName"  => $fullName,
            "username"  => $username,
        ], "auth");
    }
}
