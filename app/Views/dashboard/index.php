
<div class="page-head">
    <div>
        <h1>Dashboard</h1>
        <p><?php echo e(date("l, F j, Y")); ?></p>
    </div>
    <div class="page-actions">
        <a href="add_medicine.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add product</a>
    </div>
</div>

<!-- ============================ KEY METRIC CARDS ============================ -->
<div class="kpi-grid section">
    <a href="medicine.php" class="kpi-card kpi-emerald">
        <div class="kpi-header">
            <span class="kpi-label">Total Products</span>
        </div>
        <div class="kpi-value num"><?php echo number_format((int) $stats["total_medicines"]); ?></div>
        <div class="kpi-footer">
            <span class="kpi-sub"><i class="bi bi-arrow-right-short"></i> View inventory</span>
        </div>
    </a>

    <a href="medicine.php?expiry=good" class="kpi-card kpi-blue">
        <div class="kpi-header">
            <span class="kpi-label">Units Available</span>
        </div>
        <div class="kpi-value num"><?php echo number_format((int) $stats["available_units"]); ?></div>
        <div class="kpi-footer">
            <span class="kpi-sub"><i class="bi bi-arrow-right-short"></i> Active stock</span>
        </div>
    </a>

    <a href="medicine.php?stock=low" class="kpi-card kpi-amber">
        <div class="kpi-header">
            <span class="kpi-label">Low Stock</span>
        </div>
        <div class="kpi-value num">
            <?php echo number_format((int) $stats["low_count"]); ?>
        </div>
        <div class="kpi-footer">
            <span class="kpi-badge <?php echo $stats["low_count"] > 0 ? "badge-amber" : "badge-neutral"; ?>">
                <?php echo $stats["low_count"] > 0 ? "Needs restock" : "Levels fine"; ?>
            </span>
        </div>
    </a>

    <a href="medicine.php?stock=out" class="kpi-card kpi-rose">
        <div class="kpi-header">
            <span class="kpi-label">Out of Stock</span>
        </div>
        <div class="kpi-value num">
            <?php echo number_format((int) $stats["out_count"]); ?>
        </div>
        <div class="kpi-footer">
            <span class="kpi-badge <?php echo $stats["out_count"] > 0 ? "badge-rose" : "badge-neutral"; ?>">
                <?php echo $stats["out_count"] > 0 ? "Out of units" : "None depleted"; ?>
            </span>
        </div>
    </a>

    <a href="expiry.php" class="kpi-card kpi-orange">
        <div class="kpi-header">
            <span class="kpi-label">Expiring (&le;<?php echo (int) $days; ?>d)</span>
        </div>
        <div class="kpi-value num">
            <?php echo number_format((int) $stats["expiring_count"]); ?>
        </div>
        <div class="kpi-footer">
            <span class="kpi-badge <?php echo $stats["expiring_count"] > 0 ? "badge-orange" : "badge-neutral"; ?>">
                <?php echo $stats["expiring_count"] > 0 ? "Review soon" : "All clear"; ?>
            </span>
        </div>
    </a>

    <a href="expiry.php?status=expired" class="kpi-card kpi-red">
        <div class="kpi-header">
            <span class="kpi-label">Expired</span>
        </div>
        <div class="kpi-value num">
            <?php echo number_format((int) $stats["expired_count"]); ?>
        </div>
        <div class="kpi-footer">
            <span class="kpi-badge <?php echo $stats["expired_count"] > 0 ? "badge-red" : "badge-neutral"; ?>">
                <?php echo $stats["expired_count"] > 0 ? "Dispose items" : "No expired"; ?>
            </span>
        </div>
    </a>
</div>

<!-- ========================= NEEDS ATTENTION ========================= -->
<div class="grid-2 section">

    <div class="card">
        <div class="card-head">
            <h2>Needs restock <span class="muted fw-normal">(<?php echo $restockTotal; ?>)</span></h2>
            <?php if ($restockTotal > 0) { ?><a href="medicine.php?stock=low_out">View all</a><?php } ?>
        </div>
        <?php if (!$restock) { ?>
            <div class="empty">
                <div class="empty-icon success"><i class="bi bi-check2-circle"></i></div>
                <strong>All stock levels are healthy</strong>
                <span>Nothing is below the <?php echo (int) $low; ?>-unit threshold.</span>
            </div>
        <?php } else { ?>
            <ul class="rows">
                <?php foreach ($restock as $m) { ?>
                    <li>
                        <div class="grow">
                            <a href="view_medicine.php?id=<?php echo (int) $m["medicine_id"]; ?>" class="title"><?php echo e($m["medicine_name"]); ?></a>
                            <div class="mt-1"><?php echo status_badge(stock_status($m["quantity"]), qty_unit($m["quantity"], $m["unit"]) . " left"); ?></div>
                        </div>
                        <a href="stock.php?id=<?php echo (int) $m["medicine_id"]; ?>" class="btn btn-light btn-sm">Restock</a>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Expiring or expired <span class="muted fw-normal">(<?php echo $expiryTotal; ?>)</span></h2>
            <?php if ($expiryTotal > 0) { ?><a href="expiry.php">View all</a><?php } ?>
        </div>
        <?php if (!$expiring) { ?>
            <div class="empty">
                <div class="empty-icon success"><i class="bi bi-shield-check"></i></div>
                <strong>No expiry concerns</strong>
                <span>No products expire in the next <?php echo (int) $days; ?> days.</span>
            </div>
        <?php } else { ?>
            <ul class="rows">
                <?php foreach ($expiring as $m) {
                    $ex = expiry_status($m["expiry_date"]); ?>
                    <li>
                        <div class="grow">
                            <a href="view_medicine.php?id=<?php echo (int) $m["medicine_id"]; ?>" class="title"><?php echo e($m["medicine_name"]); ?></a>
                            <span class="sub"><?php echo $m["batch_number"] ? "Batch " . e($m["batch_number"]) . " · " : ""; ?><?php echo e(qty_unit($m["quantity"], $m["unit"])); ?></span>
                        </div>
                        <div class="end">
                            <div class="num text-muted" style="font-size: 12px; margin-bottom: 2px;"><?php echo e(format_date($m["expiry_date"])); ?></div>
                            <?php echo status_badge($ex, $ex["key"] === "expired" ? "Expired" : days_label($ex["days"])); ?>
                        </div>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>
    </div>
</div>

<!-- ===================== ACTIVITY + RECENTLY ADDED ===================== -->
<div class="grid-main">

    <div class="card align-self-start">
        <div class="card-head">
            <h2>Stock activity</h2>
            <div class="legend" aria-hidden="true">
                <span><i style="background:#059669"></i>Stock In</span>
                <span><i style="background:#0284c7"></i>Stock Out</span>
            </div>
        </div>
        <div class="card-pad">
            <?php if (!$hasActivity) { ?>
                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-bar-chart"></i></div>
                    <strong>No stock movements yet</strong>
                    <span>Stock movements recorded will be graphed here over the last 6 months.</span>
                </div>
            <?php } else { ?>
                <div class="chart">
                    <canvas id="activityChart" role="img" aria-label="Units added and removed per month, last 6 months"></canvas>
                </div>
                <!-- The same numbers as text, for screen readers -->
                <table class="visually-hidden">
                    <caption>Units added and removed per month</caption>
                    <tr><th>Month</th><th>In</th><th>Out</th></tr>
                    <?php foreach ($months as $m) { ?>
                        <tr><td><?php echo e($m["label"]); ?></td><td><?php echo $m["in"]; ?></td><td><?php echo $m["out"]; ?></td></tr>
                    <?php } ?>
                </table>
            <?php } ?>
        </div>
    </div>

    <div class="d-flex flex-column gap-4 min-w-0">
        <div class="card">
            <div class="card-head">
                <h2>Recently added</h2>
                <a href="medicine.php?sort=added&amp;dir=desc">View all</a>
            </div>
            <?php if (!$recent) { ?>
                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-box"></i></div>
                    <strong>No products yet</strong>
                    <span><a href="add_medicine.php">Add your first product</a> to start tracking.</span>
                </div>
            <?php } else { ?>
                <ul class="rows">
                    <?php foreach ($recent as $m) { ?>
                        <li>
                            <div class="grow">
                                <a href="view_medicine.php?id=<?php echo (int) $m["medicine_id"]; ?>" class="title"><?php echo e($m["medicine_name"]); ?></a>
                                <span class="sub"><?php echo e($m["product_type"]); ?> · <?php echo e(format_date($m["date_added"])); ?></span>
                            </div>
                            <div class="end num fw-semibold"><?php echo e(qty_unit($m["quantity"], $m["unit"])); ?></div>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>

        <div class="card">
            <div class="card-head"><h2>Products by type</h2></div>
            <?php if (!$typeCounts) { ?>
                <div class="empty">
                    <strong>No products yet</strong>
                </div>
            <?php } else { ?>
                <ul class="rows">
                    <?php foreach ($typeCounts as $t => $count) { ?>
                        <li>
                            <div class="grow"><a href="<?php echo e(build_url("medicine.php", ["type" => $t])); ?>" class="title"><?php echo e($t); ?></a></div>
                            <div class="end"><span class="tag"><?php echo number_format($count); ?></span></div>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    </div>
</div>

<script type="application/json" id="dashboardData"><?php
    echo json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?></script>
