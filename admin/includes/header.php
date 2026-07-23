<?php
require_once '../config.php';
requireAdmin();

$current_page = basename($_SERVER['PHP_SELF']);

// Get initials for admin badge
$words = explode(" ", $_SESSION['user_name']);
$initials = "";
foreach ($words as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
$initials = substr($initials, 0, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aura Heights | Manager Console</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Style CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar">
        <div class="admin-logo">
            Aura Heights <span style="font-size: 0.8rem; display: block; color: var(--text-secondary); margin-top: 0.3rem;">Manager Panel</span>
        </div>
        
        <ul class="admin-nav">
            <li class="admin-nav-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <a href="index.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Dashboard Overview
                </a>
            </li>
            <li class="admin-nav-item <?php echo ($current_page == 'bookings.php') ? 'active' : ''; ?>">
                <a href="bookings.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    Manage Bookings
                </a>
            </li>
            <li class="admin-nav-item <?php echo ($current_page == 'rooms.php') ? 'active' : ''; ?>">
                <a href="rooms.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    Room Inventory
                </a>
            </li>
            <li class="admin-nav-item <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
                <a href="users.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Registered Guests
                </a>
            </li>
        </ul>
        
        <div class="admin-sidebar-footer">
            <a href="../index.php" class="admin-nav-item" style="display: block; margin-bottom: 0.5rem; text-align: center; color: var(--primary-admin); font-size: 0.85rem;">← Visit Guest Site</a>
            <form action="../logout.php" method="POST">
                <button type="submit" class="admin-logout-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Sign Out
                </button>
            </form>
        </div>
    </aside>
    
    <!-- Main Workspace Section -->
    <main class="admin-content">
        <header class="admin-header">
            <div class="admin-header-title">
                <h1><?php echo isset($page_title) ? htmlspecialchars($page_title) : "Manager Dashboard"; ?></h1>
                <p><?php echo isset($page_subtitle) ? htmlspecialchars($page_subtitle) : "Real-time analytics and controls"; ?></p>
            </div>
            
            <div class="admin-profile-badge">
                <div class="admin-avatar"><?php echo htmlspecialchars($initials); ?></div>
                <div>
                    <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong><br>
                    <small style="color: var(--text-admin-muted);">Administrator</small>
                </div>
            </div>
        </header>
