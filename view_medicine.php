<?php
// MEDICINE DETAILS - one medicine and its stock history.
// MVC entry point: this file only starts the controller.
//   Controller: app/Controllers/MedicineController.php  ->  show()
require_once __DIR__ . "/includes/bootstrap.php";

(new MedicineController($conn))->show();
