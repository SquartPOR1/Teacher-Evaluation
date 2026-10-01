<?php
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
  redirect(dashboard_path(current_user()['role']));
}

$configuredCode = (string) (getenv('ADMIN_SIGNUP_CODE') ?: '');
$codeConfigured = strlen($configuredCode) >= 24;
$authorizedUntil = (int) ($_SESSION['admin_signup_authorized_until'] ?? 0);
$authorized = $authorizedUntil > time();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  if (($_POST['action'] ?? '') === 'verify_code') {
    $attempts = $_SESSION['admin_signup_attempts'] ?? ['count' => 0, 'since' => time()];
    if (time() - (int) $attempts['since'] > 900) {
      $attempts = ['count' => 0, 'since' => time()];
    }
    if (!$codeConfigured) {
      $error = 'Administrator registration is not configured yet. Ask the site owner to set a private ADMIN_SIGNUP_CODE of at least 24 characters in the local Apache configuration.';
    } elseif ((int) $attempts['count'] >= 5) {
      $error = 'Too many incorrect passcode attempts. Wait 15 minutes and try again.';
    } elseif (hash_equals($configuredCode, (string) ($_POST['passcode'] ?? ''))) {
      unset($_SESSION['admin_signup_attempts']);
      $_SESSION['admin_signup_authorized_until'] = time() + 600;
      redirect('account-help.php');
    } else {
      $attempts['count']++;
      $_SESSION['admin_signup_attempts'] = $attempts;
      $error = 'That administrator passcode is incorrect.';
    }
  } elseif (($_POST['action'] ?? '') === 'create_admin' && $authorized && $codeConfigured) {
    $name = post_string('full_name', 160);
    $email = mb_strtolower(post_string('email', 190));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12 || $password !== $confirm) {
      $error = 'Enter a valid name and email, matching passwords, and a password of at least 12 characters.';
    } else {
      $pdo = db();
      try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO users (role_id, full_name, email, password_hash) SELECT id, ?, ?, ? FROM roles WHERE name = ?');
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), 'admin']);
        if ($stmt->rowCount() !== 1) {
          throw new RuntimeException('Administrator role is unavailable.');
        }
        $newUserId = (int) $pdo->lastInsertId();
        audit('create', 'users', $newUserId, ['role' => 'admin', 'source' => 'passcode_registration']);
        $pdo->commit();
        unset($_SESSION['admin_signup_authorized_until']);
        flash('Administrator account created. You can now sign in.');
        redirect('login.php');
      } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
        $error = $exception->getCode() === '23000' ? 'That email address is already in use.' : 'The administrator account could not be created.';
      } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
        error_log((string) $exception);
        $error = 'The administrator account could not be created.';
      }
    }
  } else {
    $authorized = false;
    unset($_SESSION['admin_signup_authorized_until']);
    $error = 'The passcode verification expired. Enter the passcode again.';
  }
}

$pageTitle = 'Administrator account';
require __DIR__ . '/includes/header.php';
?>
<section class="login-card account-help-card">
  <div class="eyebrow">ADMINISTRATORS ONLY</div>
  <?php if (!$codeConfigured): ?>
  <h2>Administrator registration is not configured</h2>
  <p class="muted">For security, administrator account creation requires a private passcode of at least 24 characters set by the site owner in the project’s local Apache .htaccess file. Add <strong>SetEnv ADMIN_SIGNUP_CODE "your-long-private-passcode"</strong> on a new line, replacing the example with a long, unique value. Do not share or commit this passcode.</p>
  <?php elseif ($authorized): ?>
  <h2>Create an administrator account</h2>
  <p class="muted">Passcode accepted. This form creates an administrator account only.</p>
  <?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="form-stack"><?= csrf_field() ?><input type="hidden" name="action" value="create_admin">
    <label>Full name<input name="full_name" required maxlength="160" autocomplete="name" value="<?= e($_POST['full_name'] ?? '') ?>"></label>
    <label>Email address<input type="email" name="email" required maxlength="190" autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>"></label>
    <label>Password<input type="password" name="password" minlength="12" autocomplete="new-password" required></label>
    <label>Confirm password<input type="password" name="confirm_password" minlength="12" autocomplete="new-password" required></label>
    <button class="button button-primary button-wide" type="submit">Create administrator</button>
  </form>
  <?php else: ?>
  <h2>Verify the administrator passcode</h2>
  <p class="muted">Enter the private passcode configured by the site owner. It authorizes administrator account creation for 10 minutes.</p>
  <?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="form-stack"><?= csrf_field() ?><input type="hidden" name="action" value="verify_code">
    <label>Administrator passcode<input type="password" name="passcode" autocomplete="current-password" required></label>
    <button class="button button-primary button-wide" type="submit">Continue</button>
  </form>
  <?php endif; ?>
  <p class="login-foot"><a href="<?= e(url('login.php')) ?>">Back to sign in</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
