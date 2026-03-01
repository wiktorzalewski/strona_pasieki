<?php
/**
 * analytics.php — Prosta Analityka Odwiedzin
 */
require_once __DIR__ . '/helper.php';
requireLogin();
requirePermission('analytics');

$pdo = getDB();

// Zakres dat
$range = intval($_GET['days'] ?? 30);
$allowedRanges = [7, 30, 90];
if (!in_array($range, $allowedRanges)) $range = 30;

$stats = [];
$topPages = [];
$dailyViews = [];
$totalViews = 0;
$totalDays = 0;

if ($pdo) {
    try {
        // Łączna liczba odwiedzin w okresie
        $stmt = $pdo->prepare("SELECT SUM(views) FROM page_views WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)");
        $stmt->execute([$range]);
        $totalViews = (int)$stmt->fetchColumn();

        // Ile dni z danymi
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT date) FROM page_views WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)");
        $stmt->execute([$range]);
        $totalDays = (int)$stmt->fetchColumn();

        // Top 10 stron
        $stmt = $pdo->prepare("
            SELECT page, SUM(views) as total
            FROM page_views
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY page
            ORDER BY total DESC
            LIMIT 10
        ");
        $stmt->execute([$range]);
        $topPages = $stmt->fetchAll();

        // Dzienne odwiedziny (dla wykresu)
        $stmt = $pdo->prepare("
            SELECT date, SUM(views) as total
            FROM page_views
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY date
            ORDER BY date ASC
        ");
        $stmt->execute([$range]);
        $dailyViews = $stmt->fetchAll();

        // Dziś
        $stmt = $pdo->query("SELECT SUM(views) FROM page_views WHERE date = CURDATE()");
        $todayViews = (int)$stmt->fetchColumn();

        // Wczoraj
        $stmt = $pdo->query("SELECT SUM(views) FROM page_views WHERE date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
        $yesterdayViews = (int)$stmt->fetchColumn();

    } catch (Exception $e) {
        // Tabela może nie istnieć jeszcze
        $needsMigration = true;
    }
}

$avgViews = $totalDays > 0 ? round($totalViews / $totalDays) : 0;

// Przygotuj dane dla wykresu
$chartLabels = json_encode(array_column($dailyViews, 'date'));
$chartData = json_encode(array_map('intval', array_column($dailyViews, 'total')));
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Analityka — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-left">
            <a href="dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i></a>
            <i class="fa-solid fa-chart-line" style="color: #3498db;"></i>
            <span class="nav-title">Analityka Odwiedzin</span>
        </div>
        <div class="nav-right">
            <span class="nav-user"><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars(currentUsername()); ?></span>
            <a href="logout.php" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </nav>

    <main class="admin-container">

        <?php if (isset($needsMigration)): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Tabela <code>page_views</code> nie istnieje. Uruchom <a href="../apply_analytics_migration.php" target="_blank"><strong>apply_analytics_migration.php</strong></a> aby ją utworzyć.
        </div>
        <?php else: ?>

        <!-- Filtr czasu -->
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:20px; flex-wrap:wrap;">
            <h2 style="margin:0;"><i class="fa-solid fa-chart-line"></i> Odwiedziny</h2>
            <div style="margin-left:auto; display:flex; gap:8px;">
                <?php foreach ([7=>'7 dni', 30=>'30 dni', 90=>'90 dni'] as $d => $label): ?>
                <a href="?days=<?php echo $d; ?>"
                   style="padding:6px 14px; border-radius:20px; text-decoration:none; font-size:0.85rem; font-weight:600;
                          <?php echo $range === $d ? 'background:var(--gold);color:#000;' : 'background:var(--bg-input);color:var(--text);border:1px solid var(--border);'; ?>">
                    <?php echo $label; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Karty statystyk -->
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); margin-bottom:24px;">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-eye" style="color:#3498db;"></i></div>
                <div class="stat-info">
                    <span class="stat-number"><?php echo number_format($totalViews, 0, ',', ' '); ?></span>
                    <span class="stat-label">Odsłon (<?php echo $range; ?> dni)</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-calendar-day" style="color:#2ecc71;"></i></div>
                <div class="stat-info">
                    <span class="stat-number"><?php echo number_format($todayViews ?? 0, 0, ',', ' '); ?></span>
                    <span class="stat-label">Dzisiaj</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-clock-rotate-left" style="color:#e67e22;"></i></div>
                <div class="stat-info">
                    <span class="stat-number"><?php echo number_format($yesterdayViews ?? 0, 0, ',', ' '); ?></span>
                    <span class="stat-label">Wczoraj</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-chart-simple" style="color:#9b59b6;"></i></div>
                <div class="stat-info">
                    <span class="stat-number"><?php echo number_format($avgViews, 0, ',', ' '); ?></span>
                    <span class="stat-label">Śr. dziennie</span>
                </div>
            </div>
        </div>

        <!-- Wykres -->
        <div class="settings-card" style="margin-bottom:24px; padding:24px;">
            <h3 style="margin:0 0 16px 0;"><i class="fa-solid fa-chart-area"></i> Odwiedziny dziennie</h3>
            <?php if (empty($dailyViews)): ?>
            <p style="color:var(--text-muted); text-align:center; padding:40px 0;">
                <i class="fa-solid fa-database" style="font-size:2rem; opacity:0.3;"></i><br><br>
                Brak danych dla wybranego okresu. Dane zaczną się zbierać po aktywacji trackingu.
            </p>
            <?php else: ?>
            <canvas id="viewsChart" height="100"></canvas>
            <?php endif; ?>
        </div>

        <!-- Top stron -->
        <div class="settings-card" style="padding:0; overflow:hidden;">
            <div style="padding:20px 24px 12px; border-bottom:1px solid var(--border);">
                <h3 style="margin:0;"><i class="fa-solid fa-ranking-star"></i> Najpopularniejsze strony</h3>
            </div>
            <?php if (empty($topPages)): ?>
            <p style="color:var(--text-muted); text-align:center; padding:30px;">
                Brak danych. Tracking zacznie zbierać dane po wgraniu pliku na serwer.
            </p>
            <?php else: ?>
            <?php $maxViews = $topPages[0]['total'] ?? 1; ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Strona</th>
                        <th>Odsłony</th>
                        <th style="width:200px;">Udział</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($topPages as $i => $p): ?>
                    <tr>
                        <td style="color:var(--text-muted); font-weight:600;"><?php echo $i+1; ?></td>
                        <td>
                            <a href="https://pasiekapodgruszka.pl/<?php echo htmlspecialchars($p['page']); ?>"
                               target="_blank" style="color:var(--gold);">
                                /<?php echo htmlspecialchars($p['page']); ?>
                            </a>
                        </td>
                        <td><strong><?php echo number_format($p['total'], 0, ',', ' '); ?></strong></td>
                        <td>
                            <div style="background:var(--bg-input); border-radius:10px; height:8px; overflow:hidden;">
                                <div style="width:<?php echo round(($p['total']/$maxViews)*100); ?>%; height:100%; background:linear-gradient(90deg, #3498db, #2980b9); border-radius:10px;"></div>
                            </div>
                            <small style="color:var(--text-muted);"><?php echo round(($p['total']/$totalViews)*100, 1); ?>%</small>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <?php endif; ?>
    </main>

    <?php if (!isset($needsMigration) && !empty($dailyViews)): ?>
    <script>
    const ctx = document.getElementById('viewsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo $chartLabels; ?>,
            datasets: [{
                label: 'Odsłony',
                data: <?php echo $chartData; ?>,
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#3498db',
                pointRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    ticks: { color: '#888', maxTicksLimit: 10 },
                    grid: { color: 'rgba(255,255,255,0.05)' }
                },
                y: {
                    ticks: { color: '#888' },
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    beginAtZero: true
                }
            }
        }
    });
    </script>
    <?php endif; ?>
</body>
</html>
