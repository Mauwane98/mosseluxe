<?php
/**
 * Wishlist Controller
 * 
 * Handles wishlist operations with proper session and database management.
 * 
 * Strategy:
 * - Logged-in users: Store in database immediately
 * - Guest users: Store in session with cookie backup
 */

namespace App\Controllers;

use App\Services\InputSanitizer;

class WishlistController
{
    private \mysqli $db;
    private ?int $userId;
    private const COOKIE_NAME = 'ml_wishlist';
    private const COOKIE_EXPIRY = 30 * 24 * 60 * 60; // 30 days
    
    public function __construct(\mysqli $db)
    {
        $this->db = $db;
        $this->userId = $_SESSION['user_id'] ?? null;
        
        // Restore guest wishlist from cookie if session is empty
        if (!$this->userId && empty($_SESSION['wishlist'])) {
            $this->restoreFromCookie();
        }
    }
    
    /**
     * Add product to wishlist
     * 
     * @param int $productId Product ID to add
     * @return array Response with success status and message
     */
    public function add(int $productId): array
    {
        // Validate product exists
        if (!$this->productExists($productId)) {
            return [
                'success' => false,
                'message' => 'Product not found'
            ];
        }
        
        if ($this->userId) {
            return $this->addToDatabase($productId);
        } else {
            return $this->addToSession($productId);
        }
    }
    
    /**
     * Remove product from wishlist
     */
    public function remove(int $productId): array
    {
        if ($this->userId) {
            return $this->removeFromDatabase($productId);
        } else {
            return $this->removeFromSession($productId);
        }
    }
    
    /**
     * Toggle product in wishlist (add if not exists, remove if exists)
     */
    public function toggle(int $productId): array
    {
        if ($this->isInWishlist($productId)) {
            return $this->remove($productId);
        } else {
            return $this->add($productId);
        }
    }
    
    /**
     * Check if product is in wishlist
     */
    public function isInWishlist(int $productId): bool
    {
        if ($this->userId) {
            $stmt = $this->db->prepare(
                "SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?"
            );
            $stmt->bind_param("ii", $this->userId, $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            $exists = $result->num_rows > 0;
            $stmt->close();
            return $exists;
        } else {
            return in_array($productId, $_SESSION['wishlist'] ?? []);
        }
    }
    
    /**
     * Get all wishlist items with product details
     */
    public function getItems(): array
    {
        if ($this->userId) {
            return $this->getItemsFromDatabase();
        } else {
            return $this->getItemsFromSession();
        }
    }
    
    /**
     * Get wishlist count
     */
    public function getCount(): int
    {
        if ($this->userId) {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as count FROM wishlists WHERE user_id = ?"
            );
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            $stmt->close();
            return (int) $count;
        } else {
            return count($_SESSION['wishlist'] ?? []);
        }
    }
    
    /**
     * Clear entire wishlist
     */
    public function clear(): array
    {
        if ($this->userId) {
            $stmt = $this->db->prepare("DELETE FROM wishlists WHERE user_id = ?");
            $stmt->bind_param("i", $this->userId);
            $success = $stmt->execute();
            $stmt->close();
        } else {
            $_SESSION['wishlist'] = [];
            $this->saveToCookie([]);
            $success = true;
        }
        
        return [
            'success' => $success,
            'message' => $success ? 'Wishlist cleared' : 'Failed to clear wishlist'
        ];
    }
    
    /**
     * Merge guest wishlist into user wishlist after login
     */
    public function mergeGuestWishlist(int $userId): void
    {
        $guestWishlist = $_SESSION['wishlist'] ?? [];
        
        if (empty($guestWishlist)) {
            return;
        }
        
        foreach ($guestWishlist as $productId) {
            // Check if already in user's wishlist
            $stmt = $this->db->prepare(
                "SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?"
            );
            $stmt->bind_param("ii", $userId, $productId);
            $stmt->execute();
            $exists = $stmt->get_result()->num_rows > 0;
            $stmt->close();
            
            if (!$exists) {
                $stmt = $this->db->prepare(
                    "INSERT INTO wishlists (user_id, product_id, created_at) VALUES (?, ?, NOW())"
                );
                $stmt->bind_param("ii", $userId, $productId);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        // Clear guest wishlist
        $_SESSION['wishlist'] = [];
        $this->saveToCookie([]);
    }
    
    /**
     * Move item to cart
     */
    public function moveToCart(int $productId): array
    {
        // First, add to cart (using cart functions)
        if (function_exists('addToCart')) {
            $cartResult = addToCart($productId, 1);
            if (!$cartResult['success']) {
                return $cartResult;
            }
        }
        
        // Then remove from wishlist
        return $this->remove($productId);
    }
    
    // ========================================
    // Private Methods - Database Operations
    // ========================================
    
    private function addToDatabase(int $productId): array
    {
        // Check if already exists
        if ($this->isInWishlist($productId)) {
            return [
                'success' => false,
                'message' => 'Already in wishlist',
                'in_wishlist' => true
            ];
        }
        
        $stmt = $this->db->prepare(
            "INSERT INTO wishlists (user_id, product_id, created_at) VALUES (?, ?, NOW())"
        );
        $stmt->bind_param("ii", $this->userId, $productId);
        $success = $stmt->execute();
        $stmt->close();
        
        return [
            'success' => $success,
            'message' => $success ? 'Added to wishlist' : 'Failed to add to wishlist',
            'in_wishlist' => $success,
            'count' => $this->getCount()
        ];
    }
    
    private function removeFromDatabase(int $productId): array
    {
        $stmt = $this->db->prepare(
            "DELETE FROM wishlists WHERE user_id = ? AND product_id = ?"
        );
        $stmt->bind_param("ii", $this->userId, $productId);
        $success = $stmt->execute();
        $affected = $stmt->affected_rows > 0;
        $stmt->close();
        
        return [
            'success' => $affected,
            'message' => $affected ? 'Removed from wishlist' : 'Item not in wishlist',
            'in_wishlist' => false,
            'count' => $this->getCount()
        ];
    }
    
    private function getItemsFromDatabase(): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.name, p.description, p.price, p.sale_price, 
                    p.image, p.stock, w.created_at as added_at
             FROM wishlists w
             INNER JOIN products p ON w.product_id = p.id
             WHERE w.user_id = ? AND p.status = 1
             ORDER BY w.created_at DESC"
        );
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
        
        return $items;
    }
    
    // ========================================
    // Private Methods - Session Operations
    // ========================================
    
    private function addToSession(int $productId): array
    {
        if (!isset($_SESSION['wishlist'])) {
            $_SESSION['wishlist'] = [];
        }
        
        if (in_array($productId, $_SESSION['wishlist'])) {
            return [
                'success' => false,
                'message' => 'Already in wishlist',
                'in_wishlist' => true
            ];
        }
        
        $_SESSION['wishlist'][] = $productId;
        $this->saveToCookie($_SESSION['wishlist']);
        
        return [
            'success' => true,
            'message' => 'Added to wishlist',
            'in_wishlist' => true,
            'count' => count($_SESSION['wishlist'])
        ];
    }
    
    private function removeFromSession(int $productId): array
    {
        if (!isset($_SESSION['wishlist'])) {
            $_SESSION['wishlist'] = [];
        }
        
        $key = array_search($productId, $_SESSION['wishlist']);
        
        if ($key === false) {
            return [
                'success' => false,
                'message' => 'Item not in wishlist',
                'in_wishlist' => false
            ];
        }
        
        unset($_SESSION['wishlist'][$key]);
        $_SESSION['wishlist'] = array_values($_SESSION['wishlist']); // Re-index
        $this->saveToCookie($_SESSION['wishlist']);
        
        return [
            'success' => true,
            'message' => 'Removed from wishlist',
            'in_wishlist' => false,
            'count' => count($_SESSION['wishlist'])
        ];
    }
    
    private function getItemsFromSession(): array
    {
        $productIds = $_SESSION['wishlist'] ?? [];
        
        if (empty($productIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $types = str_repeat('i', count($productIds));
        
        $sql = "SELECT id, name, description, price, sale_price, image, stock
                FROM products 
                WHERE id IN ($placeholders) AND status = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$productIds);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
        
        return $items;
    }
    
    // ========================================
    // Private Methods - Cookie Operations
    // ========================================
    
    private function saveToCookie(array $wishlist): void
    {
        $value = json_encode($wishlist);
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
        
        $wishlist = json_decode($_COOKIE[self::COOKIE_NAME], true);
        
        if (is_array($wishlist)) {
            // Validate all IDs are integers
            $_SESSION['wishlist'] = array_filter(
                $wishlist,
                fn($id) => is_int($id) && $id > 0
            );
        }
    }
    
    // ========================================
    // Private Methods - Validation
    // ========================================
    
    private function productExists(int $productId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM products WHERE id = ? AND status = 1"
        );
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
}
