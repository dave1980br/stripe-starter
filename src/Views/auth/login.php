<h1>Admin Login</h1>

<?php if (!empty($error)): ?>
  <div style="padding:10px;border:1px solid #c00;color:#c00;margin-bottom:12px;">
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($basePath) ?>/login" style="max-width:420px;">
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

  <div style="margin-bottom:10px;">
    <label>Email</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required style="width:100%;padding:8px;">
  </div>

  <div style="margin-bottom:12px;">
    <label>Password</label><br>
    <input type="password" name="password" required style="width:100%;padding:8px;">
  </div>

  <button type="submit">Sign in</button>
</form>

<p style="margin-top:14px;">
  <a href="<?= htmlspecialchars($basePath) ?>/shop">← Back to shop</a>
</p>