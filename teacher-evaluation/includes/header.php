<?php
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/csrf.php';
$pageTitle = $pageTitle ?? APP_NAME;
$user = current_user();
$notice = consume_flash();
$schoolName = setting('school_name', APP_NAME);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#153e75">
  <title><?= e($pageTitle) ?> · <?= e($schoolName) ?></title>
  <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
  <?php if (!$user): ?><link rel="stylesheet" href="<?= e(url('assets/css/auth.css?v=2')) ?>"><?php endif; ?>
  <?php if ($user): ?><link rel="stylesheet" href="<?= e(url('assets/css/luxury.css?v=5')) ?>"><?php endif; ?>
  <script defer src="<?= e(url('assets/js/app.js')) ?>"></script>
</head>
<body class="<?= $user ? 'app-user' : 'app-guest' ?>">
<div class="app-shell">
  <header class="topbar">
    <a class="brand" href="<?= e(url($user ? dashboard_path($user['role']) : 'login.php')) ?>"><span class="brand-mark" aria-hidden="true">TE</span><span class="brand-name"><?= e($schoolName) ?></span></a>
    <?php if ($user): ?>
      <button class="menu-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">☰</button>
      <div class="user-menu"><span class="avatar"><?= e(strtoupper(mb_substr($user['name'], 0, 1))) ?></span><span><?= e($user['name']) ?><small><?= e(ucfirst($user['role'])) ?></small></span><form method="post" action="<?= e(url('logout.php')) ?>"><?= csrf_field() ?><button class="link-button" type="submit">Sign out</button></form></div>
    <?php endif; ?>
  </header>
  <?php if ($user): ?>
  <div class="layout">
    <aside class="sidebar" id="sidebar">
      <p class="nav-label">WORKSPACE</p>
      <a href="<?= e(url(dashboard_path($user['role']))) ?>">▦ <span>Overview</span></a>
      <?php if ($user['role'] === 'admin'): ?>
        <p class="nav-label">ADMINISTRATION</p>
        <a href="<?= e(url('admin/users.php')) ?>">♙ <span>People</span></a>
        <a href="<?= e(url('admin/academics.php')) ?>">⌂ <span>Academics & setup</span></a>
        <a href="<?= e(url('admin/questionnaire.php')) ?>">☷ <span>Questionnaire</span></a>
        <a href="<?= e(url('admin/reports.php')) ?>">▤ <span>Reports</span></a>
        <a href="<?= e(url('admin/settings.php')) ?>">⚙ <span>Settings</span></a>
      <?php elseif ($user['role'] === 'student'): ?>
        <a href="<?= e(url('student/index.php')) ?>#evaluations">✓ <span>My evaluations</span></a>
      <?php else: ?>
        <a href="<?= e(url('teacher/index.php')) ?>#feedback">♡ <span>Feedback</span></a>
      <?php endif; ?>
      <div class="sidebar-note"><span class="status-dot"></span> Secure evaluation portal</div>
    </aside>
    <main class="content">
      <?php if ($notice): ?><div class="alert alert-<?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div><?php endif; ?>
  <?php else: ?>
    <main class="content content-auth">
      <?php if ($notice): ?><div class="alert alert-<?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div><?php endif; ?>
  <?php endif; ?>