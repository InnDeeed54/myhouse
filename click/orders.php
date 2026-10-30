<?php
session_start();
require_once __DIR__ . '/config.php';

$message = '';
$messageClass = '';

// 1. Выход из аккаунта
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role']);
    header('Location: orders.php');
    exit;
}

// Запоминаем house_id из каталога, чтобы не потерять после авторизации
if (isset($_GET['house_id'])) {
    $_SESSION['redirect_house_id'] = intval($_GET['house_id']);
}

// 2. Проверка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $password !== '') {
        $stmt = $connection->prepare('SELECT id, username, password, role FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = !empty($user['role']) ? $user['role'] : 'user';
            
            // Если зашел админ notnp, сразу перенаправляем в админку
            if ($_SESSION['role'] === 'admin' || $_SESSION['username'] === 'notnp') {
                $_SESSION['role'] = 'admin'; // дублируем для надежности
                header('Location: admin.php');
                exit;
            }

            // Если обычный юзер шел покупать дом, отправляем на покупку
            if (!empty($_SESSION['redirect_house_id'])) {
                header('Location: orders.php?buy_house_id=' . $_SESSION['redirect_house_id']);
                exit;
            }

            header('Location: orders.php');
            exit;
        } else {
            $message = 'Неверный логин или пароль.';
            $messageClass = 'error';
        }
    } else {
        $message = 'Заполните все поля.';
        $messageClass = 'error';
    }
}
// 3. Автоматическая покупка дома (для авторизованных)
$buyHouseId = 0;
if (isset($_SESSION['user_id'])) {
    if (isset($_GET['buy_house_id'])) {
        $buyHouseId = intval($_GET['buy_house_id']);
    } elseif (isset($_SESSION['redirect_house_id'])) {
        $buyHouseId = $_SESSION['redirect_house_id'];
    }
    unset($_SESSION['redirect_house_id']); 
}

if ($buyHouseId > 0 && isset($_SESSION['user_id'])) {
    try {
        $houseStmt = $connection->prepare('SELECT title, price FROM houses WHERE id = :id AND (status = "available" OR status IS NULL) LIMIT 1');
        $houseStmt->execute(['id' => $buyHouseId]);
        $house = $houseStmt->fetch(PDO::FETCH_ASSOC);

        if ($house) {
            $connection->beginTransaction();

            $insertOrder = $connection->prepare('
                INSERT INTO orders (user_id, house_id, project_title, price, ordered_at) 
                VALUES (:user_id, :house_id, :project_title, :price, NOW())
            ');
            $insertOrder->execute([
                'user_id'       => $_SESSION['user_id'],
                'house_id'      => $buyHouseId,
                'project_title' => $house['title'],
                'price'         => $house['price']
            ]);

            // Меняем статус, чтобы дом скрылся из общего поиска
            $updateHouse = $connection->prepare('UPDATE houses SET status = "booked" WHERE id = :id');
            $updateHouse->execute(['id' => $buyHouseId]);

            $connection->commit();
            
            $_SESSION['flash_message'] = 'Дом успешно оформлен в заказ!';
            $_SESSION['flash_class'] = 'success';
            header('Location: orders.php');
            exit;
        } else {
            $message = 'Данный дом уже куплен или не существует.';
            $messageClass = 'error';
        }
    } catch (PDOException $e) {
        if ($connection->inTransaction()) { $connection->rollBack(); }
        $message = 'Ошибка при оформлении заказа: ' . $e->getMessage();
        $messageClass = 'error';
    }
}

if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageClass = $_SESSION['flash_class'];
    unset($_SESSION['flash_message'], $_SESSION['flash_class']);
}

// 4. Отмена заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order']) && isset($_SESSION['user_id'])) {
    $orderId = intval($_POST['order_id'] ?? 0);

    if ($orderId > 0) {
        try {
            $orderStmt = $connection->prepare('SELECT house_id FROM orders WHERE id = :id AND user_id = :user_id LIMIT 1');
            $orderStmt->execute(['id' => $orderId, 'user_id' => $_SESSION['user_id']]);
            $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                $connection->beginTransaction();

                if (!empty($order['house_id'])) {
                    $restoreHouse = $connection->prepare('UPDATE houses SET status = "available" WHERE id = :house_id');
                    $restoreHouse->execute(['house_id' => $order['house_id']]);
                }

                $deleteOrder = $connection->prepare('DELETE FROM orders WHERE id = :id');
                $deleteOrder->execute(['id' => $orderId]);

                $connection->commit();
                $message = 'Заказ успешно отменён. Проект возвращён в каталог.';
                $messageClass = 'success';
            } else {
                $message = 'Заказ не найден.';
                $messageClass = 'error';
            }
        } catch (PDOException $e) {
            if ($connection->inTransaction()) { $connection->rollBack(); }
            $message = 'Ошибка при отмене заказа: ' . $e->getMessage();
            $messageClass = 'error';
        }
    }
}

// Выборка всех заказов текущего пользователя
$orders = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $connection->prepare('SELECT id, project_title, price, ordered_at FROM orders WHERE user_id = :user_id ORDER BY ordered_at DESC');
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный кабинет — MyHouse.ru</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background-color: #f0f0f0; color: #333; line-height: 1.5; }
        .container { max-width: 1100px; margin: 20px auto; background-color: #ffffff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        header { width: 100%; height: 180px; background-image: url('shapka_div.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; }
        .main-content { padding: 30px; background-image: url('fon9.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; min-height: 600px; text-align: center; }
        h1 { font-size: 24px; margin-bottom: 10px; color: #0056b3; }
        h2 { font-size: 18px; margin-bottom: 20px; color: #333; }
        form.auth-form { width: 450px; margin: 0 auto 20px auto; padding: 20px; border: 1px solid #ccc; background: rgba(255, 255, 255, 0.9); text-align: left; }
        .form-element { margin-bottom: 15px; }
        .form-element label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 14px; }
        .form-element input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        form.auth-form button { background-color: #ff4757; color: white; border: none; padding: 10px 20px; cursor: pointer; font-weight: bold; border-radius: 4px; width: 100%; font-size: 15px; }
        form.auth-form button:hover { background-color: #ff6b81; }
        table.results-table { width: 100%; margin: 25px auto; border-collapse: collapse; background: rgba(255, 255, 255, 0.95); }
        table.results-table th, table.results-table td { padding: 10px; border: 2px solid #ccc; text-align: center; font-size: 13px; }
        table.results-table th { background-color: #0056b3; color: white; }
        .cancel-btn { background-color: #ff4757; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px; font-weight: bold; }
        .cancel-btn:hover { background-color: #ff6b81; }
        .message { padding: 10px; margin-bottom: 20px; border-radius: 4px; color: white; font-weight: bold; display: inline-block; }
        .success { background-color: yellowgreen; }
        .error { background-color: orangered; }
        .logout-link { color: #ff4757; text-decoration: none; font-weight: bold; }
        .logout-link:hover { text-decoration: underline; }
        .links-block { margin-top: 30px; }
        .links-block a { margin: 0 10px; color: #ff4757; font-weight: bold; text-decoration: none; font-size: 14px; }
        .links-block a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <header></header>
        <div class="main-content">
            <h1>Личный кабинет</h1>
            <h2>Управление оформленными заказами</h2>

            <?php if ($message !== ''): ?>
                <div class="message <?= $messageClass ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <form method="post" action="orders.php" class="auth-form">
                    <div class="form-element">
                        <label for="username">Логин</label>
                        <input id="username" type="text" name="username" required autocomplete="username">
                    </div>
                    <div class="form-element">
                        <label for="password">Пароль</label>
                        <input id="password" type="password" name="password" required autocomplete="current-password">
                    </div>
                    <button type="submit" name="login">Войти</button>
                </form>
            <?php else: ?>
                <p style="font-size: 16px; margin-bottom: 20px; background: white; display: inline-block; padding: 5px 15px; border-radius: 4px;">
                    Добро пожаловать, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>! 
                    [<a href="orders.php?action=logout" class="logout-link">Выйти</a>]
                </p>

                <?php if (count($orders) > 0): ?>
                    <table cellpadding="5" border="2" class="results-table">
                        <thead>
                            <tr>
                                <th>Номер заказа</th>
                                <th>Название проекта</th>
                                <th>Стоимость</th>
                                <th>Дата заказа</th>
                                <th>Действие</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= htmlspecialchars($order['id']) ?></td>
                                    <td align="left"><strong><?= htmlspecialchars($order['project_title']) ?></strong></td>
                                    <td><?= htmlspecialchars(number_format($order['price'], 0, '', ' ')) ?> ₽</td>
                                    <td><?= htmlspecialchars($order['ordered_at']) ?></td>
                                    <td>
                                        <form method="post" action="orders.php" style="margin:0; padding:0;">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <button type="submit" name="cancel_order" class="cancel-btn" onclick="return confirm('Вы уверены, что хотите отменить этот заказ?')">Отменить</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="background: white; padding: 10px;">У вас пока нет оформленных заказов.</p>
                <?php endif; ?>
            <?php endif; ?>

            <div class="links-block">
                <a href="search.php">← Перейти к поиску домов</a>
                <a href="index.html" style="color: #666;">На главную</a>
            </div>
        </div>
    </div>
</body>
</html>
