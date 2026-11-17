<?php
// =====================================================================
//  REPORT MODEL
//  Read-only queries used by the Reports page. Each method returns the
//  data needed by one report tab (inventory, expiry, stock movement).
// =====================================================================

class Report extends Model
{
    // -----------------------------------------------------------------
    // INVENTORY REPORT
    // Every product with its total stock value (quantity × unit price).
    // -----------------------------------------------------------------
    public function inventoryReport(?int $userId = null): array
    {
        $userId = $userId ?? current_user_id();
        $userWhere = $userId ? "WHERE user_id = ?" : "";
        $types = $userId ? "i" : "";
        $vals = $userId ? [$userId] : [];

        return $this->all(
            "SELECT medicine_name, category, quantity, unit, unit_price,
                    (quantity * unit_price) AS total_value
               FROM medicines
               $userWhere
           ORDER BY category, medicine_name",
            $types,
            $vals
        );
    }

    // -----------------------------------------------------------------
    // EXPIRY REPORT
    // Products grouped into three urgency buckets.
    // -----------------------------------------------------------------
    public function expiryReport(?int $userId = null): array
    {
        $userId = $userId ?? current_user_id();
        $userWhere = $userId ? "AND user_id = ?" : "";
        $types = $userId ? "i" : "";
        $vals = $userId ? [$userId] : [];

        $expired = $this->all(
            "SELECT medicine_name, batch_number, quantity, unit, expiry_date
               FROM medicines
              WHERE expiry_date < CURDATE()
                AND expiry_date IS NOT NULL
                AND quantity > 0
                $userWhere
           ORDER BY expiry_date ASC",
            $types,
            $vals
        );

        $within30 = $this->all(
            "SELECT medicine_name, batch_number, quantity, unit, expiry_date
               FROM medicines
              WHERE expiry_date >= CURDATE()
                AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                $userWhere
           ORDER BY expiry_date ASC",
            $types,
            $vals
        );

        $within90 = $this->all(
            "SELECT medicine_name, batch_number, quantity, unit, expiry_date
               FROM medicines
              WHERE expiry_date > DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
                $userWhere
           ORDER BY expiry_date ASC",
            $types,
            $vals
        );

        return [
            "expired"  => $expired,
            "within30" => $within30,
            "within90" => $within90,
        ];
    }

    // -----------------------------------------------------------------
    // STOCK MOVEMENT REPORT
    // All stock changes in a date range, joined with the user who made
    // each change.
    // -----------------------------------------------------------------
    public function stockMovementReport(string $from, string $to, ?int $userId = null): array
    {
        $userId = $userId ?? current_user_id();
        $userWhere = $userId ? "AND (sm.user_id = ? OR m.user_id = ?)" : "";
        $types = $userId ? "ssii" : "ss";
        $vals = $userId ? [$from . " 00:00:00", $to . " 23:59:59", $userId, $userId] : [$from . " 00:00:00", $to . " 23:59:59"];

        return $this->all(
            "SELECT sm.created_at, sm.medicine_name, sm.movement_type,
                    sm.quantity, sm.quantity_before, sm.quantity_after,
                    sm.note, u.full_name
               FROM stock_movements sm
          LEFT JOIN medicines m ON m.medicine_id = sm.medicine_id
          LEFT JOIN users u ON u.user_id = sm.user_id
              WHERE sm.created_at BETWEEN ? AND ?
              $userWhere
           ORDER BY sm.created_at DESC",
            $types,
            $vals
        );
    }
}
