<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function require_login(): void
{
    $user = current_user();
    if ($user === null) {
        flash('Sign in to continue.', 'warning');
        redirect('login.php');
    }
    if (session_status() !== PHP_SESSION_ACTIVE) {
        http_response_code(401);
        exit('Authentication required.');
    }
    if (time() - (int) ($_SESSION['last_activity'] ?? time()) > SESSION_IDLE_TIMEOUT) {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('Your session expired. Please sign in again.', 'warning');
        redirect('login.php');
    }
    $_SESSION['last_activity'] = time();
}

function dashboard_path(string $role): string
{
    return match ($role) {
        'admin' => 'admin/index.php',
        'teacher' => 'teacher/index.php',
        'student' => 'student/index.php',
        default => 'login.php',
    };
}