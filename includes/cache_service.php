<?php
class CacheService {
    private static $cache_dir = __DIR__ . '/../cache/';
    private static $cache_time = 3600; // 1 hour

    public static function get($key) {
        $file = self::$cache_dir . $key;
        if (file_exists($file) && (time() - self::$cache_time < filemtime($file))) {
            return unserialize(file_get_contents($file));
        }
        return false;
    }

    public static function set($key, $data) {
        if (!is_dir(self::$cache_dir)) {
            mkdir(self::$cache_dir, 0755, true);
        }
        file_put_contents(self::$cache_dir . $key, serialize($data));
    }
}
// No closing PHP tag - prevents accidental whitespace output