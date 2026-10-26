<?php
require_once __DIR__ . '/includes/require_admin.php';

try {
    $menuCount = (int) $db->query('SELECT COUNT(*) FROM menu')->fetchColumn();
    $imageCount = (int) $db->query("SELECT COUNT(*) FROM menu WHERE image_path IS NOT NULL AND image_path <> ''")->fetchColumn();
    $loginCount = (int) $db->query(
        'SELECT COUNT(*) FROM admin_login_logs WHERE success = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
    )->fetchColumn();

    $categoryRows = $db->query(
        "SELECT COALESCE(NULLIF(TRIM(c.cat_name), ''), 'Uncategorized') AS category_name, COUNT(m.menu_id) AS item_count
         FROM menu m
         LEFT JOIN category c ON c.cat_id = m.cat_id
         GROUP BY category_name
         ORDER BY item_count DESC, category_name ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $loginRows = $db->query(
        'SELECT DATE(created_at) AS login_day, COUNT(*) AS login_count
         FROM admin_login_logs
         WHERE success = 1 AND created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
         GROUP BY DATE(created_at)
         ORDER BY login_day ASC'
    )->fetchAll(PDO::FETCH_ASSOC);

    $categoryLabels = array_column($categoryRows, 'category_name');
    $categoryValues = array_map('intval', array_column($categoryRows, 'item_count'));
    $loginCountsByDay = [];
    foreach ($loginRows as $row) {
        $loginCountsByDay[$row['login_day']] = (int) $row['login_count'];
    }

    $loginLabels = [];
    $loginValues = [];
    $today = new DateTimeImmutable('today');
    for ($daysAgo = 29; $daysAgo >= 0; $daysAgo--) {
        $day = $today->sub(new DateInterval('P' . $daysAgo . 'D'));
        $key = $day->format('Y-m-d');
        $loginLabels[] = $day->format('M j');
        $loginValues[] = $loginCountsByDay[$key] ?? 0;
    }
} catch (PDOException $e) {
    error_log('Unable to load admin dashboard data: ' . $e->getMessage());
    http_response_code(500);
    $menuCount = 0;
    $imageCount = 0;
    $loginCount = 0;
    $categoryLabels = [];
    $categoryValues = [];
    $loginLabels = [];
    $loginValues = [];
    $flash = ['message' => 'Dashboard data could not be loaded.', 'type' => 'error'];
}

$flash = $flash ?? adminConsumeFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin dashboard | KABC Hotel</title>
    <link rel="icon" type="image/png" href="../img/icon.png">
    <script src="../assets/js/admin_theme.js"></script>
    <link rel="stylesheet" href="../assets/css/admin.css?v=10">
</head>
<body>
<div class="admin-layout">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <?php require __DIR__ . '/includes/header.php'; ?>
        <header class="admin-header">
            <div>
                <p class="eyebrow">ADMINISTRATION</p>
                <h1>Welcome, <?= adminEscape($_SESSION['admin_username']) ?></h1>
                <p class="muted">Manage your menu and account from one place.</p>
            </div>
        </header>

        <?php if ($flash): ?>
            <div class="notice <?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status"><?= adminEscape($flash['message']) ?></div>
        <?php endif; ?>
        <section class="stats-grid" aria-label="Menu summary">
            <article class="admin-card stat-card"><span class="stat-icon" aria-hidden="true">01</span><span class="muted">Menu items</span><strong><?= $menuCount ?></strong><small>Across your full menu</small></article>
            <article class="admin-card stat-card"><span class="stat-icon" aria-hidden="true">02</span><span class="muted">Items with images</span><strong><?= $imageCount ?></strong><small><?= $menuCount > 0 ? (int) round($imageCount / $menuCount * 100) : 0 ?>% of menu items</small></article>
            <article class="admin-card stat-card"><span class="stat-icon" aria-hidden="true">03</span><span class="muted">Successful logins</span><strong><?= $loginCount ?></strong><small>Over the last 30 days</small></article>
        </section>

        <section class="dashboard-charts" aria-label="Menu and account activity charts">
            <article class="admin-card chart-card chart-card-wide">
                <div class="chart-heading"><div><p class="eyebrow">ACCOUNT ACTIVITY</p><h2>Login activity</h2><p class="muted">Successful sign-ins over the last 30 days.</p></div><span class="chart-period">30 DAYS</span></div>
                <div class="chart-canvas chart-canvas-line"><canvas id="loginActivityChart" role="img" aria-label="Line chart of successful sign-ins over the last 30 days"></canvas></div>
            </article>
            <article class="admin-card chart-card">
                <div class="chart-heading"><div><p class="eyebrow">MENU BREAKDOWN</p><h2>Items by category</h2><p class="muted">How your menu is organized.</p></div></div>
                <div class="chart-canvas chart-canvas-doughnut"><canvas id="categoryChart" role="img" aria-label="Doughnut chart showing menu items by category"></canvas></div>
                <ul class="chart-legend" id="categoryChartLegend" aria-label="Menu category totals"></ul>
            </article>
        </section>

        <section class="admin-grid">
            <article class="admin-card">
                <h2>Manage menu</h2>
                <p class="muted">Update item names, descriptions, and prices.</p>
                <a class="button primary" href="menu.php">Open menu management</a>
            </article>
            <article class="admin-card">
                <h2>Upload images</h2>
                <p class="muted">Add or replace photos shown on the public menu.</p>
                <a class="button primary" href="menu_image_upload.php">Upload a menu image</a>
            </article>
        </section>
    </main>
</div>
<script src="../assets/js/chart.js"></script>
<script>
    (function () {
        if (typeof Chart === 'undefined') return;
        const data = <?= json_encode([
            'loginLabels' => $loginLabels,
            'loginValues' => $loginValues,
            'categoryLabels' => $categoryLabels,
            'categoryValues' => $categoryValues,
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const colors = ['#82c996', '#d8ae68', '#83a9d6', '#d58d78', '#9b8bd1', '#5eb8ad', '#c8a66a', '#78917c'];
        const isDark = document.documentElement.dataset.theme === 'dark';
        const lineColor = isDark ? '#78bff0' : '#238b70';
        const panelColor = isDark ? '#151f2e' : '#ffffff';
        const gridColor = isDark ? 'rgba(255,255,255,.07)' : 'rgba(23,51,77,.08)';
        const textColor = isDark ? '#a0afc0' : '#71839a';

        new Chart(document.getElementById('loginActivityChart'), {
            type: 'line',
            data: {
                labels: data.loginLabels,
                datasets: [{
                    label: 'Successful sign-ins',
                    data: data.loginValues,
                    borderColor: lineColor,
                    backgroundColor: isDark ? 'rgba(120,191,240,.14)' : 'rgba(35,139,112,.12)',
                    borderWidth: 2.5,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointBackgroundColor: lineColor,
                    fill: true,
                    tension: .38
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: '#101411', titleColor: '#edf4ee', bodyColor: '#c6d1c8', padding: 12, displayColors: false }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: textColor, maxTicksLimit: 8, maxRotation: 0 } },
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, precision: 0 }, border: { display: false } }
                }
            }
        });

        const categoryCanvas = document.getElementById('categoryChart');
        const legend = document.getElementById('categoryChartLegend');
        if (data.categoryLabels.length === 0) {
            categoryCanvas.closest('.chart-canvas').innerHTML = '<p class="chart-empty">Add menu items to see your category breakdown.</p>';
            return;
        }

        new Chart(categoryCanvas, {
            type: 'doughnut',
            data: { labels: data.categoryLabels, datasets: [{ data: data.categoryValues, backgroundColor: colors, borderColor: panelColor, borderWidth: 4, hoverOffset: 5 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { display: false }, tooltip: { backgroundColor: '#101411', titleColor: '#edf4ee', bodyColor: '#c6d1c8', padding: 12 } } }
        });

        data.categoryLabels.forEach(function (label, index) {
            const item = document.createElement('li');
            const swatch = document.createElement('span');
            const name = document.createElement('span');
            const value = document.createElement('strong');
            swatch.className = 'legend-swatch';
            swatch.style.backgroundColor = colors[index % colors.length];
            name.textContent = label;
            value.textContent = data.categoryValues[index];
            item.append(swatch, name, value);
            legend.append(item);
        });
    })();
</script>
</body>
</html>
