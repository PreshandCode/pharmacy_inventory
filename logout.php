<?php
// SIGN OUT (Sign out button only)
// MVC entry point: this file only starts the controller.
//   Controller: app/Controllers/AuthController.php  ->  logout()
require_once __DIR__ . "/includes/bootstrap.php";

(new AuthController($conn))->logout();
