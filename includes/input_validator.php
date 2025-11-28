<?php
/**
 * Input Validation Service
 * Centralized validation for common input types
 */

class InputValidator {
    
    /**
     * Validate email address
     */
    public static function email($email, &$error = null) {
        $email = trim($email);
        
        if (empty($email)) {
            $error = 'Email is required.';
            return false;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format.';
            return false;
        }
        
        // Check for disposable email domains (optional)
        $disposable_domains = ['tempmail.com', 'throwaway.email', '10minutemail.com'];
        $domain = substr(strrchr($email, "@"), 1);
        if (in_array($domain, $disposable_domains)) {
            $error = 'Disposable email addresses are not allowed.';
            return false;
        }
        
        return $email;
    }
    
    /**
     * Validate phone number
     */
    public static function phone($phone, &$error = null) {
        $phone = preg_replace('/[^0-9+]/', '', trim($phone));
        
        if (empty($phone)) {
            $error = 'Phone number is required.';
            return false;
        }
        
        if (strlen($phone) < 10 || strlen($phone) > 15) {
            $error = 'Invalid phone number length.';
            return false;
        }
        
        return $phone;
    }
    
    /**
     * Validate name (first/last name)
     */
    public static function name($name, &$error = null) {
        $name = trim($name);
        
        if (empty($name)) {
            $error = 'Name is required.';
            return false;
        }
        
        if (strlen($name) < 2) {
            $error = 'Name must be at least 2 characters.';
            return false;
        }
        
        if (strlen($name) > 50) {
            $error = 'Name must not exceed 50 characters.';
            return false;
        }
        
        if (!preg_match('/^[a-zA-Z\s\'-]+$/', $name)) {
            $error = 'Name contains invalid characters.';
            return false;
        }
        
        return $name;
    }
    
    /**
     * Validate address
     */
    public static function address($address, &$error = null) {
        $address = trim($address);
        
        if (empty($address)) {
            $error = 'Address is required.';
            return false;
        }
        
        if (strlen($address) < 5) {
            $error = 'Address is too short.';
            return false;
        }
        
        if (strlen($address) > 200) {
            $error = 'Address is too long.';
            return false;
        }
        
        return $address;
    }
    
    /**
     * Validate postal/zip code
     */
    public static function postalCode($code, &$error = null) {
        $code = trim($code);
        
        if (empty($code)) {
            $error = 'Postal code is required.';
            return false;
        }
        
        // South African postal code format (4 digits)
        if (!preg_match('/^[0-9]{4}$/', $code)) {
            $error = 'Invalid postal code format (4 digits required).';
            return false;
        }
        
        return $code;
    }
    
    /**
     * Validate password strength
     */
    public static function password($password, &$error = null) {
        if (empty($password)) {
            $error = 'Password is required.';
            return false;
        }
        
        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
            return false;
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $error = 'Password must contain at least one uppercase letter.';
            return false;
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $error = 'Password must contain at least one lowercase letter.';
            return false;
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $error = 'Password must contain at least one number.';
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate price/amount
     */
    public static function price($price, &$error = null) {
        $price = filter_var($price, FILTER_VALIDATE_FLOAT);
        
        if ($price === false) {
            $error = 'Invalid price format.';
            return false;
        }
        
        if ($price < 0) {
            $error = 'Price cannot be negative.';
            return false;
        }
        
        return $price;
    }
    
    /**
     * Validate quantity
     */
    public static function quantity($quantity, $max = 99, &$error = null) {
        $quantity = filter_var($quantity, FILTER_VALIDATE_INT);
        
        if ($quantity === false) {
            $error = 'Invalid quantity.';
            return false;
        }
        
        if ($quantity < 1) {
            $error = 'Quantity must be at least 1.';
            return false;
        }
        
        if ($quantity > $max) {
            $error = "Quantity cannot exceed $max.";
            return false;
        }
        
        return $quantity;
    }
    
    /**
     * Sanitize HTML input (for descriptions, etc.)
     */
    public static function html($html, $allowed_tags = '<p><br><strong><em><ul><ol><li><a>') {
        return strip_tags($html, $allowed_tags);
    }
    
    /**
     * Validate URL
     */
    public static function url($url, &$error = null) {
        $url = trim($url);
        
        if (empty($url)) {
            $error = 'URL is required.';
            return false;
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $error = 'Invalid URL format.';
            return false;
        }
        
        return $url;
    }
}
// No closing PHP tag - prevents accidental whitespace output