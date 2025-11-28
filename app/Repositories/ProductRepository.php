<?php
/**
 * Product Repository
 * 
 * Centralizes all product-related database queries.
 * Replaces scattered SQL throughout the codebase.
 */

namespace App\Repositories;

class ProductRepository
{
    private \mysqli $conn;
    
    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }
    
    /**
     * Get products with filtering, sorting, and pagination
     * 
     * @param array $filters ['category' => int, 'search' => string, 'min_price' => float, 'max_price' => float]
     * @param string $sortBy 'newest', 'price_asc', 'price_desc', 'name_asc', 'name_desc'
     * @param int $page Current page number
     * @param int $perPage Items per page
     * @return array ['products' => array, 'total' => int, 'pages' => int]
     */
    public function getFiltered(array $filters = [], string $sortBy = 'newest', int $page = 1, int $perPage = 12): array
    {
        $params = [];
        $types = '';
        $where = ['status = 1'];
        
        // Search filter
        if (!empty($filters['search'])) {
            $where[] = '(name LIKE ? OR description LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ss';
        }
        
        // Category filter
        if (!empty($filters['category']) && $filters['category'] > 0) {
            $where[] = 'category = ?';
            $params[] = (int)$filters['category'];
            $types .= 'i';
        }
        
        // Price range filter
        if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
            $minPrice = $filters['min_price'] ?? 0;
            $maxPrice = $filters['max_price'] ?? PHP_FLOAT_MAX;
            $where[] = 'price BETWEEN ? AND ?';
            $params[] = (float)$minPrice;
            $params[] = (float)$maxPrice;
            $types .= 'dd';
        }
        
        // Featured filter
        if (!empty($filters['featured'])) {
            $where[] = 'is_featured = 1';
        }
        
        // New arrivals filter
        if (!empty($filters['new'])) {
            $where[] = 'is_new = 1';
        }
        
        // Bestsellers filter
        if (!empty($filters['bestseller'])) {
            $where[] = 'is_bestseller = 1';
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countSql = "SELECT COUNT(id) as total FROM products WHERE $whereClause";
        $total = 0;
        
        if ($stmt = $this->conn->prepare($countSql)) {
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $total = (int)$result->fetch_assoc()['total'];
            $stmt->close();
        }
        
        // Build sort clause
        $orderBy = match($sortBy) {
            'price_asc' => 'price ASC',
            'price_desc' => 'price DESC',
            'name_asc' => 'name ASC',
            'name_desc' => 'name DESC',
            'bestseller' => 'is_bestseller DESC, created_at DESC',
            default => 'created_at DESC'
        };
        
        // Pagination
        $offset = ($page - 1) * $perPage;
        $params[] = $offset;
        $params[] = $perPage;
        $types .= 'ii';
        
        // Fetch products
        $sql = "SELECT id, name, description, price, sale_price, image, stock, 
                       is_featured, is_coming_soon, is_bestseller, is_new, created_at
                FROM products 
                WHERE $whereClause 
                ORDER BY $orderBy 
                LIMIT ?, ?";
        
        $products = [];
        if ($stmt = $this->conn->prepare($sql)) {
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            $stmt->close();
        }
        
        return [
            'products' => $products,
            'total' => $total,
            'pages' => (int)ceil($total / $perPage),
            'current_page' => $page,
            'per_page' => $perPage
        ];
    }
    
    /**
     * Get new arrivals
     */
    public function getNewArrivals(int $limit = 8): array
    {
        return $this->getFiltered(['new' => true], 'newest', 1, $limit)['products'];
    }
    
    /**
     * Get featured products
     */
    public function getFeatured(int $limit = 8): array
    {
        return $this->getFiltered(['featured' => true], 'newest', 1, $limit)['products'];
    }
    
    /**
     * Get bestsellers
     */
    public function getBestsellers(int $limit = 8): array
    {
        return $this->getFiltered(['bestseller' => true], 'bestseller', 1, $limit)['products'];
    }
    
    /**
     * Get products on sale
     */
    public function getOnSale(int $limit = 8): array
    {
        $sql = "SELECT id, name, description, price, sale_price, image, stock,
                       is_featured, is_coming_soon, is_bestseller, is_new
                FROM products 
                WHERE status = 1 AND sale_price > 0 AND sale_price < price
                ORDER BY (price - sale_price) DESC
                LIMIT ?";
        
        $products = [];
        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            $stmt->close();
        }
        
        return $products;
    }
    
    /**
     * Get single product by ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM products WHERE id = ? AND status = 1";
        
        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();
            return $product ?: null;
        }
        
        return null;
    }
    
    /**
     * Get all categories
     */
    public function getCategories(): array
    {
        $sql = "SELECT id, name, slug FROM categories ORDER BY name ASC";
        $categories = [];
        
        if ($result = $this->conn->query($sql)) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
            $result->free();
        }
        
        return $categories;
    }
    
    /**
     * Get price range for filters
     */
    public function getPriceRange(): array
    {
        $sql = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE status = 1";
        
        if ($result = $this->conn->query($sql)) {
            $range = $result->fetch_assoc();
            $result->free();
            return [
                'min' => (float)floor($range['min_price'] ?? 0),
                'max' => (float)ceil($range['max_price'] ?? 10000)
            ];
        }
        
        return ['min' => 0, 'max' => 10000];
    }
    
    /**
     * Search products
     */
    public function search(string $query, int $limit = 20): array
    {
        return $this->getFiltered(['search' => $query], 'newest', 1, $limit)['products'];
    }
    
    /**
     * Get related products (same category, excluding current)
     */
    public function getRelated(int $productId, int $categoryId, int $limit = 4): array
    {
        $sql = "SELECT id, name, price, sale_price, image, is_new, is_featured
                FROM products 
                WHERE status = 1 AND category = ? AND id != ?
                ORDER BY RAND()
                LIMIT ?";
        
        $products = [];
        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param('iii', $categoryId, $productId, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            $stmt->close();
        }
        
        return $products;
    }
    
    /**
     * Get product images
     */
    public function getImages(int $productId): array
    {
        $sql = "SELECT id, image_path, media_type, variant_color, is_primary, sort_order
                FROM product_images 
                WHERE product_id = ?
                ORDER BY is_primary DESC, sort_order ASC";
        
        $images = [];
        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param('i', $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $images[] = $row;
            }
            $stmt->close();
        }
        
        return $images;
    }
    
    /**
     * Get product variants
     */
    public function getVariants(int $productId): array
    {
        $sql = "SELECT id, variant_type, variant_value, price_modifier, stock
                FROM product_variants 
                WHERE product_id = ?
                ORDER BY variant_type, variant_value";
        
        $variants = [];
        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param('i', $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $type = $row['variant_type'];
                if (!isset($variants[$type])) {
                    $variants[$type] = [];
                }
                $variants[$type][] = $row;
            }
            $stmt->close();
        }
        
        return $variants;
    }
}
