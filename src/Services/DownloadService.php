<?php
declare(strict_types=1);

namespace App\Services;

class DownloadService
{
    /**
     * Mengalirkan berkas ke client dengan dukungan:
     * 1. Nginx X-Accel-Redirect (Zero-Copy) jika running di Nginx Docker
     * 2. Apache X-Sendfile jika modul terpasang
     * 3. High Performance Chunked Stream dengan HTTP Range support untuk XAMPP
     */
    public static function stream(
        string $filePath, 
        string $clientFilename, 
        string $mimeType = 'application/octet-stream', 
        bool $forceDownload = true
    ): void {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            http_response_code(404);
            echo "Berkas fisik tidak ditemukan di server.";
            exit;
        }

        $fileSize = filesize($filePath);
        $disposition = $forceDownload ? 'attachment' : 'inline';
        $encodedFilename = rawurlencode($clientFilename);

        // 1. Cek apakah berjalan di Nginx dengan X-Accel-Redirect (via server header atau path Linux /var/www)
        $isNginx = (isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'nginx') !== false)
            || (str_starts_with($filePath, '/var/www/') && file_exists('/etc/alpine-release'));

        if ($isNginx) {
            // Path internal Nginx
            $baseStorage = StorageService::getUploadDir();
            $relativePath = str_replace([$baseStorage, '\\'], ['', '/'], $filePath);
            $internalUri = '/internal-storage' . (str_starts_with($relativePath, '/') ? $relativePath : '/' . $relativePath);

            header("Content-Type: {$mimeType}");
            header("Content-Disposition: {$disposition}; filename=\"{$clientFilename}\"; filename*=UTF-8''{$encodedFilename}");
            header("X-Accel-Redirect: {$internalUri}");
            header("X-Accel-Buffering: no");
            header("Cache-Control: private, no-transform, no-cache, must-revalidate");
            header("Pragma: no-cache");
            exit;
        }

        // 2. Fallback Streaming Cerdas untuk Apache XAMPP dengan Dukungan HTTP Range (RFC 7233)
        // Memungkinkan video/audio seeking dan download resume tanpa crash memory
        if (ob_get_level()) {
            ob_end_clean();
        }

        $start = 0;
        $end = $fileSize - 1;
        $length = $fileSize;
        $httpCode = 200;

        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
            if (preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $range, $matches)) {
                $start = (int) $matches[1];
                if (!empty($matches[2])) {
                    $end = (int) $matches[2];
                }
            }
            if ($start > $end || $start >= $fileSize) {
                http_response_code(416); // Range Not Satisfiable
                header("Content-Range: bytes */{$fileSize}");
                exit;
            }
            $length = $end - $start + 1;
            $httpCode = 206; // Partial Content
        }

        http_response_code($httpCode);
        header("Content-Type: {$mimeType}");
        header("Content-Disposition: {$disposition}; filename=\"{$clientFilename}\"; filename*=UTF-8''{$encodedFilename}");
        header("Accept-Ranges: bytes");
        header("Content-Range: bytes {$start}-{$end}/{$fileSize}");
        header("Content-Length: {$length}");
        header("Cache-Control: private, max-age=3600, must-revalidate");
        header("Pragma: public");

        $fp = fopen($filePath, 'rb');
        if ($fp === false) {
            http_response_code(500);
            exit;
        }

        fseek($fp, $start);
        $bufferSize = 1024 * 64; // 64 KB per chunk
        $bytesRemaining = $length;

        while (!feof($fp) && $bytesRemaining > 0 && !connection_aborted()) {
            $readSize = min($bufferSize, $bytesRemaining);
            $data = fread($fp, $readSize);
            if ($data === false) {
                break;
            }
            echo $data;
            flush();
            $bytesRemaining -= strlen($data);
        }

        fclose($fp);
        exit;
    }
}
