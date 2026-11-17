<?php
// =====================================================================
//  EXPIRY CONTROLLER  (page: expiry.php)
//  Products that are expired or expiring soon (products without an
//  expiry date are left out), with status tabs,
//  search and category filter.
// =====================================================================

class ExpiryController extends Controller
{
    public function index(): void
    {
        $warnDays     = EXPIRY_WARNING_DAYS;
        $criticalDays = min(EXPIRY_CRITICAL_DAYS, EXPIRY_WARNING_DAYS);
        $model        = new Medicine($this->db);

        // Tab labels with their counts
        $counts = $model->expiryCounts($criticalDays, $warnDays);
        $tabs = [
            "attention" => ["Needs attention", (int) $counts["expired"] + (int) $counts["critical"] + (int) $counts["soon"]],
            "expired"   => ["Expired", (int) $counts["expired"]],
            "critical"  => ["Within " . $criticalDays . " days", (int) $counts["critical"]],
            "soon"      => [($criticalDays + 1) . "–" . $warnDays . " days", (int) $counts["soon"]],
            "all"       => ["All", (int) $counts["total"]],
        ];

        $status   = array_key_exists($this->query("status"), $tabs) ? $this->query("status") : "attention";
        $search   = mb_substr($this->query("search"), 0, 100);
        $category = mb_substr($this->query("category"), 0, 50);

        $list = $model->expiryList($status, $search, $category, $criticalDays, $warnDays, ROWS_PER_PAGE, (int) $this->query("page", "1"));

        $this->view("expiry/index", [
            "pageTitle"  => "Expiry",
            "warnDays"   => $warnDays,
            "tabs"       => $tabs,
            "status"     => $status,
            "search"     => $search,
            "category"   => $category,
            "categories" => $model->categories(),
            "rows"       => $list["rows"],
            "totalRows"  => $list["total"],
            "page"       => $list["page"],
            "totalPages" => $list["pages"],
            "offset"     => $list["offset"],
            "params"     => [
                "status"   => $status === "attention" ? "" : $status,
                "search"   => $search,
                "category" => $category,
            ],
        ]);
    }
}
