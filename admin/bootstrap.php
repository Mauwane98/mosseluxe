<?php
/**
 * Admin Bootstrap File
 * Automatically includes all necessary files and sets up enhanced admin functionality
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../includes/db_connect.php';

// Include CSRF protection
require_once '../includes/csrf.php';

// Check admin authentication
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Get database connection
$conn = get_db_connection();

// Set default page title if not set
if (!isset($pageTitle)) {
    $pageTitle = 'Mossé Luxe Admin';
}

// Note: Header is now included manually in each page to avoid duplication

// Function to display success messages
function displaySuccessMessage() {
    if (isset($_SESSION['success_message'])) {
        echo '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 success-message" role="alert">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <div>
                    <p class="font-bold">Success</p>
                    <p>' . htmlspecialchars($_SESSION['success_message']) . '</p>
                </div>
            </div>
        </div>';
        unset($_SESSION['success_message']);
    }
}

// Function to display error messages
function displayErrorMessage() {
    if (isset($_SESSION['error_message'])) {
        echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 error-message" role="alert">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <div>
                    <p class="font-bold">Error</p>
                    <p>' . htmlspecialchars($_SESSION['error_message']) . '</p>
                </div>
            </div>
        </div>';
        unset($_SESSION['error_message']);
    }
}

// Function to get current page for navigation highlighting
function getCurrentPage() {
    return basename($_SERVER['PHP_SELF']);
}

// Function to format currency
function formatCurrency($amount) {
    return 'R' . number_format($amount, 2);
}

// Function to format date
function formatDate($date, $format = 'd M Y, H:i') {
    return date($format, strtotime($date));
}

// Function to get status badge classes
function getStatusBadgeClass($status) {
    $statusClasses = [
        'Pending' => 'bg-yellow-100 text-yellow-800',
        'Processing' => 'bg-blue-100 text-blue-800',
        'Shipped' => 'bg-indigo-100 text-indigo-800',
        'Completed' => 'bg-green-100 text-green-800',
        'Cancelled' => 'bg-red-100 text-red-800',
        'Failed' => 'bg-red-100 text-red-800'
    ];
    return $statusClasses[$status] ?? 'bg-gray-100 text-gray-800';
}



// Function to check if user has permission (for future role-based access)
function hasPermission($permission) {
    // For now, all logged-in admins have full access
    return isset($_SESSION["admin_loggedin"]) && $_SESSION["admin_loggedin"] === true;
}

// Auto-cleanup: Remove old session messages after displaying
register_shutdown_function(function() {
    if (isset($_SESSION['success_message'])) {
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        unset($_SESSION['error_message']);
    }
});
?>
