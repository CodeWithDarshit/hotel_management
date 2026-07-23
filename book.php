<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: rooms.php");
    exit;
}

$room_type_id = isset($_POST['room_type_id']) ? (int)$_POST['room_type_id'] : 0;
$check_in = isset($_POST['check_in']) ? sanitizeInput($_POST['check_in']) : '';
$check_out = isset($_POST['check_out']) ? sanitizeInput($_POST['check_out']) : '';

if ($room_type_id <= 0 || empty($check_in) || empty($check_out)) {
    $_SESSION['booking_error'] = "Missing or invalid reservation dates.";
    header("Location: room-details.php?id=" . $room_type_id);
    exit;
}

// Date validation
$today = date('Y-m-d');
if ($check_in < $today) {
    $_SESSION['booking_error'] = "Check-in date cannot be in the past.";
    header("Location: room-details.php?id=" . $room_type_id);
    exit;
}

if ($check_out <= $check_in) {
    $_SESSION['booking_error'] = "Check-out date must be after the check-in date.";
    header("Location: room-details.php?id=" . $room_type_id);
    exit;
}

try {
    // 1. Fetch Room Type Base Price for validation and calculations
    $stmt = $pdo->prepare("SELECT base_price FROM room_types WHERE id = ?");
    $stmt->execute([$room_type_id]);
    $room_type = $stmt->fetch();
    
    if (!$room_type) {
        $_SESSION['booking_error'] = "Invalid room category selected.";
        header("Location: rooms.php");
        exit;
    }
    
    // Calculate accurate backend price
    $date1 = new DateTime($check_in);
    $date2 = new DateTime($check_out);
    $nights = $date2->diff($date1)->days;
    
    if ($nights <= 0) {
        $_SESSION['booking_error'] = "Invalid stay duration.";
        header("Location: room-details.php?id=" . $room_type_id);
        exit;
    }
    
    $subtotal = $room_type['base_price'] * $nights;
    $tax = $subtotal * 0.12; // 12% tax
    $backend_total_price = $subtotal + $tax;

    // 2. Query to find an available physical room of this type
    $room_sql = "SELECT id, room_number 
                 FROM rooms 
                 WHERE room_type_id = :type_id 
                 AND status != 'maintenance' 
                 AND id NOT IN (
                     SELECT DISTINCT room_id 
                     FROM bookings 
                     WHERE status NOT IN ('cancelled', 'checked_out') 
                     AND check_in < :checkout 
                     AND check_out > :checkin
                 ) 
                 LIMIT 1";
                 
    $room_stmt = $pdo->prepare($room_sql);
    $room_stmt->execute([
        ':type_id' => $room_type_id,
        ':checkin' => $check_in,
        ':checkout' => $check_out
    ]);
    $available_room = $room_stmt->fetch();
    
    if (!$available_room) {
        $_SESSION['booking_error'] = "We apologize, but all rooms in this category are occupied during your selected dates.";
        header("Location: room-details.php?id=" . $room_type_id . "&check_in=" . $check_in . "&check_out=" . $check_out);
        exit;
    }
    
    $assigned_room_id = $available_room['id'];
    $user_id = $_SESSION['user_id'];
    
    // 3. Create the Booking Record
    $booking_sql = "INSERT INTO bookings (user_id, room_id, check_in, check_out, total_price, status, payment_status) 
                    VALUES (:user_id, :room_id, :check_in, :check_out, :total_price, 'pending', 'unpaid')";
                    
    $booking_stmt = $pdo->prepare($booking_sql);
    $booking_stmt->execute([
        ':user_id' => $user_id,
        ':room_id' => $assigned_room_id,
        ':check_in' => $check_in,
        ':check_out' => $check_out,
        ':total_price' => $backend_total_price
    ]);
    
    // Optional: mark physical room status as 'booked' or let it be handled dynamically.
    // In our logic, room availability is dynamic based on bookings, so we don't strictly need to alter rooms.status unless we want to.
    
    $_SESSION['booking_success'] = "Thank you! Your luxury booking request has been submitted. Status: PENDING admin confirmation.";
    header("Location: profile.php");
    exit;

} catch (PDOException $e) {
    $_SESSION['booking_error'] = "A database error occurred: " . $e->getMessage();
    header("Location: room-details.php?id=" . $room_type_id);
    exit;
}
?>
