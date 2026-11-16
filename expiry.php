<?php
// EXPIRY - expired and expiring medicines.
// MVC entry point: this file only starts the controller.
//   Controller: app/Controllers/ExpiryController.php  ->  index()
require_once __DIR__ . "/includes/bootstrap.php";

(new ExpiryController($conn))->index();
