<?php
// =====================================================================
//  USER MODEL  (table: users)
//  Login accounts. Passwords are only ever stored as secure hashes.
// =====================================================================

class User extends Model
{
    // How many accounts exist. Shows a clear message if upgrade.sql was not run.
    public function count(): int
    {
        try {
            return (int) $this->one("SELECT COUNT(*) AS total FROM users")["total"];
        } catch (mysqli_sql_exception $e) {
            throw new AppException("The 'users' table was not found. Please run database/upgrade.sql in phpMyAdmin first.");
        }
    }

    // Account details (without the password hash)
    public function find(int $userId): ?array
    {
        return $this->one(
            "SELECT user_id, username, full_name, role, created_at, last_login FROM users WHERE user_id = ?",
            "i",
            [$userId]
        );
    }

    // Used only for signing in (includes the password hash)
    public function findForLogin(string $username): ?array
    {
        return $this->one(
            "SELECT user_id, username, full_name, password_hash, role FROM users WHERE username = ?",
            "s",
            [$username]
        );
    }

    public function passwordHash(int $userId): ?string
    {
        $row = $this->one("SELECT password_hash FROM users WHERE user_id = ?", "i", [$userId]);
        return $row["password_hash"] ?? null;
    }

    // Create an account and return its new ID
    public function create(string $username, string $fullName, string $password, string $role): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->exec(
            "INSERT INTO users (username, full_name, password_hash, role) VALUES (?, ?, ?, ?)",
            "ssss",
            [$username, $fullName, $hash, $role]
        );
        return (int) $this->db->insert_id;
    }

    public function updateName(int $userId, string $fullName): void
    {
        $this->exec("UPDATE users SET full_name = ? WHERE user_id = ?", "si", [$fullName, $userId]);
    }

    public function updatePassword(int $userId, string $newPassword): void
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->exec("UPDATE users SET password_hash = ? WHERE user_id = ?", "si", [$hash, $userId]);
    }

    public function touchLastLogin(int $userId): void
    {
        $this->exec("UPDATE users SET last_login = NOW() WHERE user_id = ?", "i", [$userId]);
    }

    // -----------------------------------------------------------------
    // IP-BASED LOGIN LOCKOUT
    // Falls back to session-based tracking if the login_attempts table
    // has not been created yet (upgrade_3_security.sql not run).
    // -----------------------------------------------------------------

    /** Returns the number of seconds left in the lockout, or 0 if not locked. */
    public function lockoutSecondsLeft(string $ip): int
    {
        if ($ip === "") { return 0; }
        try {
            $row = $this->one(
                "SELECT GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), locked_until)) AS secs
                 FROM login_attempts WHERE ip_address = ? AND locked_until > NOW()",
                "s",
                [$ip]
            );
            return $row ? (int) $row["secs"] : 0;
        } catch (mysqli_sql_exception $e) {
            // Table not yet created — fall back to session
            return max(0, (int) ($_SESSION["login_locked_until"] ?? 0) - time());
        }
    }

    /**
     * Record one failed attempt for the IP.
     * Returns the number of attempts remaining before lockout (0 means just locked).
     */
    public function recordFailedAttempt(string $ip, int $maxAttempts, int $lockMinutes): int
    {
        if ($ip === "") {
            // Fallback: session-based
            $_SESSION["login_fails"] = (int) ($_SESSION["login_fails"] ?? 0) + 1;
            $left = $maxAttempts - (int) $_SESSION["login_fails"];
            if ($left <= 0) {
                $_SESSION["login_locked_until"] = time() + $lockMinutes * 60;
                $_SESSION["login_fails"] = 0;
            }
            return max(0, $left);
        }

        try {
            // Upsert: insert or increment the attempt counter
            $this->exec(
                "INSERT INTO login_attempts (ip_address, attempts, locked_until)
                    VALUES (?, 1, NULL)
                 ON DUPLICATE KEY UPDATE
                    attempts    = IF(locked_until IS NOT NULL AND locked_until > NOW(), attempts, attempts + 1),
                    locked_until = IF(attempts + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), locked_until)",
                "sii",
                [$ip, $maxAttempts, $lockMinutes]
            );
            $row = $this->one("SELECT attempts FROM login_attempts WHERE ip_address = ?", "s", [$ip]);
            $attempts = (int) ($row["attempts"] ?? 1);
            return max(0, $maxAttempts - $attempts);
        } catch (mysqli_sql_exception $e) {
            // Table not yet created — fallback
            $_SESSION["login_fails"] = (int) ($_SESSION["login_fails"] ?? 0) + 1;
            $left = $maxAttempts - (int) $_SESSION["login_fails"];
            if ($left <= 0) {
                $_SESSION["login_locked_until"] = time() + $lockMinutes * 60;
                $_SESSION["login_fails"] = 0;
            }
            return max(0, $left);
        }
    }

    /** Clear the lockout record after a successful login. */
    public function clearLoginAttempts(string $ip): void
    {
        if ($ip === "") { return; }
        try {
            $this->exec("DELETE FROM login_attempts WHERE ip_address = ?", "s", [$ip]);
        } catch (mysqli_sql_exception $e) {
            // Table doesn't exist yet — clear session fallback
        }
        unset($_SESSION["login_fails"], $_SESSION["login_locked_until"]);
    }
}
