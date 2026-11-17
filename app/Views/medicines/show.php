
<div class="page-head">
    <div class="min-w-0">
        <a href="medicine.php" class="back-link"><i class="bi bi-arrow-left"></i> Products</a>
        <h1 class="text-break"><?php echo e($med["medicine_name"]); ?></h1>
        <p class="d-flex flex-wrap gap-3">
            <?php if ($med["requires_rx"]) { ?><span class="tag" title="Prescription required">Rx</span><?php } ?>
            <?php echo status_badge($st); ?>
            <?php echo status_badge($ex, $ex["key"] === "good" ? "Expires " . days_label($ex["days"]) : null); ?>
        </p>
    </div>
    <div class="page-actions">
        <?php if (is_admin()) { ?>
            <form action="delete_medicine.php" method="POST" class="d-inline-flex"
                  data-confirm-title="Delete product?"
                  data-confirm-button="Delete"
                  data-confirm="&quot;<?php echo e($med["medicine_name"]); ?>&quot; will be removed. Its stock history is kept.">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <button type="submit" class="btn btn-light">Delete</button>
            </form>
        <?php } ?>
        <a href="edit_medicine.php?id=<?php echo $id; ?>" class="btn btn-light">Edit</a>
        <a href="stock.php?id=<?php echo $id; ?>" class="btn btn-primary">Update stock</a>
    </div>
</div>

<div class="card section">
    <dl class="details">
        <div><dt>Quantity</dt><dd class="num"><?php echo e(qty_unit($med["quantity"], $med["unit"])); ?></dd></div>
        <div><dt>Unit price</dt><dd class="num"><?php echo e(money($med["unit_price"])); ?></dd></div>
        <div><dt>Stock value</dt><dd class="num"><?php echo e(money($value)); ?></dd></div>
        <div><dt>Expiry date</dt><dd>
            <?php if ($ex["key"] === "none") { ?>
                No expiry
            <?php } else { ?>
                <?php echo e(format_date($med["expiry_date"])); ?> <span class="muted fw-normal">(<?php echo e(days_label($ex["days"])); ?>)</span>
            <?php } ?>
        </dd></div>
        <div><dt>Type</dt><dd><?php echo e($med["product_type"]); ?></dd></div>
        <?php if ($med["product_type"] === "Medicine") { ?>
            <div><dt>Prescription</dt><dd><?php echo $med["requires_rx"] ? "Required (Rx)" : "Not required"; ?></dd></div>
        <?php } ?>
        <div><dt>Category</dt><dd><?php echo e($med["category"]); ?></dd></div>
        <div><dt>Brand</dt><dd><?php echo $med["brand"] ? e($med["brand"]) : $dash; ?></dd></div>
        <div><dt>Batch number</dt><dd><?php echo $med["batch_number"] ? e($med["batch_number"]) : $dash; ?></dd></div>
        <div><dt>Supplier</dt><dd><?php echo $med["supplier"] ? e($med["supplier"]) : $dash; ?></dd></div>
        <div><dt>Date added</dt><dd><?php echo e(format_date($med["date_added"])); ?></dd></div>
        <div><dt>Product ID</dt><dd>#<?php echo $id; ?></dd></div>
        <?php if ($med["description"]) { ?>
            <div class="wide"><dt>Description</dt><dd><?php echo e($med["description"]); ?></dd></div>
        <?php } ?>
    </dl>
</div>

<div class="card">
    <div class="card-head"><h2>Stock history</h2></div>
    <?php if (!$history) { ?>
        <div class="empty"><strong>No stock changes yet</strong></div>
    <?php } else {
        $showMedicine = false;
        include APP_PATH . "/Views/partials/history_table.php";
    } ?>
</div>
