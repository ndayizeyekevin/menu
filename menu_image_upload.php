<?php
require_once __DIR__ . '/includes/DBController.php';

session_start();

$message = '';
$messageClass = '';
$uploadDir = __DIR__ . '/uploads';
$uploadUrlPath = 'uploads';

function setFlashMessage(string $message, string $class = 'success'): void {
    $_SESSION['menu_image_upload_flash'] = ['message' => $message, 'class' => $class];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $menuId = isset($_POST['menu_id']) ? trim($_POST['menu_id']) : '';

    if ($menuId === '') {
        $message = 'Please select a menu item.';
        $messageClass = 'error';
    } elseif (empty($_FILES['menu_image']) || $_FILES['menu_image']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Please choose an image file to upload.';
        $messageClass = 'error';
    } else {
        $file = $_FILES['menu_image'];
        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        $fileInfo = getimagesize($file['tmp_name']);
        $mimeType = $fileInfo['mime'] ?? '';

        if (!isset($allowedMimeTypes[$mimeType])) {
            $message = 'Only JPG, PNG, GIF, and WEBP image files are allowed.';
            $messageClass = 'error';
        } else {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileExt = $allowedMimeTypes[$mimeType];
            $safeMenuId = preg_replace('/[^a-zA-Z0-9_-]/', '', $menuId);
            $baseName = 'menu_' . $safeMenuId . '_' . time();
            $targetName = $baseName . '.' . $fileExt;
            $targetPath = $uploadDir . '/' . $targetName;
            $relativePath = $uploadUrlPath . '/' . $targetName;

            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                $message = 'Upload failed. Please check folder permissions on ./uploads.';
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
                        $message = 'Menu item not found or image path unchanged.';
                        $messageClass = 'error';
                    }
                } catch (PDOException $e) {
                    $message = 'Database error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
                    $messageClass = 'error';
                }
            }
        }
    }
}

try {
    $menuStmt = $db->prepare('SELECT menu_id, menu_name, image_path FROM menu ORDER BY menu_name ASC');
    $menuStmt->execute();
    $menuItems = $menuStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $menuItems = [];
    setFlashMessage('Unable to load menu items: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'), 'error');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (!empty($_SESSION['menu_image_upload_flash'])) {
    $flash = $_SESSION['menu_image_upload_flash'];
    $message = $flash['message'];
    $messageClass = $flash['class'];
    unset($_SESSION['menu_image_upload_flash']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Menu Image</title>
    <link rel="icon" type="image/png" href="./img/icon.png">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f1115;
            --panel: #161a1f;
            --text: #eef2f7;
            --muted: #a1a8b8;
            --green: #2ecc71;
            --green-dark: #1a7a3e;
            --border: rgba(255,255,255,0.08);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }
        .page-wrapper {
            max-width: 900px;
            margin: 0 auto;
            padding: 24px;
        }
        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.18);
        }
        h1 {
            margin: 0 0 12px;
            font-size: 2.4rem;
            letter-spacing: 0.03em;
        }
        .page-subtitle {
            margin: 0 0 24px;
            color: var(--muted);
            font-size: 0.98rem;
        }
        
        .form-control,
        .form-select,
        .select2-container--default .select2-selection--single {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.12);
            color: var(--text);
        }
        .select2-container .select2-selection--single {
            height: auto;
            padding: 0.65rem 0.9rem;
            border-radius: 0.35rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--text);
            line-height: 1.4;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100%;
        }
        .select2-container--default .select2-dropdown,
        .select2-container--classic .select2-dropdown {
            background: #0f1115 !important;
            border-color: rgba(255,255,255,0.12) !important;
        }
        .select2-container--default .select2-results__option,
        .select2-container--classic .select2-results__option {
            background-color: #101317 !important;
            color: #eef2f7 !important;
            padding: 10px 12px;
        }
        .select2-container--default .select2-results__option--highlighted,
        .select2-container--classic .select2-results__option--highlighted,
        .select2-container--default .select2-results__option[aria-selected="true"],
        .select2-container--classic .select2-results__option[aria-selected="true"] {
            background-color: rgba(46, 204, 113, 0.24) !important;
            color: #111 !important;
        }
        .spinner-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.76);
            display: none;
            place-items: center;
            z-index: 9999;
        }
        .spinner-overlay.active {
            display: grid;
        }
        .spinner {
            width: 72px;
            height: 72px;
            border: 8px solid rgba(255, 255, 255, 0.16);
            border-top-color: var(--green);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .form-control:focus,
        .form-select:focus {
            border-color: var(--green);
            box-shadow: 0 0 0 0.25rem rgba(46,204,113,0.18);
        }
        .btn-primary {
            background: var(--green);
            border-color: var(--green);
            color: #111;
        }
        .alert {
            border-radius: 14px;
        }
        .preview-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 18px;
            margin-top: 24px;
        }
        .menu-preview {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 18px;
            padding: 18px;
        }
        .menu-preview h2 {
            margin: 0 0 14px;
            font-size: 1.05rem;
            color: var(--green);
        }
        .menu-preview-item {
            margin-bottom: 10px;
        }
        .menu-preview-item:last-child { margin-bottom: 0; }
        .menu-preview-item span {
            color: var(--muted);
            display: block;
            margin-top: 4px;
            font-size: 0.92rem;
        }
        .existing-image {
            max-width: 100%;
            border-radius: 14px;
            margin-top: 14px;
            display: block;
        }
        @media (max-width: 720px) {
            .preview-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <div class="panel">
            <h1>Upload Menu Image</h1>
            <p class="page-subtitle">Choose a menu item and upload an image. The image will be stored in <code>./uploads</code> and the menu's <code>image_path</code> column will be updated.</p>

            <?php if ($message !== ''): ?>
                <div class="alert alert-<?= $messageClass === 'success' ? 'success' : 'danger' ?>" role="alert">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>" method="post" enctype="multipart/form-data">
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
                </div>
                <button class="btn btn-primary" type="submit">Upload Image</button>
                <a class="btn btn-secondary ms-2" href="full_menu.php">Back to Image Menu</a>
            </form>

            <div class="preview-row">
                <div class="menu-preview">
                    <h2>Upload Guidelines</h2>
                    <div class="menu-preview-item">Allowed formats: JPG, PNG, GIF, WEBP</div>
                    <div class="menu-preview-item">Images are stored in <code>./uploads</code> under the site root.</div>
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
    <script>
        $(document).ready(function() {
            $('#menu_id').select2({
                placeholder: 'Select a menu item',
                width: '100%',
                theme: 'classic'
            });

            $('#menu_id').closest('form').on('submit', function () {
                $('#spinnerOverlay').addClass('active');
            });

            var flashMessage = <?= json_encode($message, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            var flashType = <?= json_encode($messageClass, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

            if (flashMessage) {
                Swal.fire({
                    icon: flashType === 'success' ? 'success' : 'error',
                    title: flashType === 'success' ? 'Success' : 'Oops!',
                    text: flashMessage,
                    confirmButtonColor: '#2ecc71',
                }).then(function () {
                    window.location.replace(window.location.pathname);
                });
            }
        });
    </script>
</body>
</html>
