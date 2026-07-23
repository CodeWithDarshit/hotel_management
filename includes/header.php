<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aura Heights Resort & Spa | Luxury Hotel</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Main Style CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <a href="index.php">AURA<span class="logo-gold">HEIGHTS</span></a>
        </div>
        <ul class="nav-links">
            <li><a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Home</a></li>
            <li><a href="rooms.php" class="<?php echo ($current_page == 'rooms.php' || $current_page == 'room-details.php') ? 'active' : ''; ?>">Rooms & Suites</a></li>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="profile.php" class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">My Bookings</a></li>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <li><a href="admin/index.php" class="nav-btn">Admin Dashboard</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Log Out</a></li>
            <?php else: ?>
                <li><a href="login.php" class="<?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">Log In</a></li>
                <li><a href="register.php" class="nav-btn <?php echo ($current_page == 'register.php') ? 'active' : ''; ?>">Book Now</a></li>
            <?php endif; ?>
        </ul>
    </nav>
