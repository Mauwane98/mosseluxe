<?php
/**
 * Cart Controller
 * 
 * Handles all cart operations with proper session management.
 * Uses database for logged-in users, session + cookie for guests.
 */

namespace App\Controllers;

use App\Services\InputSanitizer;

class CartController
{
    private \mysqli $db;
    private ?int $userId;
    private const COOKIE_NAME = 'ml_cart';
    private const COOKIE_EXPIRY = 7 * 24 * 60 * 60; // 7 days
    
    public function __construct(\mysqli $db)
    {
        $this->db = $db;
        $this->userId = $_SESSION['user_id'] ?? null;
        
        // Initialize cart session
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Restore from cookie if session is empty
        if (empty($_SESSION['cart'])) {
            $this->restoreFromCookie();
        }
    }
    
    /**
     * Add product to cart
     * 
     * @param int $productId Product ID
     * @param int $quantity Quantity to add
     * @param array $options Variant options (color, size, etc.)
     * @return array Response with success status
     */
    public function add(int $productId, int $quantity = 1, array $options = []): array
    {
        // Validate quantity
        if ($quantity < 1 || $quantity > 99) {
            return [
                'success' => false,
                'message' => 'Invalid quantity'
            ];
        }
        
        // Get product details
        $product = $this->getProduct($productId);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Product not found'
            ];
        }
        
        // Check stock
        if ($product['stock'] < $quantity) {
            return [
                'success' => false,
                'message' => 'Insufficient stock. Only ' . $product['stock'] . ' available.'
            ];
        }
        
        // Generate cart item key (includes variant options)
        $cartKey = $this->generateCartKey($productId, $options);
        
        // Check if already in cart
        if (isset($_SESSION['cart'][$cartKey])) {
            $newQty = $_SESSION['cart'][$cartKey]['quantity'] + $quantity;
            
            if ($newQty > $product['stock']) {
                return [
                    'success' => false,
                    'message' => 'Cannot add more. Maximum stock reached.'
                ];
            }
            
            $_SESSION['cart'][$cartKey]['quantity'] = $newQty;
        } else {
            $_SESSION['cart'][$cartKey] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'options' => $options,
                'added_at' => time()
            ];
        }
        
        $this->saveToCookie();
        $this->syncToDatabase();
        
        return [
            'success' => true,
            'message' => 'Added to cart',
            'cart_count' => $this->getItemCount(),
            'cart_total' => $this->getTotal()
        ];
    }
    
    /**
     * Update cart item quantity
     */
    public function update(string $cartKey, int $quantity): array
    {
        if (!isset($_SESSION['cart'][$cartKey])) {
            return [
                'success' => false,
                'message' => 'Item not in cart'
            ];
        }
        
        if ($quantity < 1) {
            return $this->remove($cartKey);
        }
        
        $productId = $_SESSION['cart'][$cartKey]['product_id'];
        $product = $this->getProduct($productId);
        
        if (!$product) {
            unset($_SESSION['cart'][$cartKey]);
            return [
                'success' => false,
                'message' => 'Product no longer available'
            ];
        }
        
        if ($quantity > $product['stock']) {
            return [
                'success' => false,
                'message' => 'Only ' . $product['stock'] . ' available'
            ];
        }
        
        $_SESSION['cart'][$cartKey]['quantity'] = $quantity;
        $this->saveToCookie();
        $this->syncToDatabase();
        
        return [
            'success' => true,
            'message' => 'Cart updated',
            'cart_count' => $this->getItemCount(),
            'cart_total' => $this->getTotal()
        ];
    }
    
    /**
     * Remove item from cart
     */
    public function remove(string $cartKey): array
    {
        if (!isset($_SESSION['cart'][$cartKey])) {
            return [
                'success' => false,
                'message' => 'Item not in cart'
            ];
        }
        
        unset($_SESSION['cart'][$cartKey]);
        $this->saveToCookie();
        $this->syncToDatabase();
        
        return [
            'success' => true,
            'message' => 'Removed from cart',
            'cart_count' => $this->getItemCount(),
            'cart_total' => $this->getTotal()
        ];
    }
    
    /**
     * Clear entire cart
     */
    public function clear(): array
    {
        $_SESSION['cart'] = [];
        $this->saveToCookie();
        
        if ($this->userId) {
            $stmt = $this->db->prepare("DELETE FROM cart_items WHERE user_id = ?");
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            $stmt->close();
        }
        
        return [
            'success' => true,
            'message' => 'Cart cleared'
        ];
    }
    
    /**
     * Get all cart items with product details
     */
    public function getItems(): array
    {
        $items = [];
        
        foreach ($_SESSION['cart'] as $cartKey => $item) {
            $product = $this->getProduct($item['product_id']);
            
            if (!$product) {
                // Product no longer exists, remove from cart
                unset($_SESSION['cart'][$cartKey]);
                continue;
            }
            
            $price = $product['sale_price'] > 0 ? $product['sale_price'] : $product['price'];
            
            $items[] = [
                'cart_key' => $cartKey,
                'product_id' => $item['product_id'],
                'name' => $product['name'],
                'image' => $product['image'],
                'price' => $price,
                'original_price' => $product['price'],
                'quantity' => $item['quantity'],
                'options' => $item['options'] ?? [],
                'subtotal' => $price * $item['quantity'],
                'stock' => $product['stock'],
                'in_stock' => $product['stock'] >= $item['quantity']
            ];
        }
        
        return $items;
    }
    
    /**
     * Get cart item count
     */
    public function getItemCount(): int
    {
        $count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }
    
    /**
     * Get unique product count
     */
    public function getUniqueCount(): int
    {
        return count($_SESSION['cart']);
    }
    
    /**
     * Get cart subtotal
     */
    public function getSubtotal(): float
    {
        $subtotal = 0;
        
        foreach ($_SESSION['cart'] as $item) {
            $product = $this->getProduct($item['product_id']);
            if ($product) {
                $price = $product['sale_price'] > 0 ? $product['sale_price'] : $product['price'];
                $subtotal += $price * $item['quantity'];
            }
        }
        
        return round($subtotal, 2);
    }
    
    /**
     * Get cart total (including shipping, discounts)
     */
    public function getTotal(float $shipping = 0, float $discount = 0): float
    {
        $subtotal = $this->getSubtotal();
        $total = $subtotal + $shipping - $discount;
        return max(0, round($total, 2));
    }
    
    /**
     * Check if cart is empty
     */
    public function isEmpty(): bool
    {
        return empty($_SESSION['cart']);
    }
    
    /**
     * Validate cart before checkout
     */
    public function validate(): array
    {
        $errors = [];
        $warnings = [];
        
        foreach ($_SESSION['cart'] as $cartKey => $item) {
            $product = $this->getProduct($item['product_id']);
            
            if (!$product) {
                $errors[] = "A product in your cart is no longer available";
                unset($_SESSION['cart'][$cartKey]);
                continue;
            }
            
            if ($product['stock'] < $item['quantity']) {
                if ($product['stock'] == 0) {
                    $errors[] = "{$product['name']} is out of stock";
                    unset($_SESSION['cart'][$cartKey]);
                } else {
                    $warnings[] = "{$product['name']} only has {$product['stock']} in stock";
                    $_SESSION['cart'][$cartKey]['quantity'] = $product['stock'];
                }
            }
        }
        
        $this->saveToCookie();
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    /**
     * Merge guest cart into user cart after login
     */
    public function mergeGuestCart(int $userId): void
    {
        // Current session cart becomes the user's cart
        $this->userId = $userId;
        $this->syncToDatabase();
    }
    
    // ========================================
    // Private Methods
    // ========================================
    
    private function generateCartKey(int $productId, array $options): string
    {
        ksort($options);
        $optionString = json_encode($options);
        return md5($productId . $optionString);
    }
    
    private function getProduct(int $productId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, name, price, sale_price, image, stock 
             FROM products 
             WHERE id = ? AND status = 1"
        );
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
        
        return $product;
    }
    
    private function saveToCookie(): void
    {
        $value = json_encode($_SESSION['cart']);
        $expiry = time() + self::COOKIE_EXPIRY;
        
        setcookie(
            self::COOKIE_NAME,
            $value,
            [
                'expires' => $expiry,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }
    
    private function restoreFromCookie(): void
    {
        if (!isset($_COOKIE[self::COOKIE_NAME])) {
            return;
        }
        
        $cart = json_decode($_COOKIE[self::COOKIE_NAME], true);
        
        if (is_array($cart)) {
            $_SESSION['cart'] = $cart;
        }
    }
    
    private function syncToDatabase(): void
    {
        if (!$this->userId) {
            return;
        }
        
        // Clear existing cart items
        $stmt = $this->db->prepare("DELETE FROM cart_items WHERE user_id = ?");
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $stmt->close();
        
        // Insert current cart items
        if (!empty($_SESSION['cart'])) {
            $stmt = $this->db->prepare(
                "INSERT INTO cart_items (user_id, product_id, quantity, options, created_at) 
                 VALUES (?, ?, ?, ?, NOW())"
            );
            
            foreach ($_SESSION['cart'] as $item) {
                $options = json_encode($item['options'] ?? []);
                $stmt->bind_param(
                    "iiis",
                    $this->userId,
                    $item['product_id'],
                    $item['quantity'],
                    $options
                );
                $stmt->execute();
            }
            $stmt->close();
        }
    }
}
