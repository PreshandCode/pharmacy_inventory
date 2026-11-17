
<div class="page-head">
    <div>
        <h1>Products</h1>
        <p><?php echo number_format($totalRows); ?> <?php echo $hasFilters ? "matching" : "in total"; ?></p>
    </div>
    <div class="page-actions">
        <a href="add_medicine.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add product</a>
    </div>
</div>

<!-- Search and filters (dropdowns apply as soon as you change them) -->
<form method="GET" action="medicine.php" class="toolbar" role="search">
    <div class="search">
        <i class="bi bi-search"></i>
        <input type="search" name="search" class="form-control" placeholder="Search name, brand, batch, supplier"
               value="<?php echo e($search); ?>" maxlength="100" aria-label="Search products">
    </div>

    <select name="type" class="form-select" data-autosubmit aria-label="Product type">
        <option value="">All types</option>
        <?php foreach ($types as $t) { ?>
            <option value="<?php echo e($t); ?>" <?php echo $t === $type ? "selected" : ""; ?>><?php echo e($t); ?></option>
        <?php } ?>
    </select>

    <select name="category" class="form-select" data-autosubmit aria-label="Category">
        <option value="">All categories</option>
        <?php foreach ($categories as $cat) { ?>
            <option value="<?php echo e($cat); ?>" <?php echo $cat === $category ? "selected" : ""; ?>><?php echo e($cat); ?></option>
        <?php } ?>
    </select>

    <select name="stock" class="form-select" data-autosubmit aria-label="Stock level">
        <?php foreach ($stockOptions as $value => $label) { ?>
            <option value="<?php echo e($value); ?>" <?php echo $value === $stock ? "selected" : ""; ?>><?php echo e($label); ?></option>
        <?php } ?>
    </select>

    <select name="expiry" class="form-select" data-autosubmit aria-label="Expiry">
        <?php foreach ($expiryOptions as $value => $label) { ?>
            <option value="<?php echo e($value); ?>" <?php echo $value === $expiry ? "selected" : ""; ?>><?php echo e($label); ?></option>
        <?php } ?>
    </select>

    <?php foreach (["sort" => $params["sort"], "dir" => $params["dir"], "per_page" => $params["per_page"]] as $k => $v) {
        if ($v !== "") { ?><input type="hidden" name="<?php echo e($k); ?>" value="<?php echo e($v); ?>"><?php }
    } ?>
    <button type="submit" class="visually-hidden">Search</button>

    <?php if ($hasFilters) { ?>
        <a href="medicine.php" class="clear">Clear filters</a>
    <?php } ?>
</form>

<div class="card">
    <?php if (!$medicines) { ?>
        <div class="empty">
            <?php if ($hasFilters) { ?>
                <strong>No products match</strong>Try another search or <a href="medicine.php">clear the filters</a>.
            <?php } else { ?>
                <strong>No products yet</strong><a href="add_medicine.php">Add your first product</a>
            <?php } ?>
        </div>
    <?php } else { ?>
        <div class="table-wrap">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <?php echo sort_header("name", "Product", $params, $sort, $dir); ?>
                        <?php echo sort_header("category", "Category", $params, $sort, $dir); ?>
                        <?php echo sort_header("quantity", "Stock", $params, $sort, $dir, "text-end"); ?>
                        <?php echo sort_header("price", "Unit price", $params, $sort, $dir, "text-end"); ?>
                        <?php echo sort_header("expiry", "Expiry", $params, $sort, $dir); ?>
                        <th class="actions"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medicines as $row) {
                        $id = (int) $row["medicine_id"];
                        $st = stock_status($row["quantity"]);
                        $ex = expiry_status($row["expiry_date"]);
                        $sub = array_filter([$row["brand"], $row["batch_number"] ? "Batch " . $row["batch_number"] : ""]); ?>
                        <tr>
                            <td>
                                <a href="view_medicine.php?id=<?php echo $id; ?>" class="title"><?php echo e($row["medicine_name"]); ?></a>
                                <?php if ($row["requires_rx"]) { ?><span class="tag" title="Prescription required">Rx</span><?php } ?>
                                <?php if ($sub) { ?><div class="sub"><?php echo e(implode(" · ", $sub)); ?></div><?php } ?>
                            </td>
                            <td>
                                <span class="tag"><?php echo e($row["category"]); ?></span>
                                <div class="sub"><?php echo e($row["product_type"]); ?></div>
                            </td>
                            <td class="text-end">
                                <span class="num"><?php echo e(qty_unit($row["quantity"], $row["unit"])); ?></span>
                                <?php if ($st["key"] !== "in") { ?><div><?php echo status_badge($st); ?></div><?php } ?>
                            </td>
                            <td class="text-end num"><?php echo e(money($row["unit_price"])); ?></td>
                            <td>
                                <?php if ($ex["key"] === "none") { ?>
                                    <span class="muted">No expiry</span>
                                <?php } else { ?>
                                    <span class="num"><?php echo e(format_date($row["expiry_date"])); ?></span>
                                <?php } ?>
                                <?php if ($ex["key"] !== "good" && $ex["key"] !== "none") { ?><div><?php echo status_badge($ex); ?></div><?php } ?>
                            </td>
                            <td class="actions">
                                <a href="edit_medicine.php?id=<?php echo $id; ?>" class="icon-btn" title="Edit" aria-label="Edit <?php echo e($row["medicine_name"]); ?>"><i class="bi bi-pencil"></i></a>
                                <?php if (is_admin()) { ?>
                                    <form action="delete_medicine.php" method="POST" class="d-inline"
                                          data-confirm-title="Delete product?"
                                          data-confirm-button="Delete"
                                          data-confirm="&quot;<?php echo e($row["medicine_name"]); ?>&quot; will be removed. Its stock history is kept.">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                                        <input type="hidden" name="return" value="<?php echo e($returnUrl); ?>">
                                        <button type="submit" class="icon-btn danger" title="Delete" aria-label="Delete <?php echo e($row["medicine_name"]); ?>"><i class="bi bi-trash3"></i></button>
                                    </form>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1) { ?>
            <div class="table-foot">
                <span><?php echo number_format($offset + 1); ?>–<?php echo number_format($offset + count($medicines)); ?> of <?php echo number_format($totalRows); ?></span>
                <?php echo render_pagination("medicine.php", $params, $page, $totalPages); ?>
            </div>
        <?php } ?>
    <?php } ?>
</div>
