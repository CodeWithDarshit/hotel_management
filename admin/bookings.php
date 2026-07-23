<?php
$page_title = "Manage Reservations";
$page_subtitle = "Update registration statuses and record guest payments";

require_once 'includes/header.php';

$alert_msg = "";
$alert_type = "";

// 1. Handle State Transitions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $booking_id = (int)$_POST['booking_id'];
    $action = sanitizeInput($_POST['action']);
    
    try {
        $stmt = $pdo->prepare("SELECT b.*, r.room_number FROM bookings b JOIN rooms r ON b.room_id = r.id WHERE b.id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();
        
        if ($booking) {
            $room_id = $booking['room_id'];
            $room_num = $booking['room_number'];
            
            if ($action === 'confirm') {
                $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?")->execute([$booking_id]);
                $alert_msg = "Booking #$booking_id has been CONFIRMED.";
                $alert_type = "success";
            } elseif ($action === 'check_in') {
                // Set booking as checked_in
                $pdo->prepare("UPDATE bookings SET status = 'checked_in' WHERE id = ?")->execute([$booking_id]);
                // Mark physical room as booked
                $pdo->prepare("UPDATE rooms SET status = 'booked' WHERE id = ?")->execute([$room_id]);
                
                $alert_msg = "Guest checked in! Room $room_num status updated to OCCUPIED.";
                $alert_type = "success";
            } elseif ($action === 'check_out') {
                // Set booking as checked_out and mark as paid
                $pdo->prepare("UPDATE bookings SET status = 'checked_out', payment_status = 'paid' WHERE id = ?")->execute([$booking_id]);
                // Set room status back to available
                $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = ?")->execute([$room_id]);
                
                $alert_msg = "Guest checked out successfully! Invoice marked as PAID. Room $room_num is now AVAILABLE.";
                $alert_type = "success";
            } elseif ($action === 'cancel') {
                // Cancel booking
                $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$booking_id]);
                // Free up room
                $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = ?")->execute([$room_id]);
                
                $alert_msg = "Booking #$booking_id has been CANCELLED. Room $room_num released.";
                $alert_type = "success";
            }
        } else {
            $alert_msg = "Error: Booking record not found.";
            $alert_type = "danger";
        }
    } catch (PDOException $e) {
        $alert_msg = "Error modifying booking: " . $e->getMessage();
        $alert_type = "danger";
    }
}

// 2. Load Bookings with Optional Filter
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$bookings = [];

try {
    $sql = "SELECT b.id as booking_id, b.check_in, b.check_out, b.total_price, b.status as booking_status, b.payment_status, b.created_at,
                   u.fullname as customer_name, u.email as customer_email, u.phone as customer_phone, r.room_number, rt.name as room_type_name
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN rooms r ON b.room_id = r.id
            JOIN room_types rt ON r.room_type_id = rt.id";
            
    $params = [];
    if (!empty($status_filter)) {
        $sql .= " WHERE b.status = :status";
        $params[':status'] = $status_filter;
    }
    
    $sql .= " ORDER BY b.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $alert_msg = "Error querying reservations: " . $e->getMessage();
    $alert_type = "danger";
}
?>

<!-- Alert Container -->
<?php if (!empty($alert_msg)): ?>
    <div class="alert alert-<?php echo $alert_type; ?>" style="margin-bottom: 2rem;">
        <?php echo $alert_msg; ?>
    </div>
<?php endif; ?>

<!-- Filter Controls -->
<div class="admin-panel" style="margin-bottom: 2.5rem; padding: 1.2rem 2rem;">
    <form action="bookings.php" method="GET" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.5rem;">
        <h2 style="font-size: 1.1rem; margin: 0;">Filters</h2>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <select name="status" class="form-control" style="width: 200px; padding: 0.5rem; background: var(--bg-admin-main);">
                <option value="">All Bookings</option>
                <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Pending Approval</option>
                <option value="confirmed" <?php echo ($status_filter === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                <option value="checked_in" <?php echo ($status_filter === 'checked_in') ? 'selected' : ''; ?>>Checked In</option>
                <option value="checked_out" <?php echo ($status_filter === 'checked_out') ? 'selected' : ''; ?>>Checked Out</option>
                <option value="cancelled" <?php echo ($status_filter === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            <button type="submit" class="btn-admin-action" style="padding: 0.5rem 1.2rem;">Apply</button>
            <a href="bookings.php" class="btn-secondary" style="padding: 0.5rem 1rem; border-radius: 4px; font-size: 0.85rem;">Reset</a>
        </div>
    </form>
</div>

<!-- Bookings List Panel -->
<div class="admin-panel">
    <?php if (empty($bookings)): ?>
        <p style="color: var(--text-admin-muted); text-align: center; padding: 3rem 0;">No reservations match the search filters.</p>
    <?php else: ?>
        <div class="custom-table-container">
            <table class="custom-table" style="font-size: 0.9rem;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Guest Details</th>
                        <th>Room Assigned</th>
                        <th>Check In / Out</th>
                        <th>Billing</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Admin Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td>#<?php echo $b['booking_id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($b['customer_name']); ?></strong><br>
                                <small style="color: var(--text-admin-muted);"><?php echo htmlspecialchars($b['customer_email']); ?></small><br>
                                <small style="color: var(--text-admin-muted);"><?php echo htmlspecialchars($b['customer_phone']); ?></small>
                            </td>
                            <td>
                                <strong>Room <?php echo htmlspecialchars($b['room_number']); ?></strong><br>
                                <small style="color: var(--text-admin-muted);"><?php echo htmlspecialchars($b['room_type_name']); ?></small>
                            </td>
                            <td>
                                <span>Check-In: <strong><?php echo date('Y-m-d', strtotime($b['check_in'])); ?></strong></span><br>
                                <span>Check-Out: <strong><?php echo date('Y-m-d', strtotime($b['check_out'])); ?></strong></span>
                            </td>
                            <td>
                                <strong style="color: var(--primary-admin);"><?php echo formatCurrency($b['total_price']); ?></strong>
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
                                <div class="booking-actions">
                                    <?php if ($b['booking_status'] === 'pending'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['booking_id']; ?>">
                                            <button type="submit" name="action" value="confirm" class="action-btn confirm">Confirm</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['booking_id']; ?>">
                                            <button type="submit" name="action" value="cancel" class="action-btn cancel" onclick="return confirm('Cancel this reservation?');">Cancel</button>
                                        </form>
                                    <?php elseif ($b['booking_status'] === 'confirmed'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['booking_id']; ?>">
                                            <button type="submit" name="action" value="check_in" class="action-btn checkin">Check In</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['booking_id']; ?>">
                                            <button type="submit" name="action" value="cancel" class="action-btn cancel" onclick="return confirm('Cancel this reservation?');">Cancel</button>
                                        </form>
                                    <?php elseif ($b['booking_status'] === 'checked_in'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['booking_id']; ?>">
                                            <button type="submit" name="action" value="check_out" class="action-btn checkout" onclick="return confirm('Complete check-out and record payment?');">Check Out</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: var(--text-admin-muted); font-size: 0.8rem;">Archive</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
