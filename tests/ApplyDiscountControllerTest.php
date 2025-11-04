<?php

use PHPUnit\Framework\TestCase;
use App\Controllers\ApplyDiscountController;

class ApplyDiscountControllerTest extends TestCase
{
    private static $conn;
    private $controller;

    public static function setUpBeforeClass(): void
    {
        // Set up the test database
        $db_host = 'localhost';
        $db_user = 'testuser';
        $db_pass = 'testpassword';
        $db_name = 'test_mosse_luxe';
        self::$conn = new mysqli($db_host, $db_user, $db_pass);
        self::$conn->query("CREATE DATABASE IF NOT EXISTS $db_name");
        self::$conn->select_db($db_name);
        self::$conn->query("
        CREATE TABLE `discount_codes` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `code` varchar(50) NOT NULL,
          `type` enum('percentage','fixed') NOT NULL,
          `value` decimal(10,2) NOT NULL,
          `usage_limit` int(11) NOT NULL DEFAULT 1,
          `usage_count` int(11) NOT NULL DEFAULT 0,
          `is_active` tinyint(1) NOT NULL DEFAULT 1,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `expires_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `code` (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
        self::$conn->query("INSERT INTO `discount_codes` (`code`, `type`, `value`, `usage_limit`, `usage_count`, `is_active`) VALUES ('TESTCODE', 'fixed', '10.00', 1, 0, 1)");
    }

    protected function setUp(): void
    {
        $this->controller = new ApplyDiscountController(self::$conn);

        // Set up the session and POST data
        $_SESSION = [
            'cart' => [
                ['price' => 50, 'quantity' => 2] // Subtotal = 100
            ],
            'csrf_token' => 'test_token'
        ];
        $_POST = [
            'discount_code' => 'TESTCODE',
            'csrf_token' => 'test_token'
        ];
    }

    public function testApplyDiscountWithUsageLimit()
    {
        // Apply the discount code once
        $output1 = $this->controller->apply();
        $result1 = json_decode($output1, true);

        $this->assertTrue($result1['success']);
        $this->assertEquals(10, $result1['discount_amount']);

        // Check that the usage_count is incremented to 1
        $stmt = self::$conn->prepare("SELECT usage_count FROM discount_codes WHERE code = ?");
        $stmt->bind_param("s", $_POST['discount_code']);
        $stmt->execute();
        $result = $stmt->get_result();
        $discount = $result->fetch_assoc();
        $stmt->close();

        $this->assertEquals(1, $discount['usage_count']);

        // Apply the discount code a second time
        $output2 = $this->controller->apply();
        $result2 = json_decode($output2, true);

        $this->assertFalse($result2['success']);
        $this->assertEquals('Discount code has reached its usage limit.', $result2['message']);
    }

    public static function tearDownAfterClass(): void
    {
        // Clean up the database
        self::$conn->query("DROP DATABASE test_mosse_luxe");
        self::$conn->close();
    }
}
