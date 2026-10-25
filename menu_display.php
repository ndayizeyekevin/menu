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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../im">
    <title>Menu Board</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --green-primary: #1a7a3e;
            --green-dark: #0d4a24;
            --green-light: #2ecc71;
            --green-accent: #27ae60;
            --green-muted: #1e4d2b;
            --bg-dark: #0a0f0c;
            --bg-card: #111a13;
            --bg-card-hover: #162018;
            --text-primary: #f0f4f1;
            --text-secondary: #a8b5ad;
            --text-muted: #6b7d6e;
            --border-subtle: rgba(46, 204, 113, 0.12);
            --border-glow: rgba(46, 204, 113, 0.25);
            --shadow-soft: 0 8px 32px rgba(0, 0, 0, 0.4);
            --shadow-glow: 0 0 40px rgba(46, 204, 113, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Hero Section */
        .hero {
            position: relative;
            padding: 60px 24px 50px;
            text-align: center;
            background: linear-gradient(180deg, var(--green-dark) 0%, var(--bg-dark) 100%);
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(ellipse at 20% 0%, rgba(46, 204, 113, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 100%, rgba(26, 122, 62, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            background: rgba(46, 204, 113, 0.1);
            border: 1px solid var(--border-glow);
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--green-light);
            margin-bottom: 24px;
        }

        .hero-badge .dot {
            width: 6px;
            height: 6px;
            background: var(--green-light);
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }

        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 900;
            letter-spacing: -0.02em;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #fff 0%, var(--green-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: 1.1rem;
            color: var(--text-secondary);
            max-width: 500px;
            margin: 0 auto;
        }

        .hero-divider {
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--green-light), transparent);
            margin: 30px auto 0;
            border-radius: 2px;
        }

        /* Navigation Tabs */
        .nav-tabs {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(10, 15, 12, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-subtle);
            padding: 0 24px;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .nav-tabs::-webkit-scrollbar {
            display: none;
        }

        .nav-tabs-inner {
            display: flex;
            gap: 8px;
            max-width: 1180px;
            margin: 0 auto;
            padding: 16px 0;
        }

        .nav-tab {
            flex-shrink: 0;
            padding: 10px 24px;
            background: transparent;
            border: 1px solid var(--border-subtle);
            border-radius: 10px;
            color: var(--text-secondary);
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .nav-tab:hover {
            border-color: var(--border-glow);
            color: var(--text-primary);
            background: rgba(46, 204, 113, 0.05);
        }

        .nav-tab.active {
            background: var(--green-primary);
            border-color: var(--green-primary);
            color: #fff;
            box-shadow: 0 4px 20px rgba(26, 122, 62, 0.4);
        }

        /* Main Content */
        .menu-board {
            max-width: 1180px;
            margin: 0 auto;
            padding: 40px 24px 60px;
        }

        /* Category Section */
        .category-section {
            margin-bottom: 50px;
            scroll-margin-top: 80px;
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 28px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--border-subtle);
        }

        .category-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--green-primary), var(--green-accent));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            box-shadow: 0 4px 15px rgba(26, 122, 62, 0.3);
        }

        .category-title-group {
            flex: 1;
        }

        .category-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .category-count {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .category-line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, var(--border-subtle), transparent);
            margin-left: 16px;
        }

        /* Subcategory */
        .subcategory {
            margin-bottom: 32px;
        }

        .subcategory-title {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--green-light);
            margin-bottom: 20px;
            padding: 6px 16px;
            background: rgba(46, 204, 113, 0.08);
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
        }

        .subcategory-title::before {
            content: '';
            width: 8px;
            height: 8px;
            background: var(--green-light);
            border-radius: 2px;
            transform: rotate(45deg);
        }

        /* Menu Grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 16px;
        }

        .menu-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .menu-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--green-primary), var(--green-light));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .menu-card:hover {
            background: var(--bg-card-hover);
            border-color: var(--border-glow);
            transform: translateY(-2px);
            box-shadow: var(--shadow-glow), var(--shadow-soft);
        }

        .menu-card:hover::before {
            opacity: 1;
        }

        .menu-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 10px;
        }

        .menu-item-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.3;
        }

        .menu-item-price {
            flex-shrink: 0;
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--green-light);
            background: rgba(46, 204, 113, 0.1);
            padding: 6px 14px;
            border-radius: 10px;
            border: 1px solid var(--border-subtle);
        }

        .menu-item-desc {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .menu-item-id {
            display: inline-block;
            margin-top: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 0.05em;
            padding: 3px 10px;
            background: rgba(255,255,255,0.03);
            border-radius: 6px;
        }

        /* Empty & Error States */
        .state-message {
            text-align: center;
            padding: 80px 24px;
            max-width: 500px;
            margin: 0 auto;
        }

        .state-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 24px;
            background: rgba(46, 204, 113, 0.1);
            border: 1px solid var(--border-subtle);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .state-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .state-text {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .error-message .state-icon {
            background: rgba(231, 76, 60, 0.1);
            border-color: rgba(231, 76, 60, 0.2);
        }

        .error-message .state-title {
            color: #e74c3c;
        }

        /* Footer */
        .menu-footer {
            text-align: center;
            padding: 40px 24px;
            border-top: 1px solid var(--border-subtle);
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .menu-footer .green-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            background: var(--green-light);
            border-radius: 50%;
            margin: 0 8px;
            vertical-align: middle;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero {
                padding: 40px 20px 35px;
            }
            .hero h1 {
                font-size: 2.4rem;
            }
            .hero p {
                font-size: 1rem;
            }
            .menu-board {
                padding: 24px 16px 40px;
            }
            .menu-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .category-header {
                flex-wrap: wrap;
            }
            .category-line {
                display: none;
            }
            .nav-tab {
                padding: 8px 16px;
                font-size: 0.85rem;
            }
        }

        @media (min-width: 1200px) {
            .menu-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <header class="hero">
        <img src="./img/icon.png" alt="KABC Logo" width="200" style="margin-bottom: 20px;">
        <div class="hero-content">
            <div class="hero-badge">
                <span class="dot"></span>
                Fresh From The Kitchen
            </div>
            <h1>Our Menu</h1>
            <p>Discover our carefully curated selection of fresh dishes and drinks, crafted with passion and the finest ingredients.</p>
            <div class="hero-divider"></div>
        </div>
    </header>

    <?php if (!empty($errorMessage)): ?>
        <div class="menu-board">
            <div class="state-message error-message">
                <div class="state-icon">⚠</div>
                <div class="state-title">Oops!</div>
                <div class="state-text"><?= $errorMessage ?></div>
            </div>
        </div>
    <?php elseif (empty($sections)): ?>
        <div class="menu-board">
            <div class="state-message">
                <div class="state-icon">🍽</div>
                <div class="state-title">Menu Coming Soon</div>
                <div class="state-text">No menu items are available right now. Check back later!</div>
            </div>
        </div>
    <?php else: ?>
        <!-- Navigation Tabs -->
        <nav class="nav-tabs">
            <div class="nav-tabs-inner">
                <button class="nav-tab active" onclick="scrollToSection('all')">All Items</button>
                <?php foreach ($sections as $sectionName => $subsections): ?>
                    <button class="nav-tab" onclick="scrollToSection('<?= htmlspecialchars(strtolower(str_replace(' ', '-', $sectionName))) ?>')">
                        <?= htmlspecialchars($sectionName) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </nav>

        <!-- Menu Content -->
        <div class="menu-board">
            <?php foreach ($sections as $sectionName => $subsections): 
                $sectionId = strtolower(str_replace(' ', '-', $sectionName));
                $totalItems = array_sum(array_map('count', $subsections));
            ?>
                <section class="category-section" id="<?= htmlspecialchars($sectionId) ?>">
                    <div class="category-header">
                        <div class="category-icon">🍽</div>
                        <div class="category-title-group">
                            <h2 class="category-title"><?= htmlspecialchars($sectionName) ?></h2>
                            <span class="category-count"><?= $totalItems ?> item<?= $totalItems !== 1 ? 's' : '' ?></span>
                        </div>
                        <div class="category-line"></div>
                    </div>

                    <?php foreach ($subsections as $subName => $items): ?>
                        <div class="subcategory">
                            <div class="subcategory-title"><?= htmlspecialchars($subName) ?></div>
                            <div class="menu-grid">
                                <?php foreach ($items as $item): ?>
                                    <article class="menu-card">
                                        <div class="menu-card-header">
                                            <div class="menu-item-name"><?= htmlspecialchars($item['menu_name']) ?></div>
                                            <div class="menu-item-price"><?= number_format($item['menu_price'], 0) ?> RWF</div>
                                        </div>
                                        <?php if (!empty($item['menu_desc'])): ?>
                                            <div class="menu-item-desc"><?= htmlspecialchars($item['menu_desc']) ?></div>
                                        <?php endif; ?>
                                        <span class="menu-item-id">#<?= htmlspecialchars($item['menu_id']) ?></span>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <footer class="menu-footer">
        Made with care <span class="green-dot"></span> Fresh ingredients daily
        <br>
        &copy; <script>document.write(new Date().getFullYear());</script> KABC Hotel &bull; All rights reserved
        <br>
        <!-- Powered By Credence Software -->
        <p>Powered by <a href="#" style="color: var(--green-light); text-decoration: none; font-weight: 600;" disabled>Credence Software</a></p>
    </footer>

    <script>
        // Smooth scroll to section
        function scrollToSection(id) {
            if (id === 'all') {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                const el = document.getElementById(id);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            // Update active tab
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
                if (tab.textContent.trim().toLowerCase().replace(/\s+/g, '-') === id || 
                    (id === 'all' && tab.textContent.trim() === 'All Items')) {
                    tab.classList.add('active');
                }
            });
        }

        // Highlight active tab on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.id;
                    document.querySelectorAll('.nav-tab').forEach(tab => {
                        tab.classList.remove('active');
                        const tabText = tab.textContent.trim().toLowerCase().replace(/\s+/g, '-');
                        if (tabText === id) {
                            tab.classList.add('active');
                        }
                    });
                }
            });
        }, { rootMargin: '-100px 0px -60% 0px' });

        document.querySelectorAll('.category-section').forEach(section => {
            observer.observe(section);
        });
    </script>
</body>
</html>