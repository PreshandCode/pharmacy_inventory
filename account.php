<?php
// MY ACCOUNT - change your name and password.
// MVC entry point: this file only starts the controller.
//   Controller: app/Controllers/AccountController.php  ->  index()
require_once __DIR__ . "/includes/bootstrap.php";

(new AccountController($conn))->index();
