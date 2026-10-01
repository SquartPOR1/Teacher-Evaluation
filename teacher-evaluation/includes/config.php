<?php
declare(strict_types=1);

define('APP_NAME', 'Teacher Evaluation');
define('APP_BASE_URL', rtrim((string) (getenv('APP_BASE_URL') ?: ''), '/'));
define('DB_HOST', (string) (getenv('DB_HOST') ?: '127.0.0.1'));
define('DB_PORT', (string) (getenv('DB_PORT') ?: '3306'));
define('DB_NAME', (string) (getenv('DB_NAME') ?: 'teacher_evaluation'));
define('DB_USER', (string) (getenv('DB_USER') ?: 'root'));
define('DB_PASS', (string) (getenv('DB_PASS') ?: ''));
define('SESSION_NAME', 'teacher_eval_session');
define('SESSION_IDLE_TIMEOUT', 1800);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
set_exception_handler(static function (Throwable $exception): void {
    error_log((string) $exception);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Service error</title><body style="font:16px system-ui;max-width:600px;margin:12vh auto;padding:24px;color:#203957"><h1>Something went wrong</h1><p>Please try again later. Technical details are not shown for your protection.</p></body></html>';
});

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secure,
        'samesite' => 'Lax',
        'path' => APP_BASE_URL !== '' ? APP_BASE_URL . '/' : '/',
    ]);
    session_start();
}