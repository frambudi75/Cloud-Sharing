<?php
declare(strict_types=1);

namespace App\Services;

class WebhookService
{
    /**
     * Kirim notifikasi webhook untuk event tertentu
     */
    public static function trigger(string $event, string $title, string $description, array $fields = []): bool
    {
        $enabled = SettingService::get('webhook_enabled', '0');
        if ($enabled !== '1') {
            return false;
        }

        $url = trim(SettingService::get('webhook_url', '') ?? '');
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $activeEvents = json_decode(SettingService::get('webhook_events', '[]') ?? '[]', true);
        if (!is_array($activeEvents) || !in_array($event, $activeEvents, true)) {
            return false;
        }

        // Tentukan warna embed berdasarkan event (Hex to decimal untuk Discord)
        $color = match ($event) {
            'upload'   => 0x10b981, // Emerald Green
            'download' => 0x3b82f6, // Blue
            'login'    => 0x6366f1, // Indigo
            default    => 0x64748b, // Slate
        };

        $eventIcons = [
            'upload'   => '📤',
            'download' => '📥',
            'login'    => '🔐',
        ];
        $icon = $eventIcons[$event] ?? '🔔';

        // Format payload universal (kompatibel Discord, Slack, and generic webhooks)
        $embedFields = [];
        foreach ($fields as $name => $value) {
            $embedFields[] = [
                'name'   => (string) $name,
                'value'  => (string) $value,
                'inline' => true,
            ];
        }

        $payload = [
            'username'   => 'Cloud Sharing Bot',
            'content'    => "{$icon} **[{$event}]** {$title}\n{$description}",
            'text'       => "{$icon} [{$event}] {$title}: {$description}", // Untuk Slack/Mattermost
            'event'      => $event,
            'title'      => $title,
            'timestamp'  => date('c'),
            'embeds'     => [
                [
                    'title'       => "{$icon} {$title}",
                    'description' => $description,
                    'color'       => $color,
                    'fields'      => $embedFields,
                    'footer'      => [
                        'text' => 'Cloud Sharing Drive System',
                    ],
                    'timestamp'   => date('c'),
                ]
            ],
        ];

        return self::sendPost($url, $payload);
    }

    /**
     * Kirim HTTP POST dengan timeout sangat rendah (non-blocking feel)
     */
    public static function sendPost(string $url, array $payload): bool
    {
        $json = json_encode($payload);
        $startTime = microtime(true);
        $httpCode = 0;
        $success = false;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $json,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($json),
                    'User-Agent: CloudSharing-Webhook/1.2',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 3, // Maksimal 3 detik
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $success = ($response !== false && $httpCode >= 200 && $httpCode < 300);
        } else {
            // Fallback file_get_contents
            $context = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/json\r\nUser-Agent: CloudSharing-Webhook/1.2\r\n",
                    'content' => $json,
                    'timeout' => 3,
                ]
            ]);

            $res = @file_get_contents($url, false, $context);
            $success = ($res !== false);
            $httpCode = $success ? 200 : 500;
        }

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        // Catat pengiriman webhook ke log riwayat
        self::logDelivery([
            'timestamp'   => date('Y-m-d H:i:s'),
            'event'       => (string) ($payload['event'] ?? 'generic'),
            'title'       => (string) ($payload['title'] ?? 'Notification'),
            'url_masked'  => self::maskWebhookUrl($url),
            'http_code'   => $httpCode,
            'duration_ms' => $durationMs,
            'success'     => $success,
        ]);

        return $success;
    }

    /**
     * Dapatkan daftar riwayat pengiriman webhook terakhir
     */
    public static function getRecentDeliveries(int $limit = 10): array
    {
        try {
            $raw = SettingService::get('recent_webhook_deliveries', '[]');
            $list = json_decode((string)$raw, true);
            if (!is_array($list)) {
                return [];
            }
            return array_slice($list, 0, $limit);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Catat entri pengiriman ke daftar riwayat (maksimal 15 entri tersimpan)
     */
    public static function logDelivery(array $entry): void
    {
        try {
            $raw = SettingService::get('recent_webhook_deliveries', '[]');
            $list = json_decode((string)$raw, true);
            if (!is_array($list)) {
                $list = [];
            }
            array_unshift($list, $entry);
            $list = array_slice($list, 0, 15);
            SettingService::set('recent_webhook_deliveries', json_encode($list));
        } catch (\Throwable) {
            // Silently ignore
        }
    }

    /**
     * Samarkan URL webhook agar token rahasia tidak bocor di tampilan
     */
    public static function maskWebhookUrl(string $url): string
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? 'webhook';
        $path = $parsed['path'] ?? '';
        if (strlen($path) > 16) {
            $pathMasked = substr($path, 0, 8) . '...' . substr($path, -6);
        } else {
            $pathMasked = $path;
        }
        return ($parsed['scheme'] ?? 'https') . '://' . $host . $pathMasked;
    }
}
