<?php
require_once __DIR__ . '/includes/auth.php';
if (current_user()) {
    redirect(dashboard_path(current_user()['role']));
}
redirect('login.php');