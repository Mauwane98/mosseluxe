<?php

require_once __DIR__ . '/bootstrap.php'; // Includes db_connect.php and config.php

class Auth {

    /**
     * Checks if an admin is currently logged in and verifies their status.
     * If not, it redirects to the login page.
     */
    public static function checkAdmin() {

        // If not logged in via session, try to log in via "Remember Me" cookie
        if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
            self::loginFromCookie();
        }

        if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true || !isset($_SESSION['admin_id'])) {
            self::redirectToAdminLogin();
        }

        // Re-verify against the database on each request for enhanced security
        $conn = get_db_connection();

        $admin_id = $_SESSION['admin_id'];
        $sql = "SELECT role FROM users WHERE id = ? AND role = 'admin'";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $admin_id);
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows !== 1) {
                    // User is no longer an admin, log them out
                    self::logout();
                    self::redirectToAdminLogin();
                }
            } else {
                // SQL error, fail securely
                error_log("Auth::checkAdmin() - SQL execution error: " . $stmt->error);
                self::logout();
                self::redirectToAdminLogin();
            }
            $stmt->close();
        }
        // The connection will be closed by the script that included this service.
    }

    /**
     * Handles the admin login process.
     *
     * @param array $adminData The admin user data from the database.
     */
    public static function loginAdmin($adminData) {
        session_regenerate_id(true); // Regenerate session ID to prevent session fixation

        $_SESSION['admin_loggedin'] = true;
        $_SESSION['admin_id'] = $adminData['id'];
        $_SESSION['admin_name'] = $adminData['name'];
        $_SESSION['admin_role'] = $adminData['role']; // Store role for potential future use
    }

    /**
     * Creates a "Remember Me" token and cookie.
     *
     * @param int $user_id The admin's user ID.
     */
    public static function rememberAdmin($user_id) {
        $conn = get_db_connection();

        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $hashed_validator = hash('sha256', $validator);

        $expires = new DateTime('now');
        $expires->add(new DateInterval('P30D')); // Token expires in 30 days
        $expires_str = $expires->format('Y-m-d H:i:s');

        $sql = "INSERT INTO admin_auth_tokens (selector, hashed_validator, user_id, expires_at) VALUES (?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssis", $selector, $hashed_validator, $user_id, $expires_str);
            if ($stmt->execute()) {
                $cookie_value = $selector . ':' . $validator;
                setcookie('remember_admin', $cookie_value, $expires->getTimestamp(), '/', '', true, true); // Secure, HttpOnly cookie
            }
            $stmt->close();
        }
    }

    /**
     * Clears the "Remember Me" token from the database and cookie.
     *
     * @param string $selector The token selector.
     */
    private static function clearToken($selector) {
        $conn = get_db_connection();

        // Clear cookie
        setcookie('remember_admin', '', time() - 3600, '/');

        // Clear token from database
        $sql = "DELETE FROM admin_auth_tokens WHERE selector = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $selector);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Logs out the user by destroying the session.
     */
    public static function logout() {
        // Clear "Remember Me" cookie and token if it exists
        if (isset($_COOKIE['remember_admin'])) {
            list($selector, ) = explode(':', $_COOKIE['remember_admin']);
            self::clearToken($selector);
        }

        // Unset all session variables
        $_SESSION = [];

        // Destroy the session
        session_destroy();
    }

    /**
     * Redirects to the admin login page.
     */
    private static function redirectToAdminLogin() {
        // Build an absolute path to the admin login page to avoid relative path issues.
        // Assumes the 'admin' folder is at the root of the site.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        header("Location: " . SITE_URL . "admin/login.php");
        exit();
    }

    /**
     * Attempts to log in a user from a "Remember Me" cookie.
     */
    private static function loginFromCookie() {
        if (empty($_COOKIE['remember_admin'])) {
            return;
        }

        list($selector, $validator) = explode(':', $_COOKIE['remember_admin']);

        if (empty($selector) || empty($validator)) {
            return;
        }

        $conn = get_db_connection();

        $sql = "SELECT * FROM admin_auth_tokens WHERE selector = ? AND expires_at >= NOW()";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $selector);
            $stmt->execute();
            $result = $stmt->get_result();
            $token_data = $result->fetch_assoc();
            $stmt->close();

            if ($token_data) {
                // Token found, verify the validator
                if (hash_equals($token_data['hashed_validator'], hash('sha256', $validator))) {
                    // Token is valid, fetch user data and log them in
                    $sql_user = "SELECT id, name, role FROM users WHERE id = ? AND role = 'admin'";
                    if ($stmt_user = $conn->prepare($sql_user)) {
                        $stmt_user->bind_param("i", $token_data['user_id']);
                        $stmt_user->execute();
                        $user_result = $stmt_user->get_result();
                        if ($user_data = $user_result->fetch_assoc()) {
                            self::loginAdmin($user_data);
                        }
                        $stmt_user->close();
                    }
                } else {
                    // Validator is incorrect, clear the token (potential theft attempt)
                    self::clearToken($selector);
                }
            }
        }
    }
}
?>
