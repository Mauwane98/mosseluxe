<?php
/**
 * Paxi (Pargo) Delivery Integration
 * Provides Paxi Point pickup locations and delivery options
 */

class PaxiService {
    
    private static $api_key;
    private static $api_url = 'https://api.pargo.co.za/v3';
    
    /**
     * Initialize Paxi service
     */
    public static function init() {
        self::$api_key = defined('PAXI_API_KEY') ? PAXI_API_KEY : getenv('PAXI_API_KEY');
    }
    
    /**
     * Get nearby Paxi Points based on postal code or city
     * 
     * @param string $postal_code South African postal code
     * @param string $city City name
     * @param int $limit Number of results to return
     * @return array List of Paxi Points
     */
    public static function getNearbyPoints($postal_code = null, $city = null, $limit = 10) {
        // For now, return mock data until API key is configured
        // Replace with actual API call when ready
        
        if (empty(self::$api_key)) {
            return self::getMockPaxiPoints($city);
        }
        
        try {
            $params = [
                'limit' => $limit
            ];
            
            if ($postal_code) {
                $params['postal_code'] = $postal_code;
            }
            
            if ($city) {
                $params['city'] = $city;
            }
            
            $url = self::$api_url . '/pickup-points?' . http_build_query($params);
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . self::$api_key,
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code === 200) {
                $data = json_decode($response, true);
                return $data['pickup_points'] ?? [];
            }
            
            error_log("Paxi API Error: HTTP $http_code - $response");
            return self::getMockPaxiPoints($city);
            
        } catch (Exception $e) {
            error_log("Paxi Service Error: " . $e->getMessage());
            return self::getMockPaxiPoints($city);
        }
    }
    
    /**
     * Get comprehensive Paxi Points across South Africa
     * Includes PEP stores, shopping centers, and other pickup points
     * Total: 55+ locations nationwide
     */
    private static function getMockPaxiPoints($city = null) {
        // Load comprehensive Paxi points data
        $all_points = require __DIR__ . '/paxi_points_data.php';
        
        // Fallback if file doesn't load
        if (!is_array($all_points)) {
            $all_points = [
            // ========== GAUTENG ==========
            
            // Pretoria / Tshwane
            [
                'id' => 'PAXI_PTA_001',
                'name' => 'Paxi Menlyn Park',
                'address' => 'Menlyn Park Shopping Centre, Atterbury Road',
                'city' => 'Pretoria',
                'province' => 'Gauteng',
                'postal_code' => '0181',
                'phone' => '012 348 1234',
                'hours' => 'Mon-Fri: 9am-6pm, Sat: 9am-5pm, Sun: 9am-3pm',
                'distance' => '2.5 km'
            ],
            [
                'id' => 'PAXI_PTA_002',
                'name' => 'Paxi Woodlands',
                'address' => 'Woodlands Boulevard, Pretorius Street',
                'city' => 'Pretoria',
                'province' => 'Gauteng',
                'postal_code' => '0081',
                'phone' => '012 481 2345',
                'hours' => 'Mon-Fri: 9am-6pm, Sat: 9am-5pm, Sun: Closed',
                'distance' => '3.2 km'
            ],
            [
                'id' => 'PAXI_PTA_003',
                'name' => 'Paxi Brooklyn Mall',
                'address' => 'Brooklyn Mall, Veale Street',
                'city' => 'Pretoria',
                'province' => 'Gauteng',
                'postal_code' => '0181',
                'phone' => '012 346 5678',
                'hours' => 'Mon-Sat: 9am-7pm, Sun: 9am-5pm',
                'distance' => '4.1 km'
            ],
            // Johannesburg
            [
                'id' => 'PAXI_JHB_001',
                'name' => 'Paxi Sandton City',
                'address' => 'Sandton City Shopping Centre, Rivonia Road',
                'city' => 'Johannesburg',
                'province' => 'Gauteng',
                'postal_code' => '2196',
                'phone' => '011 217 6000',
                'hours' => 'Mon-Sat: 9am-7pm, Sun: 9am-5pm',
                'distance' => '1.8 km'
            ],
            [
                'id' => 'PAXI_JHB_002',
                'name' => 'Paxi Rosebank Mall',
                'address' => 'Rosebank Mall, Cradock Avenue',
                'city' => 'Johannesburg',
                'province' => 'Gauteng',
                'postal_code' => '2196',
                'phone' => '011 788 5530',
                'hours' => 'Mon-Fri: 9am-7pm, Sat-Sun: 9am-6pm',
                'distance' => '2.3 km'
            ],
            [
                'id' => 'PAXI_JHB_003',
                'name' => 'Paxi Mall of Africa',
                'address' => 'Mall of Africa, Lone Creek Crescent',
                'city' => 'Johannesburg',
                'province' => 'Gauteng',
                'postal_code' => '1685',
                'phone' => '011 100 8000',
                'hours' => 'Mon-Sun: 9am-7pm',
                'distance' => '5.5 km'
            ],
            // Cape Town
            [
                'id' => 'PAXI_CPT_001',
                'name' => 'Paxi V&A Waterfront',
                'address' => 'Victoria & Alfred Waterfront',
                'city' => 'Cape Town',
                'province' => 'Western Cape',
                'postal_code' => '8001',
                'phone' => '021 408 7600',
                'hours' => 'Mon-Sun: 9am-9pm',
                'distance' => '1.2 km'
            ],
            [
                'id' => 'PAXI_CPT_002',
                'name' => 'Paxi Canal Walk',
                'address' => 'Canal Walk Shopping Centre, Century City',
                'city' => 'Cape Town',
                'province' => 'Western Cape',
                'postal_code' => '7441',
                'phone' => '021 555 4444',
                'hours' => 'Mon-Sat: 9am-7pm, Sun: 9am-5pm',
                'distance' => '3.8 km'
            ],
            // Durban
            [
                'id' => 'PAXI_DBN_001',
                'name' => 'Paxi Gateway Theatre',
                'address' => 'Gateway Theatre of Shopping, Umhlanga',
                'city' => 'Durban',
                'province' => 'KwaZulu-Natal',
                'postal_code' => '4319',
                'phone' => '031 566 1000',
                'hours' => 'Mon-Sun: 9am-7pm',
                'distance' => '2.1 km'
            ],
            [
                'id' => 'PAXI_DBN_002',
                'name' => 'Paxi Pavilion',
                'address' => 'The Pavilion Shopping Centre, Westville',
                'city' => 'Durban',
                'province' => 'KwaZulu-Natal',
                'postal_code' => '3629',
                'phone' => '031 265 0558',
                'hours' => 'Mon-Fri: 9am-6pm, Sat-Sun: 9am-5pm',
                'distance' => '4.5 km'
            ]
            ];
        }
        
        // Filter by city if provided
        if ($city) {
            $filtered = array_filter($all_points, function($point) use ($city) {
                return stripos($point['city'], $city) !== false;
            });
            return array_values($filtered);
        }
        
        return $all_points;
    }
    
    /**
     * Calculate Paxi delivery cost
     * 
     * @param float $cart_total Cart subtotal
     * @param string $paxi_speed Delivery speed: 'standard' (7-9 days) or 'express' (3-5 days)
     * @return float Delivery cost
     */
    public static function calculateCost($cart_total, $paxi_speed = 'standard') {
        // Free delivery over threshold
        if ($cart_total >= FREE_SHIPPING_THRESHOLD) {
            return 0;
        }
        
        // Paxi pricing based on delivery speed
        if ($paxi_speed === 'express') {
            return PAXI_EXPRESS_COST; // R109.95 for 3-5 business days
        } else {
            return PAXI_STANDARD_COST; // R59.95 for 7-9 business days
        }
    }
    
    /**
     * Get delivery time estimate
     * 
     * @param string $speed Delivery speed: 'standard' or 'express'
     * @return string Delivery estimate
     */
    public static function getDeliveryEstimate($speed = 'standard') {
        if ($speed === 'express') {
            return '3-5 business days';
        } else {
            return '7-9 business days';
        }
    }
    
    /**
     * Create Paxi shipment (when order is placed)
     * 
     * @param array $order_data Order information
     * @return array Shipment details
     */
    public static function createShipment($order_data) {
        // This would integrate with Paxi API to create actual shipment
        // For now, return mock tracking data
        
        return [
            'success' => true,
            'tracking_number' => 'PAXI' . strtoupper(substr(md5(time()), 0, 10)),
            'paxi_point_id' => $order_data['paxi_point_id'] ?? null,
            'estimated_delivery' => date('Y-m-d', strtotime('+3 days')),
            'status' => 'pending_pickup'
        ];
    }
}

// Initialize service
PaxiService::init();
// No closing PHP tag - prevents accidental whitespace output