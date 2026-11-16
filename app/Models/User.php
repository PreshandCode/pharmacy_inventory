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
}
