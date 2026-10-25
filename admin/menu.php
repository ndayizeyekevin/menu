<?php
require_once __DIR__ . '/includes/require_admin.php';

$search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$flash = adminConsumeFlash();

try {
    if ($search === '') {
        $stmt = $db->query(
            'SELECT menu_id, menu_name, menu_desc, menu_price, image_path
             FROM menu ORDER BY menu_name ASC LIMIT 200'
        );
    } else {
        $stmt = $db->prepare(
            'SELECT menu_id, menu_name, menu_desc, menu_price, image_path
             FROM menu WHERE menu_name LIKE :search_name OR menu_desc LIKE :search_desc
             ORDER BY menu_name ASC LIMIT 200'
        );
        $searchTerm = '%' . $search . '%';
        $stmt->execute([':search_name' => $searchTerm, ':search_desc' => $searchTerm]);
    }
    $menuItems = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Unable to load menu items for admin: ' . $e->getMessage());
    http_response_code(500);
    $menuItems = [];
    $flash = ['message' => 'Menu items could not be loaded.', 'type' => 'error'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu management | KABC Hotel</title>
    <link rel="icon" type="image/png" href="../img/icon.png">
    <script src="../assets/js/admin_theme.js"></script>
    <link rel="stylesheet" href="../assets/css/admin.css?v=7">
</head>
<body>
<div class="admin-layout">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <?php require __DIR__ . '/includes/header.php'; ?>
        <header class="admin-header">
            <div>
                <p class="eyebrow">MENU MANAGEMENT</p>
                <h1>Menu items</h1>
                <p class="muted">Search and edit item names, descriptions, and prices.</p>
            </div>
            <a class="button primary" href="menu_image_upload.php">Upload image</a>
        </header>

        <?php if ($flash): ?>
            <div class="notice <?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status"><?= adminEscape($flash['message']) ?></div>
        <?php endif; ?>

        <form class="search-form" id="menuSearchForm" method="get">
            <label class="visually-hidden" for="menuSearch">Search menu</label>
            <input id="menuSearch" name="q" type="search" value="<?= adminEscape($search) ?>" placeholder="Search by item or description">
            <button class="button primary" type="submit">Search</button>
            <a class="button" id="clearMenuSearch" href="menu.php"<?= $search === '' ? ' hidden' : '' ?>>Clear</a>
        </form>

        <section class="admin-card table-wrap">
            <p class="muted" id="menuSearchStatus" aria-live="polite">
                <?= count($menuItems) === 200 ? 'Showing up to 200 results. Narrow your search for other items.' : count($menuItems) . ' items found.' ?>
            </p>
            <table class="admin-table">
                <thead><tr><th>Item</th><th>Description</th><th>Price</th><th>Image</th><th></th></tr></thead>
                <tbody id="menuResults">
                <?php foreach ($menuItems as $item): ?>
                    <tr>
                        <td><?= adminEscape($item['menu_name']) ?></td>
                        <td><?= adminEscape($item['menu_desc']) ?></td>
                        <td><?= number_format((float) $item['menu_price']) ?> RWF</td>
                        <td><?= !empty($item['image_path']) ? 'Available' : 'None' ?></td>
                        <td><a class="button" href="menu_edit.php?id=<?= (int) $item['menu_id'] ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$menuItems): ?><tr><td colspan="5" class="muted">No menu items found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>
<script src="../assets/js/admin_menu_search.js"></script>
</body>
</html>
