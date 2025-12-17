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
    } elseif (isset($_POST['upload'])) {
        if (!isset($_SESSION['user_login'])) {
            $message = 'сначала авторизуйтесь';
        } else {
            $attempts = $auth->getUserAttemptsLeft();
            if ($attempts <= 0) {
                $message = 'попытки закончились.';
            } else {
                if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    $fileName = $_FILES['file']['name'];
                    $fileTmpName = $_FILES['file']['tmp_name'];
                    $fileType = $_FILES['file']['type'];
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    $allowedExtensions = ['txt', 'docx', 'pdf'];
                    if (in_array($fileExtension, $allowedExtensions)) {
                        $decrementResult = $auth->decrementAttempts();
                        if ($decrementResult['success']) {
                            $message = 'файл успешно загружен';
                        } else {
                            $message = $decrementResult['message'];
                        }
                    } else {
                        $message = 'неподдерживаемый формат файла. разрешены: txt, docx, pdf';
                    }
                } else {
                    $message = 'ошибка загрузки файла';
                }
            }
        }
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
        <p>ккк <?php echo htmlspecialchars($_SESSION['user_login']); ?>!</p>
        <p>осталось попыток: <?php echo $attempts_left; ?></p>

        <h2>загрузить работу</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="file" accept=".txt,.docx,.pdf" required>
            <button type="submit" name="upload">хагрузить и проверить</button>
        </form>

    <?php else: ?>
        <h2>регистрация</h2>
        <form method="post">
            <input type="text" name="login" placeholder="логин" required>
            <input type="password" name="password" placeholder="пароль" required>
            <button type="submit" name="register">зарегистрироваться</button>
        </form>

        <h2>вход</h2>
        <form method="post">
            <input type="text" name="login" placeholder="логин" required>
            <input type="password" name="password" placeholder="пароль" required>
            <button type="submit" name="login">войти</button>
        </form>
    <?php endif; ?>
</body>
</html>
