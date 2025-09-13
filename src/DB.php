<?php
namespace App;

use PDO;

class DB {
    public static function get(array $config): PDO {
        static $pdo = null;
        if ($pdo) return $pdo;
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['db']['host'], $config['db']['port'], $config['db']['name'], $config['db']['charset']);
        $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    }
}