<?php
// Colors for action badges
$actionColors = [
    'Login'            => 'success',
    'Logout'           => 'secondary',
    'Add Product'      => 'success',
    'Edit Product'     => 'warning',
    'Delete Product'   => 'danger',
    'Stock In'         => 'success',
    'Stock Out'        => 'orange',
    'Stock Correction' => 'warning',
    'Password Change'  => 'warning',
];
?>

<div class="page-head">
    <div>
        <h1>Audit Trail</h1>
        <p>System activity and user action history log.</p>
    </div>
    <div class="page-actions no-print">
        <button type="button" class="btn btn-light" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    </div>
</div>

<form method="GET" action="audit.php" class="toolbar no-print" role="search">
    <div class="search">
        <i class="bi bi-search"></i>
        <input type="search" name="search" id="auditSearch" class="form-control" placeholder="Search user, action, details"
               value="<?php echo e($search); ?>" maxlength="100" aria-label="Search audit logs">
    </div>

    <select name="action" class="form-select" data-autosubmit aria-label="Action filter">
        <option value="">All actions</option>
        <?php foreach ($actions as $act) { ?>
            <option value="<?php echo e($act); ?>" <?php echo $act === $actionFilter ? "selected" : ""; ?>><?php echo e($act); ?></option>
        <?php } ?>
    </select>

    <button type="submit" class="visually-hidden">Search</button>

    <?php if ($search !== "" || $actionFilter !== "") { ?>
        <a href="audit.php" class="clear"><i class="bi bi-x-circle"></i> Clear filters</a>
    <?php } ?>
</form>

<div class="card">
    <?php if (!$rows) { ?>
        <div class="empty">
            <div class="empty-icon"><i class="bi bi-clock-history"></i></div>
            <strong>No audit records found</strong>
            <span><?php echo ($search !== "" || $actionFilter !== "") ? "Try clearing search keywords or action filter." : "User actions will be recorded here."; ?></span>
        </div>
    <?php } else { ?>
        <div class="table-wrap">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date and Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r) {
                        $badgeClass = $actionColors[$r["action"]] ?? "secondary"; ?>
                        <tr>
                            <td class="text-nowrap">
                                <?php echo e(date("M j, Y", strtotime($r["created_at"]))); ?>
                                <div class="sub"><?php echo e(date("g:i A", strtotime($r["created_at"]))); ?></div>
                            </td>
                            <td class="text-nowrap">
                                <strong><?php echo e($r["username"] ?? "System"); ?></strong>
                                <?php if (!empty($r["full_name"])) { ?>
                                    <div class="sub"><?php echo e($r["full_name"]); ?></div>
                                <?php } ?>
                            </td>
                            <td>
                                <span class="status status-<?php echo e($badgeClass); ?>">
                                    <?php echo e($r["action"]); ?>
                                </span>
                            </td>
                            <td><?php echo $r["details"] ? e($r["details"]) : '<span class="muted">—</span>'; ?></td>
                            <td class="text-nowrap text-muted num"><?php echo $r["ip_address"] ? e($r["ip_address"]) : '<span class="muted">—</span>'; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1) { ?>
            <div class="table-foot">
                <span>Showing <?php echo number_format($offset + 1); ?>–<?php echo number_format($offset + count($rows)); ?> of <?php echo number_format($totalRows); ?></span>
                <?php echo render_pagination("audit.php", $params, $page, $totalPages); ?>
            </div>
        <?php } ?>
    <?php } ?>
</div>
