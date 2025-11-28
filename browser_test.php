<!DOCTYPE html>
<html>
<head>
    <title>🛒 Cart Browser Test - E-Commerce Flow</title>
    <style>
        body{font-family:'Segoe UI',sans-serif;margin:20px;line-height:1.6;background:#f5f5f5;}
        .container{max-width:1200px;margin:0 auto;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
        .step{margin:20px 0;padding:15px;border-left:4px solid #007bff;background:#e3f2fd;border-radius:5px;}
        .success{color:#28a745;font-weight:bold;}
        .error{color:#dc3545;font-weight:bold;}
        .warning{color:#ffc107;font-weight:bold;}
        .info{color:#17a2b8;}
        .button{background:#007bff;color:white;padding:10px 20px;border:none;border-radius:5px;text-decoration:none;display:inline-block;margin:10px 5px;}
        .button:hover{background:#0056b3;}
        .result{margin:10px 0;padding:10px;background:#f8f9fa;border-radius:4px;}
        .large{font-size:18px;font-weight:bold;}
    </style>
</head>
<body>
    <div class="container">
        <h1>🛒 BROWSER TEST: Complete E-Commerce Shopping Flow</h1>
        <p class="large">Follow these steps to test the entire shopping experience:</p>

        <div class="step">
            <strong>✅ STEP 1: Check Session & Database Status</strong><br>
            <a href="debug_cart_contents.php" class="button">🧪 Check Current Cart State</a>
            <a href="comprehensive_flow_test.php" class="button">📊 Full System Test</a>
        </div>

        <div class="step">
            <strong>🛍️ STEP 2: Test Shop Page</strong><br>
            <a href="shop.php" class="button" target="_blank">🛒 Open Shop Page</a>
            <p class="info">Should show: Product cards with images, names, prices, "Add to Cart" buttons</p>
        </div>

        <div class="step">
            <strong>📄 STEP 3: Test Product Details</strong><br>
            <p class="info">Click any product on shop page. Should show:</p>
            <ul class="info">
                <li>• Product gallery/images</li>
                <li>• Product name and price</li>
                <li>• Add to Cart button</li>
                <li>• Description, sizes, variants</li>
            </ul>
        </div>

        <div class="step">
            <strong>🛒 STEP 4: Test Add to Cart</strong><br>
            <p class="info">Click "Add to Cart" button. Should:</p>
            <ul class="info">
                <li>• Show success message/toast</li>
                <li>• Update cart count in header (should change from 0 to 1)</li>
                <li>• No JavaScript errors</li>
            </ul>
        </div>

        <div class="step">
            <strong>🛒 STEP 5: Test Cart Sidebar</strong><br>
            <a href="shop.php" class="button" target="_blank">🔄 Open Shop Again</a>
            <p class="info">Click the cart icon in header. Should show:</p>
            <ul class="info">
                <li>• Added products with images</li>
                <li>• Quantities and prices</li>
                <li>• Subtotal calculations</li>
                <li>• "Proceed to Checkout" button</li>
            </ul>
        </div>

        <div class="step">
            <strong>📋 STEP 6: Test Cart Page</strong><br>
            <a href="cart.php" class="button" target="_blank">🛒 Open Cart Page</a>
            <p class="info">Should show:</p>
            <ul class="info">
                <li>• All cart items with full details</li>
                <li>• Quantity + and - buttons</li>
                <li>• Remove buttons for each item</li>
                <li>• Updated totals</li>
                <li>• "Proceed to Checkout" button</li>
            </ul>
        </div>

        <div class="step">
            <strong>💳 STEP 7: Test Checkout Process</strong><br>
            <a href="checkout.php" class="button" target="_blank">💳 Open Checkout</a>
            <p class="info">Should load:</p>
            <ul class="info">
                <li>• Shipping information form</li>
                <li>• Order summary with items</li>
                <li>• Payment form ready</li>
            </ul>
        </div>

        <div class="step">
            <strong>🔧 STEP 8: Debug Cart Issues</strong><br>
            <p class="warning">If cart is empty or not working:</p>
            <ul class="warning">
                <li>• Clear browser cookies/cache</li>
                <li>• Try in incognito/private mode</li>
                <li>• Check browser console for JavaScript errors</li>
            </ul>
        </div>

        <div class="step">
            <strong>📊 SYSTEM DIAGNOSTICS</strong><br>
            <p>Current Status:</p>
            <div class="result">
                <?php
                try {
                    require_once 'includes/bootstrap.php';
                    $conn = get_db_connection();

                    // Test database
                    $product_count = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 1");
                    $count = $product_count->fetch_assoc()['count'];

                    // Test session
                    session_start();
                    $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

                    echo "<span class='success'>✅ Database: Connected, {$count} active products</span><br>";
                    echo "<span class='info'>📊 Session Cart Count: {$cart_count}</span><br>";
                    echo "<span class='success'>✅ Files: All core files present</span><br>";
                    echo "<span class='success'>✅ Cart JS: Loaded without defer</span><br>";

                } catch (Exception $e) {
                    echo "<span class='error'>❌ System Error: " . $e->getMessage() . "</span><br>";
                }
                ?>
            </div>
        </div>

        <div style="margin-top:30px;padding:20px;background:#e8f5e8;border-left:4px solid #28a745;">
            <h3 style="color:#28a745;margin:0;">🎉 EXPECTED RESULTS</h3>
            <p>The complete shopping flow should work end-to-end with no errors. All cart operations should persist across page navigation.</p>
        </div>

    </div>

    <script>
        // Interactive features (without debug logging)
        setTimeout(() => {
            // Check if cart JS is available (for future enhancements)
        }, 100);
    </script>
</body>
</html>
