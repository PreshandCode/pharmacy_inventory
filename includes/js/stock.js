/* =====================================================================
   STOCK PAGE
   - Shows only the reasons that match Stock in / Stock out / Set count
   - One-line preview: "Current 35 → after saving 20 (Low stock)"
   - Blocks impossible changes before sending (the server checks again)
   ===================================================================== */
(function () {
    "use strict";

    var form = document.getElementById("stockForm");
    if (!form) { return; }

    var LOW = parseInt(form.dataset.low, 10);
    var MAX = parseInt(form.dataset.max, 10);
    var TODAY = form.dataset.today;

    var select   = document.getElementById("medicine_id");
    var qty      = document.getElementById("quantity");
    var qtyLabel = document.getElementById("quantityLabel");
    var qtyFeed  = document.getElementById("quantityFeedback");
    var reason   = document.getElementById("reason");
    var note     = document.getElementById("note");
    var noteHint = document.getElementById("noteHint");
    var preview  = document.getElementById("preview");
    var fmt = new Intl.NumberFormat("en-PH");

    var LABELS = { "in": "Quantity to add", "out": "Quantity to remove", "set": "New total count" };

    // 50 + "box" -> "50 boxes" (same rule as qty_unit() in functions.php)
    function withUnit(n, unit) {
        unit = unit || "unit";
        if (n !== 1) { unit += unit === "box" ? "es" : "s"; }
        return fmt.format(n) + " " + unit;
    }

    function type() {
        var r = form.querySelector('input[name="type"]:checked');
        return r ? r.value : "in";
    }

    // Show only the reasons for the chosen type
    function updateReasons(wanted) {
        var t = type(), first = null, found = false;
        wanted = wanted || reason.value;
        Array.prototype.forEach.call(reason.options, function (o) {
            var ok = o.dataset.type === t;
            o.hidden = !ok;
            o.disabled = !ok;
            if (ok && !first) { first = o; }
            if (ok && o.value === wanted) { o.selected = true; found = true; }
        });
        if (!found && first) { first.selected = true; }
    }

    function show(text, cls) {
        preview.textContent = text;
        preview.className = "preview" + (cls ? " " + cls : "");
    }

    function update() {
        var t = type();
        qtyLabel.textContent = LABELS[t];
        qty.min = t === "set" ? "0" : "1";

        var needsNote = reason.value === "Other";
        note.required = needsNote;
        noteHint.textContent = needsNote ? "(required)" : "(optional)";

        qty.setCustomValidity("");
        var opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) { show(""); return; }

        var before = parseInt(opt.dataset.qty, 10);
        var unit = opt.dataset.unit;
        // Products without an expiry date are never expired
        var expired = opt.dataset.expiry !== "" && opt.dataset.expiry < TODAY;
        var n = qty.value === "" ? NaN : Number(qty.value);
        var after = NaN;
        if (Number.isInteger(n) && n >= 0) {
            after = t === "in" ? before + n : (t === "out" ? before - n : n);
        }

        var problem = "";
        if (t === "in" && expired) {
            problem = "This batch has expired. Add new stock as a new product with its own expiry date.";
        } else if (t === "out" && before === 0) {
            problem = "This product is out of stock.";
        } else if (!isNaN(after) && after < 0) {
            problem = "Only " + withUnit(before, unit) + " available.";
        } else if (!isNaN(after) && after > MAX) {
            problem = "Total cannot be more than " + fmt.format(MAX) + ".";
        } else if (t === "set" && after === before) {
            problem = "That is already the current count.";
        } else if (t !== "set" && n === 0) {
            problem = "Enter at least 1.";
        }

        if (problem) {
            qty.setCustomValidity(problem);
            qtyFeed.textContent = problem;
            show(problem, "is-error");
            return;
        }
        qtyFeed.textContent = "Enter a whole number.";

        if (isNaN(after)) {
            show("Current stock: " + withUnit(before, unit));
            return;
        }

        var status = after === 0 ? "out of stock" : (after < LOW ? "low stock" : "in stock");
        var text = fmt.format(before) + " → " + withUnit(after, unit) + " after saving (" + status + ")";
        show(text, after < LOW ? "is-warn" : "");
    }

    form.querySelectorAll('input[name="type"]').forEach(function (r) {
        r.addEventListener("change", function () { updateReasons(); update(); });
    });
    [select, qty, reason].forEach(function (el) {
        el.addEventListener("input", update);
        el.addEventListener("change", update);
    });

    updateReasons(reason.dataset.selected);
    update();

    // Opened from a "Restock" or "Update stock" button: put the cursor in Quantity
    if (select.value && !qty.value) { qty.focus(); }
})();
