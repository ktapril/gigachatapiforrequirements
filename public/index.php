<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php'; 

use App\Auth;

$message = '';
$auth = new Auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {
        $result = $auth->register($_POST['login'], $_POST['password']);
        $message = $result['message'];
    } elseif (isset($_POST['login'])) {
        $result = $auth->login($_POST['login'], $_POST['password']);
        $message = $result['message'];
    }
}

$attempts_left = isset($_SESSION['attempts_left']) ? $_SESSION['attempts_left'] : null;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>сервис для проверки студенческих работ</title>
</head>
<body>
    <h1>сервис для проверки студенческих работ</h1>

    <?php if ($message): ?>
        <p><strong><?php echo htmlspecialchars($message); ?></strong></p>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_login'])): ?>
        <p>кккк, <?php echo htmlspecialchars($_SESSION['user_login']); ?>!</p>
        <p>осталось попыток: <?php echo $attempts_left; ?></p>
        <!-- здесь будет форма загрузки файла -->
    <?php else: ?>
        <h2>регистрация</h2>
        <form method="post">
            <input type="text" name="login" placeholder="логин" required>
            <input type="password" name="password" placeholder="пароль" required>
            <button type="submit" name="register">зарегистрироваться</button>
        </form>

        <h2>Вход</h2>
        <form method="post">
            <input type="text" name="login" placeholder="логин" required>
            <input type="password" name="password" placeholder="пароль" required>
            <button type="submit" name="login">войти</button>
        </form>
    <?php endif; ?>
</body>
</html>
