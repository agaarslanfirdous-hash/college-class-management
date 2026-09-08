<?php
/**
 * Simple mail sender: PHP mail() with optional SMTP notes for cPanel.
 * For production PHPMailer can be dropped into app/vendor — this class stays the facade.
 *
 * @package CollegeCMS\Core
 */

declare(strict_types=1);

namespace App\Core;

final class Mailer
{
    public static function send(string $to, string $subject, string $bodyText): bool
    {
        $cfg = \app_config()['mail'] ?? [];
        $from = (string) ($cfg['from_address'] ?? 'noreply@localhost');
        $fromName = (string) ($cfg['from_name'] ?? 'CMS');
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=UTF-8',
            'From: ' . self::encodeHeader($fromName) . " <{$from}>",
            'Reply-To: ' . $from,
            'X-Mailer: PHP/' . PHP_VERSION,
        ];
        $ok = @mail($to, self::encodeHeader($subject), $bodyText, implode("\r\n", $headers));
        if (!$ok) {
            Logger::error('mail() failed', ['to' => $to, 'subject' => $subject]);
        }
        return $ok;
    }

    private static function encodeHeader(string $s): string
    {
        if (preg_match('/[^\x20-\x7E]/', $s)) {
            return '=?UTF-8?B?' . base64_encode($s) . '?=';
        }
        return $s;
    }
}
