<?php
use PHPUnit\Framework\TestCase;

class SearchTest extends TestCase
{
    private $conn;

    protected function setUp(): void
    {
        // It's better to use a test database
        require_once 'includes/bootstrap.php';
        $this->conn = get_db_connection();
    }

    protected function tearDown(): void
    {
        $this->conn->close();
    }

    public function testSearchWithValidQuery()
    {
        $is_test_run = true;
        // Capture output
        ob_start();
        include 'search.php';
        $output = ob_get_clean();

        // Assert that results are found
        $this->assertStringContainsString('Found', $output);
        $this->assertStringContainsString($query, $output);
    }

    public function testSearchWithNoResults()
    {
        // Simulate a search query that yields no results
        $query = "NonExistentProductAbc";
        $_GET['q'] = $query;

        $is_test_run = true;
        // Capture output
        ob_start();
        include 'search.php';
        $output = ob_get_clean();

        // Assert that "No products found" message is shown
        $this->assertStringContainsString('No products found', $output);
    }

    public function testSearchWithShortQuery()
    {
        // Simulate a short search query
        $query = "a";
        $_GET['q'] = $query;

        $is_test_run = true;
        // Capture output
        ob_start();
        include 'search.php';
        $output = ob_get_clean();

        // Assert that the "enter at least 2 characters" message is shown
        $this->assertStringContainsString('Please enter at least 2 characters', $output);
    }
}
