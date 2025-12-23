<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\FileHandler;
use App\GigaChatClient;

$message = '';
$messageType = 'info'; 
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

                                    $apiConfig = json_decode(file_get_contents('../config/api_keys.json'), true);
                                    $authKey = $apiConfig['gigachat_auth_key'];

                                    if (!$authKey) {
                                        throw new Exception('Authorization Key не найден в config/api_keys.json.');
                                    }

                                    $gigaClient = new GigaChatClient($authKey);

                                    $aiResult = $gigaClient->checkForAI($text);
                                    $pastTenseResult = $gigaClient->checkForPastTense($text);
                                    $introductionResult = $gigaClient->checkForIntroduction($text);
                                    $mainBodyResult = $gigaClient->checkForMainBody($text);
                                    $conclusionResult = $gigaClient->checkForConclusion($text);
                                    $tableResult = $gigaClient->checkForTableFormatting($text);
                                    $figureResult = $gigaClient->checkForFigureFormatting($text);

                                    $message = '<div class="result-section"><h3>Результат проверки на ИИ:</h3><p>' . htmlspecialchars($aiResult) . '</p></div>';
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
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 300px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .modal form input, .modal form button {
            width: 100%;
            margin-bottom: 10px;
            padding: 8px;
        }
    </style>
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
            <h2>вход</h2>
            <form method="post">
                <input type="text" name="login" placeholder="логин" required>
                <input type="password" name="password" placeholder="пароль" required>
                <button type="submit" name="do_login">войти</button>
            </form>

            <br>
            <button onclick="document.getElementById('registerModal').style.display='block'">регистрация</button>
        <?php endif; ?>
    </div>

    <div id="registerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('registerModal').style.display='none'">&times;</span>
            <h2>регистрация</h2>
            <form method="post">
                <input type="text" name="login" placeholder="логин" required>
                <input type="password" name="password" placeholder="пароль" required>
                <button type="submit" name="register">зарегистрироваться</button>
            </form>
        </div>
    </div>

    <script>
        window.onclick = function(event) {
            var registerModal = document.getElementById('registerModal');
            if (event.target == registerModal) {
                registerModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>