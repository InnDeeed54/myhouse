<?php
session_start();
require_once __DIR__ . '/config.php';

// ЖЁСТКАЯ ЗАЩИТА: Доступ разрешён ТОЛЬКО администратору notnp
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SESSION['username'] !== 'notnp') {
    header('Location: search.php');
    exit('Доступ запрещен. Вы не являетесь администратором.');
}

$msg = '';
$msgClass = '';

$districts = $connection->query("SELECT id, name FROM districts")->fetchAll(PDO::FETCH_ASSOC);
$employees = $connection->query("SELECT id, first_name, last_name FROM employees")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_house'])) {
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

    $imageName = 'no-image.jpg';

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

            $imageName = time() . '_' . md5(rand()) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $imageName;

            if (!move_uploaded_file($fileTmpPath, $dest_path)) {
                $msg = 'Ошибка при перемещении файла в папку назначения.';
                $msgClass = 'error';
            }
        } else {
            $msg = 'Недопустимый тип файла (разрешены: JPG, PNG, GIF, WEBP).';
            $msgClass = 'error';
        }
    }

    if ($msgClass !== 'error' && !empty($title) && $price > 0 && !empty($selected_material)) {
        $variants_map = [
            'brick' => ['brick', 'Кирпич'],
            'aerated' => ['aerated', 'Газобетон'],
            'wood'  => ['wood', 'Дерево'],
            'frame' => ['frame', 'Каркас']
        ];
        
        $test_values = $variants_map[$selected_material] ?? ['brick'];
        $success_saved = false;

        foreach ($test_values as $val) {
            try {
                $stmt = $connection->prepare('
                    INSERT INTO houses (title, floors, windows, roof_type, wall_material, has_attic, has_finishing, area, price, district_id, employee_id, image, status) 
                    VALUES (:title, :floors, :windows, :roof_type, :wall_material, :has_attic, :has_finishing, :area, :price, :district_id, :employee_id, :image, "available")
                ');
                
                $stmt->execute([
                    'title' => $title, 'floors' => $floors, 'windows' => $windows, 'roof_type' => $roof_type,
                    'wall_material' => $val, 'has_attic' => $has_attic, 'has_finishing' => $has_finishing,
                    'area' => $area, 'price' => $price, 'district_id' => $district_id, 'employee_id' => $employee_id,
                    'image' => $imageName
                ]);

                $success_saved = true;
                $msg = 'Проект дома успешно добавлен и доступен в каталоге!';
                $msgClass = 'success';
                break;
            } catch (PDOException $e) {
                if ($e->getCode() == '22001' || strpos($e->getMessage(), '1265') !== false) {
                    continue; 
                }
                $msg = 'Ошибка добавления в БД: ' . $e->getMessage();
                $msgClass = 'error';
                break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавление дома — Панель администратора</title>
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
        button { width: 100%; background: #2ed573; color: white; border: none; padding: 12px; cursor: pointer; border-radius: 4px; font-size: 16px; font-weight: bold; }
        button:hover { background: #26af5f; }
        .alert { padding: 10px; color: white; font-weight: bold; text-align: center; margin-bottom: 15px; border-radius: 4px; }
        .success { background: yellowgreen; }
        .error { background: orangered; }
        .back-lnk { display: block; text-align: center; margin-top: 20px; color: #0056b3; font-weight: bold; text-decoration: none; }
    </style>
</head>
<body>
<div class="form-wrap">
    <h2>Добавление нового дома</h2>

    <?php if (!empty($msg)): ?>
        <div class="alert <?= $msgClass ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="post" action="admin.php" enctype="multipart/form-data">
        <div class="row-el">
            <label>Название проекта/дома</label>
            <input type="text" name="title" required placeholder="Например: d-152-1d">
        </div>
        <div class="row-el">
            <label>Количество этажей</label>
            <input type="number" name="floors" min="1" value="2" required>
        </div>
        <div class="row-el">
            <label>Количество окон</label>
            <input type="number" name="windows" min="1" value="6" required>
        </div>
        <div class="row-el">
            <label>Тип крыши</label>
            <select name="roof_type">
                <option value="gable">Двускатная (gable)</option>
                <option value="hip">Вальмовая (hip)</option>
                <option value="flat">Плоская (flat)</option>
            </select>
        </div>
        <div class="row-el">
            <label>Материал стен</label>
            <select name="wall_material" required>
                <option value="" disabled selected>-- Выберите материал стен --</option>
                <option value="brick">Кирпич</option>
                <option value="wood">Дерево</option>
                <option value="aerated">Газобетон</option>
                <option value="frame">Каркас</option>
            </select>
        </div>
        <div class="checkbox-el">
            <label><input type="checkbox" name="has_attic" value="1"> Наличие мансарды</label>
            <label><input type="checkbox" name="has_finishing" value="1"> С отделкой</label>
        </div>
        <div class="row-el">
            <label>Площадь (м²)</label>
            <input type="number" name="area" step="0.01" min="1" value="120" required>
        </div>
        <div class="row-el">
            <label>Цена (₽)</label>
            <input type="number" name="price" min="1" value="5000000" required>
        </div>
        <div class="row-el">
            <label>Район</label>
            <select name="district_id">
                <?php foreach ($districts as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="row-el">
            <label>Ответственный сотрудник</label>
            <select name="employee_id">
                <?php foreach ($employees as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="row-el">
            <label>Фотография дома (изображение)</label>
            <input type="file" name="house_img" accept="image/*">
        </div>
        <button type="submit" name="add_house">Загрузить проект на сайт</button>
    </form>
    <a href="search.php" class="back-lnk">← Назад в каталог к поиску</a>
</div>
</body>
</html>
