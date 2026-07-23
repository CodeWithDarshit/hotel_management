<?php
// Prevent direct access to config file if needed, but standard configuration works.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hotel_management');

try {
    // Connect to database (assuming database exists. setup.php will handle database creation)
    $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Select database if it exists (to prevent errors before setup.php is run)
    $stmt = $pdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    if ($stmt->rowCount() > 0) {
        $pdo->query("USE `" . DB_NAME . "`");
    }
} catch (PDOException $e) {
    // If the database connection fails, output a clean message
    die("Database Connection Error: " . $e->getMessage());
}

// Global Helper Functions
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: ../login.php");
        exit;
    }
}
?>
