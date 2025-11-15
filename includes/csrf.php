<?php

function generate_csrf_token() {
    // Generate a new token if one doesn't exist.
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    // Verify the submitted token against the one in the session.
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }

    return true;
}

function regenerate_csrf_token() {
    // Force a new token to be generated on the next request.
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function generate_csrf_token_input() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
?>
