<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SESSION['username'] !== 'notnp') {
    header('Location: search.php');
    exit('Доступ запрещен. Вы не являетесь администратором.');
}

$msg = '';
$msgClass = '';

// Получаем ID редактируемого дома из URL
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: search.php');
    exit;
}

// Загружаем текущие данные дома
$stmt = $connection->prepare("SELECT * FROM houses WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $id]);
$house = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$house) {
    die('Ошибка: Данный проект дома не найден в базе данных. <a href="search.php">Назад</a>');
}

// Загружаем списки районов и сотрудников для выпадающих списков
$districts = $connection->query("SELECT id, name FROM districts")->fetchAll(PDO::FETCH_ASSOC);
$employees = $connection->query("SELECT id, first_name, last_name FROM employees")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_house'])) {
    $title = trim($_POST['title'] ?? '');
    $floors = intval($_POST['floors'] ?? 1);
    $windows = intval($_POST['windows'] ?? 1);
    $roof_type = $_POST['roof_type'] ?? 'gable';
    $selected_material = $_POST['wall_material'] ?? '';
    $has_attic = isset($_POST['has_attic']) ? 1 : 0;
    $has_finishing = isset($_POST['has_finishing']) ? 1 : 0;
    $area = floatval($_POST['area'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $district_id = intval($_POST['district_id'] ?? 1);
    $employee_id = intval($_POST['employee_id'] ?? 1);

    $imageName = $house['image']; // По умолчанию оставляем старое имя файла

    // Если загружено новое изображение
    if (isset($_FILES['house_img']) && $_FILES['house_img']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['house_img']['tmp_name'];
        $fileName = $_FILES['house_img']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadFileDir = __DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            // Генерируем уникальное имя для нового файла
            $imageName = time() . '_' . md5(rand()) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $imageName;

            if (!move_uploaded_file($fileTmpPath, $dest_path)) {
                $msg = 'Ошибка при сохранении нового файла фотографии.';
                $msgClass = 'error';
            }
        } else {
            $msg = 'Недопустимый тип файла (разрешены: JPG, PNG, GIF, WEBP).';
            $msgClass = 'error';
        }
    }

    // Если критических ошибок с фото не возникло, обновляем базу
    if ($msgClass !== 'error' && !empty($title) && $price > 0 && !empty($selected_material)) {
        try {
            $updateStmt = $connection->prepare('
                UPDATE houses SET 
                    title = :title, floors = :floors, windows = :windows, roof_type = :roof_type, 
                    wall_material = :wall_material, has_attic = :has_attic, has_finishing = :has_finishing, 
                    area = :area, price = :price, district_id = :district_id, employee_id = :employee_id, image = :image
                WHERE id = :id
            ');
            
            $updateStmt->execute([
                'title' => $title, 'floors' => $floors, 'windows' => $windows, 'roof_type' => $roof_type,
                'wall_material' => $selected_material, 'has_attic' => $has_attic, 'has_finishing' => $has_finishing,
                'area' => $area, 'price' => $price, 'district_id' => $district_id, 'employee_id' => $employee_id,
                'image' => $imageName, 'id' => $id
            ]);

            $msg = 'Проект дома успешно изменён!';
            $msgClass = 'success';
            
            // Сразу обновляем данные в локальной переменной, чтобы форма перерисовалась с новыми значениями
            $house['title'] = $title; $house['floors'] = $floors; $house['windows'] = $windows; $house['roof_type'] = $roof_type;
            $house['wall_material'] = $selected_material; $house['has_attic'] = $has_attic; $house['has_finishing'] = $has_finishing;
            $house['area'] = $area; $house['price'] = $price; $house['district_id'] = $district_id; $house['employee_id'] = $employee_id; $house['image'] = $imageName;
        } catch (PDOException $e) {
            $msg = 'Ошибка изменения в БД: ' . $e->getMessage();
            $msgClass = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование дома — Панель администратора</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background-color: #f0f0f0; padding: 20px; }
        .form-wrap { max-width: 550px; margin: 30px auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h2 { margin-bottom: 20px; color: #0056b3; border-bottom: 2px solid #0056b3; padding-bottom: 5px; text-align: center; }
        .row-el { margin-bottom: 15px; }
        .row-el label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 14px; }
        .row-el input[type="text"], .row-el input[type="number"], .row-el select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .checkbox-el { margin: 15px 0; }
        .checkbox-el label { font-weight: bold; margin-right: 20px; font-size: 14px; }
        button { width: 100%; background: #0056b3; color: white; border: none; padding: 12px; cursor: pointer; border-radius: 4px; font-size: 16px; font-weight: bold; }
        button:hover { background: #004085; }
        .alert { padding: 10px; color: white; font-weight: bold; text-align: center; margin-bottom: 15px; border-radius: 4px; }
        .success { background: yellowgreen; }
        .error { background: orangered; }
        .back-lnk { display: block; text-align: center; margin-top: 20px; color: #0056b3; font-weight: bold; text-decoration: none; }
        .current-photo-preview { display: block; max-width: 150px; height: auto; margin-top: 10px; border: 1px solid #ccc; border-radius: 4px; }
    </style>
</head>
<body>
<div class="form-wrap">
    <h2>Редактирование проекта дома</h2>

    <?php if (!empty($msg)): ?>
        <div class="alert <?= $msgClass ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="post" action="edit-house.php?id=<?= $id ?>" enctype="multipart/form-data">
        <div class="row-el">
            <label>Название проекта/дома</label>
            <input type="text" name="title" required value="<?= htmlspecialchars($house['title']) ?>">
        </div>
        <div class="row-el">
            <label>Количество этажей</label>
            <input type="number" name="floors" min="1" required value="<?= htmlspecialchars($house['floors']) ?>">
        </div>
        <div class="row-el">
            <label>Количество окон</label>
            <input type="number" name="windows" min="1" required value="<?= htmlspecialchars($house['windows']) ?>">
        </div>
        <div class="row-el">
            <label>Тип крыши</label>
            <select name="roof_type">
                <option value="gable" <?= $house['roof_type'] === 'gable' ? 'selected' : '' ?>>Двускатная (gable)</option>
                <option value="hip" <?= $house['roof_type'] === 'hip' ? 'selected' : '' ?>>Вальмовая (hip)</option>
                <option value="flat" <?= $house['roof_type'] === 'flat' ? 'selected' : '' ?>>Плоская (flat)</option>
            </select>
        </div>
        <div class="row-el">
            <label>Материал стен</label>
            <select name="wall_material" required>
                <option value="brick" <?= $house['wall_material'] === 'brick' ? 'selected' : '' ?>>Кирпич</option>
                <option value="wood" <?= $house['wall_material'] === 'wood' ? 'selected' : '' ?>>Дерево</option>
                <option value="aerated" <?= $house['wall_material'] === 'aerated' ? 'selected' : '' ?>>Газобетон</option>
                <option value="frame" <?= $house['wall_material'] === 'frame' ? 'selected' : '' ?>>Каркас</option>
            </select>
        </div>
        <div class="checkbox-el">
            <label><input type="checkbox" name="has_attic" value="1" <?= $house['has_attic'] ? 'checked' : '' ?>> Наличие мансарды</label>
            <label><input type="checkbox" name="has_finishing" value="1" <?= $house['has_finishing'] ? 'checked' : '' ?>> С отделкой</label>
        </div>
        <div class="row-el">
            <label>Площадь (м²)</label>
            <input type="number" name="area" step="0.01" min="1" required value="<?= htmlspecialchars($house['area']) ?>">
        </div>
        <div class="row-el">
            <label>Цена (₽)</label>
            <input type="number" name="price" min="1" required value="<?= htmlspecialchars($house['price']) ?>">
        </div>
        <div class="row-el">
            <label>Район</label>
            <select name="district_id">
                <?php foreach ($districts as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $house['district_id'] == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="row-el">
            <label>Ответственный сотрудник</label>
            <select name="employee_id">
                <?php foreach ($employees as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= $house['employee_id'] == $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="row-el">
            <label>Фотография проекта (загрузите новую для изменения)</label>
            <input type="file" name="house_img" accept="image/*">
            <p style="margin-top: 5px; font-size: 12px; color: #666;">Текущее изображение:</p>
            <img src="images/<?= htmlspecialchars($house['image']) ?>" class="current-photo-preview" alt="Текущее фото дома">
        </div>
        <button type="submit" name="edit_house">Сохранить изменения</button>
    </form>
    <a href="house-details.php?id=<?= $id ?>" class="back-lnk">← Отмена и назад в карточку</a>
</div>
</body>
</html>
