<?php
// =====================================================================
//  PAGE FOOTER - closes the main area, then adds notifications, the
//  confirmation dialog and the JavaScript files.
//
//  Optional variables a page can set BEFORE including this file:
//    $useCharts   = true;                          // load Chart.js
//    $pageScripts = ["includes/js/dashboard.js"];  // extra page scripts
// =====================================================================

$flashMessages = get_flash_messages();
$jsVersion = @filemtime(dirname(APP_PATH) . "/includes/js/app.js") ?: 1;
?>
    </main>
</div>

<!-- Notifications (e.g. "Product saved") -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer">
    <?php foreach ($flashMessages as $msg) {
        $type = in_array($msg["type"], ["success", "error", "warning", "info"], true) ? $msg["type"] : "info"; ?>
        <div class="toast app-toast toast-<?php echo e($type); ?>" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
            <div class="toast-body"><?php echo e($msg["message"]); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    <?php } ?>
</div>

<!-- Confirmation dialog, used by any form with data-confirm="message" -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body">
                <h2 class="modal-title" id="confirmModalTitle">Are you sure?</h2>
                <p id="confirmModalMessage">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmModalOk">Delete</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($useCharts)) { ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php } ?>
<script src="includes/js/app.js?v=<?php echo $jsVersion; ?>"></script>
<?php foreach (($pageScripts ?? []) as $script) {
    $v = @filemtime(dirname(APP_PATH) . "/" . $script) ?: 1; ?>
<script src="<?php echo e($script); ?>?v=<?php echo $v; ?>"></script>
<?php } ?>

</body>
</html>
