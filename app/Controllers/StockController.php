<?php
// =====================================================================
//  STOCK CONTROLLER  (page: stock.php)
//    index()  the Update stock form + history
//    store()  save a stock in / out / set count
//
//  The safety rules (never below 0, no stock into an expired batch,
//  row locking) are in Medicine::changeStock().
//  stock.php?id=5 opens the page with product #5 already selected.
// =====================================================================

class StockController extends Controller
{
    public function index(): void
    {
        $reasons = StockMovement::REASONS;

        // Values typed before an error (if any), otherwise from the address bar
        $old = $_SESSION["stock_old"] ?? null;
        unset($_SESSION["stock_old"]);

        $selectedId = (int) ($old["medicine_id"] ?? $this->query("id"));
        $formType   = $old["type"] ?? ($this->query("type") !== "" ? $this->query("type") : "in");
        if (!isset($reasons[$formType])) {
            $formType = "in";
        }
        $formReason = $old["reason"] ?? "";
        if ($formReason === "" && $this->query("reason") === "expired") {   // "Dispose" button on the Expiry page
            $formType   = "out";
            $formReason = "Expired - disposed";
        }

        // History, filtered by type, 15 per page
        $histType = in_array($this->query("type_filter"), ["in", "out", "adjust"], true) ? $this->query("type_filter") : "";
        $history  = (new StockMovement($this->db))->paginate($histType, 15, (int) $this->query("page", "1"));

        $this->view("stock/index", [
            "pageTitle"    => "Stock",
            "pageScripts"  => ["includes/js/stock.js"],
            "reasons"      => $reasons,
            "old"          => $old,
            "selectedId"   => $selectedId,
            "formType"     => $formType,
            "formReason"   => $formReason,
            "allMedicines" => (new Medicine($this->db))->allForSelect(),
            "histType"     => $histType,
            "history"      => $history["rows"],
            "histTotal"    => $history["total"],
            "histPage"     => $history["page"],
            "histPages"    => $history["pages"],
            "showMedicine" => true,
            "linkParams"   => ["id" => $selectedId ?: "", "type_filter" => $histType],
        ]);
    }

    public function store(): void
    {
        csrf_check("stock.php");

        $medicineId = (int) $this->input("medicine_id");
        $type       = $this->input("type");
        $qtyText    = $this->input("quantity");
        $reason     = $this->input("reason");
        $note       = $this->input("note");
        $reasons    = StockMovement::REASONS;

        $backUrl = "stock.php" . ($medicineId > 0 ? "?id=" . $medicineId : "");

        // Remember what was typed, in case the form must be shown again
        $_SESSION["stock_old"] = ["medicine_id" => $medicineId, "type" => $type, "quantity" => $qtyText, "reason" => $reason, "note" => $note];

        // ---- Check the form ----
        $error = "";
        if ($medicineId <= 0) {
            $error = "Please choose a product.";
        } elseif (!isset($reasons[$type])) {
            $error = "Please choose Stock In, Stock Out or Set Quantity.";
        } elseif (!preg_match('/^\d+$/', $qtyText)) {
            $error = "Quantity must be a whole number.";
        } elseif ($type !== "set" && (int) $qtyText < 1) {
            $error = "Quantity must be at least 1.";
        } elseif ((int) $qtyText > MED_MAX_QUANTITY) {
            $error = "Quantity cannot be more than " . number_format(MED_MAX_QUANTITY) . ".";
        } elseif (!in_array($reason, $reasons[$type], true)) {
            $error = "Please choose a reason.";
        } elseif ($reason === "Other" && $note === "") {
            $error = "Please describe the reason in the note when you choose \"Other\".";
        } elseif (mb_strlen($note) > 200) {
            $error = "The note must be 200 characters or fewer.";
        }
        if ($error !== "") {
            flash("error", $error);
            redirect($backUrl);
        }

        // ---- Apply the change (the model enforces the stock rules) ----
        $qty    = (int) $qtyText;
        $result = (new Medicine($this->db))->changeStock(
            $medicineId, $type, $qty, $reason . ($note !== "" ? ": " . $note : ""), current_user_id()
        );

        if (isset($result["error"])) {
            flash("error", $result["error"]);
            redirect(!empty($result["missing"]) ? "stock.php" : $backUrl);
        }

        unset($_SESSION["stock_old"]);

        // ---- Tell the user what happened ----
        $name   = "\"" . $result["name"] . "\"";
        $unit   = $result["unit"];
        $before = $result["before"];
        $after  = $result["after"];
        if ($type === "in") {
            flash("success", "Added " . qty_unit($qty, $unit) . " to " . $name . ". New stock: " . qty_unit($after, $unit) . ".");
        } elseif ($type === "out") {
            flash("success", "Removed " . qty_unit($qty, $unit) . " from " . $name . ". New stock: " . qty_unit($after, $unit) . ".");
        } else {
            flash("success", "Stock of " . $name . " set from " . number_format($before) . " to " . qty_unit($after, $unit) . ".");
        }

        if ($after === 0) {
            flash("warning", $name . " is now OUT OF STOCK.");
        } elseif ($after < LOW_STOCK_THRESHOLD && $before >= LOW_STOCK_THRESHOLD) {
            flash("warning", $name . " is now LOW on stock (" . qty_unit($after, $unit) . " left).");
        }

        redirect("stock.php?id=" . $medicineId);
    }
}
