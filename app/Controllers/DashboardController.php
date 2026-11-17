<?php
// =====================================================================
//  DASHBOARD CONTROLLER  (page: dashboard.php)
//  Collects the overview numbers from the models and shows the view.
// =====================================================================

class DashboardController extends Controller
{
    public function index(): void
    {
        $medicines = new Medicine($this->db);
        $movements = new StockMovement($this->db);

        $stats = $medicines->dashboardStats();

        // Stock activity: last 6 months including this one
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $key = date("Y-m", strtotime("first day of -$i month"));
            $months[$key] = ["short" => date("M", strtotime($key . "-01")), "label" => date("F Y", strtotime($key . "-01")), "in" => 0, "out" => 0];
        }
        foreach ($movements->monthlyTotals(array_key_first($months) . "-01 00:00:00") as $ym => $t) {
            if (isset($months[$ym])) {
                $months[$ym]["in"]  = $t["in"];
                $months[$ym]["out"] = $t["out"];
            }
        }

        $this->view("dashboard/index", [
            "pageTitle"    => "Dashboard",
            "useCharts"    => true,
            "pageScripts"  => ["includes/js/dashboard.js"],
            "low"          => LOW_STOCK_THRESHOLD,
            "days"         => EXPIRY_WARNING_DAYS,
            "stats"        => $stats,
            "restock"      => $medicines->needsRestock(5),
            "restockTotal" => (int) $stats["low_count"] + (int) $stats["out_count"],
            "expiring"     => $medicines->expiringSoon(5),
            "expiryTotal"  => (int) $stats["expired_count"] + (int) $stats["expiring_count"],
            "recent"       => $medicines->recentlyAdded(5),
            "typeCounts"   => $medicines->typeCounts(),
            "months"       => $months,
            "hasActivity"  => array_sum(array_column($months, "in")) + array_sum(array_column($months, "out")) > 0,
            "chartData"    => [
                "labels" => array_column(array_values($months), "short"),
                "full"   => array_column(array_values($months), "label"),
                "in"     => array_column(array_values($months), "in"),
                "out"    => array_column(array_values($months), "out"),
            ],
        ]);
    }
}
