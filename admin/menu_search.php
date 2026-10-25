<?php
require_once __DIR__ . '/includes/require_admin.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, private');

$search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$limit = 200;
$searchTerm = '%' . $search . '%';

try {
    $countStmt = $db->prepare(
        'SELECT COUNT(*) FROM menu
         WHERE :empty_search = \'\' OR menu_name LIKE :count_name OR menu_desc LIKE :count_desc'
    );
    $countStmt->execute([
        ':empty_search' => $search,
        ':count_name' => $searchTerm,
        ':count_desc' => $searchTerm,
    ]);
    $total = (int) $countStmt->fetchColumn();

    $stmt = $db->prepare(
        'SELECT menu_id, menu_name, menu_desc, menu_price, image_path
         FROM menu
         WHERE :empty_search = \'\' OR menu_name LIKE :search_name OR menu_desc LIKE :search_desc
         ORDER BY menu_name ASC LIMIT ' . $limit
    );
    $stmt->execute([
        ':empty_search' => $search,
        ':search_name' => $searchTerm,
        ':search_desc' => $searchTerm,
    ]);

    $items = [];
    foreach ($stmt->fetchAll() as $item) {
        $items[] = [
            'id' => (int) $item['menu_id'],
            'name' => (string) $item['menu_name'],
            'description' => (string) $item['menu_desc'],
            'price' => number_format((float) $item['menu_price']),
            'has_image' => !empty($item['image_path']),
        ];
    }
} catch (PDOException $e) {
    error_log('Unable to search menu items for admin: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Menu items could not be searched. Please try again.']);
    exit;
}

echo json_encode([
    'items' => $items,
    'total' => $total,
    'limit' => $limit,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
