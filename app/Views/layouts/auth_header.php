<?php
// =====================================================================
//  SIGN-IN LAYOUT (top half) - used by login.php and setup_admin.php.
//  A single centred card. Set $pageTitle before including.
// =====================================================================


$pageTitle  = $pageTitle ?? "Sign in";
$cssVersion = @filemtime(dirname(APP_PATH) . "/includes/css/style.css") ?: 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo e($pageTitle); ?> · <?php echo e(APP_NAME . " " . APP_TAGLINE); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="includes/css/style.css?v=<?php echo $cssVersion; ?>" rel="stylesheet">

    <script>
        (function() {
            var theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', theme);
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>

<body>
<div class="auth-page position-relative">
    <div style="position: absolute; top: 18px; right: 20px; z-index: 10;">
        <button type="button" class="btn btn-light rounded-circle p-2 theme-toggle-btn shadow-sm" aria-label="Toggle dark/light mode" title="Toggle theme" style="width: 38px; height: 38px; display: inline-grid; place-items: center;">
            <i class="bi bi-moon-stars-fill theme-icon"></i>
        </button>
    </div>
    <div class="auth-box">

        <div class="brand">
            <span class="brand-mark"><i class="bi bi-capsule-pill"></i></span>
            <span class="brand-name"><?php echo e(APP_NAME); ?> <small><?php echo e(APP_TAGLINE); ?></small></span>
        </div>

        <?php foreach (get_flash_messages() as $msg) {
            $map = ["success" => "success", "error" => "danger", "warning" => "warning", "info" => "info"]; ?>
            <div class="alert alert-<?php echo $map[$msg["type"]] ?? "info"; ?>" role="alert"><?php echo e($msg["message"]); ?></div>
        <?php } ?>

        <div class="card">
