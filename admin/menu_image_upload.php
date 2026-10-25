<?php
require_once __DIR__ . '/../includes/DBController.php';

session_start();

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Menu Image</title>
    <link rel="icon" type="image/png" href="../img/icon.png">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/admin_menu_image_upload.css" rel="stylesheet">

</head>
<body>
    <div class="page-wrapper">
        <div class="panel">
            <h1>Upload Menu Image</h1>
            <p class="page-subtitle">Choose a menu item and upload an image. The image will be stored in <code>../uploads</code> and the menu's <code>image_path</code> column will be updated.</p>

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

            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="mb-3">
                    <label for="menu_id" class="form-label">Menu Item</label>
                    <select class="form-select" id="menu_id" name="menu_id" required>
                        <option value="">Select a menu item</option>
                        <?php foreach ($menuItems as $item): ?>
                            <option value="<?= htmlspecialchars($item['menu_id'], ENT_QUOTES, 'UTF-8') ?>"
                                <?= isset($_POST['menu_id']) && $_POST['menu_id'] === (string) $item['menu_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($item['menu_name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="menu_image" class="form-label">Menu Image</label>
                    <input class="form-control" type="file" id="menu_image" name="menu_image" accept="image/jpeg,image/png,image/gif,image/webp" required>
                    <div class="form-text text-light">Maximum file size: 5 MB.</div>
                </div>
                <button class="btn btn-primary" type="submit">Upload Image</button>
                <a class="btn btn-secondary ms-2" href="../full_menu">Back to Image Menu</a>
            </form>

            <div class="preview-row">
                <div class="menu-preview">
                    <h2>Upload Guidelines</h2>
                    <div class="menu-preview-item">Allowed formats: JPG, PNG, GIF, WEBP</div>
                    <div class="menu-preview-item">Images are stored in the shared <code>uploads</code> folder under the site root.</div>
                    <div class="menu-preview-item">The database column <code>image_path</code> is updated with the relative path to the stored image.</div>
                    <div class="menu-preview-item">If the selected menu item already has an image, the new upload replaces the database reference, but not the old file.</div>
                </div>
                <div class="menu-preview">
                    <h2>Existing Menu Count</h2>
                    <div class="menu-preview-item">Total menu items: <?= count($menuItems) ?></div>
                    <?php if (!empty($menuItems)): ?>
                        <div class="menu-preview-item">Selected item preview uses current stored path.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div id="spinnerOverlay" class="spinner-overlay">
        <div class="spinner"></div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/admin_menu_image_upload.js"></script>
</body>
</html>
