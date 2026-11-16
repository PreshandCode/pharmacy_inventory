
<h1>Create the admin account</h1>
<p class="lead">First-time setup. You only do this once.</p>

<?php if ($errors) { ?>
    <div class="alert alert-danger" role="alert">
        <ul class="m-0"><?php foreach ($errors as $err) { ?><li><?php echo e($err); ?></li><?php } ?></ul>
    </div>
<?php } ?>

<form method="POST" action="setup_admin.php" class="needs-validation" novalidate autocomplete="off">
    <?php echo csrf_field(); ?>

    <div class="mb-3">
        <label for="full_name" class="form-label">Full name</label>
        <input type="text" id="full_name" name="full_name" class="form-control" maxlength="100" required
               value="<?php echo e($fullName); ?>">
        <div class="invalid-feedback">Enter your full name.</div>
    </div>

    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" id="username" name="username" class="form-control" minlength="3" maxlength="50"
               pattern="[A-Za-z0-9._]{3,50}" required autocapitalize="none" value="<?php echo e($username); ?>">
        <div class="form-text">Letters, numbers, dot or underscore.</div>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <div class="password-wrap">
            <input type="password" id="password" name="password" class="form-control"
                   minlength="<?php echo (int) MIN_PASSWORD_LENGTH; ?>" required autocomplete="new-password">
            <button type="button" class="icon-btn" data-toggle-password="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
        </div>
        <div class="form-text">At least <?php echo (int) MIN_PASSWORD_LENGTH; ?> characters, with a letter and a number.</div>
    </div>

    <div class="mb-4">
        <label for="confirm_password" class="form-label">Confirm password</label>
        <input type="password" id="confirm_password" name="confirm_password" class="form-control"
               minlength="<?php echo (int) MIN_PASSWORD_LENGTH; ?>" required autocomplete="new-password">
    </div>

    <button type="submit" class="btn btn-primary w-100">Create account</button>
</form>
