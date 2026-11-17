<?php
// =====================================================================
//  PAGE HEADER - opens the HTML page and loads fonts, icons and styles.
//
//  Set before including this file:
//    $pageTitle - text in the browser tab (e.g. "Products")
// =====================================================================


$pageTitle = $pageTitle ?? "Pharmacy Inventory";

// ?v=<last modified time> makes browsers load the newest CSS after you upload changes
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
</head>

<body>
