<?php
require_once __DIR__ . '/includes/require_admin.php';

$menuId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$menuId || $menuId < 1) {
    http_response_code(400);
    exit('Invalid menu item.');
}

try {
    $stmt = $db->prepare(
        'SELECT menu_id, menu_name, menu_desc, menu_price, image_path
         FROM menu WHERE menu_id = :menu_id'
    );
    $stmt->execute([':menu_id' => $menuId]);
    $item = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Unable to load menu item in admin: ' . $e->getMessage());
    http_response_code(500);
    exit('Menu item could not be loaded.');
}

if (!$item) {
    http_response_code(404);
    exit('Menu item not found.');
}

$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!adminVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errorMessage = 'Your session has expired. Please try again.';
    } else {
        $name = isset($_POST['menu_name']) && is_string($_POST['menu_name']) ? trim($_POST['menu_name']) : '';
        $description = isset($_POST['menu_desc']) && is_string($_POST['menu_desc']) ? trim($_POST['menu_desc']) : '';
        $price = isset($_POST['menu_price']) && is_string($_POST['menu_price']) ? trim($_POST['menu_price']) : '';

        if ($name === '' || mb_strlen($name) > 255) {
            $errorMessage = 'Enter an item name between 1 and 255 characters.';
        } elseif (mb_strlen($description) > 100) {
            $errorMessage = 'The description must be 100 characters or fewer.';
        } elseif (!preg_match('/^\d{1,10}$/', $price) || (int) $price > 2147483647) {
            $errorMessage = 'Enter a whole-number price between 0 and 2,147,483,647.';
        } else {
            try {
                $update = $db->prepare(
                    'UPDATE menu SET menu_name = :menu_name, menu_desc = :menu_desc, menu_price = :menu_price
                     WHERE menu_id = :menu_id'
                );
                $update->execute([
                    ':menu_name' => $name,
                    ':menu_desc' => $description,
                    ':menu_price' => (int) $price,
                    ':menu_id' => $menuId,
                ]);
                adminSetFlash('Menu item updated successfully.');
                header('Location: menu.php');
                exit;
            } catch (PDOException $e) {
                error_log('Unable to update menu item: ' . $e->getMessage());
                $errorMessage = 'The menu item could not be updated. Please try again.';
            }
        }

        $item['menu_name'] = $name;
        $item['menu_desc'] = $description;
        $item['menu_price'] = $price;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit menu item | KABC Hotel</title>
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
                <h1>Edit menu item</h1>
                <p class="muted">Changes apply to the public menu. Item ID: <?= (int) $menuId ?></p>
            </div>
            <a class="button" href="menu.php">Back to menu items</a>
        </header>

        <?php if ($errorMessage !== ''): ?>
            <div class="notice error" role="alert"><?= adminEscape($errorMessage) ?></div>
        <?php endif; ?>

        <section class="admin-card">
            <?php if (!empty($item['image_path'])): ?>
                <p>Current image: <a href="../<?= adminEscape($item['image_path']) ?>" target="_blank" rel="noopener">View image</a></p>
            <?php endif; ?>
            <form class="admin-form" method="post">
                <input type="hidden" name="csrf_token" value="<?= adminEscape(adminCsrfToken()) ?>">
                <label for="menu_name">Item name</label>
                <input id="menu_name" name="menu_name" maxlength="255" required value="<?= adminEscape($item['menu_name']) ?>">

                <label for="menu_desc">Description <span class="muted">(100 characters maximum)</span></label>
                <textarea id="menu_desc" name="menu_desc" maxlength="100"><?= adminEscape($item['menu_desc']) ?></textarea>

                <label for="menu_price">Price (RWF)</label>
                <input id="menu_price" name="menu_price" type="number" min="0" max="2147483647" step="1" required value="<?= adminEscape($item['menu_price']) ?>">

                <button class="button primary" type="submit">Save changes</button>
            </form>
        </section>
    </main>
</div>
</body>
</html>
