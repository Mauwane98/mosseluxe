<?php
/**
 * Product Controller
 * 
 * Handles all product-related business logic.
 * Views should only receive prepared data from this controller.
 */

namespace App\Controllers;

use App\Services\InputSanitizer;

class ProductController
{
    private \mysqli $db;
    
    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }
    
    /**
     * Get a single product by ID
     * 
     * @param int $id Product ID
     * @return array|null Product data or null if not found
     */
    public function getProduct(int $id): ?array
    {
        $sql = "SELECT id, name, description, price, sale_price, image, stock, 
                       is_featured, is_coming_soon, is_bestseller, is_new, 
                       category_id, created_at, updated_at
                FROM products 
                WHERE id = ? AND status = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $product = $result->fetch_assoc();
        $stmt->close();
        
        if (!$product) {
            return null;
        }
        
        // Attach additional data
        $product['images'] = $this->getProductImages($id);
        $product['variants'] = $this->getProductVariants($id);
        $product['reviews'] = $this->getProductReviews($id);
        
        return $product;
    }
    
    /**
     * Get product images
     */
    public function getProductImages(int $productId): array
    {
        $sql = "SELECT id, image_path, media_type, variant_color, variant_size, 
                       is_primary, sort_order, is_360_view
                FROM product_images 
                WHERE product_id = ?
                ORDER BY is_primary DESC, sort_order ASC, id ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $images = [];
        while ($row = $result->fetch_assoc()) {
            $images[] = $row;
        }
        $stmt->close();
        
        return $images;
    }
    
    /**
     * Get product variants grouped by type
     */
    public function getProductVariants(int $productId): array
    {
        $sql = "SELECT id, variant_type, variant_value, price_modifier, stock, sku
                FROM product_variants 
                WHERE product_id = ? AND stock > 0
                ORDER BY variant_type, sort_order, variant_value";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $variants = [];
        while ($row = $result->fetch_assoc()) {
            $type = $row['variant_type'];
            if (!isset($variants[$type])) {
                $variants[$type] = [];
            }
            $variants[$type][] = $row;
        }
        $stmt->close();
        
        return $variants;
    }
    
    /**
     * Get product reviews
     */
    public function getProductReviews(int $productId, int $limit = 10): array
    {
        $sql = "SELECT r.id, r.rating, r.review_text, r.created_at,
                       u.first_name, u.last_name
                FROM reviews r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.product_id = ? AND r.status = 1
                ORDER BY r.created_at DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $productId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }
        $stmt->close();
        
        return $reviews;
    }
    
    /**
     * Get products for shop page with filtering and pagination
     */
    public function getProducts(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        $where = ["p.status = 1"];
        $params = [];
        $types = "";
        
        // Category filter
        if (!empty($filters['category_id'])) {
            $where[] = "p.category_id = ?";
            $params[] = (int) $filters['category_id'];
            $types .= "i";
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $search = "%" . $filters['search'] . "%";
            $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $types .= "ss";
        }
        
        // Price range
        if (!empty($filters['min_price'])) {
            $where[] = "COALESCE(p.sale_price, p.price) >= ?";
            $params[] = (float) $filters['min_price'];
            $types .= "d";
        }
        
        if (!empty($filters['max_price'])) {
            $where[] = "COALESCE(p.sale_price, p.price) <= ?";
            $params[] = (float) $filters['max_price'];
            $types .= "d";
        }
        
        // Featured/New/Bestseller filters
        if (!empty($filters['is_featured'])) {
            $where[] = "p.is_featured = 1";
        }
        if (!empty($filters['is_new'])) {
            $where[] = "p.is_new = 1";
        }
        if (!empty($filters['is_bestseller'])) {
            $where[] = "p.is_bestseller = 1";
        }
        
        $whereClause = implode(" AND ", $where);
        
        // Sorting
        $orderBy = match($filters['sort'] ?? 'newest') {
            'price_asc' => "COALESCE(p.sale_price, p.price) ASC",
            'price_desc' => "COALESCE(p.sale_price, p.price) DESC",
            'name_asc' => "p.name ASC",
            'name_desc' => "p.name DESC",
            'bestseller' => "p.is_bestseller DESC, p.created_at DESC",
            default => "p.created_at DESC"
        };
        
        // Count total
        $countSql = "SELECT COUNT(*) as total FROM products p WHERE $whereClause";
        $stmt = $this->db->prepare($countSql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
        
        // Get products
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.id, p.name, p.description, p.price, p.sale_price, 
                       p.image, p.stock, p.is_featured, p.is_new, p.is_bestseller,
                       c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE $whereClause
                ORDER BY $orderBy
                LIMIT ? OFFSET ?";
        
        $params[] = $perPage;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $stmt->close();
        
        return [
            'products' => $products,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Get related products
     */
    public function getRelatedProducts(int $productId, int $categoryId, int $limit = 4): array
    {
        $sql = "SELECT id, name, price, sale_price, image, is_new, is_bestseller
                FROM products 
                WHERE category_id = ? AND id != ? AND status = 1
                ORDER BY RAND()
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iii", $categoryId, $productId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $stmt->close();
        
        return $products;
    }
    
    /**
     * Get featured products for homepage
     */
    public function getFeaturedProducts(int $limit = 8): array
    {
        $sql = "SELECT id, name, price, sale_price, image, is_new, is_bestseller
                FROM products 
                WHERE is_featured = 1 AND status = 1
                ORDER BY created_at DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $stmt->close();
        
        return $products;
    }
    
    /**
     * Get new arrivals
     */
    public function getNewArrivals(int $limit = 8): array
    {
        $sql = "SELECT id, name, price, sale_price, image, is_bestseller
                FROM products 
                WHERE is_new = 1 AND status = 1
                ORDER BY created_at DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $stmt->close();
        
        return $products;
    }
    
    /**
     * Generate SEO-friendly product URL
     */
    public static function generateProductUrl(int $id, string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return "/product/{$id}/{$slug}";
    }
}
