<?php
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect(dashboard_path(current_user()['role']));
}
$accountActionUrl = url('account-help.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = mb_strtolower(post_string('email', 190));
    $password = (string) ($_POST['password'] ?? '');
    $attempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'since' => time()];
    if (time() - (int) $attempts['since'] > 900) {
        $attempts = ['count' => 0, 'since' => time()];
    }
    if ((int) $attempts['count'] >= 8) {
        $error = 'Too many attempts. Wait 15 minutes before trying again.';
    } else {
        $stmt = db()->prepare("SELECT u.id, u.full_name, u.email, u.password_hash, r.name AS role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = ? AND u.status = 'active' LIMIT 1");
        $stmt->execute([$email]);
        $account = $stmt->fetch();
        if ($account && password_verify($password, $account['password_hash'])) {
            session_regenerate_id(true);
            unset($_SESSION['login_attempts']);
            $_SESSION['user'] = ['id' => (int) $account['id'], 'name' => $account['full_name'], 'email' => $account['email'], 'role' => $account['role']];
            $_SESSION['last_activity'] = time();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$account['id']]);
            audit('login', 'auth');
            redirect(dashboard_path($account['role']));
        }
        $attempts['count']++;
        $_SESSION['login_attempts'] = $attempts;
        $error = 'Email or password is incorrect.';
    }
}
$pageTitle = 'Sign in';
require __DIR__ . '/includes/header.php';
?>
<section class="login-wrap login-simple">
  <div class="login-card">
    <h1>Sign in</h1>
    <p class="muted">Use the email and password provided by your school.</p>
    <?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form-stack" autocomplete="on"><?= csrf_field() ?>
      <label>Email address<input type="email" name="email" autocomplete="username" required maxlength="190" value="<?= e($_POST['email'] ?? '') ?>"></label>
      <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
      <button class="button button-primary button-wide" type="submit">Sign in</button>
    </form><p class="login-foot">Your account and responses are protected by school access controls.</p>
    <a class="button button-wide" href="<?= e($accountActionUrl) ?>">Admin create account</a>
    <p class="login-foot"><a href="<?= e(url('demo.php')) ?>">Explore a demo of the portal</a></p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>