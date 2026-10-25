<?php

// Include database connection
require_once __DIR__ . '/includes/DBController.php';

try {
    $stmt = $db->prepare(
        "SELECT m.menu_id, m.menu_name, m.menu_price, m.menu_desc, m.image_path, c.cat_name, s.subcat_name
        FROM menu m
        LEFT JOIN category c ON c.cat_id = m.cat_id
        LEFT JOIN subcategory s ON s.subcat_id = m.subcat_ID
        ORDER BY c.cat_name ASC, s.subcat_name ASC, m.menu_name ASC"
    );
    $stmt->execute();
    $menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $menuItems = [];
    error_log('Menu load failed: ' . $e->getMessage());
    http_response_code(500);
    $errorMessage = 'The menu is temporarily unavailable. Please try again later.';
}

$sections = [];
foreach ($menuItems as $item) {
    $category = trim((string) ($item['cat_name'] ?? '')) ?: 'Uncategorized';
    $subCategory = trim((string) ($item['subcat_name'] ?? '')) ?: 'General';

    if (!isset($sections[$category])) {
        $sections[$category] = [];
    }
    if (!isset($sections[$category][$subCategory])) {
        $sections[$category][$subCategory] = [];
    }
    $sections[$category][$subCategory][] = $item;
}

function getItemInitial(string $name): string
{
    $trimmed = trim($name);
    return $trimmed === '' ? '?' : strtoupper(mb_substr($trimmed, 0, 1));
}

function renderMenuColumn(array $categories): void
{
    ?>
    <div class="menu-col">
        <?php foreach ($categories as $catName => $subsections):
            $totalItems = array_sum(array_map('count', $subsections));
        ?>
            <div class="category-block">
                <div class="cat-header">
                    <div class="cat-bar">
                        <span class="cat-title"><?= htmlspecialchars(strtoupper($catName), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="cat-bar-arrow"></span>
                    </div>
                    <span class="cat-count ms-3"><?= $totalItems ?> items</span>
                </div>
                <?php foreach ($subsections as $subName => $items): ?>
                    <?php if ($subName !== 'General' || count($subsections) > 1): ?>
                        <div class="subcat-label"><?= htmlspecialchars(strtoupper($subName), ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <?php foreach ($items as $item):
                        $imagePath = trim((string) ($item['image_path'] ?? ''));
                        $menuName = (string) ($item['menu_name'] ?? '');
                        $menuDescription = (string) ($item['menu_desc'] ?? '');
                        $searchText = implode(' ', [$menuName, $menuDescription, $catName, $subName]);
                        $initial = getItemInitial($menuName);
                    ?>
                        <div class="menu-row" data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="item-image-wrapper">
                                <div class="image-placeholder"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if ($imagePath !== ''): ?>
                                    <img class="item-image" src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($menuName, ENT_QUOTES, 'UTF-8') ?>"
                                         onload="this.previousElementSibling.style.display='none'"
                                         onerror="this.style.display='none'">
                                <?php endif; ?>
                            </div>
                            <div class="menu-left">
                                <div class="menu-name-row">
                                    <span class="menu-name"><?= htmlspecialchars($menuName, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="menu-dots"></span>
                                    <span class="menu-price"><?= number_format((float) ($item['menu_price'] ?? 0), 0) ?> RWF</span>
                                </div>
                                <?php if ($menuDescription !== ''): ?>
                                    <div class="menu-desc"><?= htmlspecialchars($menuDescription, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="./img/icon.png">
    <title>KABC Hotel Menu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/full_menu.css?v=3" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a class="header-home-link" href="./"><span aria-hidden="true">←</span> KABC HOTEL</a>
            <img src="./img/icon.png" alt="Menu icon">
            <h1>OUR MENU</h1>
            <p class="header-sub">Fresh dishes & drinks from our kitchen</p>
            <div class="header-line"></div>
            <div class="search-wrap">
                <input id="menuSearch" class="menu-search" type="search" placeholder="Search dishes, categories or descriptions…" aria-label="Search menu items">
            </div>
            <a class="export-link" href="menu_table.php" target="_blank" rel="noopener">Download menu <span aria-hidden="true">↗</span></a>
        </div>
    </header>

    <div class="menu-board">
        <?php if (!empty($errorMessage)): ?>
            <div class="state-box error">
                <h2>Menu Unavailable</h2>
                <p><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php elseif (empty($sections)): ?>
            <div class="state-box">
                <h2>Coming Soon</h2>
                <p>No menu items are available right now.</p>
            </div>
        <?php else: ?>
            <div id="searchState" class="state-box" style="display:none;">
                <h2>No matching menu items</h2>
                <p>Try searching for a dish name or description.</p>
            </div>
            <div class="menu-columns">
                <?php
                $col1 = [];
                $col2 = [];
                $keys = array_keys($sections);
                foreach ($keys as $i => $key) {
                    if ($i % 2 === 0) {
                        $col1[$key] = $sections[$key];
                    } else {
                        $col2[$key] = $sections[$key];
                    }
                }
                ?>

                <?php renderMenuColumn($col1); ?>
                <?php renderMenuColumn($col2); ?>
            </div>
        <?php endif; ?>
    </div>

    <?php require __DIR__ . '/includes/footer.php'; ?>
    <script src="assets/js/full_menu.js" defer></script>
</body>
</html>
