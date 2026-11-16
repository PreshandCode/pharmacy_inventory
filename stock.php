<?php
// STOCK - record stock in / out / count corrections, and see the history.
// MVC entry point: this file only starts the controller.
//   Controller: app/Controllers/StockController.php  ->  store() for the form, index() for the page
require_once __DIR__ . "/includes/bootstrap.php";

$controller = new StockController($conn);
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $controller->store();
} else {
    $controller->index();
}
