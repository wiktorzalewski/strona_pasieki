-- Baza danych: pasieka_db

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `users` (Administratorzy)
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

--
-- Zrzut danych tabeli `users`
-- (Hasło domyślne: admin123 - W PRODUKCJI NALEŻY ZMIENIĆ)
-- $2y$10$ ... to hash dla 'admin123'
INSERT INTO `users` (`id`, `username`, `password_hash`) VALUES
(1, 'admin', '$2y$10$8.w/x.X.x.X.x.X.x.X.x.X.x.X.x.X.x.X.x.X.x.X.x.X.x.X'); 
-- Uwaga: Powyższy hash jest przykładowy, w PHP użyj password_hash('haslo', PASSWORD_DEFAULT)

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `products` (Miody)
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `slug` varchar(50) NOT NULL, -- np. 'spadziowy'
  `name` varchar(100) NOT NULL, -- np. 'Miód Spadziowy'
  `price` decimal(10,2) NOT NULL,
  `description` text NOT NULL, -- Pełny opis HTML
  `taste` varchar(255) DEFAULT NULL,
  `usage_text` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

--
-- Zrzut danych tabeli `products` (Początkowe dane)
--

INSERT INTO `products` (`id`, `slug`, `name`, `price`, `description`, `taste`, `usage_text`, `image_path`, `sort_order`) VALUES
(1, 'spadziowy', 'Miód Spadziowy', 55.00, '<span class=\"highlight-text\">Królewski, wytrawny smak prosto z lasu.</span><p>Miód spadziowy, nazywany często \"królewskim\", powstaje ze spadzi drzew iglastych.</p>', 'Żywiczny, łagodny, mało słodki.', 'Do kawy, na chleb, prosto z łyżeczki.', 'assets/images/products/spadziowy.jpg', 1),
(2, 'lipowy', 'Miód Lipowy', 45.00, '<span class=\"highlight-text\">Złoty lek na przeziębienia.</span><p>Jeden z najbardziej aromatycznych miodów polskich.</p>', 'Ostry, wyrazisty, aromat kwiatów lipy.', 'Herbata z cytryną, syropy domowe.', 'assets/images/products/lipowy.jpg', 2),
(3, 'rzepakowy', 'Miód Rzepakowy', 40.00, '<span class=\"highlight-text\">Kremowy, łagodny i energetyczny.</span><p>Miód wiosenny o bardzo szybkiej krystalizacji.</p>', 'Bardzo słodki, łagodny, kremowy.', 'Słodzenie twarogu, napoje.', 'assets/images/products/rzepakowy.jpg', 3);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `kits` (Zestawy)
--

CREATE TABLE `kits` (
  `id` int(11) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price_label` varchar(50) NOT NULL, -- Text bo 'Wycena Indywidualna'
  `description` text NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

--
-- Zrzut danych tabeli `kits`
--

INSERT INTO `kits` (`id`, `slug`, `name`, `price_label`, `description`, `image_path`) VALUES
(1, 'trio', 'Zestaw Trio Smaków', '45.00 PLN', 'Idealny na drobny upominek. Zawiera 3 słoiczki po 130g.', 'assets/images/zestawy/trio.jpg'),
(2, 'swiateczny', 'Zestaw Świąteczny', '95.00 PLN', 'Bogaty zestaw pod choinkę. W środku: 2x500ml miodu.', 'assets/images/zestawy/duz.jpg'),
(3, 'firmowy', 'Zestaw dla Firm', 'Wycena Indywidualna', 'Szukasz prezentów dla pracowników? Przygotujemy zestawy dopasowane do budżetu.', 'assets/images/zestawy/firmowy.jpg');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `gallery_images`
--

CREATE TABLE `gallery_images` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL, -- np. '1.jpg'
  `title` varchar(100) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

--
-- Indeksy
--

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

ALTER TABLE `kits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

ALTER TABLE `gallery_images`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT
--

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `kits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `gallery_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `recipes` (Przepisy)
--

CREATE TABLE `recipes` (
  `id` int(11) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `title` varchar(100) NOT NULL,
  `short_desc` varchar(255) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `prep_time` varchar(20) DEFAULT '30 min',
  `difficulty` varchar(20) DEFAULT 'Średni',
  `ingredients` TEXT NOT NULL,
  `steps` TEXT NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

--
-- Zrzut danych tabeli `recipes`
--

INSERT INTO `recipes` (`id`, `slug`, `title`, `short_desc`, `image_path`, `prep_time`, `difficulty`, `ingredients`, `steps`, `sort_order`) VALUES
(1, 'miodownik', 'Tradycyjny Miodownik', 'Kruche blaty miodowe przekładane domową masą grysikową. Smakuje najlepiej po 2 dniach leżakowania.', '/assets/images/recipes/miodownik.jpg', '90 min', 'Średni', '["500g mąki pszennej", "200g masła", "3 łyżki miodu spadziowego", "2 jajka", "1 szklanka cukru", "1 łyżeczka sody", "1 litr mleka (do kremu)", "8 łyżek kaszy manny (do kremu)"]', '["Zagnieć ciasto z mąki, masła, miodu, jajek, cukru i sody.", "Podziel ciasto na 3 części i upiecz osobno każdą (ok. 15 min w 180°C).", "Ugotuj kaszę manną na mleku z odrobiną cukru, wystudź.", "Utrzyj masło i dodawaj wystudzoną kaszę, tworząc krem.", "Przełóż blaty kremem. Wierzch możesz polać czekoladą.", "Odstaw na min. 24h, aby blaty zmiękły."]', 1),
(2, 'lemoniada', 'Miodowa Lemoniada', 'Idealne orzeźwienie na upalne dni. Połączenie miodu lipowego, cytryny i świeżej mięty.', '/assets/images/recipes/lemoniada.jpg', '10 min', 'Łatwy', '["1 litr wody mineralnej", "3 cytryny", "4 łyżki miodu lipowego", "Garść świeżej mięty", "Kostki lodu"]', '["Miód rozpuść w szklance letniej wody (nie gorącej!).", "Wciśnij sok z cytryn do dzbanka.", "Dodaj wodę z miodem oraz resztę wody mineralnej.", "Wrzuć listki mięty i kostki lodu.", "Wymieszaj i podawaj schłodzone."]', 2),
(3, 'kurczak', 'Kurczak w Miodzie i Musztardzie', 'Soczyste kawałki kurczaka w słodko-pikantnej glazurze. Doskonały pomysł na szybki obiad.', '/assets/images/recipes/kurczak.jpg', '45 min', 'Łatwy', '["500g piersi z kurczaka", "3 łyżki miodu rzepakowego", "2 łyżki musztardy", "1 łyżka sosu sojowego", "2 ząbki czosnku", "Oliwa do smażenia"]', '["Kurczaka pokrój w kostkę.", "Wymieszaj miód, musztardę, sos sojowy i przeciśnięty czosnek.", "Zalej kurczaka marynatą i odstaw na 30 min.", "Smaż na rozgrzanej patelni, aż sos się skarmelizuje i oblepi mięso.", "Podawaj z ryżem i warzywami."]', 3);

ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

ALTER TABLE `recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `about_sections` (O nas)
--

CREATE TABLE `about_sections` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `content` TEXT NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `image_position` ENUM('left', 'right') DEFAULT 'left',
  `button_text` varchar(50) DEFAULT NULL,
  `button_link` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

--
-- Zrzut danych tabeli `about_sections`
--

INSERT INTO `about_sections` (`id`, `title`, `content`, `image_path`, `image_position`, `button_text`, `button_link`, `sort_order`) VALUES
(1, 'Rodzinna Tradycja', '<p>Pasieka "Pod Gruszką" to nie tylko miejsce pracy, to serce naszej rodziny. Wszystko zaczęło się od pasji do natury i szacunku do tych małych, pracowitych stworzeń.</p><p>Nasze ule znajdują się w malowniczych okolicach Woli Prażmowskiej, z dala od przemysłu i zgiełku wielkiego miasta. Dzięki temu miód, który trafia na Twój stół, jest czysty, aromatyczny i pełen zdrowotnych właściwości.</p>', '/assets/images/o_nas/1.jpg', 'left', NULL, NULL, 1),
(2, 'Szacunek do Natury', '<p>Nie nastawiamy się na masową produkcję. Dla nas najważniejsze jest dobrostan pszczół.</p><p>Wierzymy, że to, co dajemy pszczołom, wraca do nas w postaci płynnego złota. Każdy słoik jest ręcznie nalewany i etykietowany z najwyższą starannością.</p>', '/assets/images/o_nas/2.jpg', 'right', 'SPRÓBUJ NASZYCH MIODÓW', 'products.php', 2);

-- --------------------------------------------------------

--
-- Tabela settings (Ustawienia globalne)
--
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Domyślne ustawienia
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('maintenance_mode', '0');

ALTER TABLE `about_sections`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `about_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

COMMIT;
