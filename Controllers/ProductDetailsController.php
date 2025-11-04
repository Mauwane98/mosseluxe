<?php

namespace App\Controllers;

class ProductDetailsController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getDetails($id)
    {
        header('Content-Type: application/json');

        $product_id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);

        if (!$product_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
            return;
        }

        $sql = "SELECT id, name, description, price, sale_price, image FROM products WHERE id = ? AND status = 1";
        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();

            if ($product) {
                echo json_encode(['success' => true, 'product' => $product]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Product not found.']);
            }
        } else {
            error_log("Error preparing product details query: " . $this->conn->error);
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
    }
}
