
<div class="page-head">
    <div>
        <h1>Expiry Tracking</h1>
        <p>Products expiring within <?php echo (int) $warnDays; ?> days require monitoring or disposal.</p>
    </div>
    <div class="page-actions no-print">
        <button type="button" class="btn btn-light" onclick="window.print()"><i class="bi bi-printer"></i> Print list</button>
    </div>
</div>

<!-- Shown on paper only -->
<p class="print-only muted">
    <?php echo e($tabs[$status][0]); ?><?php echo $category !== "" ? " · " . e($category) : ""; ?><?php echo $search !== "" ? ' · "' . e($search) . '"' : ""; ?>
    · Printed <?php echo e(date("M j, Y g:i A")); ?>
</p>

<nav class="tabs no-print" aria-label="Expiry status tabs">
    <?php foreach ($tabs as $key => [$label, $count]) {
        $p = $params; $p["status"] = $key === "attention" ? "" : $key;
        $urgent = in_array($key, ["expired", "critical"], true) && $count > 0; ?>
        <a href="<?php echo e(build_url("expiry.php", $p)); ?>" class="tab<?php echo $status === $key ? " active" : ""; ?>"
           <?php echo $status === $key ? 'aria-current="true"' : ""; ?>>
            <?php echo e($label); ?> <span class="count<?php echo $urgent ? " bad" : ""; ?>"><?php echo $count; ?></span>
        </a>
    <?php } ?>
</nav>

<form method="GET" action="expiry.php" class="toolbar no-print" role="search">
    <?php if ($params["status"] !== "") { ?><input type="hidden" name="status" value="<?php echo e($params["status"]); ?>"><?php } ?>
    <div class="search">
        <i class="bi bi-search"></i>
        <input type="search" name="search" id="expirySearch" class="form-control" placeholder="Search name, brand, batch"
               value="<?php echo e($search); ?>" maxlength="100" aria-label="Search products">
        <kbd class="search-kbd" title="Press / to focus search">/</kbd>
    </div>
    <select name="category" class="form-select" data-autosubmit aria-label="Category">
        <option value="">All categories</option>
        <?php foreach ($categories as $cat) { ?>
            <option value="<?php echo e($cat); ?>" <?php echo $cat === $category ? "selected" : ""; ?>><?php echo e($cat); ?></option>
        <?php } ?>
    </select>
    <button type="submit" class="visually-hidden">Search</button>
    <?php if ($search !== "" || $category !== "") { ?>
        <a href="<?php echo e(build_url("expiry.php", ["status" => $params["status"]])); ?>" class="clear"><i class="bi bi-x-circle"></i> Clear filters</a>
    <?php } ?>
</form>

<div class="card">
    <?php if (!$rows) { ?>
        <div class="empty">
            <div class="empty-icon success"><i class="bi bi-calendar2-check"></i></div>
            <strong><?php echo $status === "expired" ? "No expired products found" : "No products in this view"; ?></strong>
            <span><?php echo ($search !== "" || $category !== "") ? "Try clearing search keywords or category filters." : "All products in this category are within safe dates."; ?></span>
        </div>
    <?php } else { ?>
        <div class="table-wrap">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-end">Quantity</th>
                        <th>Expiry date</th>
                        <th>Status</th>
                        <th class="actions no-print"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r) {
                        $id  = (int) $r["medicine_id"];
                        $qty = (int) $r["quantity"];
                        $ex  = expiry_status($r["expiry_date"]);
                        $critical = !empty($ex["critical"]);

                        if ($ex["key"] === "expired") {
                            $dateClass = "date-expired";
                            $badge = status_badge($ex, "Expired " . days_label($ex["days"]));
                        } elseif ($critical) {
                            $dateClass = "date-critical";
                            $badge = status_badge(["class" => "orange"], $ex["days"] === 0 ? "Expires today" : $ex["days"] . " days left");
                        } elseif ($ex["key"] === "expiring") {
                            $dateClass = "date-soon";
                            $badge = status_badge(["class" => "warning"], $ex["days"] . " days left");
                        } else {
                            $dateClass = "";
                            $badge = status_badge($ex, "Good");
                        }
                        $sub = array_filter([$r["brand"], $r["batch_number"] ? "Batch " . $r["batch_number"] : ""]); ?>
                        <tr>
                            <td>
                                <a href="view_medicine.php?id=<?php echo $id; ?>" class="title"><?php echo e($r["medicine_name"]); ?></a>
                                <?php if ($sub) { ?><div class="sub"><?php echo e(implode(" · ", $sub)); ?></div><?php } ?>
                            </td>
                            <td class="text-end num fw-semibold"><?php echo e(qty_unit($qty, $r["unit"])); ?></td>
                            <td><span class="date <?php echo $dateClass; ?>"><?php echo e(format_date($r["expiry_date"])); ?></span></td>
                            <td><?php echo $badge; ?></td>
                            <td class="actions no-print">
                                <?php if ($ex["key"] === "expired" && $qty > 0) { ?>
                                    <a href="stock.php?id=<?php echo $id; ?>&amp;reason=expired" class="btn btn-danger btn-sm"><i class="bi bi-trash3"></i> Dispose</a>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1) { ?>
            <div class="table-foot">
                <span>Showing <?php echo number_format($offset + 1); ?>–<?php echo number_format($offset + count($rows)); ?> of <?php echo number_format($totalRows); ?></span>
                <?php echo render_pagination("expiry.php", $params, $page, $totalPages); ?>
            </div>
        <?php } ?>
    <?php } ?>
</div>
