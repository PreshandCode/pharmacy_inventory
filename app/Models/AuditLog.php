<?php
// =====================================================================
//  AUDIT LOG MODEL  (table: audit_logs)
//  Records user actions for accountability and security tracking.
//  Gracefully degrades if the audit_logs table has not been created yet.
// =====================================================================

class AuditLog extends Model
{
    /**
     * Record an action. Silently fails if table doesn't exist yet.
     */
    public function log(string $action, string $details = "", ?int $userId = null, ?string $username = null, ?string $ip = null): void
    {
        if ($username === null) {
            $user = current_user();
            $userId   = $user ? (int) $user["user_id"] : $userId;
            $username = $user ? $user["username"] : "System";
        }
        $ip = $ip ?: ($_SERVER["REMOTE_ADDR"] ?? "");

        try {
            $this->exec(
                "INSERT INTO audit_logs (user_id, username, action, details, ip_address) VALUES (?, ?, ?, ?, ?)",
                "issss",
                [$userId, $username, $action, mb_substr($details, 0, 2000), $ip]
            );
        } catch (\mysqli_sql_exception $e) {
            // Table not created yet — silently ignore
        }
    }

    /**
     * Paginated list of audit logs, newest first.
     */
    public function paginate(string $actionFilter, string $search, int $perPage, int $page): array
    {
        $where  = [];
        $types  = "";
        $vals   = [];

        if ($actionFilter !== "") {
            $where[] = "a.action = ?";
            $types  .= "s";
            $vals[]  = $actionFilter;
        }
        if ($search !== "") {
            $where[] = "(a.username LIKE ? OR a.action LIKE ? OR a.details LIKE ?)";
            $like    = "%" . addcslashes($search, "\\%_") . "%";
            $types  .= "sss";
            array_push($vals, $like, $like, $like);
        }

        $whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

        try {
            $total = (int) $this->one("SELECT COUNT(*) AS c FROM audit_logs a $whereSql", $types, $vals)["c"];
        } catch (\mysqli_sql_exception $e) {
            return ["rows" => [], "total" => 0, "page" => 1, "pages" => 1, "offset" => 0];
        }

        $pages  = max(1, (int) ceil($total / $perPage));
        $page   = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $rows = $this->all(
            "SELECT a.*, u.full_name
             FROM audit_logs a
             LEFT JOIN users u ON u.user_id = a.user_id
             $whereSql
             ORDER BY a.created_at DESC, a.log_id DESC
             LIMIT ? OFFSET ?",
            $types . "ii",
            array_merge($vals, [$perPage, $offset])
        );

        return ["rows" => $rows, "total" => $total, "page" => $page, "pages" => $pages, "offset" => $offset];
    }

    /**
     * Distinct action values for the filter dropdown.
     */
    public function actions(): array
    {
        try {
            return array_column(
                $this->all("SELECT DISTINCT action FROM audit_logs ORDER BY action"),
                "action"
            );
        } catch (\mysqli_sql_exception $e) {
            return [];
        }
    }
}
