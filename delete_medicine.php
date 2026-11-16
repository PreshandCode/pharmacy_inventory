<?php
// DELETE MEDICINE - admins only.
// MVC entry point: this file only starts the controller.
//   Controller: app/Controllers/MedicineController.php  ->  destroy()
require_once __DIR__ . "/includes/bootstrap.php";

(new MedicineController($conn))->destroy();
