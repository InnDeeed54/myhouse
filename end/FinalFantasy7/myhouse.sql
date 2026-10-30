-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Окт 30 2026 г., 03:47
-- Версия сервера: 10.3.13-MariaDB-log
-- Версия PHP: 7.1.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `myhouse`
--

-- --------------------------------------------------------

--
-- Структура таблицы `districts`
--

CREATE TABLE `districts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `districts`
--

INSERT INTO `districts` (`id`, `name`) VALUES
(3, 'Лесной массив'),
(1, 'Пригородный район'),
(2, 'Центральный округ');

-- --------------------------------------------------------

--
-- Структура таблицы `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `employees`
--

INSERT INTO `employees` (`id`, `first_name`, `last_name`, `phone`) VALUES
(1, 'Иван', 'Иванов', '+7 (999) 111-22-33'),
(2, 'Ольга', 'Петрова', '+7 (999) 444-55-66');

-- --------------------------------------------------------

--
-- Структура таблицы `houses`
--

CREATE TABLE `houses` (
  `id` int(11) NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `floors` int(11) NOT NULL,
  `windows` int(11) NOT NULL,
  `roof_type` enum('gable','hip','flat') COLLATE utf8mb4_unicode_ci NOT NULL,
  `wall_material` enum('brick','aerated','wood','frame') COLLATE utf8mb4_unicode_ci NOT NULL,
  `has_attic` tinyint(1) DEFAULT 0,
  `has_finishing` tinyint(1) DEFAULT 0,
  `area` decimal(6,2) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `district_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'no-image.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `houses`
--

INSERT INTO `houses` (`id`, `title`, `floors`, `windows`, `roof_type`, `wall_material`, `has_attic`, `has_finishing`, `area`, `price`, `district_id`, `employee_id`, `status`, `image`) VALUES
(1, 'Проект D-152-1D', 1, 6, 'gable', 'wood', 1, 0, '154.50', '4500000.00', 3, 1, 'available', 'no-image.jpg'),
(2, 'Проект Н-173-1Р', 2, 8, 'gable', 'aerated', 0, 1, '173.20', '5200000.00', 1, 2, 'available', 'no-image.jpg'),
(4, 'Модерн Флэт', 3, 12, 'flat', 'frame', 0, 1, '300.00', '12000000.00', 2, 2, 'available', 'no-image.jpg'),
(6, 'Уютное Местечко', 1, 5, 'gable', 'wood', 0, 0, '85.30', '2900000.00', 3, 1, 'available', 'no-image.jpg'),
(8, 'Проект Хай-Тек Х1', 3, 16, 'flat', 'aerated', 0, 1, '320.50', '14500000.00', 2, 2, 'available', 'no-image.jpg'),
(10, 'Проект V-208-1P', 2, 4, 'gable', 'brick', 0, 0, '150.00', '7800000.00', 1, 1, 'available', 'no-image.jpg'),
(11, 'Сканди Лайф', 2, 4, 'gable', 'brick', 0, 0, '150.00', '4100000.00', 1, 1, 'available', 'no-image.jpg'),
(12, 'РПВ', 2, 6, 'gable', 'brick', 1, 0, '120.00', '5000000.00', 3, 1, 'booked', '1793230697_5284ea160caa55bf27f80aeb8d06d44e.png');

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `project_title` varchar(150) NOT NULL,
  `price` int(11) NOT NULL,
  `ordered_at` timestamp NULL DEFAULT current_timestamp(),
  `house_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `project_title`, `price`, `ordered_at`, `house_id`) VALUES
(1, 6, 'Вилла Эмеральд', 9500000, '2026-10-28 03:19:28', NULL),
(7, 8, 'РПВ', 5000000, '2026-10-29 03:05:24', 12);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password`, `created_at`, `role`) VALUES
(1, 'alex99', 'alex.ivanov@mail.ru', '+7 (916) 123-45-67', '$2y$10$EozVbmdUv6MvE8Z0eQepv.g36W8K.g7IenHn2i8W/4N3aenMWhC7i', '2026-10-21 23:33:06', 'user'),
(2, 'marina_k', 'kovaleva.m@yandex.ru', '+7 (925) 987-65-43', '$2y$10$EozVbmdUv6MvE8Z0eQepv.g36W8K.g7IenHn2i8W/4N3aenMWhC7i', '2026-10-21 23:33:06', 'user'),
(3, 'dmitry_st', 'dima.smirnov@gmail.com', '+7 (903) 555-11-22', '$2y$10$EozVbmdUv6MvE8Z0eQepv.g36W8K.g7IenHn2i8W/4N3aenMWhC7i', '2026-10-21 23:33:06', 'user'),
(4, 'elena_petrova', 'petrova.elena@rambler.ru', '+7 (999) 444-55-66', '$2y$10$EozVbmdUv6MvE8Z0eQepv.g36W8K.g7IenHn2i8W/4N3aenMWhC7i', '2026-10-21 23:33:06', 'user'),
(5, 'sergey_v', 'volkov.s@mail.ru', '+7 (905) 777-88-99', '$2y$10$EozVbmdUv6MvE8Z0eQepv.g36W8K.g7IenHn2i8W/4N3aenMWhC7i', '2026-10-21 23:33:06', 'user'),
(6, 'Nikita', 'ssdseww@gmail.com', '+76767676768', '$2y$10$h6.9FLmb1Lg78N.HGufod.VRMYXP5iKHVFE/1jPtZvRdpJoVPe95.', '2026-10-21 23:34:13', 'user'),
(7, 'notnp', 'nini@mail.ru', '+79121231212', '$2y$10$6HPKBxgC8uN7H167kb0yQOquWiwi/bTWYrGt7gcHgukWobuWGEw4e', '2026-10-28 02:30:22', 'admin'),
(8, 'sss', 'sss@gmail.com', '+76767676767', '$2y$10$zwelNt4UAra1t6.isQ0r6uuDFHWLr5RWw2ASDS2kQAHxKjEM7n99u', '2026-10-29 02:52:02', 'user');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `houses`
--
ALTER TABLE `houses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `district_id` (`district_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `houses`
--
ALTER TABLE `houses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `houses`
--
ALTER TABLE `houses`
  ADD CONSTRAINT `houses_ibfk_1` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `houses_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
