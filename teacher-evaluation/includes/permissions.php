<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function require_role(string ...$roles): void
{
    require_login();
    if (!in_array((string) current_user()['role'], $roles, true)) {
        http_response_code(403);
        require __DIR__ . '/errors/403.php';
        exit;
    }
}