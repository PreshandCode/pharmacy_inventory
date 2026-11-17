<?php
// =====================================================================
//  HELPER FUNCTIONS - small reusable tools used by every page
// =====================================================================


// An error whose message is safe to show to the user on the live site
// (e.g. "Please run upgrade.sql"). Other errors show a generic message.
class AppException extends Exception
{
}


// ---------------------------------------------------------------------
// OUTPUT & FORMATTING
// ---------------------------------------------------------------------

// Safely print text inside HTML (prevents XSS attacks)
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
}

// 1234.5 -> "₱1,234.50"
function money($amount): string
{
    return CURRENCY . number_format((float) $amount, 2);
}

// "2026-08-28" -> "Aug 28, 2026"
function format_date($date): string
{
    if (empty($date) || $date === "0000-00-00") {
        return "—";
    }
    $time = strtotime((string) $date);
    return $time ? date("M j, Y", $time) : "—";
}

// Go to another page and stop the current one
function redirect(string $url): void
{
    header("Location: " . $url);
    exit;
}

// Name of the current page file, e.g. "medicine.php"
function current_page(): string
{
    return basename($_SERVER["SCRIPT_NAME"] ?? "");
}


// ---------------------------------------------------------------------
// FLASH MESSAGES
// Show a one-time notification on the next page (e.g. "Product saved")
// type: success | error | warning | info
// ---------------------------------------------------------------------

function flash(string $type, string $message): void
{
    $_SESSION["flash"][] = ["type" => $type, "message" => $message];
}

function get_flash_messages(): array
{
    $messages = $_SESSION["flash"] ?? [];
    unset($_SESSION["flash"]);
    return $messages;
}


// ---------------------------------------------------------------------
// CSRF PROTECTION
// Every form that changes data includes a secret token. This stops other
// websites from tricking a logged-in user into deleting or editing data.
// ---------------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

// Put this inside every <form method="POST">
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// Call this at the start of every POST handler
function csrf_check(string $redirectTo = "dashboard.php"): void
{
    $sent = $_POST["csrf_token"] ?? "";
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        flash("error", "Your session expired or the form was invalid. Please try again.");
        redirect($redirectTo);
    }
}


// ---------------------------------------------------------------------
// STOCK & EXPIRY STATUS
// One place that decides what "Low Stock" or "Expiring Soon" means,
// so every page shows the same result.
// ---------------------------------------------------------------------

// Returns: key, label, css class, icon
function stock_status($quantity): array
{
    $quantity = (int) $quantity;

    if ($quantity <= 0) {
        return ["key" => "out", "label" => "Out of stock", "class" => "danger", "icon" => "bi-x-circle"];
    }
    if ($quantity < LOW_STOCK_THRESHOLD) {
        return ["key" => "low", "label" => "Low stock", "class" => "warning", "icon" => "bi-exclamation-triangle"];
    }
    return ["key" => "in", "label" => "In stock", "class" => "success", "icon" => "bi-check-circle"];
}

// Returns: key, label, css class, icon, days (days until expiry; negative = expired)
function expiry_status($expiryDate): array
{
    // Products such as thermometers have no expiry date
    if ($expiryDate === null || $expiryDate === "") {
        return ["key" => "none", "label" => "No expiry", "class" => "secondary", "icon" => "bi-dash-circle", "days" => null];
    }

    $today  = new DateTime("today");
    $expiry = DateTime::createFromFormat("Y-m-d", (string) $expiryDate);

    if (!$expiry) {
        return ["key" => "unknown", "label" => "No date", "class" => "secondary", "icon" => "bi-question-circle", "days" => null];
    }
    $expiry->setTime(0, 0);

    $days = (int) $today->diff($expiry)->format("%r%a");

    if ($days < 0) {
        return ["key" => "expired", "label" => "Expired", "class" => "danger", "icon" => "bi-calendar-x", "days" => $days];
    }
    if ($days <= EXPIRY_WARNING_DAYS) {
        // "critical" = true when it expires very soon (e.g. within 30 days)
        return [
            "key" => "expiring", "label" => "Expiring soon", "class" => "orange", "icon" => "bi-hourglass-split",
            "days" => $days, "critical" => $days <= min(EXPIRY_CRITICAL_DAYS, EXPIRY_WARNING_DAYS),
        ];
    }
    return ["key" => "good", "label" => "Good", "class" => "success", "icon" => "bi-calendar-check", "days" => $days];
}

// Coloured dot + text, e.g. status_badge(stock_status(5)) -> "● Low stock"
// (the text is always shown, so meaning never depends on colour alone)
function status_badge(array $status, ?string $label = null): string
{
    return '<span class="status status-' . e($status["class"]) . '">'
        . e($label ?? $status["label"])
        . '</span>';
}

// Quantity with its unit: (50, "box") -> "50 boxes", (1, "tube") -> "1 tube",
// (5, null) -> "5 units"
function qty_unit($quantity, ?string $unit): string
{
    $quantity = (int) $quantity;
    $unit = $unit ?: "unit";
    if ($quantity !== 1) {
        $unit .= ($unit === "box") ? "es" : "s";   // every other unit in Medicine::UNITS just adds "s"
    }
    return number_format($quantity) . " " . $unit;
}

// "in 12 days", "today", "3 days ago"
function days_label(?int $days): string
{
    if ($days === null) {
        return "";
    }
    if ($days === 0) {
        return "Expires today";
    }
    if ($days > 0) {
        return "in " . $days . " day" . ($days === 1 ? "" : "s");
    }
    $ago = abs($days);
    return $ago . " day" . ($ago === 1 ? "" : "s") . " ago";
}

// ---------------------------------------------------------------------
// LINKS & PAGINATION
// ---------------------------------------------------------------------

// Make a LIKE search safe (so % and _ typed by the user are literal)
function like_pattern(string $text): string
{
    return "%" . addcslashes($text, "\\%_") . "%";
}

// Build "page.php?a=1&b=2" from $params, dropping empty values
function build_url(string $page, array $params): string
{
    $params = array_filter($params, function ($v) {
        return $v !== "" && $v !== null;
    });
    return $page . ($params ? "?" . http_build_query($params) : "");
}

// Bootstrap pagination: « 1 … 4 5 6 … 12 »
//   $pageKey - name of the page number in the address (default "page")
//   $anchor  - optional "#section" to jump to after clicking
function render_pagination(
    string $page,
    array $params,
    int $current,
    int $totalPages,
    string $pageKey = "page",
    string $anchor = ""
): string {
    if ($totalPages <= 1) {
        return "";
    }

    $link = function (int $p) use ($page, $params, $pageKey, $anchor) {
        $params[$pageKey] = $p > 1 ? $p : "";
        return e(build_url($page, $params) . $anchor);
    };

    // Which page numbers to show
    $pages = [1, $totalPages];
    for ($i = $current - 1; $i <= $current + 1; $i++) {
        if ($i >= 1 && $i <= $totalPages) {
            $pages[] = $i;
        }
    }
    $pages = array_unique($pages);
    sort($pages);

    $html  = '<nav aria-label="Pages"><ul class="pagination flex-wrap">';
    $html .= '<li class="page-item' . ($current <= 1 ? " disabled" : "") . '">'
           . '<a class="page-link" href="' . $link(max(1, $current - 1)) . '" aria-label="Previous"><i class="bi bi-chevron-left"></i></a></li>';

    $prev = 0;
    foreach ($pages as $p) {
        if ($p - $prev > 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
        $html .= '<li class="page-item' . ($p === $current ? " active" : "") . '">'
               . '<a class="page-link" href="' . $link($p) . '"' . ($p === $current ? ' aria-current="page"' : "") . '>' . $p . '</a></li>';
        $prev = $p;
    }

    $html .= '<li class="page-item' . ($current >= $totalPages ? " disabled" : "") . '">'
           . '<a class="page-link" href="' . $link(min($totalPages, $current + 1)) . '" aria-label="Next"><i class="bi bi-chevron-right"></i></a></li>';
    $html .= '</ul></nav>';

    return $html;
}

// Sortable column header for the Products table, e.g. sort_header("name", "Medicine", ...)
function sort_header(string $key, string $label, array $params, string $sort, string $dir, string $class = ""): string
{
    $isSorted = ($sort === $key);
    $nextDir  = ($isSorted && $dir === "asc") ? "desc" : "asc";

    $p = $params;
    $p["sort"] = ($key === "name" && $nextDir === "asc") ? "" : $key;
    $p["dir"]  = $nextDir === "asc" ? "" : "desc";
    $p["page"] = "";

    $icon = $isSorted ? ($dir === "asc" ? '<i class="bi bi-arrow-up"></i>' : '<i class="bi bi-arrow-down"></i>') : "";
    $aria = $isSorted ? ($dir === "asc" ? "ascending" : "descending") : "none";

    return '<th class="' . e($class) . '" aria-sort="' . $aria . '">'
         . '<a class="sort' . ($isSorted ? " on" : "") . '" href="' . e(build_url("medicine.php", $p)) . '">'
         . e($label) . $icon . '</a></th>';
}
