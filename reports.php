<?php
// REPORTS - inventory valuation, expiry tracking and stock movement logs.
// MVC entry point: this file only starts the controller.
//   Controller: app/Controllers/ReportController.php  ->  index()
require_once __DIR__ . "/includes/bootstrap.php";

(new ReportController($conn))->index();
