<?php

namespace App;

class Database
{
    private $pdo;

    public function __construct()
    {
        $host = 'localhost';
        $dbname = 'gigachat_users';
        $username = 'jimin';
        $password = 'jikook13';

        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

        try {
            $this->pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
            $this->createTable();
        } catch (\PDOException $e) {
            throw new \Exception("дключения к базе данных: ' . $e->getMessage());
        }
    }

    private function createTable()
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            login VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            attempts_left INT DEFAULT 5
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $this->pdo->exec($sql);
    }

    public function getConnection()
    {
        return $this->pdo;
    }
}