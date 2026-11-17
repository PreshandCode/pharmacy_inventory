<?php
require_once __DIR__ . "/includes/bootstrap.php";
(new AuthController($conn))->register();
