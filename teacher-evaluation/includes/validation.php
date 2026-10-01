<?php
declare(strict_types=1);

function post_string(string $key, int $maxLength = 5000): string
{
    $value = trim((string) ($_POST[$key] ?? ''));
    return mb_substr($value, 0, $maxLength);
}

function valid_date_range(string $start, string $end): bool
{
    $from = strtotime($start);
    $to = strtotime($end);
    return $from !== false && $to !== false && $to > $from;
}

function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed.');
    }
}