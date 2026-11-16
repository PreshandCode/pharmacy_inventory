<?php
// SIGN IN
// MVC entry point: this file only starts the controller.
//   Controller: app/Controllers/AuthController.php  ->  login()
require_once __DIR__ . "/includes/bootstrap.php";

(new AuthController($conn))->login();
