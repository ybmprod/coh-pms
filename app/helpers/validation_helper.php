<?php
declare(strict_types=1);

function is_required(mixed $value): bool
{
    return trim((string) $value) !== '';
}

function is_valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function is_valid_phone(string $phone): bool
{
    return preg_match('/^[0-9+\-\s()]{7,20}$/', trim($phone)) === 1;
}

function is_positive_number(mixed $value): bool
{
    if (!is_numeric($value)) {
        return false;
    }

    return (float) $value >= 0;
}

function is_valid_date(string $value): bool
{
    $date = DateTime::createFromFormat('Y-m-d', $value);

    return $date instanceof DateTime && $date->format('Y-m-d') === $value;
}

function validate_password(string $password): array
{
    $errors = [];

    if (mb_strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if (preg_match('/[A-Za-z]/', $password) !== 1) {
        $errors[] = 'Password must include at least one letter.';
    }
    if (preg_match('/[0-9]/', $password) !== 1) {
        $errors[] = 'Password must include at least one digit.';
    }

    return $errors;
}
