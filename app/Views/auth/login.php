
<h1>Sign in</h1>
<p class="lead">Enter your username and password.</p>

<?php if ($error !== "") { ?>
    <div class="alert alert-danger" role="alert"><?php echo e($error); ?></div>
<?php } ?>

<form method="POST" action="login.php" class="needs-validation" novalidate>
    <?php echo csrf_field(); ?>
    <input type="hidden" name="next" value="<?php echo e($next); ?>">

    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" id="username" name="username" class="form-control" required autofocus
               autocomplete="username" autocapitalize="none" value="<?php echo e($username); ?>">
        <div class="invalid-feedback">Enter your username.</div>
    </div>

    <div class="mb-4">
        <label for="password" class="form-label">Password</label>
        <div class="password-wrap">
            <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password">
            <button type="button" class="icon-btn" data-toggle-password="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100" <?php echo $lockedFor > 0 ? "disabled" : ""; ?>>Sign in</button>
</form>

<p class="auth-foot mb-0">Forgot your password? Ask your administrator.</p>
