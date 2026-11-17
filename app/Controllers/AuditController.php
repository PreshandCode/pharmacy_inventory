<?php
// =====================================================================
//  AUDIT CONTROLLER  (page: audit.php)
//  View the system audit trail / activity log.
// =====================================================================

class AuditController extends Controller
{
    public function index(): void
    {
        $audit = new AuditLog($this->db);

        $actionFilter = mb_substr($this->query("action"), 0, 50);
        $search       = mb_substr($this->query("search"), 0, 100);
        $page         = (int) $this->query("page", "1");

        $result  = $audit->paginate($actionFilter, $search, ROWS_PER_PAGE, $page);
        $actions = $audit->actions();

        $params = [
            "action" => $actionFilter,
            "search" => $search,
        ];

        $this->view("audit/index", [
            "pageTitle"    => "Audit Trail",
            "rows"         => $result["rows"],
            "totalRows"    => $result["total"],
            "page"         => $result["page"],
            "totalPages"   => $result["pages"],
            "offset"       => $result["offset"],
            "actionFilter" => $actionFilter,
            "search"       => $search,
            "actions"      => $actions,
            "params"       => $params,
        ]);
    }
}
