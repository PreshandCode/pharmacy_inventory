<?php
// UPDATE MEDICINE - saves the edit form.
// MVC entry point: this file only starts the controller.
//   Controller: app/Controllers/MedicineController.php  ->  update()
require_once __DIR__ . "/includes/bootstrap.php";

(new MedicineController($conn))->update();
