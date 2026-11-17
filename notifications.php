<?php
require_once __DIR__ . "/includes/bootstrap.php";
(new NotificationController($conn))->index();
