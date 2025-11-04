<?php
require_once 'config.php';

/**
 * Establishes and returns a database connection.
 * Uses a static variable to ensure only one connection is made per request.
 *
 * @return mysqli The database connection object.
 */
function get_db_connection() {
    // A static variable to hold the connection instance.
    static $conn = null;

    // If the connection hasn't been established yet, create it.
    if ($conn === null) {
        $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

        // Check connection
        if ($conn->connect_error) {
            // In a production environment, you would log this error instead of dying.
            error_log("Database Connection Failed: " . $conn->connect_error);
            die("A database connection error occurred. Please try again later.");
        }
        
        $conn->set_charset("utf8mb4");
    }

    return $conn;
}
?>
