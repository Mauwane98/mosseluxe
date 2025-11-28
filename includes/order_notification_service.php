<?php
/**
 * Mossé Luxe - Order Notification Service
 * Handles automated WhatsApp and email notifications for order status updates
 */

require_once __DIR__ . '/notification_service.php';

// WhatsApp API configuration - Replace with actual WhatsApp Business API integration
define('WHATSAPP_API_URL', 'https://api.whatsapp.com/send'); // Placeholder
define('WHATSAPP_API_KEY', 'your_whatsapp_api_key'); // Placeholder
define('WHATSAPP_PHONE_NUMBER', '+27676162809'); // Use the phone from whatsapp_component.php

/**
 * Send WhatsApp notification for order status update
 */
function send_order_whatsapp_notification($order_id, $customer_phone, $status_message, $order_details = []) {
    // For now, we'll simulate WhatsApp sending
    // In production, integrate with WhatsApp Business API or similar service

    $message = "📦 *Mossé Luxe Order Update*\n\n";
    $message .= "Order: ML-" . $order_id . "\n";
    $message .= "Status: " . $status_message . "\n\n";

    if (!empty($order_details)) {
        foreach ($order_details as $key => $value) {
            $message .= $key . ": " . $value . "\n";
        }
        $message .= "\n";
    }

    $message .= "Track your order: " . SITE_URL . "track_order.php\n";
    $message .= "Need help? Contact us at " . SMTP_FROM_EMAIL;

    // Log WhatsApp notification attempt
    error_log("WhatsApp notification sent to $customer_phone for order ML-$order_id: $status_message");

    // TODO: Implement actual WhatsApp API integration
    /*
    $whatsapp_url = WHATSAPP_API_URL . '?' . http_build_query([
        'phone' => $customer_phone,
        'text' => urlencode($message),
        'api_key' => WHATSAPP_API_KEY
    ]);

    $response = file_get_contents($whatsapp_url);
    return $response !== false;
    */

    return true; // Simulate success for now
}

/**
 * Send comprehensive order status notifications (both email and WhatsApp)
 */
function send_comprehensive_order_notification($order_id, $new_status) {
    // Get order details
    $conn = get_db_connection();
    $order_sql = "SELECT o.*, u.email as user_email, u.name as user_name
                  FROM orders o
                  LEFT JOIN users u ON o.user_id = u.id
                  WHERE o.id = ?";
    $stmt = $conn->prepare($order_sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_result = $stmt->get_result();
    $order = $order_result->fetch_assoc();
    $stmt->close();

    if (!$order) return false;

    // Get customer email and phone
    $customer_email = $order['user_email'];
    $customer_phone = null;
    $customer_name = $order['user_name'] ?: 'Valued Customer';

    // Extract phone from shipping address if available
    if ($order['shipping_address_json']) {
        $shipping_info = json_decode($order['shipping_address_json'], true);
        if (isset($shipping_info['phone'])) {
            $customer_phone = $shipping_info['phone'];
        }
        if (isset($shipping_info['email'])) {
            $customer_email = $shipping_info['email'];
        }
        if (isset($shipping_info['firstName']) && isset($shipping_info['lastName'])) {
            $customer_name = $shipping_info['firstName'] . ' ' . $shipping_info['lastName'];
        }
    }

    // Get order items for notification content
    $items_sql = "SELECT COUNT(*) as item_count FROM order_items WHERE order_id = ?";
    $stmt = $conn->prepare($items_sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items_result = $stmt->get_result();
    $items_data = $items_result->fetch_assoc();
    $stmt->close();

    $conn->close();

    // Prepare notification content based on status
    $status_messages = [
        'confirmed' => [
            'email_subject' => 'Order Confirmed - Mossé Luxe Order #' . $order_id,
            'whatsapp' => '✅ Your order has been confirmed! We\'ll notify you when it ships.',
            'email_status' => 'Confirmed',
            'details' => [
                'Next Step' => 'You will receive an update when your order ships.',
                'Estimated Processing' => '1-2 business days'
            ]
        ],
        'processing' => [
            'email_subject' => 'Order Processing Started - Mossé Luxe Order #' . $order_id,
            'whatsapp' => '⚙️ Your order is now being processed. We\'ll notify you when it ships!',
            'email_status' => 'Processing',
            'details' => [
                'Next Step' => 'You will receive tracking information when your order ships.',
                'Estimated Processing' => '2-3 business days'
            ]
        ],
        'pack' => [
            'email_subject' => 'Order Packed - Mossé Luxe Order #' . $order_id,
            'whatsapp' => '📦 Your order has been packed and is ready for shipment!',
            'email_status' => 'Packed',
            'details' => [
                'Next Step' => 'Your order will be handed to our courier shortly.',
                'Estimated Shipping' => '24-48 hours'
            ]
        ],
        'shipped' => [
            'email_subject' => 'Order Shipped - Mossé Luxe Order #' . $order_id,
            'whatsapp' => '🚚 Great news! Your order has been shipped and is on its way to you.',
            'email_status' => 'Shipped',
            'details' => [
                'Tracking Info' => 'Check your email for tracking details.',
                'Delivery Estimate' => '2-5 business days'
            ]
        ],
        'out_for_delivery' => [
            'email_subject' => 'Out for Delivery - Mossé Luxe Order #' . $order_id,
            'whatsapp' => '🚛 Your order is out for delivery! Please be available to receive it.',
            'email_status' => 'Out for Delivery',
            'details' => [
                'Delivery Today' => 'Keep an eye out for your package!',
                'Contact Courier' => 'If there are any issues, contact our support team.'
            ]
        ],
        'delivered' => [
            'email_subject' => 'Order Delivered - Mossé Luxe Order #' . $order_id,
            'whatsapp' => '🎉 Your order has been delivered successfully! Thank you for choosing Mossé Luxe.',
            'email_status' => 'Delivered',
            'details' => [
                'Delivery Complete' => 'We hope you love your new items!',
                'Need Help?' => 'Contact us if you have any questions or concerns.'
            ]
        ]
    ];

    // Handle both 'Confirmed' and 'Processing' statuses generically
    $notification_key = strtolower(str_replace([' ', '_'], '', $new_status));

    // Map various status formats to notification keys
    $status_mapping = [
        'confirmed' => 'confirmed',
        'processing' => 'processing',
        'packed' => 'pack',
        'shipped' => 'shipped',
        'out for delivery' => 'out_for_delivery',
        'outfordelivery' => 'out_for_delivery',
        'delivered' => 'delivered',
        'completed' => 'delivered' // Treat completed as delivered
    ];

    $notification_key = $status_mapping[$notification_key] ?? null;

    if (!$notification_key || !isset($status_messages[$notification_key])) {
        // Skip notifications for unsupported statuses
        return true;
    }

    $notification = $status_messages[$notification_key];

    // Prepare order data for email
    $order_data = [
        'order_id' => $order_id,
        'status' => $new_status,
        'customer_name' => $customer_name,
        'total' => $order['total_price'],
        'subtotal' => $order['total_price'], // Simplified - could calculate properly
        'shipping_cost' => 0, // Simplified - could calculate properly
        'items' => [] // Would need to fetch actual items
    ];

    // Send email notification
    $email_sent = NotificationService::sendShippingStatusUpdate(
        $order_data,
        $customer_email,
        $customer_name,
        $new_status
    );

    // Send WhatsApp notification if phone number available
    $whatsapp_sent = false;
    if ($customer_phone) {
        $whatsapp_sent = send_order_whatsapp_notification(
            $order_id,
            $customer_phone,
            $notification['whatsapp']
        );
    }

    // Log notification attempts
    error_log(sprintf(
        "Order notification for ML-%d (%s): Email=%s, WhatsApp=%s",
        $order_id,
        $new_status,
        $email_sent ? 'SUCCESS' : 'FAILED',
        $whatsapp_sent ? 'SUCCESS' : 'FAILED'
    ));

    return $email_sent || $whatsapp_sent;
}

/**
 * Send bulk order notifications for admin-triggered status updates
 */
function send_bulk_order_notifications($order_ids, $new_status) {
    $success_count = 0;
    $total_count = count($order_ids);

    foreach ($order_ids as $order_id) {
        if (send_comprehensive_order_notification($order_id, $new_status)) {
            $success_count++;
        }

        // Small delay to avoid overwhelming APIs
        usleep(100000); // 0.1 second delay
    }

    error_log("Bulk notifications sent: $success_count/$total_count successful for status: $new_status");
    return $success_count;
}

/**
 * Hook into order status updates to automatically send notifications
 */
function hook_order_status_update($order_id, $new_status) {
    // Define which statuses trigger notifications
    $notification_statuses = [
        'Processing',
        'Shipped',
        'Delivered',
        'Completed'
    ];

    if (in_array($new_status, $notification_statuses)) {
        return send_comprehensive_order_notification($order_id, $new_status);
    }

    return true;
}
// No closing PHP tag - prevents accidental whitespace output