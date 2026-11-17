<?php
// =====================================================================
//  REGISTRATION VIEW (page: register.php)
// =====================================================================
?>

<h1>Create account</h1>
<p class="lead">Set up your account to start managing your inventory.</p>

<?php if (!empty($errors)) { ?>
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err) { ?>
                <li><?php echo e($err); ?></li>
            <?php } ?>
        </ul>
    </div>
<?php } ?>

<form method="POST" action="register.php" class="needs-validation" novalidate>
    <?php echo csrf_field(); ?>

    <div class="mb-3">
        <label for="full_name" class="form-label">Full Name / Pharmacy Name</label>
        <input type="text" id="full_name" name="full_name" class="form-control" required autofocus
               maxlength="100" value="<?php echo e($fullName); ?>" placeholder="e.g. Maria Santos / MediCare Pharmacy">
        <div class="invalid-feedback">Enter your name or pharmacy name.</div>
    </div>

    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" id="username" name="username" class="form-control" required
               maxlength="50" autocomplete="username" autocapitalize="none" value="<?php echo e($username); ?>"
               placeholder="letters, numbers, dot, or underscore">
        <div class="invalid-feedback">Enter a username (3–50 characters).</div>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <div class="password-wrap">
            <input type="password" id="password" name="password" class="form-control" required
                   minlength="<?php echo (int) MIN_PASSWORD_LENGTH; ?>" autocomplete="new-password">
            <button type="button" class="icon-btn" data-toggle-password="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
        </div>
        <div class="form-text">At least <?php echo (int) MIN_PASSWORD_LENGTH; ?> characters with letters and numbers.</div>
    </div>

    <div class="mb-4">
        <label for="confirm_password" class="form-label">Confirm Password</label>
        <div class="password-wrap">
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required
                   minlength="<?php echo (int) MIN_PASSWORD_LENGTH; ?>" autocomplete="new-password">
            <button type="button" class="icon-btn" data-toggle-password="confirm_password" aria-label="Show password"><i class="bi bi-eye"></i></button>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100">Create Account</button>
</form>

<p class="auth-foot mb-0">Already have an account? <a href="login.php" class="fw-semibold">Sign in</a></p>
