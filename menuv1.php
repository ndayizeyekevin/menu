<?php
require_once __DIR__ . '/includes/DBController.php';

try {
    $stmt = $db->prepare(
        "SELECT m.menu_id, m.menu_name, m.menu_price, m.menu_desc, c.cat_name, s.subcat_name
        FROM menu m
        LEFT JOIN category c ON c.cat_id = m.cat_id
        LEFT JOIN subcategory s ON s.subcat_id = m.subcat_ID
        ORDER BY c.cat_name ASC, s.subcat_name ASC, m.menu_name ASC"
    );
    $stmt->execute();
    $menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $menuItems = [];
    $errorMessage = 'Menu load failed: ' . htmlspecialchars($e->getMessage());
}

$sections = [];
foreach ($menuItems as $item) {
    $category = $item['cat_name'] ?: 'Uncategorized';
    $subCategory = trim($item['subcat_name']) ?: 'General';

    if (!isset($sections[$category])) {
        $sections[$category] = [];
    }
    if (!isset($sections[$category][$subCategory])) {
        $sections[$category][$subCategory] = [];
    }
    $sections[$category][$subCategory][] = $item;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" type="image/png" href="./img/icon.png">
    <title>Menu Board</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Bootstrap -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --green: #2ecc71;
            --green-dark: #1a7a3e;
            --green-darker: #0d4a24;
            --green-light: #58d68d;
            --bg-dark: #121212;
            --bg-darker: #0a0a0a;
            --text: #e8e8e8;
            --text-dim: #888;
            --border: rgba(46, 204, 113, 0.15);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-dark);
            color: var(--text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* ===== HEADER ===== */
        .header {
            position: relative;
            background: var(--bg-darker);
            padding: clamp(30px, 6vw, 60px) clamp(16px, 4vw, 40px) clamp(25px, 5vw, 50px);
            text-align: center;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background:
                url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%232ecc71' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .header-content { position: relative; z-index: 1; }

        .header img {
            width: clamp(120px, 25vw, 300px);
            height: auto;
            margin-bottom: clamp(10px, 2vw, 20px);
            max-width: 100%;
        }

        .header h1 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(2.5rem, 8vw, 5rem);
            letter-spacing: 0.08em;
            color: var(--green);
            text-shadow: 0 0 40px rgba(46, 204, 113, 0.3);
            margin-bottom: 8px;
            line-height: 1.1;
        }

        .header-sub {
            font-size: clamp(0.8rem, 2vw, 0.95rem);
            color: var(--text-dim);
            letter-spacing: 0.15em;
            text-transform: uppercase;
            padding: 0 10px;
        }

        .header-line {
            width: clamp(80px, 15vw, 120px);
            height: 3px;
            background: var(--green);
            margin: clamp(14px, 3vw, 20px) auto 0;
            border-radius: 2px;
        }

        .search-wrap {
            margin: clamp(18px, 3vw, 24px) auto 0;
            max-width: 540px;
        }

        .menu-search {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .menu-search::placeholder {
            color: rgba(232, 232, 232, 0.6);
        }

        .menu-search:focus {
            border-color: var(--green-light);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 6px rgba(46, 204, 113, 0.08);
        }

        .export-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 18px;
            padding: 11px 18px;
            border: 1px solid rgba(46, 204, 113, 0.35);
            background: rgba(46, 204, 113, 0.08);
            color: var(--green-light);
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            border-radius: 999px;
            transition: all 0.2s ease;
        }

        .export-link:hover {
            background: rgba(46, 204, 113, 0.16);
            color: #fff;
            transform: translateY(-1px);
        }

        .print-link {
            cursor: pointer;
        }

        /* ===== MENU BOARD ===== */
        .menu-board {
            max-width: 1200px;
            margin: 0 auto;
            padding: clamp(20px, 4vw, 40px) clamp(12px, 3vw, 24px) clamp(30px, 5vw, 60px);
        }

        .menu-columns {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: clamp(16px, 3vw, 30px);
        }

        @media (max-width: 768px) {
            .menu-columns { grid-template-columns: 1fr; gap: 0; }
        }

        /* ===== CATEGORY BLOCK ===== */
        .category-block {
            margin-bottom: clamp(20px, 3vw, 30px);
        }

        .cat-header {
            display: flex;
            align-items: center;
            margin-bottom: clamp(14px, 2vw, 20px);
            gap: 12px;
            /* Give the whole header a fixed height context */
            height: clamp(36px, 5vw, 44px);
        }

        .cat-bar {
            flex: 1;
            min-width: 0;
            height: 100%;
            background: var(--green);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 0 clamp(12px, 2vw, 20px);
        }

        /* Arrow tip using a separate element for perfect alignment */
        .cat-bar-arrow {
            position: absolute;
            right: -14px;
            top: 0;
            width: 0;
            height: 0;
            border-left: 14px solid var(--green);
            border-top: clamp(18px, 2.5vw, 22px) solid transparent;
            border-bottom: clamp(18px, 2.5vw, 22px) solid transparent;
        }

        .cat-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(1.3rem, 3vw, 1.7rem);
            letter-spacing: 0.1em;
            color: #111;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .cat-count {
            font-size: clamp(0.65rem, 1.5vw, 0.8rem);
            color: var(--text-dim);
            font-weight: 600;
            letter-spacing: 0.05em;
            white-space: nowrap;
            flex-shrink: 0;
            padding-right: 4px;
        }

        /* ===== MENU ITEM ROW ===== */
        .menu-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: clamp(8px, 1.5vw, 12px) 0;
            border-bottom: 1px dashed rgba(46, 204, 113, 0.1);
            position: relative;
            gap: 8px;
        }

        .menu-row:last-child { border-bottom: none; }

        .menu-left {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }

        .menu-name-row {
            display: flex;
            align-items: baseline;
            width: 100%;
            gap: 8px;
        }

        .menu-name {
            font-size: clamp(0.85rem, 2vw, 0.95rem);
            font-weight: 500;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex-shrink: 1;
        }

        .menu-dots {
            flex: 1;
            min-width: 20px;
            border-bottom: 2px dotted rgba(46, 204, 113, 0.15);
            height: 0.55em;
            display: block;
        }

        .menu-price {
            font-size: clamp(0.85rem, 2vw, 0.95rem);
            font-weight: 700;
            color: var(--green);
            white-space: nowrap;
            flex-shrink: 0;
        }

        .menu-desc {
            font-size: clamp(0.7rem, 1.5vw, 0.78rem);
            color: var(--text-dim);
            margin-top: 3px;
            font-style: italic;
            line-height: 1.4;
            word-wrap: break-word;
        }

        /* Mobile: hide dots, stack name/price */
        @media (max-width: 480px) {
            .menu-name-row {
                flex-wrap: wrap;
                gap: 4px;
            }
            .menu-name {
                white-space: normal;
                flex: 1 1 70%;
            }
            .menu-dots {
                display: none;
            }
            .menu-price {
                flex: 0 0 auto;
                margin-left: auto;
            }
        }

        /* ===== SUBCATEGORY ===== */
        .subcat-label {
            font-size: 14px;
            font-weight: 900;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--green-light);
            margin: clamp(12px, 2vw, 16px) 0 clamp(8px, 1.5vw, 10px);
            padding-left: 8px;
            border-left: 3px solid var(--green);
        }

        /* ===== FOOTER ===== */
        .menu-footer {
            text-align: center;
            padding: clamp(20px, 4vw, 30px) clamp(12px, 3vw, 24px);
            border-top: 1px solid var(--border);
            color: var(--text-dim);
            font-size: clamp(0.7rem, 1.5vw, 0.8rem);
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .menu-footer span {
            color: var(--green);
            font-weight: 700;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm 14mm;
            }

            html, body {
                overflow: visible;
                max-width: none;
                background: #121212 !important;
                color: var(--text) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .header {
                padding: 24px 18px 18px;
            }

            .menu-board {
                max-width: none;
                padding: 18px 0 24px;
            }

            .export-link {
                display: none !important;
            }

            .menu-footer {
                padding: 18px 0 0;
            }
        }

        /* ===== EMPTY / ERROR ===== */
        .state-box {
            text-align: center;
            padding: clamp(40px, 10vw, 80px) clamp(16px, 4vw, 20px);
            grid-column: 1 / -1;
        }

        .state-box h2 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(1.8rem, 5vw, 2.5rem);
            color: var(--green);
            margin-bottom: 10px;
        }

        .state-box p {
            color: var(--text-dim);
            font-size: clamp(0.9rem, 2vw, 1rem);
        }

        .error h2 { color: #e74c3c; }

        /* Prevent horizontal overflow */
        body, html {
            overflow-x: hidden;
            max-width: 100vw;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <img src="./img/icon.png" alt="">
            <h1>OUR MENU</h1>
            <p class="header-sub">Fresh dishes & drinks from our kitchen</p>
            <div class="header-line"></div>
            <div class="search-wrap">
                <input id="menuSearch" class="menu-search" type="search" placeholder="Search dishes, categories or descriptions…" aria-label="Search menu items">
            </div>
            <!-- <a class="export-link" href="menuv1_pdf.php" target="_blank" rel="noopener">Export PDF</a> -->
            <!-- <a class="export-link" href="menuv1_content_pdf.php" target="_blank" rel="noopener" style="margin-left: 10px;">Content Only PDF</a> -->
            <!-- <a class="export-link" href="menuv1_print.php" target="_blank" rel="noopener" style="margin-left: 10px;">Print Menu</a> -->
            <!-- <a class="export-link print-link" href="#" onclick="window.print(); return false;" style="margin-left: 10px;">Print This Page</a> -->
            <!-- <a class="export-link" href="menuv1_tcpdf.php" target="_blank" rel="noopener" style="margin-left: 10px;">TCPDF PDF</a> -->
            <!-- <a class="export-link" href="menuv1_html2pdf.php" target="_blank" rel="noopener" style="margin-left: 10px;">HTML2PDF</a> -->
            <a class="export-link" href="menu_table.php" target="_blank" rel="noopener" style="margin-left: 10px;">Export to  PDF</a>
        </div>
    </header>

    <div class="menu-board">
        <?php if (!empty($errorMessage)): ?>
            <div class="state-box error">
                <h2>Menu Unavailable</h2>
                <p><?= $errorMessage ?></p>
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
                    if ($i % 2 === 0) $col1[$key] = $sections[$key];
                    else $col2[$key] = $sections[$key];
                }
                ?>

                <div class="menu-col">
                    <?php foreach ($col1 as $catName => $subsections):
                        $totalItems = array_sum(array_map('count', $subsections));
                    ?>
                        <div class="category-block">
                            <div class="cat-header">
                                <div class="cat-bar">
                                    <span class="cat-title"><?= htmlspecialchars(strtoupper($catName)) ?></span>
                                    <span class="cat-bar-arrow"></span>
                                </div>
                                <span class="cat-count ms-3"><?= $totalItems ?> items</span>
                            </div>
                            <?php foreach ($subsections as $subName => $items): ?>
                                <?php if ($subName !== 'General' || count($subsections) > 1): ?>
                                    <div class="subcat-label font-weight-bold text-white py-3 bg-success"><?= htmlspecialchars(strtoupper($subName)) ?></div>
                                <?php endif; ?>
                                <?php foreach ($items as $item): ?>
                                    <div class="menu-row">
                                        <div class="menu-left">
                                            <div class="menu-name-row">
                                                <span class="menu-name"><?= htmlspecialchars($item['menu_name']) ?></span>
                                                <span class="menu-dots"></span>
                                                <span class="menu-price"><?= number_format($item['menu_price'], 0) ?> RWF</span>
                                            </div>
                                            <?php if (!empty($item['menu_desc'])): ?>
                                                <div class="menu-desc"><?= htmlspecialchars($item['menu_desc']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="menu-col">
                    <?php foreach ($col2 as $catName => $subsections):
                        $totalItems = array_sum(array_map('count', $subsections));
                    ?>
                        <div class="category-block">
                            <div class="cat-header">
                                <div class="cat-bar">
                                    <span class="cat-title"><?= htmlspecialchars(strtoupper($catName)) ?></span>
                                    <span class="cat-bar-arrow"></span>
                                </div>
                                <span class="cat-count ms-3"><?= $totalItems ?> items</span>
                            </div>
                            <?php foreach ($subsections as $subName => $items): ?>
                                <?php if ($subName !== 'General' || count($subsections) > 1): ?>
                                    <div class="subcat-label font-weight-bold text-white py-3 bg-success"><?= htmlspecialchars(strtoupper($subName)) ?></div>
                                <?php endif; ?>
                                <?php foreach ($items as $item): ?>
                                    <div class="menu-row">
                                        <div class="menu-left">
                                            <div class="menu-name-row">
                                                <span class="menu-name"><?= htmlspecialchars($item['menu_name']) ?></span>
                                                <span class="menu-dots"></span>
                                                <span class="menu-price"><?= number_format($item['menu_price'], 0) ?> RWF</span>
                                            </div>
                                            <?php if (!empty($item['menu_desc'])): ?>
                                                <div class="menu-desc"><?= htmlspecialchars($item['menu_desc']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="menu-footer">
        Made with care <span class="green-dot"></span> Fresh ingredients daily
        <br>
        &copy; <script>document.write(new Date().getFullYear());</script> KABC Hotel &bull; All rights reserved
        <br>
        <!-- Powered By Credence Software -->
        <p>Powered by <a href="#" style="color: var(--green-light); text-decoration: none; font-weight: 600;" disabled>Credence Software</a></p>
    </footer>
    <script>
        (function () {
            var searchInput = document.getElementById('menuSearch');
            var columns = document.querySelector('.menu-columns');
            var categoryBlocks = document.querySelectorAll('.category-block');
            var searchState = document.getElementById('searchState');

            if (!searchInput || !columns) return;

            function normalize(text) {
                return String(text || '').trim().toLowerCase();
            }

            function matches(text, query) {
                return normalize(text).indexOf(query) !== -1;
            }

            function updateSearch() {
                var query = normalize(searchInput.value);
                var anyVisible = false;

                categoryBlocks.forEach(function (block) {
                    var rows = Array.from(block.querySelectorAll('.menu-row'));
                    var labels = Array.from(block.querySelectorAll('.subcat-label'));
                    var visibleCount = 0;

                    rows.forEach(function (row) {
                        var text = row.textContent || '';
                        var visible = query === '' || matches(text, query);
                        row.style.display = visible ? '' : 'none';
                        if (visible) visibleCount += 1;
                    });

                    labels.forEach(function (label) {
                        var next = label.nextElementSibling;
                        var labelVisible = false;

                        while (next && !next.classList.contains('subcat-label')) {
                            if (next.classList.contains('menu-row') && next.style.display !== 'none') {
                                labelVisible = true;
                                break;
                            }
                            next = next.nextElementSibling;
                        }

                        label.style.display = labelVisible ? '' : 'none';
                    });

                    var blockVisible = visibleCount > 0;
                    block.style.display = blockVisible ? '' : 'none';
                    if (blockVisible) anyVisible = true;

                    var countElement = block.querySelector('.cat-count');
                    if (countElement) {
                        countElement.textContent = visibleCount + ' item' + (visibleCount === 1 ? '' : 's');
                    }
                });

                if (columns) {
                    columns.style.display = anyVisible ? 'grid' : 'none';
                }
                if (searchState) {
                    searchState.style.display = anyVisible ? 'none' : '';
                }
            }

            searchInput.addEventListener('input', updateSearch);
        })();
    </script>
</body>
</html>
