<?php

namespace App;

class Auth
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function register($login, $password)
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE login = ?");
            $stmt->execute([$login]);

            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'пользователь уже существует'];
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->db->prepare("INSERT INTO users (login, password_hash, attempts_left) VALUES (?, ?, 5)");
            $stmt->execute([$login, $passwordHash]);

            return ['success' => true, 'message' => 'пользователь успешно зарегистрирован'];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'ошибка при регистрации: ' . $e->getMessage()];
        }
    }

    public function login($login, $password)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, login, password_hash, attempts_left FROM users WHERE login = ?");
            $stmt->execute([$login]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_login'] = $user['login'];
                $_SESSION['attempts_left'] = $user['attempts_left'];
                return ['success' => true, 'message' => 'вход выполнен'];
            }

            return ['success' => false, 'message' => 'неверный логин или пароль'];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'ошибка при входе: ' . $e->getMessage()];
        }
    }

    public function getUserAttemptsLeft()
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        try {
            $stmt = $this->db->prepare("SELECT attempts_left FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch();

            return $result ? (int)$result['attempts_left'] : null;
        } catch (\PDOException $e) {
            error_log('Ошибка при получении попыток: ' . $e->getMessage());
            return null;
        }
    }

    public function decrementAttempts()
    {
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'пользователь не авторизован'];
        }

        try {
            $attempts = $this->getUserAttemptsLeft();
            if ($attempts <= 0) {
                return ['success' => false, 'message' => 'попытки закончились'];
            }

            $stmt = $this->db->prepare("UPDATE users SET attempts_left = attempts_left - 1 WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);

            $_SESSION['attempts_left'] = $attempts - 1;

            return ['success' => true, 'message' => 'попытка успешно списана'];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'ошибка обновления попыток: ' . $e->getMessage()];
        }
    }
}