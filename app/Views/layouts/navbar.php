<?php
// =====================================================================
//  NAVIGATION
//  - Left sidebar: logo, 4 main links, signed-in user menu at the bottom
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
    ["dashboard.php", "Dashboard", "bi-house",          ["index.php"]],
    ["medicine.php",  "Medicines", "bi-capsule",        ["add_medicine.php", "edit_medicine.php", "view_medicine.php"]],
    ["stock.php",     "Stock",     "bi-box-seam",       []],
    ["expiry.php",    "Expiry",    "bi-calendar2-x",    []],
];

// Number on the "Expiry" link: medicines with stock that are expired or expire very soon
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
            <li>
                <form method="POST" action="logout.php">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right"></i> Sign out</button>
                </form>
            </li>
        </ul>
    </div>
    <?php } ?>
</aside>

<div class="backdrop" id="sidebarBackdrop"></div>

<!-- ============================== MAIN AREA ============================== -->
<div class="main">
    <main class="content">
