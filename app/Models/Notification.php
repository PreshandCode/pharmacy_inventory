<?php
// =====================================================================
//  NOTIFICATION MODEL
//  Gathers real-time system alerts (expiry, low/out of stock)
//  and recent operational activity (from audit_logs / stock_movements).
// =====================================================================

class Notification extends Model
{
    /**
     * Retrieve all active alerts and recent system events.
     * Returns an array with categorized notifications, count, and summary.
     */
    public function getAllNotifications(int $limitRecent = 10): array
    {
        $notifications = [];

        // 1. Expired Products (Critical Alert)
        $expired = $this->all(
            "SELECT medicine_id, medicine_name, batch_number, quantity, unit, expiry_date
             FROM medicines
             WHERE expiry_date IS NOT NULL AND expiry_date < CURDATE() AND quantity > 0
             ORDER BY expiry_date ASC LIMIT 5"
        );
        foreach ($expired as $m) {
            $notifications[] = [
                "id"       => "exp_" . $m["medicine_id"],
                "type"     => "danger",
                "category" => "Expired Stock",
                "icon"     => "bi-calendar-x",
                "title"    => $m["medicine_name"] . " has EXPIRED",
                "message"  => "Expired on " . format_date($m["expiry_date"]) . " (" . qty_unit($m["quantity"], $m["unit"]) . " in stock). Dispose immediately.",
                "link"     => "stock.php?id=" . $m["medicine_id"] . "&reason=expired",
                "time"     => $m["expiry_date"],
                "badge"    => "Expired",
                "urgent"   => true,
            ];
        }

        // 2. Products Expiring Soon (Within 30 Days)
        $criticalDays = min(EXPIRY_CRITICAL_DAYS, EXPIRY_WARNING_DAYS);
        $expiring = $this->all(
            "SELECT medicine_id, medicine_name, batch_number, quantity, unit, expiry_date,
                    DATEDIFF(expiry_date, CURDATE()) AS days_left
             FROM medicines
             WHERE expiry_date IS NOT NULL
               AND expiry_date >= CURDATE()
               AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
               AND quantity > 0
             ORDER BY expiry_date ASC LIMIT 5",
            "i",
            [$criticalDays]
        );
        foreach ($expiring as $m) {
            $daysLeft = (int) $m["days_left"];
            $daysLabel = $daysLeft === 0 ? "today" : "in " . $daysLeft . " day" . ($daysLeft === 1 ? "" : "s");
            $notifications[] = [
                "id"       => "soon_" . $m["medicine_id"],
                "type"     => "orange",
                "category" => "Expiring Soon",
                "icon"     => "bi-hourglass-split",
                "title"    => $m["medicine_name"] . " expires " . $daysLabel,
                "message"  => "Expires on " . format_date($m["expiry_date"]) . " (" . qty_unit($m["quantity"], $m["unit"]) . " remaining).",
                "link"     => "view_medicine.php?id=" . $m["medicine_id"],
                "time"     => $m["expiry_date"],
                "badge"    => $daysLeft === 0 ? "Today" : $daysLeft . "d left",
                "urgent"   => true,
            ];
        }

        // 3. Out of Stock Alert
        $outOfStock = $this->all(
            "SELECT medicine_id, medicine_name, unit
             FROM medicines
             WHERE quantity <= 0
             ORDER BY medicine_name ASC LIMIT 5"
        );
        foreach ($outOfStock as $m) {
            $notifications[] = [
                "id"       => "out_" . $m["medicine_id"],
                "type"     => "danger",
                "category" => "Out of Stock",
                "icon"     => "bi-slash-circle",
                "title"    => $m["medicine_name"] . " is Out of Stock",
                "message"  => "Inventory level depleted (0 available). Reorder or restock soon.",
                "link"     => "stock.php?id=" . $m["medicine_id"],
                "time"     => date("Y-m-d"),
                "badge"    => "0 Units",
                "urgent"   => true,
            ];
        }

        // 4. Low Stock Alert (quantity > 0 and < LOW_STOCK_THRESHOLD)
        $lowStock = $this->all(
            "SELECT medicine_id, medicine_name, quantity, unit
             FROM medicines
             WHERE quantity > 0 AND quantity < ?
             ORDER BY quantity ASC LIMIT 5",
            "i",
            [LOW_STOCK_THRESHOLD]
        );
        foreach ($lowStock as $m) {
            $notifications[] = [
                "id"       => "low_" . $m["medicine_id"],
                "type"     => "warning",
                "category" => "Low Stock",
                "icon"     => "bi-exclamation-triangle",
                "title"    => $m["medicine_name"] . " is Low on Stock",
                "message"  => "Only " . qty_unit($m["quantity"], $m["unit"]) . " remaining (threshold: " . LOW_STOCK_THRESHOLD . ").",
                "link"     => "stock.php?id=" . $m["medicine_id"],
                "time"     => date("Y-m-d"),
                "badge"    => "Low",
                "urgent"   => false,
            ];
        }

        // 5. Recent System Actions from audit_logs
        try {
            $recentLogs = $this->all(
                "SELECT log_id, username, action, details, created_at
                 FROM audit_logs
                 ORDER BY created_at DESC, log_id DESC
                 LIMIT ?",
                "i",
                [$limitRecent]
            );
            foreach ($recentLogs as $log) {
                $icon = "bi-activity";
                $type = "secondary";
                if ($log["action"] === "Add Product") {
                    $icon = "bi-plus-circle";
                    $type = "success";
                } elseif ($log["action"] === "Stock In") {
                    $icon = "bi-box-arrow-in-down";
                    $type = "success";
                } elseif ($log["action"] === "Stock Out") {
                    $icon = "bi-box-arrow-up";
                    $type = "orange";
                } elseif ($log["action"] === "Delete Product") {
                    $icon = "bi-trash3";
                    $type = "danger";
                } elseif ($log["action"] === "Edit Product" || $log["action"] === "Stock Correction") {
                    $icon = "bi-pencil-square";
                    $type = "warning";
                }

                $notifications[] = [
                    "id"       => "log_" . $log["log_id"],
                    "type"     => $type,
                    "category" => $log["action"],
                    "icon"     => $icon,
                    "title"    => $log["action"] . " by " . ($log["username"] ?: "System"),
                    "message"  => $log["details"] ?: "Action recorded in audit log.",
                    "link"     => "audit.php?search=" . urlencode($log["action"]),
                    "time"     => $log["created_at"],
                    "badge"    => "Activity",
                    "urgent"   => false,
                ];
            }
        } catch (\mysqli_sql_exception $e) {
            // If audit_logs table isn't created yet, fall back to stock_movements
            $recentMoves = $this->all(
                "SELECT sm.movement_id, sm.movement_type, sm.medicine_name, sm.quantity, sm.note, sm.created_at, u.full_name
                 FROM stock_movements sm
                 LEFT JOIN users u ON u.user_id = sm.user_id
                 ORDER BY sm.created_at DESC, sm.movement_id DESC
                 LIMIT ?",
                "i",
                [$limitRecent]
            );
            foreach ($recentMoves as $move) {
                $notifications[] = [
                    "id"       => "move_" . $move["movement_id"],
                    "type"     => $move["movement_type"] === "in" ? "success" : ($move["movement_type"] === "out" ? "orange" : "warning"),
                    "category" => "Stock " . ucfirst($move["movement_type"]),
                    "icon"     => "bi-arrow-left-right",
                    "title"    => "Stock " . ucfirst($move["movement_type"]) . ": " . $move["medicine_name"],
                    "message"  => number_format((int)$move["quantity"]) . " units updated. " . ($move["note"] ?: ""),
                    "link"     => "stock.php",
                    "time"     => $move["created_at"],
                    "badge"    => "Movement",
                    "urgent"   => false,
                ];
            }
        }

        $urgentCount = count(array_filter($notifications, fn($n) => !empty($n["urgent"])));

        return [
            "items"       => $notifications,
            "total"       => count($notifications),
            "urgentCount" => $urgentCount,
        ];
    }
}
