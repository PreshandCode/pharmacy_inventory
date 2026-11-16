<?php
// ADD MEDICINE - shows the form and saves a new medicine.
// MVC entry point: this file only starts the controller.
//   Controller: app/Controllers/MedicineController.php  ->  create()
require_once __DIR__ . "/includes/bootstrap.php";

(new MedicineController($conn))->create();
