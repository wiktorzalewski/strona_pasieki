<?php
/**
 * gallery.php — Zarządzanie galerią zdjęć
 */
require_once __DIR__ . '/helper.php';
requireLogin();
requirePermission('gallery');

$pdo = getDB();
$success = '';
$error = '';

$galleryDir = 'assets/images/gallery';
$fullGalleryDir = dirname(__DIR__) . '/' . $galleryDir;

// POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';
    
    // Upload nowych zdjęć (wiele naraz)
    if ($action === 'upload') {
        if (!is_dir($fullGalleryDir)) {
            @mkdir($fullGalleryDir, 0775, true);
        }
        
        $uploaded = 0;
        $errors = 0;
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (!empty($_FILES['images']['name'][0])) {
            $fileCount = count($_FILES['images']['name']);
            
            // Pobierz najwyższy sort_order
            $maxOrder = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) FROM gallery_images")->fetchColumn();
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) {
                    $errors++;
                    continue;
                }
                
                $origName = $_FILES['images']['name'][$i];
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                
                if (!in_array($ext, $allowed)) {
                    $errors++;
                    continue;
                }
                
                $filename = uniqid('gal_') . '.' . $ext;
                $destPath = $fullGalleryDir . '/' . $filename;
                
                if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $destPath)) {
                    $maxOrder++;
                    $title = trim($_POST['title'] ?? '');
                    if (!$title) $title = pathinfo($origName, PATHINFO_FILENAME);
                    
                    $stmt = $pdo->prepare("INSERT INTO gallery_images (file_name, title, sort_order) VALUES (?, ?, ?)");
                    $stmt->execute([$filename, $title, $maxOrder]);
                    $uploaded++;
                } else {
                    $errors++;
                }
            }
            
            if ($uploaded > 0) {
                $success = "Dodano $uploaded zdjęć.";
            }
            if ($errors > 0) {
                $error = "$errors plików nie udało się wgrać.";
            }
        } else {
            $error = 'Nie wybrano żadnych plików.';
        }
    }
    
    // Edytuj tytuł
    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        
        if ($id) {
            $pdo->prepare("UPDATE gallery_images SET title = ?, sort_order = ? WHERE id = ?")->execute([$title, $sort_order, $id]);
            $success = 'Zaktualizowano.';
        }
    }
    
    // Usuń zdjęcie
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            // Pobierz nazwę pliku
            $stmt = $pdo->prepare("SELECT file_name FROM gallery_images WHERE id = ?");
            $stmt->execute([$id]);
            $fileName = $stmt->fetchColumn();
            
            if ($fileName) {
                $filePath = $fullGalleryDir . '/' . $fileName;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
                $pdo->prepare("DELETE FROM gallery_images WHERE id = ?")->execute([$id]);
                $success = 'Zdjęcie usunięte.';
            }
        }
    }
    
    // Usuń zaznaczone
    if ($action === 'delete_selected') {
        $ids = $_POST['selected'] ?? [];
        $deleted = 0;
        foreach ($ids as $id) {
            $id = intval($id);
            $stmt = $pdo->prepare("SELECT file_name FROM gallery_images WHERE id = ?");
            $stmt->execute([$id]);
            $fileName = $stmt->fetchColumn();
            if ($fileName) {
                $filePath = $fullGalleryDir . '/' . $fileName;
                if (file_exists($filePath)) @unlink($filePath);
                $pdo->prepare("DELETE FROM gallery_images WHERE id = ?")->execute([$id]);
                $deleted++;
            }
        }
        if ($deleted > 0) $success = "Usunięto $deleted zdjęć.";
    }
}

// Pobierz zdjęcia
$images = [];
if ($pdo) {
    try {
        $images = $pdo->query("SELECT * FROM gallery_images ORDER BY sort_order ASC, id DESC")->fetchAll();
    } catch (Exception $e) {
        $error = 'Błąd: ' . $e->getMessage();
    }
}

// Edycja
$editImage = null;
if (isset($_GET['edit']) && $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM gallery_images WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $editImage = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Galeria — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .gallery-admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .gallery-admin-item {
            position: relative;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            transition: all 0.3s;
        }
        .gallery-admin-item:hover {
            border-color: var(--gold);
            transform: translateY(-2px);
        }
        .gallery-admin-item img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            display: block;
        }
        .gallery-admin-item .item-info {
            padding: 10px;
        }
        .gallery-admin-item .item-info small {
            color: var(--text-muted);
            display: block;
            margin-bottom: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .gallery-admin-item .item-actions {
            display: flex;
            gap: 6px;
        }
        .gallery-admin-item .item-select {
            position: absolute;
            top: 8px;
            left: 8px;
            z-index: 2;
            width: 22px;
            height: 22px;
            accent-color: var(--gold);
            cursor: pointer;
        }
        .upload-zone {
            border: 2px dashed var(--border);
            border-radius: var(--radius);
            padding: 40px 20px;
            text-align: center;
            color: var(--text-muted);
            transition: all 0.3s;
            cursor: pointer;
            margin-bottom: 15px;
        }
        .upload-zone:hover, .upload-zone.dragover {
            border-color: var(--gold);
            background: rgba(255, 193, 7, 0.05);
            color: var(--gold);
        }
        .upload-zone i {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: block;
        }
        .bulk-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding: 10px 15px;
            background: var(--bg-input);
            border-radius: var(--radius);
        }
    </style>
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-left">
            <a href="dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i></a>
            <i class="fa-solid fa-images" style="color: #3498db;"></i>
            <span class="nav-title">Galeria Zdjęć</span>
        </div>
        <div class="nav-right">
            <span class="nav-user"><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars(currentUsername()); ?></span>
            <a href="logout.php" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i> Wyloguj</a>
        </div>
    </nav>

    <main class="admin-container">
        <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error; ?></div><?php endif; ?>

        <?php if ($editImage): ?>
        <!-- EDYCJA -->
        <div class="settings-card">
            <h2><i class="fa-solid fa-pen"></i> Edytuj zdjęcie</h2>
            <div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap;">
                <img src="/<?php echo htmlspecialchars($galleryDir . '/' . $editImage['file_name']); ?>" 
                     alt="" style="width:200px;height:150px;object-fit:cover;border-radius:var(--radius);">
                <form method="POST" style="flex:1;min-width:250px;">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?php echo $editImage['id']; ?>">
                    <div class="form-group">
                        <label><i class="fa-solid fa-heading"></i> Tytuł / Opis</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($editImage['title'] ?? ''); ?>" placeholder="Opis zdjęcia...">
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-sort"></i> Kolejność</label>
                        <input type="number" name="sort_order" min="0" value="<?php echo $editImage['sort_order'] ?? 0; ?>">
                    </div>
                    <div style="display:flex;gap:10px;margin-top:10px;">
                        <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Zapisz</button>
                        <a href="gallery.php" class="btn-small btn-outline">Anuluj</a>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <!-- UPLOAD -->
        <div class="settings-card">
            <h2><i class="fa-solid fa-cloud-arrow-up"></i> Dodaj zdjęcia</h2>
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <input type="hidden" name="action" value="upload">
                <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click();">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <p><strong>Kliknij lub przeciągnij zdjęcia tutaj</strong></p>
                    <small>JPG, PNG, WebP, GIF • Możesz wybrać wiele plików naraz</small>
                </div>
                <input type="file" name="images[]" id="fileInput" multiple accept="image/*" style="display:none;" onchange="updateFileList()">
                <div id="fileList" style="margin-bottom:10px;"></div>
                <div class="form-group">
                    <label><i class="fa-solid fa-heading"></i> Tytuł (opcjonalny, wspólny dla wszystkich)</label>
                    <input type="text" name="title" placeholder="np. Miodobranie 2025">
                </div>
                <button type="submit" class="btn-save" id="uploadBtn" style="display:none;">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Wgraj zdjęcia
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- LISTA ZDJĘĆ -->
        <div class="settings-card">
            <h2><i class="fa-solid fa-images"></i> Zdjęcia w galerii (<?php echo count($images); ?>)</h2>
            
            <?php if (!empty($images)): ?>
            <form method="POST" id="bulkForm">
                <input type="hidden" name="action" value="delete_selected">
                <div class="bulk-actions">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" id="selectAll" onchange="toggleAll(this)" style="accent-color:var(--gold);width:18px;height:18px;">
                        <span style="font-size:0.85rem;">Zaznacz wszystkie</span>
                    </label>
                    <button type="submit" class="btn-small btn-danger" onclick="return confirm('Usunąć zaznaczone zdjęcia?');">
                        <i class="fa-solid fa-trash"></i> Usuń zaznaczone
                    </button>
                    <span style="margin-left:auto;color:var(--text-muted);font-size:0.8rem;" id="selectedCount">0 zaznaczonych</span>
                </div>
                
                <div class="gallery-admin-grid">
                    <?php foreach ($images as $img): ?>
                        <div class="gallery-admin-item">
                            <input type="checkbox" name="selected[]" value="<?php echo $img['id']; ?>" class="item-select" onchange="updateCount()">
                            <img src="/<?php echo htmlspecialchars($galleryDir . '/' . $img['file_name']); ?>" 
                                 alt="<?php echo htmlspecialchars($img['title'] ?? ''); ?>"
                                 onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect fill=%22%23333%22 width=%22100%22 height=%22100%22/><text x=%2250%22 y=%2255%22 text-anchor=%22middle%22 fill=%22%23666%22 font-size=%2220%22>?</text></svg>'">
                            <div class="item-info">
                                <small title="<?php echo htmlspecialchars($img['file_name']); ?>">
                                    <?php echo htmlspecialchars($img['title'] ?: $img['file_name']); ?>
                                </small>
                                <div class="item-actions">
                                    <a href="?edit=<?php echo $img['id']; ?>" class="btn-small btn-outline" title="Edytuj">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Usunąć to zdjęcie?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $img['id']; ?>">
                                        <button type="submit" class="btn-small btn-danger" title="Usuń">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
            <?php else: ?>
                <p style="color:var(--text-muted);text-align:center;padding:40px;">
                    <i class="fa-solid fa-image" style="font-size:3rem;display:block;margin-bottom:10px;"></i>
                    Brak zdjęć w galerii. Dodaj pierwsze zdjęcie powyżej!
                </p>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Drag & drop
        const zone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');
        
        if (zone) {
            ['dragenter','dragover'].forEach(e => zone.addEventListener(e, ev => { ev.preventDefault(); zone.classList.add('dragover'); }));
            ['dragleave','drop'].forEach(e => zone.addEventListener(e, ev => { ev.preventDefault(); zone.classList.remove('dragover'); }));
            zone.addEventListener('drop', ev => {
                fileInput.files = ev.dataTransfer.files;
                updateFileList();
            });
        }

        function updateFileList() {
            const files = fileInput.files;
            const list = document.getElementById('fileList');
            const btn = document.getElementById('uploadBtn');
            if (files.length > 0) {
                list.innerHTML = '<small style="color:var(--green);"><i class="fa-solid fa-check"></i> Wybrano ' + files.length + ' plików</small>';
                btn.style.display = 'inline-flex';
            } else {
                list.innerHTML = '';
                btn.style.display = 'none';
            }
        }

        function toggleAll(master) {
            document.querySelectorAll('.item-select').forEach(cb => cb.checked = master.checked);
            updateCount();
        }

        function updateCount() {
            const checked = document.querySelectorAll('.item-select:checked').length;
            document.getElementById('selectedCount').textContent = checked + ' zaznaczonych';
        }
    </script>
</body>
</html>
