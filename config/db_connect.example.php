<?php
// =====================================================================
//  DATABASE CONNECTION - EXAMPLE FILE (safe to share, no real password)
//
//  How to use:
//    1. Copy this file and name the copy  db_connect.php  (same folder)
//    2. Put your real InfinityFree MySQL details in db_connect.php
//
//  db_connect.php is listed in .gitignore, so your real password is
//  never committed to Git or uploaded to GitHub.
//  (On your own computer, config/db_local.php is used instead.)
// =====================================================================

$host     = "sqlXXX.infinityfree.com";              // MySQL host name
$username = "if0_XXXXXXXX";                         // MySQL user name
$password = "YOUR_DATABASE_PASSWORD";               // MySQL password
$database = "if0_XXXXXXXX_pharmacyinventory_db";    // database name

$conn = new mysqli($host, $username, $password, $database);
