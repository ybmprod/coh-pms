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
    if (mb_strlen($password) > 128) {
        $errors[] = 'Password must be 128 characters or fewer.';
    }
    if (str_contains($password, "\0")) {
        $errors[] = 'Password contains invalid characters.';
    }
    if (preg_match('/[A-Za-z]/', $password) !== 1) {
        $errors[] = 'Password must include at least one letter.';
    }
    if (preg_match('/[0-9]/', $password) !== 1) {
        $errors[] = 'Password must include at least one digit.';
    }

    return $errors;
}

function hash_password_bcrypt(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * New password writes use a deliberately stronger bcrypt cost than PHP's
 * default. Existing lower-cost hashes are upgraded on the next successful login.
 */
function hash_password_secure(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function password_hash_needs_rehash(string $hash): bool
{
    return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
}

function is_bcrypt_hash(string $hash): bool
{
    return preg_match('/^\$2[ayb]\$\d{2}\$[\/A-Za-z0-9.]{53}$/', $hash) === 1;
}
