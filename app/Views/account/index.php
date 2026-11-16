
<div class="page-head">
    <div>
        <h1>My account</h1>
        <p>
            @<?php echo e($account["username"]); ?> · <?php echo e(ucfirst($account["role"])); ?>
            · Last sign-in <?php echo $account["last_login"] ? e(date("M j, Y g:i A", strtotime($account["last_login"]))) : "—"; ?>
        </p>
    </div>
</div>

<div style="max-width: 560px">
    <form method="POST" action="account.php" class="card card-pad section needs-validation" novalidate>
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="profile">
        <label for="full_name" class="form-label">Full name</label>
        <input type="text" id="full_name" name="full_name" class="form-control" maxlength="100" required
               value="<?php echo e($account["full_name"]); ?>">
        <div class="invalid-feedback">Enter your full name.</div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save name</button>
        </div>
    </form>

    <form method="POST" action="account.php" class="card card-pad needs-validation" novalidate>
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="password">
        <h2 class="fs-6 mb-3">Change password</h2>
        <div class="mb-3">
            <label for="current_password" class="form-label">Current password</label>
            <input type="password" id="current_password" name="current_password" class="form-control" required autocomplete="current-password">
        </div>
        <div class="row g-3">
            <div class="col-sm-6">
                <label for="new_password" class="form-label">New password</label>
                <input type="password" id="new_password" name="new_password" class="form-control"
                       minlength="<?php echo (int) MIN_PASSWORD_LENGTH; ?>" required autocomplete="new-password">
            </div>
            <div class="col-sm-6">
                <label for="confirm_password" class="form-label">Confirm new password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                       minlength="<?php echo (int) MIN_PASSWORD_LENGTH; ?>" required autocomplete="new-password">
            </div>
        </div>
        <div class="form-text">At least <?php echo (int) MIN_PASSWORD_LENGTH; ?> characters, with a letter and a number.</div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Change password</button>
        </div>
    </form>
</div>
