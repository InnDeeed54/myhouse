<?php
session_start();
require __DIR__ . '/config.php';

$message = '';
$messageClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // Твои стандартные проверки валидации
    if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        $message = 'Логин может содержать только латинские буквы и цифры.';
        $messageClass = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Введите корректный адрес электронной почты.';
        $messageClass = 'error';
    } elseif ($phone === '') {
        $message = 'Введите номер телефона.';
        $messageClass = 'error';
    } elseif (strlen($password) < 8) {
        $message = 'Пароль должен содержать не менее 8 символов.';
        $messageClass = 'error';
    } elseif ($password !== $passwordConfirm) {
        $message = 'Пароли не совпадают.';
        $messageClass = 'error';
    } else {
        // Проверяем через PDO, нет ли уже такого логина или email
        $check = $connection->prepare(
            'SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1'
        );
        $check->execute([
            'email' => $email,
            'username' => $username,
        ]);

        if ($check->fetch()) {
            $message = 'Этот логин или email уже зарегистрирован.';
            $messageClass = 'error';
        } else {
            // Хешируем пароль для безопасности
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Вставляем нового пользователя в базу данных
            $insert = $connection->prepare(
                'INSERT INTO users (username, password, email, phone)
                 VALUES (:username, :password, :email, :phone)'
            );

            $insert->execute([
                'username' => $username,
                'password' => $passwordHash,
                'email' => $email,
                'phone' => $phone,
            ]);

            $message = 'Регистрация прошла успешно. Теперь вы можете войти.';
            $messageClass = 'success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Регистрация — MyHouse.ru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main>
        <h1>Регистрация</h1>

        <?php if ($message !== ''): ?>
            <p class="<?= htmlspecialchars($messageClass, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="post" action="register.php">
            <div class="form-element">
                <label for="username">Логин</label>
                <input
                    id="username"
                    type="text"
                    name="username"
                    pattern="[a-zA-Z0-9]+"
                    maxlength="50"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="form-element">
                <label for="email">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    maxlength="190"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="form-element">
                <label for="phone">Телефон</label>
                <input
                    id="phone"
                    type="tel"
                    name="phone"
                    placeholder="+7 (999) 123-45-67"
                    maxlength="30"
                    autocomplete="tel"
                    required
                >
            </div>

            <div class="form-element">
                <label for="password">Пароль</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <div class="form-element">
                <label for="password_confirm">Повторите пароль</label>
                <input
                    id="password_confirm"
                    type="password"
                    name="password_confirm"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <button type="submit" name="register" value="register">
                Зарегистрироваться
            </button>
        </form>

        <p><a href="login.php">Уже зарегистрированы? Войти</a></p>
        <p><a href="index.html">На главную</a></p>
    </main>
</body>
</html>
