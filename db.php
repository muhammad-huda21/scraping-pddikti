<?php
/**
 * Data Storage Layer (Dual-engine: SQLite PDO with fallback to JSON)
 */

class Database {
    private static $pdo = null;
    private static $useSqlite = false;
    private static $dataDir = __DIR__ . '/data';

    public static function init() {
        if (extension_loaded('pdo_sqlite') && file_exists(__DIR__ . '/pddikti_aceh.db')) {
            try {
                self::$pdo = new PDO('sqlite:' . __DIR__ . '/pddikti_aceh.db');
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$useSqlite = true;
                return;
            } catch (Exception $e) {
                self::$useSqlite = false;
            }
        }
        self::$useSqlite = false;
    }

    public static function isSqlite() {
        if (self::$pdo === null) self::init();
        return self::$useSqlite;
    }

    public static function getPdo() {
        if (self::$pdo === null) self::init();
        return self::$pdo;
    }

    public static function getJsonData($table) {
        $file = self::$dataDir . '/' . $table . '.json';
        if (file_exists($file)) {
            $json = file_get_contents($file);
            return json_decode($json, true) ?: [];
        }
        return [];
    }

    public static function saveJsonData($table, $data) {
        if (!file_exists(self::$dataDir)) {
            @mkdir(self::$dataDir, 0777, true);
        }
        $file = self::$dataDir . '/' . $table . '.json';
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }
}
