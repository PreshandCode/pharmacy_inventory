<?php
// =====================================================================
//  STOCK HISTORY TABLE - shared by view_medicine.php and stock.php
//
//  Set before including:
//    $history       rows from stock_movements (+ full_name of the user)
//    $showMedicine  true to show the product name column
// =====================================================================

$historyTypes = ["in" => "In", "out" => "Out", "adjust" => "Correction"];
?>
<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <?php if (!empty($showMedicine)) { ?><th>Product</th><?php } ?>
                <th class="text-end">Change</th>
                <th class="text-end">Balance</th>
                <th>Reason</th>
                <th>By</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($history as $h) {
                $diff = (int) $h["quantity_after"] - (int) $h["quantity_before"]; ?>
                <tr>
                    <td class="text-nowrap">
                        <?php echo e(date("M j, Y", strtotime($h["created_at"]))); ?>
                        <div class="sub"><?php echo e(date("g:i A", strtotime($h["created_at"]))); ?></div>
                    </td>
                    <?php if (!empty($showMedicine)) { ?>
                        <td>
                            <?php if (!empty($h["medicine_id"])) { ?>
                                <a href="view_medicine.php?id=<?php echo (int) $h["medicine_id"]; ?>" class="title"><?php echo e($h["medicine_name"]); ?></a>
                            <?php } else { ?>
                                <span class="title"><?php echo e($h["medicine_name"]); ?></span> <span class="sub">(deleted)</span>
                            <?php } ?>
                        </td>
                    <?php } ?>
                    <td class="text-end num text-nowrap">
                        <span class="<?php echo $diff >= 0 ? "text-success" : "text-danger"; ?> fw-semibold">
                            <?php echo ($diff > 0 ? "+" : ($diff < 0 ? "−" : "")) . number_format(abs($diff)); ?>
                        </span>
                        <div class="sub"><?php echo e($historyTypes[$h["movement_type"]] ?? ""); ?></div>
                    </td>
                    <td class="text-end num"><?php echo number_format((int) $h["quantity_after"]); ?></td>
                    <td><?php echo $h["note"] ? e($h["note"]) : '<span class="muted">—</span>'; ?></td>
                    <td class="text-nowrap"><?php echo $h["full_name"] ? e($h["full_name"]) : '<span class="muted">System</span>'; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
