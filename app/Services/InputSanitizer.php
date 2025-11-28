<?php
/**
 * Input Sanitizer Service
 * 
 * Centralized input sanitization and validation for all request data.
 * Prevents SQL injection, XSS, and other input-based attacks.
 */

namespace App\Services;

class InputSanitizer
{
    /**
     * Sanitize and validate a product ID (strict integer)
     * 
     * @param mixed $id Raw input
     * @return int|null Returns valid positive integer or null
     */
    public static function productId(mixed $id): ?int
    {
        // Must be numeric
        if (!is_numeric($id)) {
            return null;
        }
        
        $id = (int) $id;
        
        // Must be positive
        if ($id <= 0) {
            return null;
        }
        
        // Reasonable upper bound (prevent overflow attacks)
        if ($id > 2147483647) {
            return null;
        }
        
        return $id;
    }
    
    /**
     * Sanitize and validate a user ID
     */
    public static function userId(mixed $id): ?int
    {
        return self::productId($id); // Same validation rules
    }
    
    /**
     * Sanitize and validate an order ID
     */
    public static function orderId(mixed $id): ?int
    {
        return self::productId($id);
    }
    
    /**
     * Sanitize a quantity value
     * 
     * @param mixed $qty Raw input
     * @param int $max Maximum allowed quantity
     * @return int|null Returns valid quantity or null
     */
    public static function quantity(mixed $qty, int $max = 99): ?int
    {
        if (!is_numeric($qty)) {
            return null;
        }
        
        $qty = (int) $qty;
        
        if ($qty < 1 || $qty > $max) {
            return null;
        }
        
        return $qty;
    }
    
    /**
     * Sanitize a price/amount value
     * 
     * @param mixed $price Raw input
     * @return float|null Returns valid positive float or null
     */
    public static function price(mixed $price): ?float
    {
        if (!is_numeric($price)) {
            return null;
        }
        
        $price = (float) $price;
        
        if ($price < 0) {
            return null;
        }
        
        // Round to 2 decimal places
        return round($price, 2);
    }
    
    /**
     * Sanitize a string for safe output (XSS prevention)
     * 
     * @param mixed $input Raw input
     * @param int $maxLength Maximum allowed length
     * @return string Sanitized string
     */
    public static function string(mixed $input, int $maxLength = 255): string
    {
        if (!is_string($input) && !is_numeric($input)) {
            return '';
        }
        
        $input = (string) $input;
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        if (strlen($input) > $maxLength) {
            $input = substr($input, 0, $maxLength);
        }
        
        return $input;
    }
    
    /**
     * Sanitize an email address
     * 
     * @param mixed $email Raw input
     * @return string|null Returns valid email or null
     */
    public static function email(mixed $email): ?string
    {
        if (!is_string($email)) {
            return null;
        }
        
        $email = trim(strtolower($email));
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        
        return $email;
    }
    
    /**
     * Sanitize a phone number (South African format)
     * 
     * @param mixed $phone Raw input
     * @return string|null Returns sanitized phone or null
     */
    public static function phone(mixed $phone): ?string
    {
        if (!is_string($phone) && !is_numeric($phone)) {
            return null;
        }
        
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', (string) $phone);
        
        // Validate length
        if (strlen($phone) < 10 || strlen($phone) > 15) {
            return null;
        }
        
        return $phone;
    }
    
    /**
     * Sanitize a URL slug
     * 
     * @param mixed $slug Raw input
     * @return string Sanitized slug
     */
    public static function slug(mixed $slug): string
    {
        if (!is_string($slug)) {
            return '';
        }
        
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return substr($slug, 0, 100);
    }
    
    /**
     * Sanitize a search query
     * 
     * @param mixed $query Raw input
     * @return string Sanitized search query
     */
    public static function searchQuery(mixed $query): string
    {
        if (!is_string($query)) {
            return '';
        }
        
        $query = trim($query);
        
        // Remove potentially dangerous characters
        $query = preg_replace('/[<>"\']/', '', $query);
        
        // Limit length
        return substr($query, 0, 100);
    }
    
    /**
     * Sanitize all GET parameters
     * 
     * @return array Sanitized GET array
     */
    public static function get(): array
    {
        $sanitized = [];
        
        foreach ($_GET as $key => $value) {
            $key = self::string($key, 50);
            
            if (is_array($value)) {
                $sanitized[$key] = array_map(fn($v) => self::string($v), $value);
            } else {
                $sanitized[$key] = self::string($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize all POST parameters
     * 
     * @return array Sanitized POST array
     */
    public static function post(): array
    {
        $sanitized = [];
        
        foreach ($_POST as $key => $value) {
            $key = self::string($key, 50);
            
            if (is_array($value)) {
                $sanitized[$key] = array_map(fn($v) => self::string($v), $value);
            } else {
                $sanitized[$key] = self::string($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Get a sanitized GET parameter
     * 
     * @param string $key Parameter name
     * @param mixed $default Default value if not set
     * @return mixed Sanitized value
     */
    public static function getParam(string $key, mixed $default = null): mixed
    {
        if (!isset($_GET[$key])) {
            return $default;
        }
        
        return self::string($_GET[$key]);
    }
    
    /**
     * Get a sanitized POST parameter
     * 
     * @param string $key Parameter name
     * @param mixed $default Default value if not set
     * @return mixed Sanitized value
     */
    public static function postParam(string $key, mixed $default = null): mixed
    {
        if (!isset($_POST[$key])) {
            return $default;
        }
        
        return self::string($_POST[$key]);
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string|null $token Token from request
     * @return bool True if valid
     */
    public static function validateCsrf(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitize JSON input from request body
     * 
     * @return array|null Decoded JSON or null on failure
     */
    public static function jsonInput(): ?array
    {
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            return null;
        }
        
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        return $data;
    }
}
