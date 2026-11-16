<?php
// MEDICINES - list with search, filters, sorting and pages.
// MVC entry point: this file only starts the controller.
//   Controller: app/Controllers/MedicineController.php  ->  index()
require_once __DIR__ . "/includes/bootstrap.php";

(new MedicineController($conn))->index();
