<?php
/**
 * Shop Controller
 * 
 * Handles shop page logic including filtering, sorting, and pagination.
 * Separates business logic from view rendering.
 */

namespace App\Controllers;

use App\Repositories\ProductRepository;
use App\Services\InputSanitizer;

class ShopController
{
    private ProductRepository $productRepo;
    private \mysqli $conn;
    
    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
        $this->productRepo = new ProductRepository($conn);
    }
    
    /**
     * Get shop page data
     * 
     * @param array $request $_GET parameters
     * @return array All data needed for the shop view
     */
    public function index(array $request = []): array
    {
        // Sanitize and extract filters
        $filters = $this->extractFilters($request);
        $sortBy = $this->sanitizeSort($request['sort_by'] ?? 'newest');
        $page = max(1, (int)($request['page'] ?? 1));
        $perPage = 12;
        
        // Get price range for slider
        $priceRange = $this->productRepo->getPriceRange();
        
        // Set default price filters if not provided
        if (empty($filters['min_price'])) {
            $filters['min_price'] = $priceRange['min'];
        }
        if (empty($filters['max_price'])) {
            $filters['max_price'] = $priceRange['max'];
        }
        
        // Get filtered products
        $result = $this->productRepo->getFiltered($filters, $sortBy, $page, $perPage);
        
        // Get categories for filter dropdown
        $categories = $this->productRepo->getCategories();
        
        return [
            'products' => $result['products'],
            'pagination' => [
                'total' => $result['total'],
                'pages' => $result['pages'],
                'current' => $result['current_page'],
                'per_page' => $result['per_page']
            ],
            'filters' => [
                'category' => $filters['category'] ?? 0,
                'search' => $filters['search'] ?? '',
                'min_price' => $filters['min_price'],
                'max_price' => $filters['max_price'],
                'sort_by' => $sortBy
            ],
            'categories' => $categories,
            'price_range' => $priceRange
        ];
    }
    
    /**
     * Get products as JSON for AJAX filtering
     */
    public function getProductsJson(array $request = []): array
    {
        $data = $this->index($request);
        
        // Transform products for JSON response
        $products = array_map(function($product) {
            return [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => (float)$product['price'],
                'sale_price' => (float)($product['sale_price'] ?? 0),
                'image' => $this->getImageUrl($product['image']),
                'url' => $this->getProductUrl($product),
                'is_new' => (bool)$product['is_new'],
                'is_featured' => (bool)$product['is_featured'],
                'is_bestseller' => (bool)$product['is_bestseller'],
                'in_stock' => ($product['stock'] ?? 0) > 0
            ];
        }, $data['products']);
        
        return [
            'success' => true,
            'products' => $products,
            'pagination' => $data['pagination'],
            'filters' => $data['filters']
        ];
    }
    
    /**
     * Extract and sanitize filter parameters
     */
    private function extractFilters(array $request): array
    {
        $filters = [];
        
        if (!empty($request['category'])) {
            $filters['category'] = (int)$request['category'];
        }
        
        if (!empty($request['search'])) {
            $filters['search'] = InputSanitizer::string($request['search'], 100);
        }
        
        if (isset($request['min_price']) && $request['min_price'] !== '') {
            $filters['min_price'] = (float)$request['min_price'];
        }
        
        if (isset($request['max_price']) && $request['max_price'] !== '') {
            $filters['max_price'] = (float)$request['max_price'];
        }
        
        if (!empty($request['featured'])) {
            $filters['featured'] = true;
        }
        
        if (!empty($request['new'])) {
            $filters['new'] = true;
        }
        
        return $filters;
    }
    
    /**
     * Sanitize sort parameter
     */
    private function sanitizeSort(string $sort): string
    {
        $allowed = ['newest', 'price_asc', 'price_desc', 'name_asc', 'name_desc', 'bestseller'];
        return in_array($sort, $allowed) ? $sort : 'newest';
    }
    
    /**
     * Get full image URL
     */
    private function getImageUrl(?string $image): string
    {
        if (empty($image)) {
            return SITE_URL . 'assets/images/product-placeholder.png';
        }
        
        // If already a full URL, return as-is
        if (strpos($image, 'http') === 0) {
            return $image;
        }
        
        return SITE_URL . ltrim($image, '/');
    }
    
    /**
     * Generate SEO-friendly product URL
     */
    private function getProductUrl(array $product): string
    {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $product['name'] ?? 'product'));
        return SITE_URL . 'product/' . $product['id'] . '/' . $slug;
    }
}
