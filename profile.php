<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$alert_msg = "";
$alert_type = "";

// 1. Handle Booking Cancellation securely
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    try {
        // Fetch booking to verify owner and cancellation capability
        $verify_stmt = $pdo->prepare("SELECT user_id, status FROM bookings WHERE id = ?");
        $verify_stmt->execute([$booking_id]);
        $booking = $verify_stmt->fetch();
        
        if ($booking) {
            if ((int)$booking['user_id'] !== (int)$user_id) {
                $alert_msg = "Error: Unauthorized action.";
                $alert_type = "danger";
            } elseif (!in_array($booking['status'], ['pending', 'confirmed'])) {
                $alert_msg = "Error: This reservation cannot be cancelled anymore as it is already " . strtoupper($booking['status']) . ".";
                $alert_type = "danger";
            } else {
                // Cancel booking
                $update_stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
                $update_stmt->execute([$booking_id]);
                
                $alert_msg = "Success: Your reservation has been cancelled successfully.";
                $alert_type = "success";
            }
        } else {
            $alert_msg = "Error: Reservation details not found.";
            $alert_type = "danger";
        }
    } catch (PDOException $e) {
        $alert_msg = "Error processing cancellation: " . $e->getMessage();
        $alert_type = "danger";
    }
}

// 2. Fetch User Bookings
$bookings = [];
try {
    $sql = "SELECT b.id as booking_id, b.check_in, b.check_out, b.total_price, b.status as booking_status, b.payment_status, 
                   r.room_number, rt.name as room_type_name, rt.image_url
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN room_types rt ON r.room_type_id = rt.id
            WHERE b.user_id = :user_id
            ORDER BY b.created_at DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $alert_msg = "Error loading your reservations: " . $e->getMessage();
    $alert_type = "danger";
}

// Fetch user metadata
$user_meta = [];
try {
    $stmt = $pdo->prepare("SELECT fullname, email, phone, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_meta = $stmt->fetch();
} catch (PDOException $e) {}

require_once 'includes/header.php';

// Get initials for profile badge
$words = explode(" ", $_SESSION['user_name']);
$initials = "";
foreach ($words as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
$initials = substr($initials, 0, 2);
?>

<div class="profile-container">
    <!-- Sidebar -->
    <div class="profile-sidebar">
        <div class="profile-user-card">
            <div class="profile-avatar"><?php echo htmlspecialchars($initials); ?></div>
            <h3><?php echo htmlspecialchars($_SESSION['user_name']); ?></h3>
            <p><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
        </div>
        
        <ul class="profile-nav-list">
            <li class="profile-nav-item active"><a href="profile.php">My Bookings</a></li>
            <li class="profile-nav-item"><a href="rooms.php">Book New Suite</a></li>
            <li class="profile-nav-item"><a href="logout.php">Sign Out</a></li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="profile-content">
        <h2>My Luxury Reservations</h2>
        
        <?php if (!empty($alert_msg)): ?>
            <div class="alert alert-<?php echo $alert_type; ?>">
                <?php echo $alert_msg; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($bookings)): ?>
            <div style="text-align: center; padding: 3rem 0;">
                <div style="font-size: 3.5rem; margin-bottom: 1rem;">🛎️</div>
                <h3>No Bookings Found</h3>
                <p style="color: var(--text-secondary); margin-top: 0.5rem; margin-bottom: 2rem;">You haven't made any luxury reservations yet.</p>
                <a href="rooms.php" class="btn-primary">Browse Suites & Rooms</a>
            </div>
        <?php else: ?>
            <div class="custom-table-container">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Suite / Room</th>
                            <th>Stay Period</th>
                            <th>Billing Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <img src="<?php echo htmlspecialchars($b['image_url']); ?>" alt="Suite" style="width: 50px; height: 35px; object-fit: cover; border-radius: 4px; border: 1px solid rgba(212, 175, 55, 0.15);">
                                        <div>
                                            <strong style="color: var(--text-primary);"><?php echo htmlspecialchars($b['room_type_name']); ?></strong><br>
                                            <small style="color: var(--text-secondary);">Room: <?php echo htmlspecialchars($b['room_number']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span><?php echo date('M d, Y', strtotime($b['check_in'])); ?></span><br>
                                    <small style="color: var(--text-secondary);">to <?php echo date('M d, Y', strtotime($b['check_out'])); ?></small>
                                </td>
                                <td>
                                    <strong style="color: var(--primary);"><?php echo formatCurrency($b['total_price']); ?></strong>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $b['payment_status']; ?>">
                                        <?php echo htmlspecialchars($b['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $b['booking_status']; ?>">
                                        <?php echo str_replace('_', ' ', htmlspecialchars($b['booking_status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (in_array($b['booking_status'], ['pending', 'confirmed'])): ?>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this reservation?');">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['booking_id']; ?>">
                                            <button type="submit" name="cancel_booking" class="action-btn cancel" style="padding: 0.35rem 0.7rem; font-size: 0.75rem;">
                                                Cancel
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: var(--text-secondary); font-size: 0.8rem;">None</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
