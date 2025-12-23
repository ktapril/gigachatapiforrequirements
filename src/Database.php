<?php

namespace App;

class Database
{
    private $pdo;

    public function __construct()
    {
        $dbPath = __DIR__ . '/../storage/database.db';

        if (!is_dir(dirname($dbPath))) {
            mkdir(dirname($dbPath), 0777, true);
        }

        $dsn = "sqlite:$dbPath";

        try {
            $this->pdo = new \PDO($dsn, null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
            $this->createTable();
        } catch (\PDOException $e) {
            throw new \Exception('Ошибка подключения к SQLite: ' . $e->getMessage());
        }
    }

    private function createTable()
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            login TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            attempts_left INTEGER DEFAULT 5
        );
        ";
        $this->pdo->exec($sql);
    }

    public function getConnection()
    {
        return $this->pdo;
    }
}