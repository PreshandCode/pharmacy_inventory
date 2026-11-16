<?php
// =====================================================================
//  BASE MODEL
//  Every model (Medicine, StockMovement, User) extends this class.
//  It holds the database connection and small helpers for running
//  prepared statements, so the models stay short and safe.
// =====================================================================

abstract class Model
{
    protected mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    // Run a prepared statement and return ALL rows as arrays.
    //   $this->all("SELECT * FROM medicines WHERE category = ?", "s", [$cat]);
    protected function all(string $sql, string $types = "", array $values = []): array
    {
        $stmt = $this->run($sql, $types, $values);
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    // Run a prepared statement and return the FIRST row (or null).
    protected function one(string $sql, string $types = "", array $values = []): ?array
    {
        $stmt = $this->run($sql, $types, $values);
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    // Run a prepared statement that changes data (INSERT/UPDATE/DELETE).
    // Returns the number of affected rows.
    protected function exec(string $sql, string $types = "", array $values = []): int
    {
        $stmt = $this->run($sql, $types, $values);
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }

    // Prepare + bind + execute. All values are sent separately from the
    // SQL, which is what prevents SQL injection.
    private function run(string $sql, string $types, array $values): mysqli_stmt
    {
        $stmt = $this->db->prepare($sql);
        if ($types !== "") {
            $stmt->bind_param($types, ...$values);
        }
        $stmt->execute();
        return $stmt;
    }
}
