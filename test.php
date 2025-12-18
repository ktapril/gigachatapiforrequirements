<?php
$pass = 'jikook13';
$hash = '$2y$12$.W9d46fAf0SKE5YVYW.sfeQ/N.MDxHGjUtRsjG8IYyBbgsCkVAi4.';
if (password_verify($pass, $hash)) {
    echo "пароль верен!";
} else {
    echo "пароль НЕ верен!";
}
?>