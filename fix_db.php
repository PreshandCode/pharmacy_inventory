<?php
// =====================================================================
//  DATABASE AUTO-DIAGNOSTIC & REPAIR TOOL
//  Run this page directly in your browser:
//  http://precious.ifree.page/fix_db.php  or  http://localhost/pharmacy_inventory/fix_db.php
// =====================================================================

error_reporting(E_ALL);
ini_set("display_errors", "1");

$serverName = $_SERVER["SERVER_NAME"] ?? "";
$isLocal = in_array($serverName, ["localhost", "127.0.0.1", "::1"], true) || (PHP_SAPI === "cli" && is_file(__DIR__ . "/config/db_local.php"));

if ($isLocal && is_file(__DIR__ . "/config/db_local.php")) {
    require __DIR__ . "/config/db_local.php";
} else {
    require __DIR__ . "/config/db_connect.php";
}

$conn->set_charset("utf8mb4");

$messages = [];
$hasErrors = false;

// Handle password reset
$resetNotice = "";
$resetStatus = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["reset_user"])) {
    $rUser = trim($_POST["reset_user"] ?? "");
    $rPass = (string) ($_POST["reset_pass"] ?? "");
    if ($rUser !== "" && strlen($rPass) >= 8) {
        $rHash = password_hash($rPass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
        $stmt->bind_param("ss", $rHash, $rUser);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $conn->query("DELETE FROM login_attempts");
            $resetStatus = "success";
            $resetNotice = "Password for user '<strong>" . htmlspecialchars($rUser) . "</strong>' was reset to: <code>" . htmlspecialchars($rPass) . "</code>. Lockout counters were cleared. You can now sign in!";
        } else {
            $resetStatus = "danger";
            $resetNotice = "User '<strong>" . htmlspecialchars($rUser) . "</strong>' was not found in the database. Please check the spelling.";
        }
        $stmt->close();
    } else {
        $resetStatus = "warning";
        $resetNotice = "Please enter a valid username and a password with at least 8 characters.";
    }
}

function report(string $status, string $title, string $details = ""): void {
    global $messages, $hasErrors;
    if ($status === "fail") {
        $hasErrors = true;
    }
    $messages[] = [
        "status"  => $status, // 'ok', 'info', 'warn', 'fail'
        "title"   => $title,
        "details" => $details
    ];
}

// -------------------------------------------------------------
// 1. Check & Ensure Tables Exist
// -------------------------------------------------------------
try {
    // A. users
    $conn->query("CREATE TABLE IF NOT EXISTS `users` (
      `user_id`       INT(11)      NOT NULL AUTO_INCREMENT,
      `username`      VARCHAR(50)  NOT NULL,
      `full_name`     VARCHAR(100) NOT NULL,
      `password_hash` VARCHAR(255) NOT NULL,
      `role`          ENUM('admin','staff') NOT NULL DEFAULT 'staff',
      `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `last_login`    DATETIME     NULL,
      PRIMARY KEY (`user_id`),
      UNIQUE KEY `uq_username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    report("ok", "Table 'users'", "Verified / created successfully.");

    // B. login_attempts
    $conn->query("CREATE TABLE IF NOT EXISTS `login_attempts` (
      `ip_address`    VARCHAR(45)  NOT NULL,
      `attempts`      TINYINT      NOT NULL DEFAULT 0,
      `locked_until`  DATETIME     NULL,
      `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`ip_address`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    report("ok", "Table 'login_attempts'", "Verified / created successfully.");

    // C. audit_logs
    $conn->query("CREATE TABLE IF NOT EXISTS `audit_logs` (
      `log_id`      INT(11)      NOT NULL AUTO_INCREMENT,
      `user_id`     INT(11)      NULL,
      `username`    VARCHAR(50)  NOT NULL DEFAULT 'System',
      `action`      VARCHAR(50)  NOT NULL,
      `details`     TEXT         NULL,
      `ip_address`  VARCHAR(45)  NULL,
      `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`log_id`),
      KEY `idx_audit_created` (`created_at`),
      KEY `idx_audit_action` (`action`),
      KEY `idx_audit_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    report("ok", "Table 'audit_logs'", "Verified / created successfully.");

    // D. stock_movements
    $conn->query("CREATE TABLE IF NOT EXISTS `stock_movements` (
      `movement_id`     INT(11)      NOT NULL AUTO_INCREMENT,
      `medicine_id`     INT(11)      NULL,
      `medicine_name`   VARCHAR(100) NOT NULL,
      `movement_type`   ENUM('in','out','adjust') NOT NULL,
      `quantity`        INT(11)      NOT NULL,
      `quantity_before` INT(11)      NOT NULL,
      `quantity_after`  INT(11)      NOT NULL,
      `note`            VARCHAR(255) NULL,
      `user_id`         INT(11)      NULL,
      `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`movement_id`),
      KEY `idx_movement_medicine` (`medicine_id`),
      KEY `idx_movement_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    report("ok", "Table 'stock_movements'", "Verified / created successfully.");

} catch (\Throwable $e) {
    report("fail", "Table creation error", $e->getMessage());
}

// -------------------------------------------------------------
// 2. Check & Update Columns in 'medicines' and 'stock_movements'
// -------------------------------------------------------------
try {
    // Helper function to check if column exists
    $hasColumn = function(string $table, string $column) use ($conn): bool {
        $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        return $res && $res->num_rows > 0;
    };

    // Helper function to check if index exists
    $hasIndex = function(string $table, string $indexName) use ($conn): bool {
        $res = $conn->query("SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'");
        return $res && $res->num_rows > 0;
    };

    // stock_movements.user_id
    if (!$hasColumn("stock_movements", "user_id")) {
        $conn->query("ALTER TABLE `stock_movements` ADD COLUMN `user_id` INT(11) NULL AFTER `note`");
        report("info", "Column 'stock_movements.user_id'", "Added column user_id to stock_movements.");
    } else {
        report("ok", "Column 'stock_movements.user_id'", "Already exists.");
    }

    // medicines columns
    if (!$hasColumn("medicines", "user_id")) {
        $conn->query("ALTER TABLE `medicines` ADD COLUMN `user_id` INT(11) NULL AFTER `medicine_id`");
        report("info", "Column 'medicines.user_id'", "Added column user_id to medicines.");
    } else {
        report("ok", "Column 'medicines.user_id'", "Already exists.");
    }

    if (!$hasColumn("medicines", "description")) {
        $conn->query("ALTER TABLE `medicines` ADD COLUMN `description` TEXT NULL AFTER `category`");
        report("info", "Column 'medicines.description'", "Added column description.");
    }

    if (!$hasColumn("medicines", "date_added")) {
        $conn->query("ALTER TABLE `medicines` ADD COLUMN `date_added` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `supplier`");
        report("info", "Column 'medicines.date_added'", "Added column date_added.");
    }

    if (!$hasColumn("medicines", "product_type")) {
        $conn->query("ALTER TABLE `medicines` ADD COLUMN `product_type` VARCHAR(40) NOT NULL DEFAULT 'Medicine' AFTER `medicine_name`");
        report("info", "Column 'medicines.product_type'", "Added column product_type.");
    }

    if (!$hasColumn("medicines", "unit")) {
        $conn->query("ALTER TABLE `medicines` ADD COLUMN `unit` VARCHAR(20) NULL AFTER `quantity`");
        report("info", "Column 'medicines.unit'", "Added column unit.");
    }

    if (!$hasColumn("medicines", "requires_rx")) {
        $conn->query("ALTER TABLE `medicines` ADD COLUMN `requires_rx` TINYINT(1) NOT NULL DEFAULT 0 AFTER `product_type`");
        report("info", "Column 'medicines.requires_rx'", "Added column requires_rx.");
    }

    if (!$hasColumn("medicines", "last_updated")) {
        $conn->query("ALTER TABLE `medicines` ADD COLUMN `last_updated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `date_added`");
        report("info", "Column 'medicines.last_updated'", "Added column last_updated.");
    }

    // Index idx_medicines_user
    if (!$hasIndex("medicines", "idx_medicines_user")) {
        $conn->query("ALTER TABLE `medicines` ADD INDEX `idx_medicines_user` (`user_id`)");
        report("info", "Index 'idx_medicines_user'", "Created index on medicines(user_id).");
    } else {
        report("ok", "Index 'idx_medicines_user'", "Already exists.");
    }

    // Drop old single-user unique index if it exists
    if ($hasIndex("medicines", "uq_medicine_batch")) {
        $conn->query("ALTER TABLE `medicines` DROP INDEX `uq_medicine_batch`");
        report("info", "Index 'uq_medicine_batch'", "Dropped old single-user unique key.");
    }

    // Add per-user unique index if missing
    if (!$hasIndex("medicines", "uq_user_medicine_batch")) {
        $conn->query("ALTER TABLE `medicines` ADD UNIQUE KEY `uq_user_medicine_batch` (`user_id`, `medicine_name`(100), `batch_number`(50))");
        report("info", "Index 'uq_user_medicine_batch'", "Created multi-user unique key.");
    } else {
        report("ok", "Index 'uq_user_medicine_batch'", "Already exists.");
    }

    // Assign existing medicines without user_id to the first user
    $firstUserRes = $conn->query("SELECT user_id FROM users ORDER BY user_id ASC LIMIT 1");
    if ($firstUserRes && $firstUserRes->num_rows > 0) {
        $firstUserId = (int) $firstUserRes->fetch_assoc()["user_id"];
        $updated = $conn->query("UPDATE `medicines` SET `user_id` = $firstUserId WHERE `user_id` IS NULL");
        $affected = $conn->affected_rows;
        if ($affected > 0) {
            report("info", "Associated Medicines", "Assigned $affected existing medicines to User ID #$firstUserId.");
        } else {
            report("ok", "Associated Medicines", "All medicines already have a user_id.");
        }
    } else {
        report("warn", "Users check", "No users exist in the users table yet. Register or set up an admin account.");
    }

} catch (\Throwable $e) {
    report("fail", "Column/Index update error", $e->getMessage());
}

// -------------------------------------------------------------
// 3. Test Dashboard Queries Live
// -------------------------------------------------------------
$appLoaded = false;
try {
    require_once __DIR__ . "/includes/bootstrap.php";
    $appLoaded = true;

    // Pick first user or null
    $testUser = 1;
    $uRes = $conn->query("SELECT user_id FROM users ORDER BY user_id ASC LIMIT 1");
    if ($uRes && $r = $uRes->fetch_assoc()) {
        $testUser = (int) $r["user_id"];
    }

    $medicinesModel = new Medicine($conn);
    $movementsModel = new StockMovement($conn);
    $notifModel     = new Notification($conn);

    // Test queries
    $stats = $medicinesModel->dashboardStats($testUser);
    report("ok", "Query: dashboardStats()", "Returned " . count($stats) . " metric fields.");

    $restock = $medicinesModel->needsRestock(5, $testUser);
    report("ok", "Query: needsRestock()", "Returned " . count($restock) . " rows.");

    $expiring = $medicinesModel->expiringSoon(5, $testUser);
    report("ok", "Query: expiringSoon()", "Returned " . count($expiring) . " rows.");

    $recent = $medicinesModel->recentlyAdded(5, $testUser);
    report("ok", "Query: recentlyAdded()", "Returned " . count($recent) . " rows.");

    $types = $medicinesModel->typeCounts($testUser);
    report("ok", "Query: typeCounts()", "Returned " . count($types) . " types.");

    $urgent = $medicinesModel->countUrgentExpiry($testUser);
    report("ok", "Query: countUrgentExpiry()", "Returned count: $urgent");

    $totals = $movementsModel->monthlyTotals(date("Y-m-01 00:00:00"), $testUser);
    report("ok", "Query: monthlyTotals()", "Returned " . count($totals) . " monthly buckets.");

    $notifs = $notifModel->getAllNotifications(10, $testUser);
    report("ok", "Query: getAllNotifications()", "Returned " . ($notifs["total"] ?? 0) . " notifications.");

} catch (\Throwable $e) {
    report("fail", "Query verification failed", $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Repair & Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; font-family: system-ui, sans-serif; color: #1e293b; padding: 40px 16px; }
        .card { border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .badge-ok { background: #dcfce7; color: #15803d; }
        .badge-info { background: #e0f2fe; color: #0369a1; }
        .badge-warn { background: #fef3c7; color: #b45309; }
        .badge-fail { background: #fee2e2; color: #b91c1c; }
        .item-row { display: flex; align-items: flex-start; gap: 12px; padding: 12px 16px; border-bottom: 1px solid #f1f5f9; }
        .item-row:last-child { border-bottom: none; }
    </style>
</head>
<body>
<div class="container" style="max-width: 680px;">
    <div class="card p-4 bg-white mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h4 mb-0 fw-bold">Database Repair & Health Check</h2>
            <span class="badge <?php echo $hasErrors ? 'bg-danger' : 'bg-success'; ?> px-3 py-2">
                <?php echo $hasErrors ? 'Issues Found' : 'All Clear'; ?>
            </span>
        </div>
        <p class="text-secondary small">
            This tool automatically checks and creates any missing database columns, tables, and indexes required for multi-tenant accounts, then tests all live dashboard queries.
        </p>

        <div class="border rounded-3 overflow-hidden mb-4">
            <?php foreach ($messages as $m): ?>
                <div class="item-row">
                    <span class="badge badge-<?php echo $m['status']; ?> mt-1">
                        <?php echo strtoupper($m['status']); ?>
                    </span>
                    <div class="flex-grow-1">
                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($m['title']); ?></div>
                        <?php if ($m['details']): ?>
                            <div class="small text-muted font-monospace" style="font-size: 12px;">
                                <?php echo htmlspecialchars($m['details']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (!$hasErrors): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                <div><strong>Success!</strong> All database structures and queries are verified and working properly.</div>
            </div>
            <a href="dashboard.php" class="btn btn-success w-100 py-2 fw-semibold mb-4">
                Go to Dashboard <i class="bi bi-arrow-right"></i>
            </a>
        <?php else: ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                <div><strong>Action Needed:</strong> One or more queries failed. See details above.</div>
            </div>
            <a href="fix_db.php" class="btn btn-primary w-100 py-2 fw-semibold mb-4">
                Re-run Repair <i class="bi bi-arrow-clockwise"></i>
            </a>
        <?php endif; ?>

        <!-- USER LIST & PASSWORD RESET -->
        <div class="border-top pt-4">
            <h3 class="h5 fw-bold mb-2">Registered Accounts in this Database</h3>
            <?php
            $usersList = [];
            $uRes = $conn->query("SELECT user_id, username, full_name, role, created_at FROM users ORDER BY user_id ASC");
            if ($uRes) {
                while ($u = $uRes->fetch_assoc()) {
                    $usersList[] = $u;
                }
            }
            ?>

            <?php if (!empty($usersList)): ?>
                <table class="table table-sm table-bordered align-middle small mb-3">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Name / Pharmacy</th>
                            <th>Role</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usersList as $u): ?>
                            <tr>
                                <td><?php echo (int) $u['user_id']; ?></td>
                                <td><code><?php echo htmlspecialchars($u['username']); ?></code></td>
                                <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($u['role']); ?></span></td>
                                <td><?php echo htmlspecialchars($u['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-warning small mb-3">No users exist in this database yet.</div>
            <?php endif; ?>

            <?php if ($resetNotice): ?>
                <div class="alert alert-<?php echo $resetStatus; ?> small mb-3">
                    <?php echo $resetNotice; ?>
                </div>
            <?php endif; ?>

            <div class="bg-light p-3 rounded-3 border">
                <h4 class="h6 fw-bold mb-2"><i class="bi bi-key-fill text-warning me-1"></i> Quick Password Reset / Unlock</h4>
                <p class="small text-muted mb-2">Forgot an account's password or locked out by failed attempts? Enter the username and a new password below to reset it immediately.</p>
                <form method="POST" action="fix_db.php" class="row g-2">
                    <div class="col-sm-5">
                        <input type="text" name="reset_user" class="form-control form-control-sm" placeholder="Username (e.g. admin)" required>
                    </div>
                    <div class="col-sm-5">
                        <input type="password" name="reset_pass" class="form-control form-control-sm" placeholder="New Password (min 8 chars)" minlength="8" required>
                    </div>
                    <div class="col-sm-2">
                        <button type="submit" class="btn btn-warning btn-sm w-100 fw-semibold">Reset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
