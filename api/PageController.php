<?php

class PageController {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    public function getPage($slug) {
        try {
            // Try to match by slug or id
            $stmt = $this->conn->prepare("
                SELECT id, title, subtitle, slug, content, meta_title, meta_description, status, created_at, updated_at
                FROM pages
                WHERE (slug = ? OR id = ?) AND status = 1
            ");

            $stmt->bind_param("si", $slug, $slug_int);
            $slug_int = (int)$slug;
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                send_json_response(404, ['success' => false, 'message' => 'Page not found']);
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

            send_json_response(200, ['success' => true, 'data' => $response]);

        } catch (Exception $e) {
            error_log('API Page Error: ' . $e->getMessage());
            send_json_response(500, ['success' => false, 'message' => 'Internal server error']);
        }
    }
}
