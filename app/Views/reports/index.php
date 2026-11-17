<?php
// Report tabs
$tabs = [
    "inventory"      => "Inventory Report",
    "expiry"         => "Expiry Report",
    "stock_movement" => "Stock Movement Report",
];
$movementTypes = ["in" => "In", "out" => "Out", "adjust" => "Correction"];
?>

<div class="page-head">
    <div>
        <h1>Reports</h1>
        <p>Generate and export inventory, expiry and stock movement reports.</p>
    </div>
    <div class="page-actions no-print">
        <button type="button" class="btn btn-light" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        <button type="button" class="btn btn-light" onclick="window.print()"><i class="bi bi-file-earmark-pdf"></i> Export PDF</button>
        <a href="<?php echo e(build_url("reports.php", ["type" => $type, "from" => $from, "to" => $to, "export" => "csv"])); ?>"
           class="btn btn-light"><i class="bi bi-file-earmark-spreadsheet"></i> Export Excel</a>
    </div>
</div>

<!-- Shown on paper only -->
<p class="print-only muted">
    <?php echo e($tabs[$type] ?? "Report"); ?> · Generated <?php echo e(date("M j, Y g:i A")); ?>
</p>

<nav class="tabs no-print" aria-label="Report type tabs">
    <?php foreach ($tabs as $key => $label) {
        $p = ["type" => $key === "inventory" ? "" : $key]; ?>
        <a href="<?php echo e(build_url("reports.php", $p)); ?>"
           class="tab<?php echo $type === $key ? " active" : ""; ?>"
           <?php echo $type === $key ? 'aria-current="true"' : ""; ?>>
            <?php echo e($label); ?>
        </a>
    <?php } ?>
</nav>

<div class="card">

<?php if ($type === "inventory") { ?>
    <?php if (!$rows) { ?>
        <div class="empty">
            <div class="empty-icon"><i class="bi bi-box"></i></div>
            <strong>No products found</strong>
            <span>Add products to see the inventory report.</span>
        </div>
    <?php } else { ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th class="text-end">Stock Qty</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Total Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r) { ?>
                        <tr>
                            <td><?php echo e($r["medicine_name"]); ?></td>
                            <td><?php echo e($r["category"]); ?></td>
                            <td class="text-end num"><?php echo e(qty_unit($r["quantity"], $r["unit"])); ?></td>
                            <td class="text-end num"><?php echo e(money($r["unit_price"])); ?></td>
                            <td class="text-end num"><?php echo e(money($r["total_value"])); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end fw-semibold">Grand Total</td>
                        <td class="text-end num fw-semibold"><?php echo e(money($grandTotal)); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php } ?>

<?php } elseif ($type === "expiry") { ?>
    <?php
    $sections = [
        "expired"  => ["Expired Products",           "danger",  $expiry["expired"]],
        "within30" => ["Expiring Within 30 Days",     "orange",  $expiry["within30"]],
        "within90" => ["Expiring Within 31–90 Days",  "warning", $expiry["within90"]],
    ];
    $anyRows = false;
    foreach ($sections as $s) { if ($s[2]) { $anyRows = true; break; } }
    ?>

    <?php if (!$anyRows) { ?>
        <div class="empty">
            <div class="empty-icon success"><i class="bi bi-calendar2-check"></i></div>
            <strong>No expiry concerns</strong>
            <span>No products are expired or expiring within 90 days.</span>
        </div>
    <?php } else { ?>
        <?php foreach ($sections as $sKey => [$sLabel, $sClass, $sRows]) { ?>
            <?php if ($sRows) { ?>
                <div class="card-pad" <?php echo $sKey !== "expired" ? 'style="border-top: 1px solid var(--bs-border-color)"' : ''; ?>>
                    <h3 class="fs-6 mb-3"><?php echo e($sLabel); ?> <span class="muted fw-normal">(<?php echo count($sRows); ?>)</span></h3>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Batch Number</th>
                                    <th class="text-end">Quantity</th>
                                    <th>Expiry Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sRows as $r) {
                                    $ex = expiry_status($r["expiry_date"]); ?>
                                    <tr>
                                        <td><?php echo e($r["medicine_name"]); ?></td>
                                        <td><?php echo $r["batch_number"] ? e($r["batch_number"]) : '<span class="muted">—</span>'; ?></td>
                                        <td class="text-end num"><?php echo e(qty_unit($r["quantity"], $r["unit"])); ?></td>
                                        <td><?php echo e(format_date($r["expiry_date"])); ?></td>
                                        <td><?php echo status_badge($ex); ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
    <?php } ?>

<?php } elseif ($type === "stock_movement") { ?>
    <div class="card-pad no-print">
        <form method="GET" action="reports.php" class="d-flex flex-wrap gap-3 align-items-end">
            <input type="hidden" name="type" value="stock_movement">
            <div>
                <label for="from" class="form-label">From</label>
                <input type="date" id="from" name="from" class="form-control" value="<?php echo e($from); ?>">
            </div>
            <div>
                <label for="to" class="form-label">To</label>
                <input type="date" id="to" name="to" class="form-control" value="<?php echo e($to); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
    </div>

    <!-- Print header -->
    <p class="print-only muted" style="padding: 0 16px">
        Date range: <?php echo e(format_date($from)); ?> – <?php echo e(format_date($to)); ?>
    </p>

    <?php if (!$movements) { ?>
        <div class="empty">
            <div class="empty-icon"><i class="bi bi-clock-history"></i></div>
            <strong>No stock movements found</strong>
            <span>Try a different date range.</span>
        </div>
    <?php } else { ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th class="text-end">Quantity</th>
                        <th class="text-end">Before → After</th>
                        <th>Note</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movements as $m) {
                        $diff = (int) $m["quantity_after"] - (int) $m["quantity_before"]; ?>
                        <tr>
                            <td class="text-nowrap">
                                <?php echo e(date("M j, Y", strtotime($m["created_at"]))); ?>
                                <div class="sub"><?php echo e(date("g:i A", strtotime($m["created_at"]))); ?></div>
                            </td>
                            <td><?php echo e($m["medicine_name"]); ?></td>
                            <td>
                                <span class="status status-<?php echo $m["movement_type"] === "in" ? "success" : ($m["movement_type"] === "out" ? "danger" : "warning"); ?>">
                                    <?php echo e($movementTypes[$m["movement_type"]] ?? $m["movement_type"]); ?>
                                </span>
                            </td>
                            <td class="text-end num">
                                <span class="<?php echo $diff >= 0 ? "text-success" : "text-danger"; ?> fw-semibold">
                                    <?php echo ($diff > 0 ? "+" : ($diff < 0 ? "−" : "")) . number_format(abs($diff)); ?>
                                </span>
                            </td>
                            <td class="text-end num"><?php echo number_format((int) $m["quantity_before"]); ?> → <?php echo number_format((int) $m["quantity_after"]); ?></td>
                            <td><?php echo $m["note"] ? e($m["note"]) : '<span class="muted">—</span>'; ?></td>
                            <td class="text-nowrap"><?php echo $m["full_name"] ? e($m["full_name"]) : '<span class="muted">System</span>'; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>

<?php } ?>

</div>
