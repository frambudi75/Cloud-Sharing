<?php

namespace App\Services;

use App\Config\Database;
use PDO;

class ActivityLogService
{
    /**
     * Dapatkan IP asli pengguna dari request
     */
    public static function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ipList = explode(',', $_SERVER[$header]);
                $ip = trim($ipList[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Catat aktivitas ke tabel activity_logs
     */
    public static function log(
        ?int $userId,
        string $action,
        string $fileName,
        ?string $details = null,
        ?int $fileId = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): bool {
        try {
            $db = Database::getConnection();

            $clientIp = $ip ?: self::getClientIp();
            $ua = $userAgent !== null ? $userAgent : ($_SERVER['HTTP_USER_AGENT'] ?? '');

            $stmt = $db->prepare("
                INSERT INTO activity_logs (
                    user_id, action, file_id, file_name, details, ip_address, user_agent, created_at
                ) VALUES (
                    :user_id, :action, :file_id, :file_name, :details, :ip_address, :user_agent, NOW()
                )
            ");

            return $stmt->execute([
                ':user_id'    => $userId,
                ':action'     => $action,
                ':file_id'    => $fileId,
                ':file_name'  => mb_substr($fileName, 0, 255),
                ':details'    => $details,
                ':ip_address' => mb_substr($clientIp, 0, 45),
                ':user_agent' => mb_substr($ua, 0, 500)
            ]);
        } catch (\Throwable $e) {
            // Fail-safe: jangan gagalkan alur utama bila logging terkendala
            error_log('ActivityLogService error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil riwayat aktivitas pengguna
     */
    public static function getUserLogs(
        int $userId,
        int $limit = 50,
        int $offset = 0,
        ?string $filterAction = null,
        ?string $search = null
    ): array {
        try {
            $db = Database::getConnection();

            $where = ["user_id = :user_id"];
            $params = [':user_id' => $userId];

            if (!empty($filterAction) && $filterAction !== 'all') {
                $where[] = "action = :action";
                $params[':action'] = $filterAction;
            }

            if (!empty($search)) {
                $where[] = "(file_name LIKE :search OR details LIKE :search OR ip_address LIKE :search)";
                $params[':search'] = "%{$search}%";
            }

            $whereClause = implode(' AND ', $where);

            $stmt = $db->prepare("
                SELECT id, user_id, action, file_id, file_name, details, ip_address, user_agent, created_at
                FROM activity_logs
                WHERE {$whereClause}
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset
            ");

            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('ActivityLogService::getUserLogs error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Hitung total log pengguna berdasarkan filter
     */
    public static function countUserLogs(
        int $userId,
        ?string $filterAction = null,
        ?string $search = null
    ): int {
        try {
            $db = Database::getConnection();

            $where = ["user_id = :user_id"];
            $params = [':user_id' => $userId];

            if (!empty($filterAction) && $filterAction !== 'all') {
                $where[] = "action = :action";
                $params[':action'] = $filterAction;
            }

            if (!empty($search)) {
                $where[] = "(file_name LIKE :search OR details LIKE :search OR ip_address LIKE :search)";
                $params[':search'] = "%{$search}%";
            }

            $whereClause = implode(' AND ', $where);

            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM activity_logs
                WHERE {$whereClause}
            ");

            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Ambil riwayat aktivitas seluruh sistem (Khusus Administrator)
     */
    public static function getGlobalLogs(
        int $limit = 50,
        int $offset = 0,
        ?string $filterAction = null,
        ?string $search = null
    ): array {
        try {
            $db = Database::getConnection();

            $where = ["1=1"];
            $params = [];

            if (!empty($filterAction) && $filterAction !== 'all') {
                $where[] = "a.action = :action";
                $params[':action'] = $filterAction;
            }

            if (!empty($search)) {
                $where[] = "(a.file_name LIKE :search OR a.details LIKE :search OR a.ip_address LIKE :search OR u.username LIKE :search)";
                $params[':search'] = "%{$search}%";
            }

            $whereClause = implode(' AND ', $where);

            $stmt = $db->prepare("
                SELECT a.id, a.user_id, a.action, a.file_id, a.file_name, a.details, a.ip_address, a.user_agent, a.created_at,
                       u.username, u.email, u.role
                FROM activity_logs a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE {$whereClause}
                ORDER BY a.created_at DESC
                LIMIT :limit OFFSET :offset
            ");

            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('ActivityLogService::getGlobalLogs error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Hitung total log aktivitas global sistem
     */
    public static function countGlobalLogs(
        ?string $filterAction = null,
        ?string $search = null
    ): int {
        try {
            $db = Database::getConnection();

            $where = ["1=1"];
            $params = [];

            if (!empty($filterAction) && $filterAction !== 'all') {
                $where[] = "a.action = :action";
                $params[':action'] = $filterAction;
            }

            if (!empty($search)) {
                $where[] = "(a.file_name LIKE :search OR a.details LIKE :search OR a.ip_address LIKE :search OR u.username LIKE :search)";
                $params[':search'] = "%{$search}%";
            }

            $whereClause = implode(' AND ', $where);

            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM activity_logs a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE {$whereClause}
            ");

            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Statistik ringkasan aktivitas
     */
    public static function getUserLogStats(int $userId): array
    {
        try {
            $db = Database::getConnection();

            // Total aktivitas
            $totalStmt = $db->prepare("SELECT COUNT(*) FROM activity_logs WHERE user_id = :uid");
            $totalStmt->execute([':uid' => $userId]);
            $total = (int) $totalStmt->fetchColumn();

            // Total unduhan hari ini (langsung + publik)
            $dlStmt = $db->prepare("
                SELECT COUNT(*) FROM activity_logs 
                WHERE user_id = :uid 
                  AND action IN ('download', 'public_download') 
                  AND created_at >= CURDATE()
            ");
            $dlStmt->execute([':uid' => $userId]);
            $downloadsToday = (int) $dlStmt->fetchColumn();

            // Total unggahan hari ini
            $upStmt = $db->prepare("
                SELECT COUNT(*) FROM activity_logs 
                WHERE user_id = :uid 
                  AND action = 'upload' 
                  AND created_at >= CURDATE()
            ");
            $upStmt->execute([':uid' => $userId]);
            $uploadsToday = (int) $upStmt->fetchColumn();

            // Berkas paling sering diakses/diunduh
            $topFileStmt = $db->prepare("
                SELECT file_name, COUNT(*) as cnt 
                FROM activity_logs 
                WHERE user_id = :uid AND action IN ('download', 'public_download', 'upload')
                GROUP BY file_name 
                ORDER BY cnt DESC 
                LIMIT 1
            ");
            $topFileStmt->execute([':uid' => $userId]);
            $topFileRow = $topFileStmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total_events'    => $total,
                'downloads_today' => $downloadsToday,
                'uploads_today'   => $uploadsToday,
                'most_active_file'=> $topFileRow ? $topFileRow['file_name'] : '-'
            ];
        } catch (\Throwable $e) {
            return [
                'total_events'    => 0,
                'downloads_today' => 0,
                'uploads_today'   => 0,
                'most_active_file'=> '-'
            ];
        }
    }

    /**
     * Metadata badge visual dan label untuk jenis tindakan
     */
    public static function getActionBadge(string $action): array
    {
        return match ($action) {
            'upload' => [
                'label' => 'Unggah Berkas',
                'color' => 'text-emerald-400 bg-emerald-500/10 border-emerald-500/30',
                'dot'   => 'bg-emerald-400',
                'icon'  => 'arrow-up'
            ],
            'download' => [
                'label' => 'Unduhan Langsung',
                'color' => 'text-blue-400 bg-blue-500/10 border-blue-500/30',
                'dot'   => 'bg-blue-400',
                'icon'  => 'arrow-down'
            ],
            'public_download' => [
                'label' => 'Unduhan Publik',
                'color' => 'text-cyan-400 bg-cyan-500/10 border-cyan-500/30',
                'dot'   => 'bg-cyan-400',
                'icon'  => 'globe'
            ],
            'create_folder' => [
                'label' => 'Buat Folder',
                'color' => 'text-indigo-400 bg-indigo-500/10 border-indigo-500/30',
                'dot'   => 'bg-indigo-400',
                'icon'  => 'folder-plus'
            ],
            'create_text' => [
                'label' => 'Buat Catatan',
                'color' => 'text-teal-400 bg-teal-500/10 border-teal-500/30',
                'dot'   => 'bg-teal-400',
                'icon'  => 'document-text'
            ],
            'edit_text' => [
                'label' => 'Edit Berkas Teks',
                'color' => 'text-amber-400 bg-amber-500/10 border-amber-500/30',
                'dot'   => 'bg-amber-400',
                'icon'  => 'pencil'
            ],
            'rename' => [
                'label' => 'Ganti Nama',
                'color' => 'text-purple-400 bg-purple-500/10 border-purple-500/30',
                'dot'   => 'bg-purple-400',
                'icon'  => 'tag'
            ],
            'move' => [
                'label' => 'Pindahkan',
                'color' => 'text-violet-400 bg-violet-500/10 border-violet-500/30',
                'dot'   => 'bg-violet-400',
                'icon'  => 'arrows-right-left'
            ],
            'duplicate' => [
                'label' => 'Duplikat Berkas',
                'color' => 'text-sky-400 bg-sky-500/10 border-sky-500/30',
                'dot'   => 'bg-sky-400',
                'icon'  => 'document-duplicate'
            ],
            'share_public' => [
                'label' => 'Bagikan Publik',
                'color' => 'text-pink-400 bg-pink-500/10 border-pink-500/30',
                'dot'   => 'bg-pink-400',
                'icon'  => 'link'
            ],
            'revoke_share' => [
                'label' => 'Cabut Tautan',
                'color' => 'text-rose-400 bg-rose-500/10 border-rose-500/30',
                'dot'   => 'bg-rose-400',
                'icon'  => 'link-slash'
            ],
            'share_user' => [
                'label' => 'Bagi Pengguna',
                'color' => 'text-indigo-400 bg-indigo-500/10 border-indigo-500/30',
                'dot'   => 'bg-indigo-400',
                'icon'  => 'user-plus'
            ],
            'revoke_user_share' => [
                'label' => 'Cabut Akses Teman',
                'color' => 'text-orange-400 bg-orange-500/10 border-orange-500/30',
                'dot'   => 'bg-orange-400',
                'icon'  => 'user-minus'
            ],
            'delete' => [
                'label' => 'Buang ke Sampah',
                'color' => 'text-amber-400 bg-amber-500/10 border-amber-500/30',
                'dot'   => 'bg-amber-400',
                'icon'  => 'trash'
            ],
            'restore' => [
                'label' => 'Pulihkan Berkas',
                'color' => 'text-emerald-400 bg-emerald-500/10 border-emerald-500/30',
                'dot'   => 'bg-emerald-400',
                'icon'  => 'arrow-path'
            ],
            'permanent_delete' => [
                'label' => 'Hapus Permanen',
                'color' => 'text-red-400 bg-red-500/10 border-red-500/30',
                'dot'   => 'bg-red-400',
                'icon'  => 'fire'
            ],
            default => [
                'label' => ucfirst(str_replace('_', ' ', $action)),
                'color' => 'text-slate-400 bg-slate-500/10 border-slate-500/30',
                'dot'   => 'bg-slate-400',
                'icon'  => 'clock'
            ]
        };
    }

    /**
     * Deteksi perangkat dan peramban dari User Agent
     */
    public static function parseDevice(?string $userAgent): array
    {
        if (empty($userAgent)) {
            return ['device' => 'Tidak Diketahui', 'browser' => 'Klien Web'];
        }

        $device = 'Komputer';
        if (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
            $device = 'iOS / Apple';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $device = 'Android';
        } elseif (preg_match('/Windows/i', $userAgent)) {
            $device = 'Windows PC';
        } elseif (preg_match('/Macintosh|Mac OS X/i', $userAgent)) {
            $device = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $device = 'Linux';
        }

        $browser = 'Browser';
        if (preg_match('/Edg/i', $userAgent)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Google Chrome';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Mozilla Firefox';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Apple Safari';
        } elseif (preg_match('/curl|Wget/i', $userAgent)) {
            $browser = 'CLI / Terminal';
        }

        return [
            'device'  => $device,
            'browser' => $browser
        ];
    }

    /**
     * Format timestamp menjadi waktu lampau humanis
     */
    public static function formatTimeAgo(string $datetime): string
    {
        $time = strtotime($datetime);
        if (!$time) {
            return $datetime;
        }

        $diff = time() - $time;

        if ($diff < 60) {
            return 'Baru saja';
        } elseif ($diff < 3600) {
            $min = max(1, floor($diff / 60));
            return $min . ' mnt yang lalu';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' jam yang lalu';
        } elseif ($diff < 172800) {
            return 'Kemarin, ' . date('H:i', $time);
        } else {
            return date('d M Y, H:i', $time);
        }
    }
}

