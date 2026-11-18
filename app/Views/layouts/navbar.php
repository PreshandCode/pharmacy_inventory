<?php
// =====================================================================
//  NAVIGATION
//  - Left sidebar: logo, 6 main links, signed-in user menu at the bottom
//  - On phones: a slim top bar with a menu button that slides the
//    sidebar in
//  Opens the main content area; layouts/footer.php closes it.
//
//  $navExpiryCount is provided by Controller::view() (from the Medicine model)
// =====================================================================

$navCurrentFile = current_page();
$navUser = current_user();

// [file, label, icon, other pages that also highlight this link]
$navLinks = [
    ["dashboard.php", "Dashboard",         "bi-house",           ["index.php"]],
    ["medicine.php",  "Products",          "bi-grid",            ["add_medicine.php", "edit_medicine.php", "view_medicine.php"]],
    ["stock.php",     "Stock Management",  "bi-box-seam",        []],
    ["expiry.php",    "Expiry Monitoring", "bi-calendar2-x",     []],
    ["reports.php",   "Reports",           "bi-bar-chart-line",  []],
    ["audit.php",     "Audit Trail",       "bi-clock-history",   []],
];

// Number on the "Expiry" link: products with stock that are expired or expire very soon
$navBadges = ["expiry.php" => (int) ($navExpiryCount ?? 0)];
?>

<!-- ===================== PHONE TOP BAR (small screens) ===================== -->
<header class="mobilebar">
    <button type="button" class="icon-btn" id="sidebarToggle" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false">
        <i class="bi bi-list"></i>
    </button>
    <a href="dashboard.php" class="brand">
        <span class="brand-mark"><i class="bi bi-capsule-pill"></i></span>
        <span class="brand-name"><?php echo e(APP_NAME); ?></span>
    </a>
</header>

<!-- ================================ SIDEBAR ================================ -->
<aside class="sidebar" id="sidebar" aria-label="Main navigation">

    <a href="dashboard.php" class="brand sidebar-brand">
        <span class="brand-mark"><i class="bi bi-capsule-pill"></i></span>
        <span class="brand-name"><?php echo e(APP_NAME); ?> <small><?php echo e(APP_TAGLINE); ?></small></span>
    </a>

    <nav class="side-nav">
        <?php foreach ($navLinks as [$navFile, $navLabel, $navIcon, $navAlso]) {
            $navActive = $navCurrentFile === $navFile || in_array($navCurrentFile, $navAlso, true);
            $navCount  = $navBadges[$navFile] ?? 0; ?>
            <a href="<?php echo e($navFile); ?>" class="side-link<?php echo $navActive ? " active" : ""; ?>"
               <?php echo $navActive ? 'aria-current="page"' : ""; ?>>
                <i class="bi <?php echo e($navIcon); ?>"></i>
                <span><?php echo e($navLabel); ?></span>
                <?php if ($navCount > 0) { ?>
                    <b class="side-count" title="<?php echo $navCount; ?> need attention"><?php echo $navCount > 99 ? "99+" : $navCount; ?></b>
                <?php } ?>
            </a>
        <?php } ?>
    </nav>

    <?php if ($navUser) { ?>
    <div class="dropup side-user">
        <button type="button" class="side-user-btn" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="avatar"><?php echo e(user_initials($navUser["full_name"])); ?></span>
            <span class="side-user-text">
                <strong><?php echo e($navUser["full_name"]); ?></strong>
                <small><?php echo e(ucfirst($navUser["role"])); ?></small>
            </span>
            <i class="bi bi-three-dots"></i>
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="account.php"><i class="bi bi-person"></i> My account</a></li>
        </ul>
    </div>
    <?php } ?>
</aside>

<div class="backdrop" id="sidebarBackdrop"></div>

<!-- ============================== MAIN AREA ============================== -->
<div class="main">
    <!-- Top Utility Bar with Theme Toggle, Notification Bell & User Controls -->
    <header class="top-nav-bar d-flex justify-content-end align-items-center px-4 py-2" style="height: 54px; position: sticky; top: 0; z-index: 1020; gap: 12px; width: 100%; box-sizing: border-box;">
        <!-- Light / Dark Mode Toggle -->
        <button type="button" class="btn btn-light rounded-circle p-2 theme-toggle-btn shadow-xs" aria-label="Toggle dark/light mode" title="Toggle theme" style="width: 38px; height: 38px; display: inline-grid; place-items: center;">
            <i class="bi bi-moon-stars-fill theme-icon"></i>
        </button>

        <div class="dropdown">
            <button class="btn btn-light position-relative p-2 rounded-circle" type="button" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="System Notifications">
                <i class="bi bi-bell fs-5"></i>
                <?php if (!empty($topNotifUrgentCount) && $topNotifUrgentCount > 0) { ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 10px;">
                        <?php echo $topNotifUrgentCount > 9 ? "9+" : $topNotifUrgentCount; ?>
                        <span class="visually-hidden">urgent notifications</span>
                    </span>
                <?php } elseif (!empty($topNotifTotal) && $topNotifTotal > 0) { ?>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle">
                        <span class="visually-hidden">new notifications</span>
                    </span>
                <?php } ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end shadow-sm border p-0" aria-labelledby="notifDropdown" style="width: 340px; max-width: calc(100vw - 32px);">
                <div class="p-3 border-bottom d-flex align-items-center justify-content-between bg-light">
                    <h6 class="mb-0 fw-bold">System Notifications</h6>
                    <span class="badge bg-secondary"><?php echo (int)($topNotifTotal ?? 0); ?> total</span>
                </div>
                <div style="max-height: 380px; overflow-y: auto;">
                    <?php if (empty($topNotifications)) { ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-check2-circle fs-3 d-block text-success mb-1"></i>
                            <small>All systems are clear and healthy.</small>
                        </div>
                    <?php } else { ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($topNotifications as $n) { ?>
                                <a href="<?php echo e($n["link"]); ?>" class="list-group-item list-group-item-action d-flex gap-3 align-items-start p-3">
                                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center text-<?php echo e($n["type"]); ?>"
                                         style="background: var(--<?php echo $n["type"] === 'danger' ? 'rose' : ($n["type"] === 'warning' ? 'amber' : ($n["type"] === 'orange' ? 'orange' : 'emerald')); ?>-50, #f1f5f9); width: 34px; height: 34px; flex-shrink: 0; font-size: 1rem;">
                                        <i class="bi <?php echo e($n["icon"]); ?>"></i>
                                    </div>
                                    <div class="grow min-w-0">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="small text-truncate" style="max-width: 170px;"><?php echo e($n["title"]); ?></strong>
                                            <span class="status status-<?php echo e($n["type"]); ?>" style="font-size: 10px; padding: 1px 6px;"><?php echo e($n["badge"]); ?></span>
                                        </div>
                                        <p class="text-muted small mb-0 line-clamp-2" style="font-size: 11.5px; line-height: 1.35;"><?php echo e($n["message"]); ?></p>
                                    </div>
                                </a>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
                <div class="p-2 border-top text-center bg-light">
                    <a href="notifications.php" class="text-decoration-none small fw-semibold">View All Notifications &rarr;</a>
                </div>
            </div>
        </div>

        <?php if ($navUser) { ?>
            <div class="vr my-2 text-muted opacity-25"></div>
            <form method="POST" action="logout.php" class="d-inline m-0">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1 py-1 px-2 fw-medium" title="Sign out of system">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="d-none d-sm-inline">Sign out</span>
                </button>
            </form>
        <?php } ?>
    </header>
    <main class="content">
