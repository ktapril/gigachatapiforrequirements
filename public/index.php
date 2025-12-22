<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\FileHandler;
use App\GigaChatClient;

$message = '';
$messageType = 'info'; // info, success, error
$auth = new Auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    var_dump($_POST); // <-- Отладка: посмотрим, что приходит в $_POST

    if (isset($_POST['register'])) {
        $result = $auth->register($_POST['login'], $_POST['password']);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    } elseif (isset($_POST['do_login'])) {
        $result = $auth->login($_POST['login'], $_POST['password']);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    } elseif (isset($_POST['upload'])) {
        error_log("Upload started");
        if (!isset($_SESSION['user_login'])) {
            $message = 'сначала авторизуйтесь';
            $messageType = 'error';
        } else {
            $attempts = $auth->getUserAttemptsLeft();
            error_log("Attempts left: " . $attempts);
            if ($attempts <= 0) {
                $message = 'попытки закончились :(';
                $messageType = 'error';
            } else {
                if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    error_log("File uploaded successfully to temp");
                    $fileName = $_FILES['file']['name'];
                    $fileTmpName = $_FILES['file']['tmp_name'];
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    $allowedExtensions = ['txt', 'docx', 'pdf'];
                    if (in_array($fileExtension, $allowedExtensions)) {
                        $decrementResult = $auth->decrementAttempts();
                        error_log("Decrement result: " . print_r($decrementResult, true));
                        if ($decrementResult['success']) {
                            $uploadPath = '../storage/uploads/' . uniqid() . '_' . $fileName;
                            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                                error_log("File moved to: " . $uploadPath);
                                try {
                                    $fileHandler = new FileHandler();
                                    $text = $fileHandler->extractText($uploadPath, $fileExtension);
                                    error_log("Text extracted, length: " . strlen($text));

                                    // Загружаем API-ключ
                                    $apiConfig = json_decode(file_get_contents('../config/api_keys.json'), true);
                                    $authKey = $apiConfig['gigachat_auth_key'];

                                    if (!$authKey) {
                                        throw new Exception('Authorization Key не найден в config/api_keys.json.');
                                    }

                                    $gigaClient = new GigaChatClient($authKey);

                                    // Вызываем все 7 новых методов
                                    $aiResult = $gigaClient->checkForAI($text);
                                    $impersonalResult = $gigaClient->checkForImpersonalStyle($text);
                                    $pastTenseResult = $gigaClient->checkForPastTense($text);
                                    $introductionResult = $gigaClient->checkForIntroduction($text);
                                    $mainBodyResult = $gigaClient->checkForMainBody($text);
                                    $conclusionResult = $gigaClient->checkForConclusion($text);
                                    $tableResult = $gigaClient->checkForTableFormatting($text);
                                    $figureResult = $gigaClient->checkForFigureFormatting($text);

                                    // Формируем сообщение с результатами
                                    $message = '<div class="result-section"><h3>Результат проверки на ИИ:</h3><p>' . htmlspecialchars($aiResult) . '</p></div>';
                                    $message .= '<div class="result-section"><h3>Проверка безличных конструкций:</h3><p>' . htmlspecialchars($impersonalResult) . '</p></div>';
                                    $message .= '<div class="result-section"><h3>Проверка глаголов в прошедшем времени:</h3><p>' . htmlspecialchars($pastTenseResult) . '</p></div>';
                                    $message .= '<div class="result-section"><h3>Проверка введения:</h3><p>' . htmlspecialchars($introductionResult) . '</p></div>';
                                    $message .= '<div class="result-section"><h3>Проверка основной части:</h3><p>' . htmlspecialchars($mainBodyResult) . '</p></div>';
                                    $message .= '<div class="result-section"><h3>Проверка заключения:</h3><p>' . htmlspecialchars($conclusionResult) . '</p></div>';
                                    $message .= '<div class="result-section"><h3>Проверка таблиц:</h3><p>' . htmlspecialchars($tableResult) . '</p></div>';
                                    $message .= '<div class="result-section"><h3>Проверка иллюстраций:</h3><p>' . htmlspecialchars($figureResult) . '</p></div>';
                                    $messageType = 'success';

                                } catch (Exception $e) {
                                    $message = 'ошибка при обработке: ' . $e->getMessage();
                                    $messageType = 'error';
                                }
                                unlink($uploadPath); // Удаляем временный файл
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
                <button type="submit" name="do_login">войти</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>