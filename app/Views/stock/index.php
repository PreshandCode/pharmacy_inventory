
<div class="page-head">
    <div>
        <h1>Stock</h1>
        <p>Record deliveries, dispensing and count corrections.</p>
    </div>
</div>

<div class="grid-form">

    <!-- ========================= RECORD A CHANGE ========================= -->
    <div class="card">
        <div class="card-head"><h2>Update stock</h2></div>
        <div class="card-pad">
            <?php if (!$allMedicines) { ?>
                <div class="empty"><strong>No medicines yet</strong><a href="add_medicine.php">Add a medicine first</a></div>
            <?php } else { ?>
            <form method="POST" action="stock.php" class="needs-validation" novalidate id="stockForm"
                  data-low="<?php echo (int) LOW_STOCK_THRESHOLD; ?>" data-max="<?php echo MED_MAX_QUANTITY; ?>"
                  data-today="<?php echo e(date("Y-m-d")); ?>">
                <?php echo csrf_field(); ?>

                <div class="mb-3">
                    <label for="medicine_id" class="form-label">Medicine</label>
                    <select id="medicine_id" name="medicine_id" class="form-select" required>
                        <option value="">Choose a medicine…</option>
                        <?php foreach ($allMedicines as $m) { ?>
                            <option value="<?php echo (int) $m["medicine_id"]; ?>"
                                    data-qty="<?php echo (int) $m["quantity"]; ?>"
                                    data-expiry="<?php echo e($m["expiry_date"]); ?>"
                                    <?php echo (int) $m["medicine_id"] === $selectedId ? "selected" : ""; ?>>
                                <?php echo e($m["medicine_name"] . ($m["batch_number"] ? " · " . $m["batch_number"] : "") . " (" . number_format((int) $m["quantity"]) . ")"); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <div class="invalid-feedback">Choose a medicine.</div>
                </div>

                <div class="mb-3">
                    <div class="segmented" role="radiogroup" aria-label="Type of change">
                        <input type="radio" class="btn-check" name="type" id="type_in" value="in" <?php echo $formType === "in" ? "checked" : ""; ?>>
                        <label class="seg" for="type_in">Stock in</label>
                        <input type="radio" class="btn-check" name="type" id="type_out" value="out" <?php echo $formType === "out" ? "checked" : ""; ?>>
                        <label class="seg" for="type_out">Stock out</label>
                        <input type="radio" class="btn-check" name="type" id="type_set" value="set" <?php echo $formType === "set" ? "checked" : ""; ?>>
                        <label class="seg" for="type_set">Set count</label>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-5">
                        <label for="quantity" class="form-label" id="quantityLabel">Quantity</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" min="1" max="<?php echo MED_MAX_QUANTITY; ?>"
                               step="1" required inputmode="numeric" value="<?php echo e($old["quantity"] ?? ""); ?>">
                        <div class="invalid-feedback" id="quantityFeedback">Enter a whole number.</div>
                    </div>
                    <div class="col-sm-7">
                        <label for="reason" class="form-label">Reason</label>
                        <select id="reason" name="reason" class="form-select" required data-selected="<?php echo e($formReason); ?>">
                            <?php foreach ($reasons as $rType => $list) {
                                foreach ($list as $r) { ?>
                                    <option value="<?php echo e($r); ?>" data-type="<?php echo e($rType); ?>"><?php echo e($r); ?></option>
                            <?php } } ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="note" class="form-label">Note <span class="opt" id="noteHint">(optional)</span></label>
                    <input type="text" id="note" name="note" class="form-control" maxlength="200"
                           placeholder="e.g. Invoice #1234" value="<?php echo e($old["note"] ?? ""); ?>">
                    <div class="invalid-feedback">Describe the reason.</div>
                </div>

                <div class="preview" id="preview" aria-live="polite"></div>

                <button type="submit" class="btn btn-primary w-100 mt-3">Save</button>
            </form>
            <?php } ?>
        </div>
    </div>

    <!-- ============================= HISTORY ============================= -->
    <div class="card" id="history">
        <div class="card-head">
            <h2>History</h2>
            <nav class="tabs mb-0" aria-label="Filter history">
                <?php foreach (["" => "All", "in" => "In", "out" => "Out", "adjust" => "Corrections"] as $val => $lbl) {
                    $p = $linkParams; $p["type_filter"] = $val; ?>
                    <a href="<?php echo e(build_url("stock.php", $p)); ?>#history" class="tab<?php echo $histType === $val ? " active" : ""; ?>"
                       <?php echo $histType === $val ? 'aria-current="true"' : ""; ?>><?php echo e($lbl); ?></a>
                <?php } ?>
            </nav>
        </div>
        <?php if (!$history) { ?>
            <div class="empty"><strong>No stock changes yet</strong>Changes you save will be listed here.</div>
        <?php } else {
            $showMedicine = true;
            include APP_PATH . "/Views/partials/history_table.php"; ?>
            <?php if ($histPages > 1) { ?>
                <div class="table-foot">
                    <span><?php echo number_format($histTotal); ?> records</span>
                    <?php echo render_pagination("stock.php", $linkParams, $histPage, $histPages, "page", "#history"); ?>
                </div>
            <?php } ?>
        <?php } ?>
    </div>
</div>
