<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return APP_BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function consume_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($message) ? $message : null;
}

function setting(string $key, string $default = ''): string
{
    static $cache = [];
    if (!array_key_exists($key, $cache)) {
        $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $cache[$key] = $stmt->fetchColumn();
    }
    return $cache[$key] === false ? $default : (string) $cache[$key];
}

function audit(string $action, string $module, ?int $recordId = null, array $details = []): void
{
    $userId = $_SESSION['user']['id'] ?? null;
    $stmt = db()->prepare('INSERT INTO audit_logs (user_id, action, module, record_id, ip_address, details) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $action, $module, $recordId, $_SERVER['REMOTE_ADDR'] ?? null, $details ? json_encode($details, JSON_THROW_ON_ERROR) : null]);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function format_average(?string $value): string
{
    return $value === null ? '—' : number_format((float) $value, 2);
}