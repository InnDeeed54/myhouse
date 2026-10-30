<?php
require_once __DIR__ . '/config.php';
session_start();

// 1. Обработка удаления дома (Доступно строго только для администратора notnp)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_delete'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && $_SESSION['username'] === 'notnp') {
        $deleteId = intval($_POST['delete_house_id'] ?? 0);
        if ($deleteId > 0) {
            $delStmt = $connection->prepare("DELETE FROM houses WHERE id = :id");
            $delStmt->execute(['id' => $deleteId]);
            header("Location: search.php");
            exit;
        }
    } else {
        exit("Ошибка доступа! Вы не являетесь администратором.");
    }
}

$results = [];
$searched = false;

// 2. Обработка отправки поисковой формы
if (isset($_POST['posted'])) {
    $searched = true;
    $Otpravka = trim($_POST['Otpravka'] ?? '');
    $First = trim($_POST['First'] ?? '');

    if ($Otpravka == "" || $First == "") {
        echo "Вы не ввели параметры поиска. Пожалуйста, попробуйте снова.";
        exit;
    }

    // Выбираем строго только свободные (available) дома! Купленные скрываются из каталога
    $sql = "SELECT h.*, d.name AS district_name, e.first_name, e.last_name, e.phone AS emp_phone 
            FROM houses h
            LEFT JOIN districts d ON h.district_id = d.id
            LEFT JOIN employees e ON h.employee_id = e.id
            WHERE (h.status = 'available' OR h.status IS NULL)";
    $params = [];

    if ($Otpravka == 'district') { 
        $sql .= " AND d.name LIKE :s"; 
        $params['s'] = '%'.$First.'%'; 
    } elseif ($Otpravka == 'employee') { 
        $sql .= " AND (e.last_name LIKE :s OR e.first_name LIKE :s)"; 
        $params['s'] = '%'.$First.'%'; 
    } elseif ($Otpravka == 'roof') { 
        $sql .= " AND h.roof_type = :s"; 
        $params['s'] = $First; 
    } elseif ($Otpravka == 'floors') { 
        $sql .= " AND h.floors = :s"; 
        $params['s'] = intval($First); 
    }

    $stmt = $connection->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>MyHouse.ru - Поиск в БД</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background-color: #f0f0f0; color: #333; line-height: 1.5; }
        .container { max-width: 1100px; margin: 20px auto; background-color: #ffffff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        header { width: 100%; height: 180px; background-image: url('shapka_div.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; }
        .main-content { padding: 30px; background-image: url('fon9.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; min-height: 600px; }
        h1 { font-size: 24px; margin-bottom: 10px; color: #0056b3; text-align: center; }
        h2 { font-size: 18px; margin-bottom: 20px; color: #333; text-align: center; }
        form.search-form { width: 500px; margin: 0 auto 30px auto; padding: 20px; border: 1px solid #ccc; background: rgba(255, 255, 255, 0.9); box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        form.search-form p { margin-bottom: 10px; font-size: 14px; }
        form.search-form input[type="text"] { width: 100%; padding: 8px; border: 1px solid #ccc; margin-top: 5px; margin-bottom: 15px; }
        form.search-form button { background-color: #ff4757; color: white; border: none; padding: 10px 20px; cursor: pointer; font-weight: bold; border-radius: 4px; width: 100%; transition: background 0.2s; }
        form.search-form button:hover { background-color: #ff6b81; }
        table.results-table { width: 100%; margin: 20px auto; border-collapse: collapse; background: rgba(255, 255, 255, 0.95); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        table.results-table th, table.results-table td { padding: 10px; border: 2px solid #ccc; text-align: center; font-size: 13px; }
        table.results-table th { background-color: #0056b3; color: white; }
        .view-link { color: #0056b3; font-weight: bold; text-decoration: none; }
        .view-link:hover { text-decoration: underline; }
        .links-block { text-align: center; margin-top: 30px; }
        .links-block a { margin: 0 10px; color: #ff4757; font-weight: bold; text-decoration: none; font-size: 14px; }
        .links-block a:hover { text-decoration: underline; }
        .house-thumb { width: 80px; height: auto; display: block; margin: 0 auto; border-radius: 4px; border: 1px solid #ddd; }
        .user-info-bar { background: rgba(255,255,255,0.9); padding: 10px; border-radius: 4px; text-align: right; margin-bottom: 20px; font-size: 14px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <div class="container">
        <header></header>
        <div class="main-content">
            
            <div class="user-info-bar">
                <?php if (isset($_SESSION['user_id'])): ?>
                    Вы вошли как: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> (<?= htmlspecialchars($_SESSION['role'] ?? 'user') ?>) | 
                    <a href="orders.php?action=logout" style="color: #ff4757; text-decoration: none; font-weight: bold;">Выйти</a>
                <?php else: ?>
                    Вы просматриваете сайт как гость. <a href="orders.php" style="color: #0056b3; font-weight: bold; text-decoration: none;">Войти в личный кабинет</a> | <a href="register.php" style="color: #2ed573; font-weight: bold; text-decoration: none;">Регистрация</a>
                <?php endif; ?>
            </div>

            <h1>MyHouse.ru</h1>
            <h2>Поиск данных в базе данных</h2>

            <form method="post" action="search.php" class="search-form">
                <p><strong>Выберите критерий поиска:</strong></p>
                <p>
                    <input type="radio" name="Otpravka" value="district" checked> Район города<br>
                    <input type="radio" name="Otpravka" value="employee"> Фамилия сотрудника<br>
                    <input type="radio" name="Otpravka" value="roof"> Тип крыши (gable, hip, flat)<br>
                    <input type="radio" name="Otpravka" value="floors"> Количество этажей<br>
                </p>
                <p><label for="First"><strong>Введите строку:</strong></label>
                <input type="text" id="First" name="First" required></p>
                <input type="hidden" name="posted" value="1">
                <button type="submit">Поиск</button>
            </form>
            <?php if ($searched): ?>
                <p style="text-align: center; background: white; display: inline-block; padding: 5px 15px; margin: 0 auto 10px auto; border-radius: 4px; font-weight: bold; border: 1px solid #ccc;">Объектов найдено: <span id="count-holder"><?= count($results) ?></span>.</p>
                <?php if (count($results) > 0): ?>
                    <table border="2" cellpadding="5" class="results-table">
                        <thead>
                            <tr>
                                <th width="100">Фото</th>
                                <th width="150">Проект (Подробнее)</th>
                                <th width="60">Этажей</th>
                                <th width="60">Окон</th>
                                <th width="80">Крыша</th>
                                <th width="90">Материал</th>
                                <th width="70">Мансарда</th>
                                <th width="70">Отделка</th>
                                <th width="80">Площадь</th>
                                <th width="110">Цена</th>
                                <th width="120">Район</th>
                                <th width="100">Действие</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $row): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $finalThumb = '';
                                        $titleText = $row['title'];
                                        preg_match('/[a-zA-Z]-[0-9]+-[0-9a-zA-Z]+/i', $titleText, $matches);
                                        $folderName = isset($matches[0]) ? strtolower($matches[0]) : '';
                                        
                                        if (empty($folderName)) {
                                            $folderName = preg_replace('/[^a-z0-9_-]/', '', strtolower(str_replace(' ', '-', $titleText)));
                                        }
                                        
                                        if (!empty($folderName)) {
                                            $legacyFolder = __DIR__ . DIRECTORY_SEPARATOR . 'projects' . DIRECTORY_SEPARATOR . $folderName;
                                            if (is_dir($legacyFolder)) {
                                                $files = scandir($legacyFolder);
                                                foreach ($files as $file) {
                                                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                                        $finalThumb = 'projects/' . $folderName . '/' . $file;
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                        
                                        if (empty($finalThumb)) {
                                            $imgName = !empty($row['image']) ? $row['image'] : 'no-image.jpg';
                                            $finalThumb = 'images/' . $imgName;
                                        }
                                        ?>
                                        <img src="<?= htmlspecialchars($finalThumb) ?>" class="house-thumb" alt="Дом">
                                    </td>
                                    <td>
                                        <a class="view-link" href="house-details.php?id=<?= $row['id'] ?>">
                                            <?= htmlspecialchars($row['title']) ?> 🔍
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($row['floors']) ?></td>
                                    <td><?= htmlspecialchars($row['windows']) ?></td>
                                    <td><?= htmlspecialchars($row['roof_type']) ?></td>
                                    <td><?= htmlspecialchars($row['wall_material']) ?></td>
                                    <td><?= $row['has_attic'] ? 'Да' : 'Нет' ?></td>
                                    <td><?= $row['has_finishing'] ? 'Да' : 'Нет' ?></td>
                                    <td><?= htmlspecialchars($row['area']) ?> м²</td>
                                    <td><?= htmlspecialchars(number_format($row['price'], 0, '', ' ')) ?> ₽</td>
                                    <td><?= htmlspecialchars($row['district_name'] ?? '-') ?></td>
                                    <td>
                                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && $_SESSION['username'] === 'notnp'): ?>
                                            <!-- Кнопка удаления (Видит только админ notnp) -->
                                            <form method="post" action="search.php" style="margin:0; padding:0;" onsubmit="return confirm('Вы уверены, что хотите полностью удалить этот проект из базы?')">
                                                <input type="hidden" name="delete_house_id" value="<?= $row['id'] ?>">
                                                <button type="submit" name="do_delete" style="background-color: #ff4757; color: white; border: none; padding: 6px 12px; font-weight: bold; border-radius: 4px; font-size: 12px; cursor: pointer;">Удалить</button>
                                            </form>
                                        <?php else: ?>
                                            <!-- Кнопка покупки (Видят обычные пользователи и гости) -->
                                            <a href="orders.php?house_id=<?= $row['id'] ?>" style="display: inline-block; background-color: #2ed573; color: white; text-decoration: none; padding: 6px 12px; font-weight: bold; border-radius: 4px; font-size: 12px;">Купить</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; background: white; padding: 15px; border-radius: 4px; border: 1px solid #ccc;">Ничего не найдено.</p>
                <?php endif; ?>
            <?php endif; ?>

            <div class="links-block">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && $_SESSION['username'] === 'notnp'): ?>
                    <a href="admin.php">Добавить новый дом в базу</a>
                <?php endif; ?>
                <a href="orders.php">Просмотр заказов</a>
                <a href="index.html" style="color: #666;">На главную</a>
            </div>
        </div>
    </div>
</body>
</html>
