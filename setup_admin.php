<?php
// FIRST-TIME SETUP - create the first admin account (only while no accounts exist).
// MVC entry point: this file only starts the controller.
//   Controller: app/Controllers/AuthController.php  ->  setup()
require_once __DIR__ . "/includes/bootstrap.php";

(new AuthController($conn))->setup();
