
<div class="page-head">
    <div>
        <a href="view_medicine.php?id=<?php echo $id; ?>" class="back-link"><i class="bi bi-arrow-left"></i> <?php echo e($medicine["medicine_name"]); ?></a>
        <h1>Edit medicine</h1>
    </div>
</div>

<?php include APP_PATH . "/Views/partials/medicine_form.php"; ?>
