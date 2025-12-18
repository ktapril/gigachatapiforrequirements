<?php
$pass = 'jikook13';
$hash = '$2y$12$vt/7DndlY14u3HKuW5St9uyuSP0JSWfhLhy9iA.FK7Z23KXC1mdPa';
if (password_verify($pass, $hash)) {
    echo "пароль верен!";
} else {
    echo "пароль НЕ верен!";
}
?>