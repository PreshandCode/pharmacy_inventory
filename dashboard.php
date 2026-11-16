<?php
// DASHBOARD - overview of the inventory.
// MVC entry point: this file only starts the controller.
//   Controller: app/Controllers/DashboardController.php  ->  index()
require_once __DIR__ . "/includes/bootstrap.php";

(new DashboardController($conn))->index();
