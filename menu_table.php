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
    $errorMessage = 'Menu load failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

$rows = [];
foreach ($menuItems as $item) {
    $rows[] = [
        'name' => $item['menu_name'],
        'subcat' => trim((string) $item['subcat_name']) ?: 'General',
        'price' => number_format((float) $item['menu_price'], 0) . ' RWF',
        'desc' => $item['menu_desc'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Table Export</title>
    <link rel="shortcut icon" href="./img/icon.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <style>
        :root {
            --green: #2ecc71;
            --green-dark: #1a7a3e;
            --bg-dark: #121212;
            --bg-darker: #0a0a0a;
            --text: #e8e8e8;
            --text-dim: #a9a9a9;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: var(--bg-dark);
            color: var(--text);
            font-family: 'Inter', sans-serif;
        }

        body {
            padding: 18px;
        }

        .toolbar {
            max-width: 1280px;
            margin: 0 auto 14px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            border: 0;
            border-radius: 999px;
            padding: 11px 18px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .btn-primary {
            background: var(--green);
            color: #111;
        }

        .btn-secondary {
            background: #222;
            color: #fff;
        }

        .sheet {
            max-width: 1280px;
            margin: 0 auto;
            background: var(--bg-dark);
            border: 1px solid rgba(46, 204, 113, 0.18);
        }

        .header {
            text-align: center;
            background: var(--bg-darker);
            padding: 26px 18px 22px;
            border-bottom: 1px solid rgba(46, 204, 113, 0.18);
        }

        .header h1 {
            margin: 0;
            font-family: 'Bebas Neue', sans-serif;
            color: var(--green);
            font-size: 3rem;
            letter-spacing: 0.12em;
            line-height: 1;
        }

        .header p {
            margin: 8px 0 0;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.2em;
            font-size: 0.8rem;
        }

        .preview {
            padding: 18px;
        }

        table.menu-table {
            width: 100%;
            border-collapse: collapse;
            background: #0f0f0f;
        }

        table.menu-table th,
        table.menu-table td {
            border: 1px solid rgba(46, 204, 113, 0.12);
            padding: 12px 13px;
            vertical-align: top;
        }

        table.menu-table th {
            background: var(--green-dark);
            color: #fff;
            font-size: 0.88rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .subcategory-row td {
            background: #1f8f4c;
            color: #fff;
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .counter-cell {
            width: 64px;
            text-align: center;
            font-weight: 700;
            color: #fff;
            background: rgba(46, 204, 113, 0.1);
        }

        .item-name {
            color: #fff;
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1.25;
        }

        .item-desc {
            margin-top: 4px;
            color: var(--text-dim);
            font-size: 0.86rem;
            font-style: italic;
            line-height: 1.35;
        }

        .item-price {
            color: var(--green);
            font-size: 1rem;
            font-weight: 700;
            white-space: nowrap;
            text-align: right;
        }

        .empty-state {
            padding: 40px 20px;
            text-align: center;
        }

        .empty-state h2 {
            margin: 0 0 8px;
            font-family: 'Bebas Neue', sans-serif;
            color: var(--green);
            font-size: 2.2rem;
            letter-spacing: 0.08em;
        }

        .empty-state p {
            color: var(--text-dim);
        }

        .pdf-status {
            max-width: 1280px;
            margin: 0 auto 12px;
            color: #ffd88d;
            font-size: 0.9rem;
        }
        .btn:disabled {
            cursor: not-allowed;
            opacity: 0.55;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 12mm;
            }

            html, body {
                background: #fff !important;
                color: #111 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            body {
                padding: 0;
            }

            .toolbar {
                display: none;
            }

            .pdf-status {
                display: none !important;
            }

            .sheet {
                max-width: none;
                border: 0;
                background: #fff;
            }

            .header {
                background: #fff;
                border-color: #d8e2dc;
            }

            .header h1,
            .empty-state h2,
            .item-price {
                color: #1a7a3e;
            }

            .header p,
            .item-desc {
                color: #555;
            }

            table.menu-table,
            .menu-table td {
                background: #fff;
                color: #111;
            }

            .menu-table th,
            .menu-table .subcategory-row {
                background: #1a7a3e !important;
                color: #fff !important;
            }

            .menu-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .menu-table .counter-cell {
                background: #f1f6f2;
                color: #1a7a3e;
            }

            .menu-table .item-name {
                color: #111;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn btn-primary" type="button" id="downloadPdf"<?= empty($rows) || !empty($errorMessage) ? ' disabled' : '' ?>>Download PDF</button>
        <a class="btn btn-secondary" href="full_menu">Back to Menu</a>
    </div>
    <p class="pdf-status" id="pdfStatus" role="status" hidden></p>

    <div class="sheet" id="menuSheet">
        
        <div class="header">
            <h1>OUR MENU</h1>
        <p>Flat table export with counter, item, subcategory and price</p>
        </div>

        <div class="preview">
            <?php if (!empty($errorMessage)): ?>
                <div class="empty-state">
                    <h2>Menu Unavailable</h2>
                    <p><?= $errorMessage ?></p>
                </div>
            <?php elseif (empty($rows)): ?>
                <div class="empty-state">
                    <h2>Coming Soon</h2>
                    <p>No menu items are available right now.</p>
                </div>
            <?php else: ?>
                <table class="menu-table" id="menuTable">
                    <thead>
                        <tr>
                            <th style="width: 8%;">#</th>
                            <th style="width: 52%;">Item Name</th>
                            <th style="width: 25%;">Subcategory</th>
                            <th style="width: 15%;">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $index => $row): ?>
                            <tr>
                                <td class="counter-cell"><?= $index + 1 ?></td>
                                <td>
                                    <div class="item-name"><?= htmlspecialchars($row['name']) ?></div>
                                    <!-- <?php if (!empty($row['desc'])): ?>
                                        <div class="item-desc"><?= htmlspecialchars($row['desc']) ?></div>
                                    <?php endif; ?> -->
                                </td>
                                <td class="subcategory-row"><?= htmlspecialchars(strtoupper($row['subcat'])) ?></td>
                                <td class="item-price"><?= htmlspecialchars($row['price']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        (function () {
            var downloadButton = document.getElementById('downloadPdf');
            var table = document.getElementById('menuTable');
            var status = document.getElementById('pdfStatus');

            if (!downloadButton || !table) return;

            function printAsPdf() {
                status.textContent = 'Direct download is unavailable. In the print dialog, choose “Save as PDF”.';
                status.hidden = false;
                window.print();
            }

            downloadButton.addEventListener('click', function () {
                var jsPDF = window.jspdf && window.jspdf.jsPDF;
                if (typeof jsPDF !== 'function') {
                    printAsPdf();
                    return;
                }

                try {
                    var doc = new jsPDF('l', 'pt', 'a4');
                    if (typeof doc.autoTable !== 'function') {
                        printAsPdf();
                        return;
                    }

                    var title = 'KABC Hotel Menu';
                    var generated = new Date().toLocaleString();
                    var totalPagesExp = '{total_pages_count_string}';

                    doc.autoTable({
                        html: table,
                        startY: 80,
                        theme: 'grid',
                        headStyles: {
                            fillColor: [26, 122, 62],
                            textColor: [255, 255, 255],
                            fontStyle: 'bold',
                            halign: 'center'
                        },
                        bodyStyles: { fontSize: 10, cellPadding: 9, valign: 'top' },
                        styles: { overflow: 'linebreak' },
                        didDrawPage: function (data) {
                            var pageWidth = doc.internal.pageSize.getWidth();
                            var pageHeight = doc.internal.pageSize.getHeight();
                            doc.setFontSize(18);
                            doc.setTextColor(46, 122, 75);
                            doc.text(title, pageWidth / 2, 36, { align: 'center' });

                            if (data.pageNumber === 1) {
                                doc.setFontSize(9);
                                doc.setTextColor(120);
                                doc.text('Generated: ' + generated, pageWidth - 20, 54, { align: 'right' });
                            }

                            doc.setDrawColor(46, 122, 75);
                            doc.setLineWidth(0.5);
                            doc.line(data.settings.margin.left, 64, pageWidth - data.settings.margin.right, 64);
                            doc.setFontSize(9);
                            doc.setTextColor(120);
                            doc.text('Page ' + data.pageNumber + ' of ' + totalPagesExp, pageWidth - 20, pageHeight - 18, { align: 'right' });
                        }
                    });

                    if (typeof doc.putTotalPages === 'function') doc.putTotalPages(totalPagesExp);
                    doc.save('kabc-menu.pdf');
                } catch (error) {
                    printAsPdf();
                }
            });
        })();
    </script>
</body>
</html>
