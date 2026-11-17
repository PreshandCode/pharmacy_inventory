<?php
// =====================================================================
//  ACCOUNT CONTROLLER  (page: account.php)
//  The signed-in user can change their name and password.
// =====================================================================

class AccountController extends Controller
{
    public function index(): void
    {
        $users  = new User($this->db);
        $userId = (int) current_user_id();

        if ($this->isPost()) {
            csrf_check("account.php");
            $action = $this->input("action");

            if ($action === "profile") {
                $this->updateProfile($users, $userId);
            } elseif ($action === "password") {
                $this->updatePassword($users, $userId);
            }
            redirect("account.php");
        }

        $this->view("account/index", [
            "pageTitle" => "My account",
            "account"   => $users->find($userId),
        ]);
    }

    private function updateProfile(User $users, int $userId): void
    {
        $fullName = $this->input("full_name");

        if ($fullName === "" || mb_strlen($fullName) > 100) {
            flash("error", "Please enter your full name (up to 100 characters).");
            return;
        }
        $users->updateName($userId, $fullName);
        $_SESSION["user"]["full_name"] = $fullName;
        flash("success", "Your profile was updated.");
    }

    private function updatePassword(User $users, int $userId): void
    {
        $current = (string) ($_POST["current_password"] ?? "");
        $new     = (string) ($_POST["new_password"] ?? "");
        $confirm = (string) ($_POST["confirm_password"] ?? "");
        $hash    = $users->passwordHash($userId);

        if ($hash === null || !password_verify($current, $hash)) {
            flash("error", "Your current password is incorrect.");
        } elseif (strlen($new) < MIN_PASSWORD_LENGTH) {
            flash("error", "New password must be at least " . MIN_PASSWORD_LENGTH . " characters.");
        } elseif (!preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new)) {
            flash("error", "New password must contain at least one letter and one number.");
        } elseif ($new !== $confirm) {
            flash("error", "The new passwords do not match.");
        } elseif (password_verify($new, $hash)) {
            flash("error", "The new password must be different from the current one.");
        } else {
            $users->updatePassword($userId, $new);
            (new AuditLog($this->db))->log("Password Change", "Changed account password");
            session_regenerate_id(true);
            flash("success", "Your password was changed.");
        }
    }
}
