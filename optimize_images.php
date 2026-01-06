<?php
// Skrypt do optymalizacji zdjęć (zmniejszanie + usuwanie metadanych)
// Uruchom go raz wchodząc na: https://twoja-strona.pl/optimize_images.php
// Po zakończeniu skryptu skasuj ten plik z serwera!

$galleryPath = __DIR__ . '/assets/images/gallery/';
$quality = 80;          // Jakość JPG (0-100) - zmniejszyłem do 80 dla lepszej kompresji
$maxWidth = 1920;       // Maksymalna szerokość
$maxHeight = 1080;      // Maksymalna wysokość

echo "<h1>Optymalizacja Zdjęć w Galerii (Wersja 2)</h1>";
echo "<pre>";

if (!is_dir($galleryPath)) {
    die("Błąd: Folder galerii nie istnieje: $galleryPath");
}

$files = scandir($galleryPath);
$count = 0;

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    
    $filePath = $galleryPath . $file;
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    // Obsługujemy tylko JPG/JPEG
    if (!in_array($ext, ['jpg', 'jpeg'])) {
        continue;
    }

    // Pomijamy kopie zapasowe i pliki już przetworzone tej sesji (jeśli jakieś są)
    if (strpos($file, '.bak') !== false) {
        continue;
    }

    echo "Przetwarzanie: <b>$file</b> ... ";
    flush();

    // Wczytaj obraz
    $image = @imagecreatefromjpeg($filePath);
    if (!$image) {
        echo "BŁĄD (nie można wczytać)\n";
        continue;
    }

    // Pobierz wymiary i rozmiar
    clearstatcache(true, $filePath); // WAŻNE: Wyczyść cache rozmiaru
    $width = imagesx($image);
    $height = imagesy($image);
    $originalSize = filesize($filePath);

    // Oblicz nowe wymiary (skalowanie)
    $ratio = $width / $height;
    $newWidth = $width;
    $newHeight = $height;

    // Skaluj tylko jeśli obraz jest większy niż limit
    if ($width > $maxWidth || $height > $maxHeight) {
        if ($width > $height) {
            $newWidth = $maxWidth;
            $newHeight = $maxWidth / $ratio;
        } else {
            $newHeight = $maxHeight;
            $newWidth = $maxHeight * $ratio;
        }
    }

    // Tworzenie nowego płótna (GD automatycznie usuwa metadane przy tworzeniu nowego obrazu!)
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Zachowanie jakości przy skalowaniu
    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Sprawdź czy istnieje kopia zapasowa (oryginał)
    $backupPath = $filePath . '.bak';
    
    if (file_exists($backupPath)) {
        // Jeśli kopia istnieje, to znaczy że plik był już optymalizowany
        // Bierzemy rozmiar oryginału z kopii, żeby pokazać całkowitą oszczędność
        clearstatcache(true, $backupPath);
        $originalSize = filesize($backupPath);
    } else {
        // Jeśli to pierwszy raz - zrób kopię
        $originalSize = filesize($filePath);
        if (!copy($filePath, $backupPath)) {
            echo "BŁĄD TWORZENIA KOPII\n";
            continue;
        }
    }

    // Zapisz nowy plik (bez EXIF/GPS) - nadpisz oryginał
    if (imagejpeg($newImage, $filePath, $quality)) {
        
        clearstatcache(true, $filePath); // WAŻNE: Wyczyść cache rozmiaru po zapisie
        $newSize = filesize($filePath);
        
        $percent = 0;
        if ($originalSize > 0) {
            $percent = round(($originalSize - $newSize) / $originalSize * 100);
        }
        
        echo "OK! \n";
        echo "   Wymiary: {$width}x{$height} -> " . round($newWidth) . "x" . round($newHeight) . "\n";
        echo "   Rozmiar: " . round($originalSize/1024, 2) . " KB -> " . round($newSize/1024, 2) . " KB (zmiana: $percent%)\n";
        $count++;
    } else {
        echo "BŁĄD ZAPISU\n";
    }

    // Zwolnij pamięć
    imagedestroy($image);
    imagedestroy($newImage);
    
    echo "\n";
    flush(); 
    ob_flush();
}

echo "\n-----------------------------------\n";
echo "Skończone! Zoptymalizowano $count zdjęć.\n";
echo "Sprawdź szybkość ładowania galerii!";
echo "</pre>";
?>
