<div class="page-head">
    <div>
        <h1>Notifications &amp; Activity</h1>
        <p>Live inventory warnings, expiry alerts, and recent system transactions.</p>
    </div>
    <div class="page-actions no-print">
        <a href="dashboard.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Dashboard</a>
    </div>
</div>

<nav class="tabs no-print" aria-label="Notification filters">
    <a href="notifications.php" class="tab<?php echo $currentFilter === 'all' ? ' active' : ''; ?>">
        All Notifications <span class="count"><?php echo $totalCount; ?></span>
    </a>
    <a href="notifications.php?type=alerts" class="tab<?php echo $currentFilter === 'alerts' ? ' active' : ''; ?>">
        Warnings &amp; Alerts <?php if ($urgentCount > 0) { ?><span class="count bad"><?php echo $urgentCount; ?></span><?php } ?>
    </a>
    <a href="notifications.php?type=activity" class="tab<?php echo $currentFilter === 'activity' ? ' active' : ''; ?>">
        System Activity
    </a>
</nav>

<div class="card">
    <?php if (empty($notifications)) { ?>
        <div class="empty">
            <div class="empty-icon success"><i class="bi bi-bell-slash"></i></div>
            <strong>No notifications in this category</strong>
            <span>All inventory metrics are healthy and no recent items match this filter.</span>
        </div>
    <?php } else { ?>
        <div class="list-group list-group-flush">
            <?php foreach ($notifications as $n) { ?>
                <div class="list-group-item d-flex align-items-start gap-3 p-3">
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center text-<?php echo e($n["type"]); ?>"
                         style="background: var(--<?php echo $n["type"] === 'danger' ? 'rose' : ($n["type"] === 'warning' ? 'amber' : ($n["type"] === 'orange' ? 'orange' : 'emerald')); ?>-50, #f1f5f9); width: 40px; height: 40px; flex-shrink: 0; font-size: 1.25rem;">
                        <i class="bi <?php echo e($n["icon"]); ?>"></i>
                    </div>
                    <div class="grow min-w-0">
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-1">
                            <h6 class="mb-0 fw-semibold text-break">
                                <?php echo e($n["title"]); ?>
                            </h6>
                            <div class="d-flex align-items-center gap-2">
                                <span class="status status-<?php echo e($n["type"]); ?>"><?php echo e($n["badge"]); ?></span>
                                <small class="text-muted text-nowrap"><?php echo e(format_date($n["time"])); ?></small>
                            </div>
                        </div>
                        <p class="text-muted mb-2 small"><?php echo e($n["message"]); ?></p>
                        <?php if (!empty($n["link"])) { ?>
                            <a href="<?php echo e($n["link"]); ?>" class="btn btn-sm btn-light py-1 px-2" style="font-size: 12px;">
                                View Details <i class="bi bi-arrow-right"></i>
                            </a>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>
