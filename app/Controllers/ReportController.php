<?php
// =====================================================================
//  REPORT CONTROLLER  (page: reports.php)
//    index()   show inventory / expiry / stock-movement reports
//    export()  download the active report as a CSV file
//
//  All data comes from the Report model (app/Models/Report.php).
// =====================================================================

class ReportController extends Controller
{
    public function index(): void
    {
        $type  = $this->query("type", "inventory");
        $valid = ["inventory", "expiry", "stock_movement"];
        if (!in_array($type, $valid, true)) {
            $type = "inventory";
        }

        // Date range for stock-movement report (default: last 30 days)
        $from = $this->query("from") ?: date("Y-m-d", strtotime("-30 days"));
        $to   = $this->query("to")   ?: date("Y-m-d");

        $model = new Report($this->db);

        // CSV export requested?
        if ($this->query("export") === "csv") {
            $this->export($model, $type, $from, $to);
            return; // export() calls exit
        }

        // Fetch report data
        $data = [
            "pageTitle" => "Reports",
            "type"      => $type,
            "from"      => $from,
            "to"        => $to,
        ];

        switch ($type) {
            case "inventory":
                $rows       = $model->inventoryReport();
                $grandTotal = 0;
                foreach ($rows as $r) {
                    $grandTotal += (float) $r["total_value"];
                }
                $data["rows"]       = $rows;
                $data["grandTotal"] = $grandTotal;
                break;

            case "expiry":
                $data["expiry"] = $model->expiryReport();
                break;

            case "stock_movement":
                $data["movements"] = $model->stockMovementReport($from, $to);
                break;
        }

        $this->view("reports/index", $data);
    }


    // -----------------------------------------------------------------
    // CSV EXPORT
    // -----------------------------------------------------------------
    private function export(Report $model, string $type, string $from, string $to): void
    {
        $filename = "report_" . $type . "_" . date("Y-m-d") . ".csv";

        // Clear any buffered output so the download is clean
        ob_end_clean();

        header("Content-Type: text/csv; charset=UTF-8");
        header("Content-Disposition: attachment; filename=\"" . $filename . "\"");

        $out = fopen("php://output", "w");

        switch ($type) {
            case "inventory":
                fputcsv($out, ["Product Name", "Category", "Stock Qty", "Unit", "Unit Price", "Total Value"]);
                $rows       = $model->inventoryReport();
                $grandTotal = 0;
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r["medicine_name"],
                        $r["category"],
                        $r["quantity"],
                        $r["unit"],
                        $r["unit_price"],
                        $r["total_value"],
                    ]);
                    $grandTotal += (float) $r["total_value"];
                }
                fputcsv($out, ["", "", "", "", "Grand Total", $grandTotal]);
                break;

            case "expiry":
                fputcsv($out, ["Product Name", "Batch Number", "Quantity", "Unit", "Expiry Date", "Status"]);
                $groups = $model->expiryReport();
                foreach (["expired" => "Expired", "within30" => "Within 30 Days", "within90" => "Within 31-90 Days"] as $key => $label) {
                    foreach ($groups[$key] as $r) {
                        $ex = expiry_status($r["expiry_date"]);
                        fputcsv($out, [
                            $r["medicine_name"],
                            $r["batch_number"],
                            $r["quantity"],
                            $r["unit"],
                            $r["expiry_date"],
                            $ex["label"] . " (" . $label . ")",
                        ]);
                    }
                }
                break;

            case "stock_movement":
                fputcsv($out, ["Date", "Product", "Type", "Quantity", "Before", "After", "Note", "User"]);
                $rows = $model->stockMovementReport($from, $to);
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r["created_at"],
                        $r["medicine_name"],
                        ucfirst($r["movement_type"]),
                        $r["quantity"],
                        $r["quantity_before"],
                        $r["quantity_after"],
                        $r["note"],
                        $r["full_name"],
                    ]);
                }
                break;
        }

        fclose($out);
        exit;
    }
}
