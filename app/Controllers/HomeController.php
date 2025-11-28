<?php
/**
 * Home Controller
 * 
 * Handles homepage logic including hero slides, featured products,
 * new arrivals, and other homepage sections.
 */

namespace App\Controllers;

use App\Repositories\ProductRepository;

class HomeController
{
    private ProductRepository $productRepo;
    private \mysqli $conn;
    
    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
        $this->productRepo = new ProductRepository($conn);
    }
    
    /**
     * Get all homepage data
     * 
     * @return array All data needed for the homepage view
     */
    public function index(): array
    {
        return [
            'hero_slides' => $this->getHeroSlides(),
            'new_arrivals' => $this->productRepo->getNewArrivals(8),
            'featured_products' => $this->productRepo->getFeatured(8),
            'bestsellers' => $this->productRepo->getBestsellers(4),
            'on_sale' => $this->productRepo->getOnSale(4),
            'categories' => $this->getHomeCategories(),
            'homepage_sections' => $this->getHomepageSections(),
            'flash_sale' => $this->getActiveFlashSale()
        ];
    }
    
    /**
     * Get hero slides from database
     */
    public function getHeroSlides(): array
    {
        $sql = "SELECT id, title, subtitle, image_url, button_text, button_link, 
                       text_color, overlay_opacity, is_active, sort_order
                FROM hero_slides 
                WHERE is_active = 1 
                ORDER BY sort_order ASC";
        
        $slides = [];
        if ($result = $this->conn->query($sql)) {
            while ($row = $result->fetch_assoc()) {
                $slides[] = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'subtitle' => $row['subtitle'],
                    'image' => $row['image_url'],
                    'buttonText' => $row['button_text'],
                    'buttonLink' => $row['button_link'],
                    'textColor' => $row['text_color'] ?? 'white',
                    'overlayOpacity' => $row['overlay_opacity'] ?? 0.3
                ];
            }
            $result->free();
        }
        
        // Return default slide if none found
        if (empty($slides)) {
            $slides[] = [
                'id' => 0,
                'title' => 'Welcome to Mossé Luxe',
                'subtitle' => 'Discover our premium collection',
                'image' => SITE_URL . 'assets/images/hero/default.jpg',
                'buttonText' => 'Shop Now',
                'buttonLink' => SITE_URL . 'shop',
                'textColor' => 'white',
                'overlayOpacity' => 0.3
            ];
        }
        
        return $slides;
    }
    
    /**
     * Get homepage categories with images
     */
    public function getHomeCategories(): array
    {
        $sql = "SELECT c.id, c.name, c.slug, c.image, 
                       (SELECT COUNT(*) FROM products p WHERE p.category = c.id AND p.status = 1) as product_count
                FROM categories c 
                WHERE c.is_active = 1 
                ORDER BY c.sort_order ASC, c.name ASC
                LIMIT 6";
        
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
     * Get homepage sections configuration
     */
    public function getHomepageSections(): array
    {
        $sql = "SELECT section_key, section_title, section_subtitle, is_enabled, sort_order
                FROM homepage_sections 
                WHERE is_enabled = 1 
                ORDER BY sort_order ASC";
        
        $sections = [];
        if ($result = $this->conn->query($sql)) {
            while ($row = $result->fetch_assoc()) {
                $sections[$row['section_key']] = [
                    'title' => $row['section_title'],
                    'subtitle' => $row['section_subtitle'],
                    'enabled' => true,
                    'order' => $row['sort_order']
                ];
            }
            $result->free();
        }
        
        // Default sections if none configured
        if (empty($sections)) {
            $sections = [
                'new_arrivals' => ['title' => 'New Arrivals', 'subtitle' => 'Fresh styles just dropped', 'enabled' => true, 'order' => 1],
                'featured' => ['title' => 'Featured', 'subtitle' => 'Curated picks for you', 'enabled' => true, 'order' => 2],
                'bestsellers' => ['title' => 'Bestsellers', 'subtitle' => 'Most loved items', 'enabled' => true, 'order' => 3]
            ];
        }
        
        return $sections;
    }
    
    /**
     * Get active flash sale
     */
    public function getActiveFlashSale(): ?array
    {
        $sql = "SELECT id, title, discount_percent, start_time, end_time, banner_image
                FROM flash_sales 
                WHERE is_active = 1 
                  AND start_time <= NOW() 
                  AND end_time > NOW()
                ORDER BY end_time ASC
                LIMIT 1";
        
        if ($result = $this->conn->query($sql)) {
            $sale = $result->fetch_assoc();
            $result->free();
            
            if ($sale) {
                return [
                    'id' => $sale['id'],
                    'title' => $sale['title'],
                    'discount' => $sale['discount_percent'],
                    'ends_at' => $sale['end_time'],
                    'banner' => $sale['banner_image']
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Get data for JSON API (for dynamic updates)
     */
    public function getJsonData(): array
    {
        $data = $this->index();
        
        // Transform for JSON response
        return [
            'success' => true,
            'hero_slides' => $data['hero_slides'],
            'new_arrivals' => $this->transformProducts($data['new_arrivals']),
            'featured' => $this->transformProducts($data['featured_products']),
            'bestsellers' => $this->transformProducts($data['bestsellers']),
            'flash_sale' => $data['flash_sale']
        ];
    }
    
    /**
     * Transform products for JSON output
     */
    private function transformProducts(array $products): array
    {
        return array_map(function($product) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $product['name'] ?? 'product'));
            return [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => (float)$product['price'],
                'sale_price' => (float)($product['sale_price'] ?? 0),
                'image' => $this->getImageUrl($product['image']),
                'url' => SITE_URL . 'product/' . $product['id'] . '/' . $slug,
                'badges' => $this->getProductBadges($product)
            ];
        }, $products);
    }
    
    /**
     * Get product badges
     */
    private function getProductBadges(array $product): array
    {
        $badges = [];
        if (!empty($product['is_new'])) $badges[] = 'new';
        if (!empty($product['is_featured'])) $badges[] = 'featured';
        if (!empty($product['is_bestseller'])) $badges[] = 'bestseller';
        if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']) $badges[] = 'sale';
        return $badges;
    }
    
    /**
     * Get full image URL
     */
    private function getImageUrl(?string $image): string
    {
        if (empty($image)) {
            return SITE_URL . 'assets/images/product-placeholder.png';
        }
        if (strpos($image, 'http') === 0) {
            return $image;
        }
        return SITE_URL . ltrim($image, '/');
    }
}
