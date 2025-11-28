<?php
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

$conn = get_db_connection();

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Parse the request path to get the page slug
$script_name = $_SERVER['SCRIPT_NAME'];
$path_info = str_replace(dirname($script_name), '', $request_uri);
$path_parts = explode('/', trim($path_info, '/'));
array_shift($path_parts); // remove 'api'

$page_slug = isset($path_parts[1]) ? $path_parts[1] : null;

if ($method === 'GET' && $page_slug) {
    get_page_by_slug($conn, $page_slug);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function get_page_by_slug($conn, $slug) {
    try {
        // Try to match by slug or id
        $stmt = $conn->prepare("
            SELECT id, title, subtitle, slug, content, meta_title, meta_description, status, created_at, updated_at
            FROM pages
            WHERE (slug = ? OR id = ?) AND status = 1
        ");

        $stmt->bind_param("si", $slug, (int)$slug);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Page not found']);
            return;
        }

        $page = $result->fetch_assoc();
        $stmt->close();

        $response = [
            'id' => (int)$page['id'],
            'title' => $page['title'],
            'subtitle' => $page['subtitle'],
            'slug' => $page['slug'],
            'content' => $page['content'],
            'meta_title' => $page['meta_title'],
            'meta_description' => $page['meta_description'],
            'created_at' => $page['created_at'],
            'updated_at' => $page['updated_at']
        ];

        http_response_code(200);
        echo json_encode(['success' => true, 'data' => $response]);

    } catch (Exception $e) {
        error_log('API Page Error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
}

$conn->close();
?>
