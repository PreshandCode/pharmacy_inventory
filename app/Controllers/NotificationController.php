<?php
// =====================================================================
//  NOTIFICATION CONTROLLER  (page: notifications.php)
//  Shows all system notifications, live alerts, and activity logs.
// =====================================================================

class NotificationController extends Controller
{
    public function index(): void
    {
        $model = new Notification($this->db);
        $result = $model->getAllNotifications(25);

        $filter = $this->query("type", "all");

        $items = $result["items"];
        if ($filter === "alerts") {
            $items = array_filter($items, fn($n) => in_array($n["type"], ["danger", "orange", "warning"], true));
        } elseif ($filter === "activity") {
            $items = array_filter($items, fn($n) => in_array($n["type"], ["success", "secondary"], true));
        }

        $this->view("notifications/index", [
            "pageTitle"     => "Notifications",
            "notifications" => $items,
            "totalCount"    => $result["total"],
            "urgentCount"   => $result["urgentCount"],
            "currentFilter" => $filter,
        ]);
    }
}
