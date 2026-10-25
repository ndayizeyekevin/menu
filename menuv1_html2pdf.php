<?php
require_once __DIR__ . '/inc/DBController.php';

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
    $errorMessage = 'Menu load failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

$sections = [];
foreach ($menuItems as $item) {
    $category = $item['cat_name'] ?: 'Uncategorized';
    $subCategory = trim((string) $item['subcat_name']) ?: 'General';

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
    <title>Menu HTML2PDF</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --green: #2ecc71;
            --green-light: #58d68d;
            --bg-dark: #121212;
            --bg-darker: #0a0a0a;
            --text: #e8e8e8;
            --text-dim: #b0b0b0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            background: var(--bg-dark);
            color: var(--text);
            font-family: 'Inter', sans-serif;
        }

        body {
            padding: 20px;
        }

        .toolbar {
            max-width: 980px;
            margin: 0 auto 16px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn {
            border: 0;
            border-radius: 999px;
            padding: 12px 18px;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--green);
            color: #111;
        }

        .btn-secondary {
            background: #222;
            color: #fff;
        }

        .pdf-sheet {
            max-width: 980px;
            margin: 0 auto;
            background: var(--bg-dark);
            border: 1px solid rgba(46, 204, 113, 0.18);
        }

        .header {
            text-align: center;
            background: var(--bg-darker);
            padding: 24px 18px 20px;
            border-bottom: 1px solid rgba(46, 204, 113, 0.18);
        }

        .header h1 {
            font-family: 'Bebas Neue', sans-serif;
            color: var(--green);
            font-size: 3rem;
            letter-spacing: 0.12em;
            line-height: 1;
            margin-bottom: 8px;
        }

        .header p {
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.2em;
            font-size: 0.78rem;
        }

        .menu-table {
            width: 100%;
            border-collapse: collapse;
        }

        .category-row td {
            background: var(--green);
            color: #111;
            padding: 10px 12px;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.55rem;
            letter-spacing: 0.08em;
        }

        .category-count {
            float: right;
            font-family: 'Inter', sans-serif;
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            margin-top: 5px;
        }

        .subcategory-row td {
            background: #1a7a3e;
            color: #fff;
            padding: 8px 12px;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .item-row td {
            padding: 8px 12px;
            vertical-align: top;
            border-bottom: 1px solid rgba(46, 204, 113, 0.08);
        }

        .item-name {
            color: #fff;
            font-size: 0.98rem;
            font-weight: 600;
            line-height: 1.25;
        }

        .item-desc {
            margin-top: 3px;
            color: var(--text-dim);
            font-size: 0.78rem;
            font-style: italic;
            line-height: 1.35;
        }

        .item-price {
            color: var(--green);
            font-size: 0.98rem;
            font-weight: 700;
            text-align: right;
            white-space: nowrap;
        }

        .empty-state {
            padding: 48px 20px;
            text-align: center;
        }

        .empty-state h2 {
            font-family: 'Bebas Neue', sans-serif;
            color: var(--green);
            font-size: 2.2rem;
            letter-spacing: 0.08em;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: var(--text-dim);
        }

        @media print {
            body {
                padding: 0;
                background: #fff;
            }

            .toolbar {
                display: none;
            }

            .pdf-sheet {
                max-width: none;
                border: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn btn-primary" type="button" id="generatePdf">Download PDF</button>
        <button class="btn btn-secondary" type="button" onclick="window.print()">Print</button>
        <a class="btn btn-secondary" href="menuv1.php" style="text-decoration:none; display:inline-block;">Back</a>
    </div>

    <div class="pdf-sheet" id="pdfContent">
        <div class="header">
            <h1>OUR MENU</h1>
            <p>Fresh dishes &amp; drinks from our kitchen</p>
        </div>

        <?php if (!empty($errorMessage)): ?>
            <div class="empty-state">
                <h2>Menu Unavailable</h2>
                <p><?= $errorMessage ?></p>
            </div>
        <?php elseif (empty($sections)): ?>
            <div class="empty-state">
                <h2>Coming Soon</h2>
                <p>No menu items are available right now.</p>
            </div>
        <?php else: ?>
            <table class="menu-table" role="presentation">
                <tbody>
                    <?php foreach ($sections as $categoryName => $subsections): ?>
                        <?php $totalItems = array_sum(array_map('count', $subsections)); ?>
                        <tr class="category-row">
                            <td colspan="2">
                                <?= htmlspecialchars(strtoupper($categoryName)) ?>
                                <span class="category-count"><?= (int) $totalItems ?> ITEMS</span>
                            </td>
                        </tr>

                        <?php foreach ($subsections as $subName => $items): ?>
                            <?php if ($subName !== 'General' || count($subsections) > 1): ?>
                                <tr class="subcategory-row">
                                    <td colspan="2"><?= htmlspecialchars(strtoupper($subName)) ?></td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($items as $item): ?>
                                <tr class="item-row">
                                    <td style="width: 78%;">
                                        <div class="item-name"><?= htmlspecialchars($item['menu_name']) ?></div>
                                        <?php if (!empty($item['menu_desc'])): ?>
                                            <div class="item-desc"><?= htmlspecialchars($item['menu_desc']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="item-price" style="width: 22%;"><?= number_format((float) $item['menu_price'], 0) ?> RWF</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('generatePdf').addEventListener('click', function () {
            var element = document.getElementById('pdfContent');
            var options = {
                margin: [8, 8, 8, 8],
                filename: 'kabc-menu-html2pdf.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, scrollY: 0 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['css', 'legacy'] }
            };

            html2pdf().set(options).from(element).save();
        });
    </script>
</body>
</html>
