<?php
// Include config.php to get database details, but we handle DB creation separately here.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hotel_management');

$status_message = "";
$error_message = "";

if (isset($_POST['install'])) {
    try {
        // Connect to MySQL server (without specifying DB name)
        $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 1. Create Database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `" . DB_NAME . "`");
        
        // 2. Drop existing tables if they exist to start fresh
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `bookings`;");
        $pdo->exec("DROP TABLE IF EXISTS `rooms`;");
        $pdo->exec("DROP TABLE IF EXISTS `room_types`;");
        $pdo->exec("DROP TABLE IF EXISTS `users`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        
        // 3. Create Users Table
        $pdo->exec("CREATE TABLE `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `fullname` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `phone` VARCHAR(20) DEFAULT NULL,
            `role` ENUM('customer', 'admin') DEFAULT 'customer',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        // 4. Create Room Types Table
        $pdo->exec("CREATE TABLE `room_types` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(50) NOT NULL,
            `description` TEXT NOT NULL,
            `base_price` DECIMAL(10,2) NOT NULL,
            `max_occupancy` INT NOT NULL,
            `amenities` TEXT NOT NULL, -- Stored as JSON string
            `image_url` VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        // 5. Create Rooms Table
        $pdo->exec("CREATE TABLE `rooms` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `room_number` VARCHAR(10) NOT NULL UNIQUE,
            `room_type_id` INT NOT NULL,
            `status` ENUM('available', 'booked', 'maintenance') DEFAULT 'available',
            FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        // 6. Create Bookings Table
        $pdo->exec("CREATE TABLE `bookings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `room_id` INT NOT NULL,
            `check_in` DATE NOT NULL,
            `check_out` DATE NOT NULL,
            `total_price` DECIMAL(10,2) NOT NULL,
            `status` ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled') DEFAULT 'pending',
            `payment_status` ENUM('unpaid', 'paid') DEFAULT 'unpaid',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        // 7. Seed Initial Data
        
        // Users: 1 Admin and 2 Customers
        $admin_pwd = password_hash('admin123', PASSWORD_DEFAULT);
        $cust_pwd1 = password_hash('customer123', PASSWORD_DEFAULT);
        $cust_pwd2 = password_hash('john123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO `users` (`fullname`, `email`, `password`, `phone`, `role`) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['System Administrator', 'admin@hotel.com', $admin_pwd, '+1234567890', 'admin']);
        $stmt->execute(['Jane Doe', 'jane@gmail.com', $cust_pwd1, '+1987654321', 'customer']);
        $stmt->execute(['John Smith', 'john@gmail.com', $cust_pwd2, '+15550199', 'customer']);
        
        // Room Types
        $room_types = [
            [
                'Single Cozy Room',
                'Perfect for solo travelers. A compact yet highly luxurious room featuring premium mattress, workspace, and a private elegant bathroom.',
                79.00,
                1,
                json_encode(['WiFi', 'Air Conditioning', 'Flat Screen TV', 'Desk', 'Coffee Maker']),
                'assets/images/single_room.png'
            ],
            [
                'Deluxe Double Room',
                'Ideal for couples or friends. Offers a spacious layout with a plush queen-sized double bed, stunning city views, mini bar, and modern amenities.',
                129.00,
                2,
                json_encode(['WiFi', 'Air Conditioning', 'Smart TV', 'Mini Bar', 'Safe Box', 'Balcony']),
                'assets/images/double_room.png'
            ],
            [
                'Executive Family Suite',
                'Designed for families or groups. Features two queen beds, a separate lounge area, luxurious bathroom with a deep tub, and high-end services.',
                249.00,
                4,
                json_encode(['WiFi', 'Air Conditioning', '2x Smart TV', 'Mini Bar', 'Bathtub', 'Lounge Area', 'Kitchenette']),
                'assets/images/suite_room.png'
            ],
            [
                'Presidential Gold Suite',
                'The ultimate luxury experience. A sprawling master bedroom, private dining, personal butler call button, jacuzzi, and sweeping panoramic views.',
                499.00,
                2,
                json_encode(['High Speed WiFi', 'Central AC', '65-inch OLED TV', 'Premium Mini Bar', 'Jacuzzi', 'Private Dining', 'Personal Butler', 'VIP Lounge Access']),
                'assets/images/presidential_room.png'
            ]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO `room_types` (`name`, `description`, `base_price`, `max_occupancy`, `amenities`, `image_url`) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($room_types as $rt) {
            $stmt->execute($rt);
        }
        
        // Rooms mapping (10 rooms)
        // 1 = Single Cozy (101, 102, 103)
        // 2 = Deluxe Double (201, 202, 203)
        // 3 = Executive Suite (301, 302)
        // 4 = Presidential Gold Suite (401, 402)
        $rooms = [
            ['101', 1, 'available'],
            ['102', 1, 'available'],
            ['103', 1, 'maintenance'],
            ['201', 2, 'booked'],
            ['202', 2, 'available'],
            ['203', 2, 'available'],
            ['301', 3, 'booked'],
            ['302', 3, 'available'],
            ['401', 4, 'available'],
            ['402', 4, 'booked']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO `rooms` (`room_number`, `room_type_id`, `status`) VALUES (?, ?, ?)");
        foreach ($rooms as $r) {
            $stmt->execute($r);
        }
        
        // Bookings
        // Booking 1: Jane Doe, Room 201 (Deluxe), Checked In, Paid
        // Booking 2: John Smith, Room 301 (Suite), Confirmed, Unpaid
        // Booking 3: John Smith, Room 402 (Presidential), Pending, Unpaid
        // Booking 4 (Past): Jane Doe, Room 101, Checked Out, Paid
        
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $day_after_tomorrow = date('Y-m-d', strtotime('+2 days'));
        $three_days_ago = date('Y-m-d', strtotime('-3 days'));
        $next_week = date('Y-m-d', strtotime('+7 days'));
        $next_week_end = date('Y-m-d', strtotime('+10 days'));
        
        $bookings = [
            [2, 4, $yesterday, $day_after_tomorrow, 387.00, 'checked_in', 'paid', '2026-07-11 14:00:00'],
            [3, 7, $today, $day_after_tomorrow, 498.00, 'confirmed', 'unpaid', '2026-07-12 09:30:00'],
            [3, 10, $next_week, $next_week_end, 1497.00, 'pending', 'unpaid', '2026-07-12 10:15:00'],
            [2, 1, $three_days_ago, $yesterday, 158.00, 'checked_out', 'paid', '2026-07-09 11:20:00']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO `bookings` (`user_id`, `room_id`, `check_in`, `check_out`, `total_price`, `status`, `payment_status`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($bookings as $b) {
            $stmt->execute($b);
        }
        
        $status_message = "Installation completed successfully! The database 'hotel_management' has been configured and seeded with demo data.";
    } catch (PDOException $e) {
        $error_message = "Database Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Management System - Database Installer</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0c0f17;
            --card-bg: #141a29;
            --primary: #c5a880;
            --text-color: #ffffff;
            --text-secondary: #8e9bb4;
            --border-color: rgba(197, 168, 128, 0.2);
            --success: #34d399;
            --error: #f87171;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .container {
            max-width: 500px;
            width: 100%;
            background: var(--card-bg);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            border: 1px solid var(--border-color);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(197, 168, 128, 0.05) 0%, transparent 60%);
            z-index: 1;
            pointer-events: none;
        }

        h1 {
            color: var(--primary);
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }

        p {
            color: var(--text-secondary);
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
        }

        .status {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: left;
            font-size: 15px;
            line-height: 1.4;
            position: relative;
            z-index: 2;
        }

        .status-success {
            background-color: rgba(52, 211, 153, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
        }

        .status-error {
            background-color: rgba(248, 113, 113, 0.1);
            border: 1px solid var(--error);
            color: var(--error);
        }

        .btn {
            background: linear-gradient(135deg, #c5a880 0%, #b39369 100%);
            color: #0c0f17;
            border: none;
            padding: 14px 28px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(197, 168, 128, 0.3);
            position: relative;
            z-index: 2;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(197, 168, 128, 0.5);
        }

        .btn:active {
            transform: translateY(1px);
        }

        .info-box {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            padding: 20px;
            text-align: left;
            margin-top: 25px;
            font-size: 14px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
            z-index: 2;
        }

        .info-box h3 {
            margin-top: 0;
            color: var(--primary);
            font-size: 16px;
        }

        .info-box ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
            color: var(--text-secondary);
        }

        .info-box li {
            margin-bottom: 8px;
        }

        .action-links {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            gap: 15px;
            position: relative;
            z-index: 2;
        }

        .action-links a {
            flex: 1;
            padding: 10px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-color);
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            transition: background 0.3s;
        }

        .action-links a:hover {
            background: rgba(197, 168, 128, 0.1);
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>System Installer</h1>
        <p>This script sets up the MySQL database, tables, and populates mock data for the Hotel Management System.</p>
        
        <?php if (!empty($status_message)): ?>
            <div class="status status-success">
                <strong>Success:</strong> <?php echo $status_message; ?>
            </div>
            
            <div class="info-box">
                <h3>Created Demo Credentials</h3>
                <ul>
                    <li><strong>Admin Account:</strong> admin@hotel.com (Password: <code>admin123</code>)</li>
                    <li><strong>Customer Account:</strong> jane@gmail.com (Password: <code>customer123</code>)</li>
                    <li><strong>Customer Account 2:</strong> john@gmail.com (Password: <code>john123</code>)</li>
                </ul>
            </div>
            
            <div class="action-links">
                <a href="index.php">Go to Homepage</a>
                <a href="admin/login.php">Admin Panel</a>
            </div>
        <?php elseif (!empty($error_message)): ?>
            <div class="status status-error">
                <strong>Error:</strong> <?php echo $error_message; ?>
            </div>
            <form method="post">
                <button type="submit" name="install" class="btn">Retry Installation</button>
            </form>
        <?php else: ?>
            <form method="post">
                <button type="submit" name="install" class="btn">Initialize Database</button>
            </form>
            <div class="info-box">
                <h3>Default DB Connection Settings</h3>
                <p style="margin-bottom: 0; font-size: 13px;">Host: <strong>localhost</strong><br>Username: <strong>root</strong><br>Password: <strong>(empty)</strong></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
