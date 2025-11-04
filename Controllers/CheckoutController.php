<?php

namespace App\Controllers;

use Twig\Environment;

class CheckoutController
{
    private $conn;
    private $twig;

    public function __construct($conn, Environment $twig)
    {
        $this->conn = $conn;
        $this->twig = $twig;
    }

    public function index()
    {
        // Clear any previous discount on page load
        unset($_SESSION['discount']);

        $checkout_error = '';
        $order_placed = false;
        $order_id = null;
        $csrf_token = generate_csrf_token(); // Generate token for the form

        // --- Fetch cart details for display ---
        $cart_products_details = [];
        $total_cart_price_display = 0; // For display purposes before checkout submission

        if (!empty($_SESSION['cart'])) {
            $product_ids_in_cart = array_keys($_SESSION['cart']);
            
            if (!empty($product_ids_in_cart)) {
                $placeholders = implode(',', array_fill(0, count($product_ids_in_cart), '?'));
                $sql_cart_items = "SELECT id, name, price, sale_price, image FROM products WHERE id IN ($placeholders)";
                
                if ($stmt_cart_items = $this->conn->prepare($sql_cart_items)) {
                    $types = str_repeat('i', count($product_ids_in_cart));
                    $stmt_cart_items->bind_param($types, ...$product_ids_in_cart);

                    if ($stmt_cart_items->execute()) {
                        $result_cart_items = $stmt_cart_items->get_result();
                        while ($row_cart_item = $result_cart_items->fetch_assoc()) {
                            $price_to_use = (isset($row_cart_item['sale_price']) && $row_cart_item['sale_price'] > 0) ? $row_cart_item['sale_price'] : $row_cart_item['price'];
                            $cart_products_details[$row_cart_item['id']] = $row_cart_item; // Keep original details
                            // Calculate display total
                            $item_quantity = $_SESSION['cart'][$row_cart_item['id']]['quantity'];
                            $total_cart_price_display += $price_to_use * $item_quantity;
                        }
                    } else {
                        error_log("Error executing cart items display query: " . $stmt_cart_items->error);
                    }
                    $stmt_cart_items->close();
                } else {
                    error_log("Error preparing cart items display query: " . $this->conn->error);
                }
            }
        }

        echo $this->twig->render('checkout/index.html', [
            'checkout_error' => $checkout_error,
            'order_placed' => $order_placed,
            'order_id' => $order_id,
            'csrf_token' => $csrf_token,
            'cart_products_details' => $cart_products_details,
            'total_cart_price_display' => $total_cart_price_display,
            'shipping_cost' => SHIPPING_COST,
            'cart_item_count' => count($_SESSION['cart'] ?? [])
        ]);
    }

    public function placeOrder()
    {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $_SESSION['checkout_error'] = 'Invalid CSRF token. Please try again.';
            header("Location: /checkout");
            exit();
        }

        if (empty($_SESSION['cart'])) {
            $_SESSION['checkout_error'] = 'Your shopping cart is empty. Cannot place order.';
            header("Location: /checkout");
            exit();
        }

        // Sanitize and validate shipping details
        $first_name = trim($_POST["firstName"]);
        $last_name = trim($_POST["lastName"]);
        $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
        $address = trim($_POST["address"]);
        $address2 = trim($_POST["address2"]);
        $city = trim($_POST["city"]);
        $province = trim($_POST["province"]);
        $zip = trim($_POST["zip"]);
        $payment_method = isset($_POST["paymentMethod"]) ? trim($_POST["paymentMethod"]) : '';

        // Basic validation for required fields
        if (empty($first_name) || empty($last_name) || empty($email) || empty($address) || empty($city) || empty($province) || empty($zip) || empty($payment_method)) {
            $_SESSION['checkout_error'] = 'Please fill out all required shipping and payment information.';
            header("Location: /checkout");
            exit();
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['checkout_error'] = 'Invalid email format.';
            header("Location: /checkout");
            exit();
        }

        // Combine shipping address into a single string or array for storage
        $shipping_address = [
            'firstName' => $first_name,
            'lastName' => $last_name,
            'email' => $email,
            'address' => $address,
            'address2' => $address2,
            'city' => $city,
            'province' => $province,
            'zip' => $zip
        ];
        $shipping_address_json = json_encode($shipping_address);

        // Calculate total amount (including placeholder shipping cost)
        $subtotal = 0;
        foreach ($_SESSION['cart'] as $product_id => $item) {
            $subtotal += (float)$item['price'] * (int)$item['quantity'];
        }

        // Apply discount if one is set in the session
        $discount_amount = 0;
        $discount_code = null;
        if (isset($_SESSION['discount'])) {
            $discount_amount = $_SESSION['discount']['amount'];
            $discount_code = $_SESSION['discount']['code'];
        }

        $total_amount = $subtotal - $discount_amount + SHIPPING_COST;

        // --- Database Transaction for Order Creation ---
        $this->conn->begin_transaction();

        try {
            // 1. Insert into orders table (user_id can be NULL for guests)
            $user_id = isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : NULL; // Use NULL for guest users
            $status = 'Pending'; // Default status

            $sql_insert_order = "INSERT INTO orders (user_id, total_price, status, discount_code, discount_amount, shipping_address_json) VALUES (?, ?, ?, ?, ?, ?)";
            if ($stmt_insert_order = $this->conn->prepare($sql_insert_order)) {
                $stmt_insert_order->bind_param("idssds", $param_user_id, $param_total_amount, $param_status, $param_discount_code, $param_discount_amount, $param_shipping_json);

                $param_user_id = $user_id;
                $param_total_amount = $total_amount;
                $param_status = $status;
                $param_discount_code = $discount_code;
                $param_discount_amount = $discount_amount;
                $param_shipping_json = $shipping_address_json;

                if ($stmt_insert_order->execute()) {
                    $order_id = $this->conn->insert_id; // Get the ID of the newly created order

                    // 2. Insert into order_items table for each item in the cart
                    $sql_insert_item = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                    if ($stmt_insert_item = $this->conn->prepare($sql_insert_item)) {
                        foreach ($_SESSION['cart'] as $product_id => $item) {
                            $item_price_db = $item['price']; // Price stored in session
                            $item_quantity = $item['quantity'];

                            // Bind parameters for each item
                            $stmt_insert_item->bind_param("iiid", $param_order_id, $param_product_id_item, $param_quantity_item, $param_price_item);

                            $param_order_id = (int)$order_id;
                            $param_product_id_item = $product_id;
                            $param_quantity_item = $item_quantity;
                            $param_price_item = $item_price_db;

                            if (!$stmt_insert_item->execute()) {
                                throw new \Exception("Failed to insert order item for product ID: " . $product_id);
                            }
                        }

                        // 3. Decrement stock for each product
                        $sql_update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
                        if ($stmt_update_stock = $this->conn->prepare($sql_update_stock)) {
                            foreach ($_SESSION['cart'] as $product_id => $item) {
                                $stmt_update_stock->bind_param("ii", $item['quantity'], $product_id);
                                if (!$stmt_update_stock->execute()) {
                                    throw new \Exception("Failed to update stock for product ID: " . $product_id);
                                }
                            }
                            $stmt_update_stock->close();
                        } else {
                            throw new \Exception("Error preparing stock update statement: " . $this->conn->error);
                        }
                        $stmt_insert_item->close();

                        // 5. Update discount code usage
                        if ($discount_code) {
                            $sql_update_discount = "UPDATE discount_codes SET usage_count = usage_count + 1 WHERE code = ?";
                            $stmt_update_discount = $this->conn->prepare($sql_update_discount);
                            $stmt_update_discount->bind_param("s", $discount_code);
                            $stmt_update_discount->execute();
                            $stmt_update_discount->close();
                        }

                        // If order and items inserted successfully, commit the transaction
                        $this->conn->commit();
                        $order_placed = true;

                        // Clear the cart and discount sessions
                        unset($_SESSION['cart'], $_SESSION['discount']);
                        $_SESSION['cart'] = []; // Ensure it's an empty array

                        // Redirect to payment processing or success page.
                        if ($payment_method === 'PayFast') {
                            header("Location: /payfast-process?order_id=" . $order_id);
                        } else {
                            header("Location: /order-success?order_id=" . $order_id);
                        }
                        exit();

                    } else {
                        throw new \Exception("Error preparing order item insertion statement: " . $this->conn->error);
                    }
                } else {
                    throw new \Exception("Failed to insert order: " . $stmt_insert_order->error);
                }
                $stmt_insert_order->close();
            } else {
                throw new \Exception("Error preparing order insertion statement: " . $this->conn->error);
            }
        } catch (\Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            error_log("Checkout error: " . $e->getMessage());
            $_SESSION['checkout_error'] = 'An error occurred during checkout. Please try again.';
            header("Location: /checkout");
            exit();
        }
    }
}
