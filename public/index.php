<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\FileHandler;
use App\GigaChatClient;

$message = '';
$messageType = 'info'; 
$auth = new Auth();

// что приходит в $_POST
var_dump($_POST);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {
        $result = $auth->register($_POST['login'], $_POST['password']);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    } elseif (isset($_POST['login'])) {
        $result = $auth->login($_POST['login'], $_POST['password']);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    } elseif (isset($_POST['upload'])) {
        if (!isset($_SESSION['user_login'])) {
            $message = 'сначала авторизуйтесь';
            $messageType = 'error';
        } else {
            $attempts = $auth->getUserAttemptsLeft();
            if ($attempts <= 0) {
                $message = 'попытки закончились :(';
                $messageType = 'error';
            } else {
                if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    $fileName = $_FILES['file']['name'];
                    $fileTmpName = $_FILES['file']['tmp_name'];
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    $allowedExtensions = ['txt', 'docx', 'pdf'];
                    if (in_array($fileExtension, $allowedExtensions)) {
                        $decrementResult = $auth->decrementAttempts();
                        if ($decrementResult['success']) {
                            $uploadPath = '../storage/uploads/' . uniqid() . '_' . $fileName;
                            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                                try {
                                    $fileHandler = new FileHandler();
                                    $text = $fileHandler->extractText($uploadPath, $fileExtension);

                                    $apiConfig = json_decode(file_get_contents('../config/api_keys.json'), true);
                                    $authKey = $apiConfig['gigachat_auth_key'];

                                    if (!$authKey) {
                                        throw new Exception('Authorization Key не найден в config/api_keys.json.');
                                    }

                                    $gigaClient = new GigaChatClient($authKey);
                                    $aiResult = $gigaClient->checkForAI($text);
                                    $requirementsResult = $gigaClient->checkForRequirements($text);

                                    $message = '<div class="result-section"><h3>Результат проверки на ИИ:</h3><p>' . htmlspecialchars($aiResult) . '</p></div>';
                                    $message .= '<div class="result-section"><h3>Результат проверки на соответствие требованиям:</h3><p>' . htmlspecialchars($requirementsResult) . '</p></div>';
                                    $messageType = 'success';

                                } catch (Exception $e) {
                                    $message = 'ошибка при обработке: ' . $e->getMessage();
                                    $messageType = 'error';
                                }
                                unlink($uploadPath);
                            } else {
                                $message = 'ошибка при сохранении файла';
                                $messageType = 'error';
                            }
                        } else {
                            $message = $decrementResult['message'];
                            $messageType = 'error';
                        }
                    } else {
                        $message = 'неподдерживаемый формат файла. разрешены: txt, docx, pdf';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'ошибка загрузки файла';
                    $messageType = 'error';
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
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>сервис для проверки студенческих работ</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_login'])): ?>
            <p><strong>Привет, <?php echo htmlspecialchars($_SESSION['user_login']); ?>!</strong></p>
            <p>осталось попыток: <strong><?php echo $attempts_left; ?></strong></p>

            <h2>загрузить работу</h2>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="file" accept=".txt,.docx,.pdf" required>
                <button type="submit" name="upload">загрузить и проверить</button>
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
    </div>
</body>
</html>