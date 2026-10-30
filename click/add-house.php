<?php
// add-house.php — Полный скрипт добавления дома с защитой от ошибок БД

// НАСТРОЙКА ПОДКЛЮЧЕНИЯ К БАЗЕ ДАННЫХ
$host = 'localhost';
$db   = 'название_вашей_базы';  // Укажите имя вашей базы данных
$user = 'root';                 // Укажите пользователя БД
$pass = '';                     // Укажите пароль БД
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Включает вывод ошибок
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// ОБРАБОТКА ОТПРАВКИ ФОРМЫ
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $house_name = trim($_POST['house_name']);
    $wall_material = $_POST['wall_material']; // Примет строго одно из 3-х значений

    // Валидация загружаемой фотографии
    if (isset($_FILES['house_photo']) && $_FILES['house_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['house_photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($ext, $allowed)) {
            // Создаем папку проекта на основе его названия, если её нет
            $folder_name = preg_replace('/[^a-zA-Z0-String0-9_-]/', '', strtolower($house_name));
            $upload_dir = "projects/" . $folder_name;
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Имя для главного фото проекта
            $photo_name = $folder_name . "." . $ext;
            $target_path = $upload_dir . "/" . $photo_name;

            // Переносим файл в целевую папку
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                
                // СОХРАНЕНИЕ В БАЗУ ДАННЫХ
                try {
                    // ВНИМАНИЕ: Замените 'houses' на реальное имя вашей таблицы
                    $sql = "INSERT INTO houses (name, wall_material, photo_path) VALUES (:name, :wall_material, :photo_path)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'name' => $house_name,
                        'wall_material' => $wall_material, // Сюда уйдет 'Кирпич', 'Дерево' или 'Блоки'
                        'photo_path' => $target_path
                    ]);

                    $message = "<div class='success'>Дом «" . htmlspecialchars($house_name) . "» успешно добавлен в БД и на сайт!</div>";
                } catch (PDOException $e) {
                    // Если база данных всё еще ругается, выводим детальную ошибку
                    $message = "<div class='error'>Ошибка базы данных: " . $e->getMessage() . "</div>";
                }

            } else {
                $message = "<div class='error'>Не удалось сохранить загруженный файл на сервере.</div>";
            }
        } else {
            $message = "<div class='error'>Недопустимый формат файла. Разрешены только JPG, PNG, GIF.</div>";
        }
    } else {
        $message = "<div class='error'>Пожалуйста, выберите фотографию дома.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавление нового дома</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 30px; }
        .form-container { background: #fff; max-width: 500px; margin: 0 auto; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; }
        .form-group input[type="text"], .form-group select { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; font-size: 15px; }
        .form-group input[type="file"] { margin-top: 5px; }
        button { background: #28a745; color: #fff; border: none; padding: 12px 20px; font-size: 16px; border-radius: 4px; cursor: pointer; width: 100%; transition: background 0.3s; }
        button:hover { background: #218838; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; text-align: center; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; text-align: center; border: 1px solid #f5c6cb; }
        .nav-links { text-align: center; margin-top: 15px; }
        .nav-links a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Добавить новый проект дома</h2>
    
    <!-- Отображение уведомлений об успехе или ошибке -->
    <?php echo $message; ?>

    <!-- Важно: enctype="multipart/form-data" необходим для отправки файлов -->
    <form action="add-house.php" method="POST" enctype="multipart/form-data">
        
        <div class="form-group">
            <label for="house_name">Название / Код дома:</label>
            <input type="text" id="house_name" name="house_name" placeholder="Например: d-152-1d" required>
        </div>

        <div class="form-group">
            <label for="wall_material">Материал стен (выберите из списка):</label>
            <!-- Текстовое поле убрано. Вместо него выпадающий список, исключающий опечатки -->
            <select id="wall_material" name="wall_material" required>
                <option value="" disabled selected>-- Выберите материал --</option>
                <option value="Кирпич">Кирпич</option>
                <option value="Дерево">Дерево</option>
                <option value="Блоки">Блоки (газобетон/пеноблок)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="house_photo">Главная фотография дома:</label>
            <input type="file" id="house_photo" name="house_photo" accept="image/*" required>
        </div>

        <button type="submit">Добавить дом на сайт</button>
    </form>

    <div class="nav-links">
        <a href="search.php">Перейти к поиску домов →</a>
    </div>
</div>

</body>
</html>