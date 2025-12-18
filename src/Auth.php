<?php

namespace App;

class Auth
{
    private $usersFile = '../storage/users.json';

    public function register($login, $password)
    {
        $users = $this->loadUsers();
        foreach ($users as $user) {
            if ($user['login'] === $login) {
                return ['success' => false, 'message' => 'пользователь уже существует'];
            }
        }

        $newUser = [
            'login' => $login,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'attempts_left' => 5 // начальное количество попыток
        ];

        $users[] = $newUser;
        $this->saveUsers($users);

        return ['success' => true, 'message' => 'пользователь успешно зарегистрирован'];
    }

    public function login($login, $password)
    {
        $users = $this->loadUsers();

        foreach ($users as $user) {
            error_log("Checking user: " . $user['login'] . " vs " . $login);
            if ($user['login'] === $login && password_verify($password, $user['password_hash'])) {
                error_log("Login successful for: " . $login);
                $_SESSION['user_login'] = $login;
                $_SESSION['attempts_left'] = $user['attempts_left'];
                return ['success' => true, 'message' => 'вход выполнен'];
            }
        }

        error_log("Login failed for: " . $login);
        return ['success' => false, 'message' => 'неверный логин или пароль'];
    }

    public function getUserAttemptsLeft()
    {
        if (!isset($_SESSION['user_login'])) {
            return null;
        }

        $users = $this->loadUsers();
        foreach ($users as $user) {
            if ($user['login'] === $_SESSION['user_login']) {
                return $user['attempts_left'];
            }
        }

        return null;
    }

    public function decrementAttempts()
    {
        if (!isset($_SESSION['user_login'])) {
            return ['success' => false, 'message' => 'пользователь не авторизован'];
        }

        $users = $this->loadUsers();
        foreach ($users as &$user) {
            if ($user['login'] === $_SESSION['user_login']) {
                if ($user['attempts_left'] <= 0) {
                    return ['success' => false, 'message' => 'попытки закончились'];
                }
                $user['attempts_left']--;
                $_SESSION['attempts_left'] = $user['attempts_left'];
                $this->saveUsers($users);
                return ['success' => true, 'message' => 'попытка успешно списана'];
            }
        }

        return ['success' => false, 'message' => 'ошибка обновления попыток'];
    }

    private function loadUsers()
    {
        if (!file_exists($this->usersFile)) {
            return [];
        }
        return json_decode(file_get_contents($this->usersFile), true, 512, JSON_UNESCAPED_UNICODE);
    }

    private function saveUsers($users)
    {
        file_put_contents($this->usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}