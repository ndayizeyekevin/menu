<?php
require_once __DIR__ . '/includes/require_admin.php';

$csrfSessionKey = 'menu_image_upload_csrf';
if (!isset($_SESSION[$csrfSessionKey])) {
    $_SESSION[$csrfSessionKey] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION[$csrfSessionKey];

$message = '';
$messageClass = '';
$uploadDir = __DIR__ . '/../uploads';
$uploadUrlPath = 'uploads';
$maxUploadSize = 5 * 1024 * 1024;

function setFlashMessage(string $message, string $class = 'success'): void
{
    $_SESSION['menu_image_upload_flash'] = ['message' => $message, 'class' => $class];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    $menuId = isset($_POST['menu_id']) && is_string($_POST['menu_id'])
        ? trim($_POST['menu_id'])
        : '';
    $file = $_FILES['menu_image'] ?? null;

    if (!is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        $message = 'Your session has expired. Please try again.';
        $messageClass = 'error';
    } elseif ($menuId === '') {
        $message = 'Please select a menu item.';
        $messageClass = 'error';
    } elseif (!is_array($file) || !isset($file['error']) || !is_int($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $uploadError = is_array($file) && isset($file['error']) && is_int($file['error'])
            ? $file['error']
            : UPLOAD_ERR_NO_FILE;
        $message = in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'The image must be 5 MB or smaller.'
            : 'Please choose a valid image file to upload.';
        $messageClass = 'error';
    } elseif (
        !isset($file['tmp_name'], $file['size'])
        || !is_string($file['tmp_name'])
        || !is_int($file['size'])
        || !is_uploaded_file($file['tmp_name'])
    ) {
        $message = 'The uploaded file could not be verified. Please try again.';
        $messageClass = 'error';
    } elseif ($file['size'] <= 0 || $file['size'] > $maxUploadSize) {
        $message = 'The image must be larger than 0 bytes and no larger than 5 MB.';
        $messageClass = 'error';
    } else {
        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo === false ? false : finfo_file($finfo, $file['tmp_name']);
        if ($finfo !== false) {
            finfo_close($finfo);
        }
        $imageInfo = getimagesize($file['tmp_name']);

        if (
            !is_array($imageInfo)
            || $mimeType === false
            || !isset($allowedMimeTypes[$mimeType])
            || ($imageInfo['mime'] ?? '') !== $mimeType
        ) {
            $message = 'Only JPG, PNG, GIF, and WEBP image files are allowed.';
            $messageClass = 'error';
        } elseif (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            error_log('Unable to create menu image upload directory: ' . $uploadDir);
            $message = 'The upload folder is unavailable. Please contact the site administrator.';
            $messageClass = 'error';
        } else {
            $fileExt = $allowedMimeTypes[$mimeType];
            $targetName = 'menu_' . bin2hex(random_bytes(16)) . '.' . $fileExt;
            $targetPath = $uploadDir . '/' . $targetName;
            $relativePath = $uploadUrlPath . '/' . $targetName;

            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                error_log('Unable to move uploaded menu image to: ' . $targetPath);
                $message = 'The image could not be saved. Please try again or contact the site administrator.';
                $messageClass = 'error';
            } else {
                try {
                    $updateStmt = $db->prepare('UPDATE menu SET image_path = :image_path WHERE menu_id = :menu_id');
                    $updateStmt->execute([
                        ':image_path' => $relativePath,
                        ':menu_id' => $menuId,
                    ]);

                    if ($updateStmt->rowCount() > 0) {
                        $message = 'Image uploaded successfully and menu item updated.';
                        $messageClass = 'success';
                    } else {
                        if (is_file($targetPath) && !unlink($targetPath)) {
                            error_log('Unable to remove unreferenced menu image: ' . $targetPath);
                        }
                        $message = 'The selected menu item could not be updated.';
                        $messageClass = 'error';
                    }
                } catch (PDOException $e) {
                    error_log('Menu image database update failed: ' . $e->getMessage());
                    if (is_file($targetPath) && !unlink($targetPath)) {
                        error_log('Unable to remove menu image after database update failure: ' . $targetPath);
                    }
                    $message = 'The image could not be linked to the menu item. Please try again.';
                    $messageClass = 'error';
                }
            }
        }
    }

    setFlashMessage($message, $messageClass);
    $redirectPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (!is_string($redirectPath) || $redirectPath === '') {
        $redirectPath = $_SERVER['SCRIPT_NAME'];
    }
    if ($messageClass === 'error' && $menuId !== '') {
        $redirectPath .= '?selected_menu_id=' . rawurlencode($menuId);
    }
    header('Location: ' . $redirectPath, true, 303);
    exit;
}

if (!empty($_SESSION['menu_image_upload_flash'])) {
    $flash = $_SESSION['menu_image_upload_flash'];
    $message = $flash['message'];
    $messageClass = $flash['class'];
    unset($_SESSION['menu_image_upload_flash']);
}

try {
    $menuStmt = $db->prepare('SELECT menu_id, menu_name, image_path FROM menu ORDER BY menu_name ASC');
    $menuStmt->execute();
    $menuItems = $menuStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $menuItems = [];
    error_log('Unable to load menu items for image upload: ' . $e->getMessage());
    http_response_code(500);
    $message = 'Menu items could not be loaded. Please try again later.';
    $messageClass = 'error';
}

$selectedMenuId = isset($_GET['selected_menu_id']) && is_string($_GET['selected_menu_id'])
    ? $_GET['selected_menu_id']
    : '';
$selectedMenuName = '';
if ($selectedMenuId !== '') {
    foreach ($menuItems as $item) {
        if ((string) $item['menu_id'] === $selectedMenuId) {
            $selectedMenuName = (string) $item['menu_name'];
            break;
        }
    }
    if ($selectedMenuName === '') {
        $selectedMenuId = '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload menu image | KABC Hotel</title>
    <link rel="icon" type="image/png" href="../img/icon.png">
    <script src="../assets/js/admin_theme.js"></script>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/admin_menu_image_upload.css" rel="stylesheet">
    <link href="../assets/css/admin.css?v=9" rel="stylesheet">
</head>
<body>
<div class="admin-layout">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <?php require __DIR__ . '/includes/header.php'; ?>
        <header class="admin-header">
            <div>
                <p class="eyebrow">MENU MANAGEMENT</p>
                <h1>Upload menu image</h1>
                <p class="muted">Choose a menu item and upload an image to display on the public menu.</p>
            </div>
        </header>

            <?php if ($message !== ''): ?>
                <div
                    id="uploadFeedback"
                    class="alert alert-<?= $messageClass === 'success' ? 'success' : 'danger' ?>"
                    data-message="<?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>"
                    data-type="<?= $messageClass === 'success' ? 'success' : 'error' ?>"
                    role="alert"
                >
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <section class="admin-card upload-panel">
            <p class="page-subtitle">Search for a menu item, check its current photo, then choose the replacement image.</p>
            <form id="imageUploadForm" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="mb-3">
                    <label for="menuSearch" class="form-label">Choose a menu item</label>
                    <input
                        class="form-control"
                        type="search"
                        id="menuSearch"
                        placeholder="Click to browse items or start typing..."
                        autocomplete="off"
                        role="combobox"
                        aria-autocomplete="list"
                        aria-expanded="false"
                        aria-controls="menuSearchResults"
                        aria-describedby="menuSearchHelp"
                        value="<?= htmlspecialchars($selectedMenuName, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                    <input type="hidden" id="menu_id" name="menu_id" value="<?= htmlspecialchars($selectedMenuId, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-text" id="menuSearchHelp" aria-live="polite">Click to browse the menu, or type to filter items.</div>
                    <ul class="menu-search-results" id="menuSearchResults" role="listbox" hidden></ul>
                    <div id="menuItemData" hidden>
                        <?php foreach ($menuItems as $item): ?>
                            <?php
                            $itemImagePath = trim((string) ($item['image_path'] ?? ''));
                            $itemImageUrl = $itemImagePath === '' ? '' : '../' . ltrim($itemImagePath, '/');
                            ?>
                            <span
                                data-menu-id="<?= htmlspecialchars((string) $item['menu_id'], ENT_QUOTES, 'UTF-8') ?>"
                                data-menu-name="<?= htmlspecialchars((string) $item['menu_name'], ENT_QUOTES, 'UTF-8') ?>"
                                data-image="<?= htmlspecialchars($itemImageUrl, ENT_QUOTES, 'UTF-8') ?>"
                            ></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="menu_image" class="form-label">Choose image</label>
                    <input class="form-control" type="file" id="menu_image" name="menu_image" accept="image/jpeg,image/png,image/gif,image/webp" required>
                    <div class="form-text">JPG, PNG, GIF, or WEBP. Maximum file size: 5 MB.</div>
                    <div class="upload-validation" id="uploadValidation" role="alert" hidden></div>
                </div>
                <div class="upload-previews" aria-live="polite">
                    <article class="image-preview-card">
                        <h2>Current image</h2>
                        <div class="image-preview-frame" id="currentImageFrame">
                            <img id="currentImagePreview" alt="Current image for the selected menu item" hidden>
                            <p id="currentImageEmpty">Choose a menu item to see its current image.</p>
                        </div>
                        <p class="image-preview-caption" id="currentImageCaption"></p>
                    </article>
                    <article class="image-preview-card">
                        <h2>New image</h2>
                        <div class="image-preview-frame">
                            <img id="newImagePreview" alt="Preview of the image you selected" hidden>
                            <p id="newImageEmpty">Your chosen image will appear here.</p>
                        </div>
                        <p class="image-preview-caption" id="newImageCaption"></p>
                    </article>
                </div>
                <p class="replacement-note" id="replacementNote" hidden></p>
                <div class="upload-actions">
                    <button class="btn btn-primary" id="uploadSubmit" type="submit">Upload image</button>
                    <a class="btn btn-secondary" href="../full_menu">View public menu</a>
                </div>
            </form>
            </section>
    </main>
    </div>
    <div id="spinnerOverlay" class="spinner-overlay">
        <div class="spinner"></div>
    </div>
    <script src="../assets/js/admin_menu_image_upload.js?v=2"></script>
</body>
</html>
