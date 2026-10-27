<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Env;
use Redis;
use Throwable;

class CacheService
{
    private static ?Redis $redis = null;
    private static bool $connectionAttempted = false;
    private static bool $isAvailable = false;

    private static function init(): void
    {
        if (self::$connectionAttempted) {
            return;
        }

        self::$connectionAttempted = true;

        if (!class_exists('Redis')) {
            self::$isAvailable = false;
            return;
        }

        $host = Env::get('REDIS_HOST', '127.0.0.1');
        $port = (int) Env::get('REDIS_PORT', 6379);
        $timeout = 1.0; // 1 second timeout

        try {
            $client = new Redis();
            if (@$client->connect($host, $port, $timeout)) {
                $password = Env::get('REDIS_PASSWORD', null);
                if (!empty($password)) {
                    $client->auth($password);
                }
                self::$redis = $client;
                self::$isAvailable = true;
            }
        } catch (Throwable) {
            self::$isAvailable = false;
            self::$redis = null;
        }
    }

    public static function isAvailable(): bool
    {
        self::init();
        return self::$isAvailable && self::$redis !== null;
    }

    public static function get(string $key): mixed
    {
        if (!self::isAvailable()) {
            return null;
        }

        try {
            $data = self::$redis->get('fileshare:' . $key);
            if ($data === false) {
                return null;
            }
            return unserialize($data);
        } catch (Throwable) {
            return null;
        }
    }

    public static function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        if (!self::isAvailable()) {
            return false;
        }

        try {
            $serialized = serialize($value);
            return self::$redis->setex('fileshare:' . $key, $ttl, $serialized);
        } catch (Throwable) {
            return false;
        }
    }

    public static function delete(string $key): bool
    {
        if (!self::isAvailable()) {
            return false;
        }

        try {
            return (bool) self::$redis->del('fileshare:' . $key);
        } catch (Throwable) {
            return false;
        }
    }

    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        $cached = self::get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        if ($value !== null) {
            self::set($key, $value, $ttl);
        }

        return $value;
    }
}
