<?php
session_start();
include('config.php');

$loginSuccess = false;
$error = '';

if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $query = $connection->prepare(
        "SELECT id, username, password FROM users WHERE username = :username"
    );
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    $query->execute();

    $user = $query->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $loginSuccess = true;
    } else {
        $error = 'Неверное имя пользователя или пароль.';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Авторизация</h1>

    <?php if ($loginSuccess): ?>
        <p class="success">Вы успешно вошли!</p>
        <p><a href="index.html">Вернуться на сайт</a></p>
    <?php else: ?>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-element">
                <label for="username">Имя пользователя</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    pattern="[a-zA-Z0-9]+"
                    required
                >
            </div>

            <div class="form-element">
                <label for="password">Пароль</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                >
            </div>

            <button type="submit" name="login" value="login">Войти</button>
        </form>
    <?php endif; ?>
</body>
</html>