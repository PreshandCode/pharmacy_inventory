<?php
// =====================================================================
//  SIGN-IN LAYOUT (bottom half) - closes what auth_header.php opened.
// =====================================================================
?>
        </div><!-- /.card -->
    </div>
</div>

<script>
// Show / hide password
document.querySelectorAll("[data-toggle-password]").forEach(function (btn) {
    btn.addEventListener("click", function () {
        var input = document.getElementById(btn.getAttribute("data-toggle-password"));
        var show = input.type === "password";
        input.type = show ? "text" : "password";
        btn.querySelector("i").className = show ? "bi bi-eye-slash" : "bi bi-eye";
        btn.setAttribute("aria-label", show ? "Hide password" : "Show password");
    });
});

// Browser-side checks (the server checks again)
document.querySelectorAll("form.needs-validation").forEach(function (form) {
    form.addEventListener("submit", function (e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            var first = form.querySelector(":invalid");
            if (first) { first.focus(); }
        } else {
            var btn = form.querySelector('[type="submit"]');
            if (btn) { setTimeout(function () { btn.disabled = true; }, 0); }
        }
        form.classList.add("was-validated");
    });
});
</script>

</body>
</html>
