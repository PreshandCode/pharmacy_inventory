<?php
// =====================================================================
//  MEDICINE FORM - shared by add_medicine.php and edit_medicine.php
//
//  Variables the page sets before including this file:
//    $mode        "add" or "edit"
//    $form        field values to show
//    $errors      ["field" => "message"] from validation
//    $categories  existing categories (suggestions)
//    $suppliers   existing suppliers (suggestions)
//    $medicine    (edit only) the saved record
// =====================================================================

$isEdit = ($mode === "edit");
$action = $isEdit ? "update_medicine.php" : "add_medicine.php";
$cancel = $isEdit ? "view_medicine.php?id=" . (int) $medicine["medicine_id"] : "medicine.php";

// Red border + message under a field that has an error
$invalid = function (string $field) use ($errors): string {
    return isset($errors[$field]) ? " is-invalid" : "";
};
$feedback = function (string $field, string $default) use ($errors): string {
    return '<div class="invalid-feedback">' . e($errors[$field] ?? $default) . '</div>';
};
?>

<?php if ($errors) { ?>
    <div class="alert alert-danger" role="alert">
        Please fix the highlighted field<?php echo count($errors) === 1 ? "" : "s"; ?>:
        <ul><?php foreach ($errors as $msg) { ?><li><?php echo e($msg); ?></li><?php } ?></ul>
    </div>
<?php } ?>

<form method="POST" action="<?php echo e($action); ?>" class="card card-pad needs-validation" novalidate>
    <?php echo csrf_field(); ?>
    <?php if ($isEdit) { ?>
        <input type="hidden" name="medicine_id" value="<?php echo (int) $medicine["medicine_id"]; ?>">
    <?php } ?>

    <div class="row g-3">
        <div class="col-md-8">
            <label for="medicine_name" class="form-label">Medicine name <span class="req">*</span></label>
            <input type="text" id="medicine_name" name="medicine_name" class="form-control<?php echo $invalid("medicine_name"); ?>"
                   maxlength="100" required autofocus placeholder="e.g. Amoxicillin 500mg"
                   value="<?php echo e($form["medicine_name"]); ?>">
            <?php echo $feedback("medicine_name", "Enter the medicine name."); ?>
        </div>

        <div class="col-md-4">
            <label for="category" class="form-label">Category <span class="req">*</span></label>
            <input type="text" id="category" name="category" list="categoryList" class="form-control<?php echo $invalid("category"); ?>"
                   maxlength="50" required placeholder="e.g. Analgesic" value="<?php echo e($form["category"]); ?>">
            <datalist id="categoryList"><?php foreach ($categories as $c) { ?><option value="<?php echo e($c); ?>"><?php } ?></datalist>
            <?php echo $feedback("category", "Enter a category."); ?>
        </div>

        <div class="col-md-4">
            <label for="brand" class="form-label">Brand <span class="opt">(optional)</span></label>
            <input type="text" id="brand" name="brand" class="form-control<?php echo $invalid("brand"); ?>"
                   maxlength="100" value="<?php echo e($form["brand"]); ?>">
            <?php echo $feedback("brand", "Brand is too long."); ?>
        </div>

        <div class="col-md-4">
            <label for="batch_number" class="form-label">Batch number <span class="opt">(optional)</span></label>
            <input type="text" id="batch_number" name="batch_number" class="form-control<?php echo $invalid("batch_number"); ?>"
                   maxlength="50" value="<?php echo e($form["batch_number"]); ?>">
            <?php echo $feedback("batch_number", "Batch number is too long."); ?>
        </div>

        <div class="col-md-4">
            <label for="supplier" class="form-label">Supplier <span class="opt">(optional)</span></label>
            <input type="text" id="supplier" name="supplier" list="supplierList" class="form-control<?php echo $invalid("supplier"); ?>"
                   maxlength="100" value="<?php echo e($form["supplier"]); ?>">
            <datalist id="supplierList"><?php foreach ($suppliers as $s) { ?><option value="<?php echo e($s); ?>"><?php } ?></datalist>
            <?php echo $feedback("supplier", "Supplier is too long."); ?>
        </div>

        <div class="col-md-4">
            <label for="quantity" class="form-label"><?php echo $isEdit ? "Quantity" : "Starting quantity"; ?> <span class="req">*</span></label>
            <input type="number" id="quantity" name="quantity" class="form-control<?php echo $invalid("quantity"); ?>"
                   min="0" max="<?php echo MED_MAX_QUANTITY; ?>" step="1" required inputmode="numeric"
                   value="<?php echo e($form["quantity"]); ?>">
            <?php echo $feedback("quantity", "Enter a whole number, 0 or more."); ?>
            <?php if ($isEdit) { ?>
                <div class="form-text">Changes are saved in the stock history.</div>
            <?php } ?>
        </div>

        <div class="col-md-4">
            <label for="unit_price" class="form-label">Unit price (<?php echo e(CURRENCY); ?>) <span class="req">*</span></label>
            <input type="number" id="unit_price" name="unit_price" class="form-control<?php echo $invalid("unit_price"); ?>"
                   min="0" step="0.01" required inputmode="decimal" placeholder="0.00"
                   value="<?php echo e($form["unit_price"]); ?>">
            <?php echo $feedback("unit_price", "Enter a price of 0 or more."); ?>
        </div>

        <div class="col-md-4">
            <label for="expiry_date" class="form-label">Expiry date <span class="req">*</span></label>
            <input type="date" id="expiry_date" name="expiry_date" class="form-control<?php echo $invalid("expiry_date"); ?>"
                   required <?php echo $isEdit ? "" : 'min="' . date("Y-m-d") . '"'; ?>
                   value="<?php echo e($form["expiry_date"]); ?>">
            <?php echo $feedback("expiry_date", $isEdit ? "Enter a valid date." : "Enter today or a later date."); ?>
        </div>

        <div class="col-12">
            <label for="description" class="form-label">Description <span class="opt">(optional)</span></label>
            <textarea id="description" name="description" rows="2" class="form-control<?php echo $invalid("description"); ?>"
                      maxlength="2000" placeholder="Strength, dosage form, storage notes…"><?php echo e($form["description"]); ?></textarea>
            <?php echo $feedback("description", "Description is too long."); ?>
        </div>
    </div>

    <div class="form-actions">
        <a href="<?php echo e($cancel); ?>" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-primary"><?php echo $isEdit ? "Save changes" : "Save medicine"; ?></button>
    </div>
</form>
