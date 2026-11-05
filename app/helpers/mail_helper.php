<?php
declare(strict_types=1);

/**
 * Sends a plain-text system email through the configured transport.
 *
 * MAIL_TRANSPORT:
 *   auto - development: write to the local outbox; production: use PHP mail()
 *          when SMTP is configured, otherwise fail safely
 *   mail - use PHP mail() (development also keeps a copy in the outbox)
 *   log  - write to storage/logs/mail.log (offline development fallback)
 */
function send_system_email(string $to, string $subject, string $body): bool
{
    $transport = strtolower(trim((string) (function_exists('env_value')
        ? env_value('MAIL_TRANSPORT', 'auto')
        : getenv('MAIL_TRANSPORT'))));
    $isProduction = function_exists('env_value')
        ? strtolower((string) env_value('APP_ENV', 'development')) === 'production'
        : false;

    if ($transport === '' || $transport === 'auto') {
        if ($isProduction) {
            $transport = trim((string) ini_get('SMTP')) !== '' ? 'mail' : 'unavailable';
        } else {
            // PHP on Windows reports SMTP=localhost even when no mail server is
            // listening, and mail() may still return true after a send failure.
            // Keep development predictable by using the protected outbox unless
            // the developer explicitly opts into mail transport.
            $transport = 'log';
        }
    }

    $from = (string) (function_exists('env_value')
        ? env_value('MAIL_FROM', 'no-reply@hararecity.co.zw')
        : getenv('MAIL_FROM'));
    if ($from === '' || !is_valid_email_address($from)) {
        $from = 'no-reply@hararecity.co.zw';
    }

    $safeSubject = str_replace(["\r", "\n"], ' ', $subject);
    $safeTo = str_replace(["\r", "\n"], '', $to);

    if ($transport === 'unavailable') {
        write_mail_outbox($safeTo, $safeSubject, $body, 'FAILED');
        return false;
    }

    if ($transport === 'mail') {
        $headers = 'From: ' . $from . "\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n";

        if (@mail($safeTo, $safeSubject, $body, $headers)) {
            if (!$isProduction) {
                write_mail_outbox($safeTo, $safeSubject, $body, 'MAIL');
            }
            return true;
        }

        write_mail_outbox($safeTo, $safeSubject, $body, 'FAILED');
        return false;
    }

    write_mail_outbox($safeTo, $safeSubject, $body, 'LOG');
    return true;
}

function write_mail_outbox(string $to, string $subject, string $body, string $status = 'LOG'): void
{
    $logDir = function_exists('ensure_log_directory')
        ? ensure_log_directory()
        : dirname(__DIR__, 2) . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0750, true);
    }

    $entry = '[' . date('Y-m-d H:i:s') . '] ' . $status
        . ' To: ' . $to
        . ' | Subject: ' . $subject . "\n"
        . $body . "\n"
        . str_repeat('-', 72) . "\n";

    @file_put_contents($logDir . '/mail.log', $entry, FILE_APPEND | LOCK_EX);
    error_log('[COH-PMS Email ' . $status . '] To: ' . $to . ' | Subject: ' . $subject);
}

function is_valid_email_address(string $email): bool
{
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
}
