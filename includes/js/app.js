/* =====================================================================
   SHARED JAVASCRIPT (every page)
   1. Phone menu (slide-in sidebar)
   2. Notifications
   3. Confirmation dialog for forms with data-confirm="..."
   4. Dropdowns with data-autosubmit apply immediately
   5. Form validation for forms with class="needs-validation"
   6. Product form: the Rx option is only shown for type "Medicine"
   7. Keyboard shortcut ('/' to focus search)
   ===================================================================== */
(function () {
    "use strict";

    var body = document.body;

    /* ---------- 1. Phone menu ---------- */
    var toggle = document.getElementById("sidebarToggle");
    var backdrop = document.getElementById("sidebarBackdrop");

    function setMenu(open) {
        body.classList.toggle("menu-open", open);
        if (toggle) { toggle.setAttribute("aria-expanded", open ? "true" : "false"); }
    }
    if (toggle) {
        toggle.addEventListener("click", function () { setMenu(!body.classList.contains("menu-open")); });
    }
    if (backdrop) {
        backdrop.addEventListener("click", function () { setMenu(false); });
    }
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") { setMenu(false); }
    });

    /* ---------- 2. Notifications ---------- */
    document.querySelectorAll("#toastContainer .toast").forEach(function (el) {
        bootstrap.Toast.getOrCreateInstance(el).show();
    });

    /* ---------- 3. Confirmation dialog ---------- */
    // <form method="POST" data-confirm="Delete this?" data-confirm-title="Delete product">
    var modalEl = document.getElementById("confirmModal");
    if (modalEl) {
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        var okBtn = document.getElementById("confirmModalOk");
        var pendingForm = null;

        document.addEventListener("submit", function (e) {
            var form = e.target;
            if (!form.matches("form[data-confirm]") || form.dataset.confirmed === "1") { return; }
            e.preventDefault();
            pendingForm = form;
            document.getElementById("confirmModalTitle").textContent = form.dataset.confirmTitle || "Are you sure?";
            document.getElementById("confirmModalMessage").textContent = form.dataset.confirm;
            okBtn.textContent = form.dataset.confirmButton || "Continue";
            modal.show();
        });

        okBtn.addEventListener("click", function () {
            if (!pendingForm) { return; }
            pendingForm.dataset.confirmed = "1";
            okBtn.disabled = true;
            pendingForm.requestSubmit ? pendingForm.requestSubmit() : pendingForm.submit();
        });

        modalEl.addEventListener("hidden.bs.modal", function () {
            if (pendingForm) { delete pendingForm.dataset.confirmed; }
            pendingForm = null;
            okBtn.disabled = false;
        });
    }

    /* ---------- 4. Dropdowns that apply immediately ---------- */
    document.querySelectorAll("select[data-autosubmit]").forEach(function (select) {
        select.addEventListener("change", function () { select.form.submit(); });
    });

    /* ---------- 5. Form validation ---------- */
    document.querySelectorAll("form.needs-validation").forEach(function (form) {
        form.addEventListener("submit", function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopImmediatePropagation();
                var first = form.querySelector(":invalid");
                if (first) { first.focus(); }
            } else if (!form.matches("[data-confirm]")) {
                // Prevent double-clicking "Save"
                var btn = form.querySelector('[type="submit"]');
                if (btn) { setTimeout(function () { btn.disabled = true; }, 0); }
            }
            form.classList.add("was-validated");
        }, true);
    });

    /* ---------- 6. Rx option only for medicines ---------- */
    var typeSelect = document.getElementById("product_type");
    var rxField = document.getElementById("rxField");
    if (typeSelect && rxField) {
        typeSelect.addEventListener("change", function () {
            var isMedicine = typeSelect.value === "Medicine";
            rxField.classList.toggle("d-none", !isMedicine);
            if (!isMedicine) { document.getElementById("requires_rx").checked = false; }
        });
    }

    /* ---------- 7. Keyboard shortcut ('/' to focus search) ---------- */
    document.addEventListener("keydown", function (e) {
        if (e.key === "/" && !["INPUT", "TEXTAREA", "SELECT"].includes(document.activeElement.tagName)) {
            var searchInput = document.querySelector('input[type="search"]');
            if (searchInput) {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        }
    });

    /* ---------- 8. Password show / hide toggle ---------- */
    document.querySelectorAll("[data-toggle-password]").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var input = document.getElementById(btn.getAttribute("data-toggle-password"));
            if (!input) { return; }
            var show = input.type === "password";
            input.type = show ? "text" : "password";
            btn.querySelector("i").className = show ? "bi bi-eye-slash" : "bi bi-eye";
            btn.setAttribute("aria-label", show ? "Hide password" : "Show password");
        });
    });

    /* ---------- 9. Light / Dark Mode Toggle ---------- */
    function updateThemeIcons(theme) {
        document.querySelectorAll(".theme-icon").forEach(function (icon) {
            if (theme === "dark") {
                icon.className = "bi bi-sun-fill text-warning theme-icon";
            } else {
                icon.className = "bi bi-moon-stars-fill text-secondary theme-icon";
            }
        });
    }

    // Set initial icon on load
    var currentTheme = document.documentElement.getAttribute("data-bs-theme") || "light";
    updateThemeIcons(currentTheme);

    document.querySelectorAll(".theme-toggle-btn").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var activeTheme = document.documentElement.getAttribute("data-bs-theme") || "light";
            var nextTheme = activeTheme === "dark" ? "light" : "dark";

            document.documentElement.setAttribute("data-bs-theme", nextTheme);
            document.documentElement.setAttribute("data-theme", nextTheme);
            localStorage.setItem("theme", nextTheme);
            updateThemeIcons(nextTheme);

            window.dispatchEvent(new CustomEvent("themechanged", { detail: { theme: nextTheme } }));
        });
    });
})();
