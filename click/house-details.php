<?php
// house-details.php — Страница просмотра дома с поддержкой старых папок, базы данных и защитой прав
require_once __DIR__ . '/config.php';
session_start();

// 1. Проверяем, какой дом запросил пользователь
if (!isset($_GET['id'])) {
    die("Ошибка: Проект не указан. <a href='search.php'>Вернуться к поиску</a>");
}

$house_id = intval($_GET['id']);

// Получаем данные о доме из базы данных
try {
    $stmt = $connection->prepare("
        SELECT h.*, d.name as district_name, e.first_name, e.last_name 
        FROM houses h
        LEFT JOIN districts d ON h.district_id = d.id
        LEFT JOIN employees e ON h.employee_id = e.id
        WHERE h.id = :id
    ");
    $stmt->execute(['id' => $house_id]);
    $house = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$house) {
        die("Ошибка: Проект дома №{$house_id} не найден в базе данных. <a href='search.php'>Назад</a>");
    }
} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}

// 2. ОБРАБОТКА ДОБАВЛЕНИЯ НОВОГО ФОТО (Только для Администратора notnp)
$upload_message = "";
$folder_code = preg_replace('/[^a-z0-9_-]/', '', strtolower(str_replace(' ', '-', $house['title'])));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['new_photo'])) {
    // ЖЁСТКАЯ ЗАЩИТА: Обычный юзер не сможет отправить файл через POST-запрос
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && $_SESSION['username'] === 'notnp') {
        $file = $_FILES['new_photo'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_ext, $allowed_exts)) {
                $project_dir = __DIR__ . DIRECTORY_SEPARATOR . 'projects' . DIRECTORY_SEPARATOR . $folder_code;
                if (!is_dir($project_dir)) {
                    mkdir($project_dir, 0755, true);
                }
                
                $new_filename = "user_" . time() . "_" . rand(100,999) . "." . $file_ext;
                $target_path = $project_dir . DIRECTORY_SEPARATOR . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $upload_message = "<p style='color: green; font-weight:bold;'>Фотография успешно добавлена к проекту!</p>";
                } else {
                    $upload_message = "<p style='color: red;'>Ошибка при сохранении файла.</p>";
                }
            } else {
                $upload_message = "<p style='color: red;'>Разрешены только изображения (jpg, png, gif, webp).</p>";
            }
        }
    } else {
        exit("Ошибка доступа! Вы не администратор.");
    }
}

// 3. ПОИСК ВСЕХ СУЩЕСТВУЮЩИХ ФОТОГРАФИЙ (И СТАРЫХ, И НОВЫХ)
$gallery_images = [];

if (!empty($house['image']) && $house['image'] !== 'no-image.jpg') {
    if (file_exists(__DIR__ . '/images/' . $house['image'])) {
        $gallery_images[] = 'images/' . $house['image'];
    }
}

$legacy_dir = __DIR__ . '/projects/' . $folder_code;
if (is_dir($legacy_dir)) {
    $files = scandir($legacy_dir);
    foreach ($files as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $gallery_images[] = 'projects/' . $folder_code . '/' . $file;
        }
    }
}

if (empty($gallery_images)) {
    $gallery_images[] = 'images/no-image.jpg';
}

$roof_names = ['gable' => 'Двускатная', 'hip' => 'Вальмовая', 'flat' => 'Плоская'];
$roof_display = $roof_names[$house['roof_type']] ?? $house['roof_type'];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($house['title']) ?> — Карточка проекта</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 30px; color: #333; }
        .card-wrap { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .top-navigation { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .back-btn { color: #0056b3; font-weight: bold; text-decoration: none; }
        .edit-btn { background: #0056b3; color: white; text-decoration: none; padding: 8px 16px; font-weight: bold; border-radius: 4px; font-size: 14px; }
        .edit-btn:hover { background: #004085; }
        h1 { color: #0056b3; margin-bottom: 15px; border-bottom: 2px solid #eaeaea; padding-bottom: 10px; }
        
        .main-slider { text-align: center; margin-bottom: 25px; background: #fafafa; padding: 15px; border-radius: 6px; }
        .main-slider img { max-width: 100%; max-height: 450px; border-radius: 4px; object-fit: contain; }
        .thumbnails { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 30px; justify-content: center; }
        .thumbnails img { width: 100px; height: 75px; object-fit: cover; border: 2px solid #ccc; border-radius: 4px; cursor: pointer; transition: 0.2s; }
        .thumbnails img:hover { border-color: #0056b3; }
        
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .info-table td { padding: 12px; border-bottom: 1px solid #eee; }
        .info-table td:first-child { font-weight: bold; color: #666; width: 40%; }
        .price-tag { font-size: 24px; color: #2ed573; font-weight: bold; }
        
        .upload-box { background: #f9f9f9; padding: 20px; border: 1px dashed #ccc; border-radius: 6px; margin-top: 30px; }
        .upload-box h3 { margin-bottom: 15px; color: #333; }
        button { background: #2ed573; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px; font-weight: bold; }
        button:hover { background: #26af5f; }
    </style>
</head>
<body>

<div class="card-wrap">
    <div class="top-navigation">
        <a href="search.php" class="back-btn">← Вернуться в каталог к поиску</a>
        
        <!-- КНОПКА РЕДАКТИРОВАНИЯ: Появляется только для администратора notnp -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && $_SESSION['username'] === 'notnp'): ?>
            <a href="edit-house.php?id=<?= $house['id'] ?>" class="edit-btn">Редактировать проект</a>
        <?php endif; ?>
    </div>
    
    <h1><?= htmlspecialchars($house['title']) ?></h1>

    <div class="main-slider">
        <img id="viewfinder" src="<?= htmlspecialchars($gallery_images[0]) ?>" alt="Фото дома">
    </div>

    <?php if (count($gallery_images) > 1): ?>
        <div class="thumbnails">
            <?php foreach ($gallery_images as $img): ?>
                <img src="<?= htmlspecialchars($img) ?>" onclick="document.getElementById('viewfinder').src='<?= htmlspecialchars($img) ?>'" alt="Миниатюра">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <table class="info-table">
        <tr>
            <td>Стоимость проекта:</td>
            <td class="price-tag"><?= number_format($house['price'], 0, '.', ' ') ?> ₽</td>
        </tr>
        <tr>
            <td>Материал стен:</td>
            <td><?= htmlspecialchars($house['wall_material']) ?></td>
        </tr>
        <tr>
            <td>Общая площадь:</td>
            <td><?= htmlspecialchars($house['area']) ?> м²</td>
        </tr>
        <tr>
            <td>Количество этажей:</td>
            <td><?= htmlspecialchars($house['floors']) ?></td>
        </tr>
        <tr>
            <td>Количество окон:</td>
            <td><?= htmlspecialchars($house['windows']) ?></td>
        </tr>
        <tr>
            <td>Тип крыши:</td>
            <td><?= htmlspecialchars($roof_display) ?></td>
        </tr>
        <tr>
            <td>Дополнительно:</td>
            <td>
                <?= $house['has_attic'] ? '✓ С мансардой' : '✗ Без мансарды' ?><br>
                <?= $house['has_finishing'] ? '✓ С отделкой под ключ' : '✗ Без отделки' ?>
            </td>
        </tr>
        <tr>
            <td>Район постройки:</td>
            <td><?= htmlspecialchars($house['district_name'] ?? 'Не указан') ?></td>
        </tr>
        <tr>
            <td>Ответственный менеджер:</td>
            <td><?= htmlspecialchars(($house['first_name'] ?? '') . ' ' . ($house['last_name'] ?? '')) ?></td>
        </tr>
    </table>

    <!-- ФОРМА ДОБАВЛЕНИЯ ФОТО: Скрывается от обычных пользователей и гостей -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && $_SESSION['username'] === 'notnp'): ?>
        <div class="upload-box">
            <h3>Добавить еще одно фото к этому дому (Режим Администратора)</h3>
            <?= $upload_message ?>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="new_photo" accept="image/*" required style="margin-bottom: 15px; display: block;">
                <button type="submit">Загрузить изображение в галерею</button>
            </form>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
