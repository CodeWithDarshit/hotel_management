<?php
require_once 'config.php';

// Unset all session values
$_SESSION = array();

// Destroy session cookies if applicable
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to Homepage
header("Location: index.php");
exit;
?>
