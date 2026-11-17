<?php
// =====================================================================
//  STOCK MOVEMENT MODEL  (table: stock_movements)
//  The history of every stock change: in, out or adjustment.
// =====================================================================

class StockMovement extends Model
{
    // Reasons offered on the Stock page for each kind of change.
    // Only these values are accepted when the form is submitted.
    public const REASONS = [
        "in" => [
            "Delivery from supplier",
            "Customer return",
            "Transfer from another branch",
            "Other",
        ],
        "out" => [
            "Dispensed / sold",
            "Expired - disposed",
            "Damaged / contaminated",
            "Returned to supplier",
            "Transfer to another branch",
            "Other",
        ],
        "set" => [
            "Physical count correction",
            "Data entry correction",
            "Other",
        ],
    ];

    // Record one change.  $type: "in", "out" or "adjust"
    public function log(int $medicineId, string $medicineName, string $type, int $before, int $after, string $note, ?int $userId): void
    {
        $quantity = abs($after - $before);
        $note = mb_substr($note, 0, 255);

        $this->exec(
            "INSERT INTO stock_movements
                (medicine_id, medicine_name, movement_type, quantity, quantity_before, quantity_after, note, user_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            "issiiisi",
            [$medicineId, $medicineName, $type, $quantity, $before, $after, $note, $userId]
        );
    }

    // Units added / removed per month since $startDate.
    // Returns ["2026-11" => ["in" => 50, "out" => 20], ...]
    public function monthlyTotals(string $startDate, ?int $userId = null): array
    {
        $userId = $userId ?? current_user_id();
        $userWhere = $userId ? "AND (sm.user_id = ? OR m.user_id = ?)" : "";
        $types = $userId ? "sii" : "s";
        $vals = $userId ? [$startDate, $userId, $userId] : [$startDate];

        $rows = $this->all(
            "SELECT DATE_FORMAT(sm.created_at, '%Y-%m') AS ym,
                    SUM(GREATEST(sm.quantity_after - sm.quantity_before, 0)) AS units_in,
                    SUM(GREATEST(sm.quantity_before - sm.quantity_after, 0)) AS units_out
             FROM stock_movements sm
             LEFT JOIN medicines m ON m.medicine_id = sm.medicine_id
             WHERE sm.created_at >= ? $userWhere GROUP BY ym",
            $types,
            $vals
        );
        $totals = [];
        foreach ($rows as $r) {
            $totals[$r["ym"]] = ["in" => (int) $r["units_in"], "out" => (int) $r["units_out"]];
        }
        return $totals;
    }

    // One page of the full history, newest first.  $type: "", "in", "out" or "adjust"
    public function paginate(string $type, int $perPage, int $page, ?int $userId = null): array
    {
        $userId = $userId ?? current_user_id();
        $whereClauses = [];
        $types = "";
        $values = [];

        if ($type !== "") {
            $whereClauses[] = "sm.movement_type = ?";
            $types .= "s";
            $values[] = $type;
        }
        if ($userId) {
            $whereClauses[] = "(sm.user_id = ? OR m.user_id = ?)";
            $types .= "ii";
            $values[] = $userId;
            $values[] = $userId;
        }
        $where = $whereClauses ? "WHERE " . implode(" AND ", $whereClauses) : "";

        $total = (int) $this->one(
            "SELECT COUNT(*) AS c
             FROM stock_movements sm
             LEFT JOIN medicines m ON m.medicine_id = sm.medicine_id
             $where",
            $types,
            $values
        )["c"];
        $pages = max(1, (int) ceil($total / $perPage));
        $page  = min(max(1, $page), $pages);

        $rows = $this->all(
            "SELECT sm.*, u.full_name
             FROM stock_movements sm
             LEFT JOIN medicines m ON m.medicine_id = sm.medicine_id
             LEFT JOIN users u ON u.user_id = sm.user_id
             $where
             ORDER BY sm.created_at DESC, sm.movement_id DESC
             LIMIT ? OFFSET ?",
            $types . "ii",
            array_merge($values, [$perPage, ($page - 1) * $perPage])
        );

        return ["rows" => $rows, "total" => $total, "page" => $page, "pages" => $pages];
    }

    // Latest changes for one medicine
    public function forMedicine(int $medicineId, int $limit = 15): array
    {
        return $this->all(
            "SELECT sm.*, u.full_name
             FROM stock_movements sm
             LEFT JOIN users u ON u.user_id = sm.user_id
             WHERE sm.medicine_id = ?
             ORDER BY sm.created_at DESC, sm.movement_id DESC
             LIMIT ?",
            "ii",
            [$medicineId, $limit]
        );
    }
}
