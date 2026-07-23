<?php
$page_title = "Overview Dashboard";
$page_subtitle = "Live mountain resort metrics & occupancy status";

require_once 'includes/header.php';

// Initialize stats variables
$total_revenue = 0;
$active_bookings = 0;
$pending_bookings = 0;
$total_guests = 0;

$rooms_total = 0;
$rooms_available = 0;
$rooms_occupied = 0;
$rooms_maintenance = 0;

$recent_bookings = [];

try {
    // 1. Calculate Stats
    
    // Revenue (Sum of all paid or checked_out bookings)
    $stmt = $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status != 'cancelled' AND payment_status = 'paid'");
    $total_revenue = $stmt->fetchColumn() ?: 0;
    
    // Active Bookings (Checked In)
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'checked_in'");
    $active_bookings = $stmt->fetchColumn() ?: 0;
    
    // Pending Bookings
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
    $pending_bookings = $stmt->fetchColumn() ?: 0;
    
    // Total Guests (registered customers)
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
    $total_guests = $stmt->fetchColumn() ?: 0;
    
    // 2. Room Inventory Metrics
    $stmt = $pdo->query("SELECT COUNT(*) FROM rooms");
    $rooms_total = $stmt->fetchColumn() ?: 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'");
    $rooms_available = $stmt->fetchColumn() ?: 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'booked'");
    $rooms_occupied = $stmt->fetchColumn() ?: 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'maintenance'");
    $rooms_maintenance = $stmt->fetchColumn() ?: 0;
    
    // 3. Fetch Recent 5 Bookings
    $booking_sql = "SELECT b.id as booking_id, b.check_in, b.check_out, b.total_price, b.status as booking_status,
                           u.fullname as customer_name, u.email as customer_email, r.room_number, rt.name as room_type_name
                    FROM bookings b
                    JOIN users u ON b.user_id = u.id
                    JOIN rooms r ON b.room_id = r.id
                    JOIN room_types rt ON r.room_type_id = rt.id
                    ORDER BY b.created_at DESC
                    LIMIT 5";
    $recent_bookings = $pdo->query($booking_sql)->fetchAll();

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Data Fetching Error: " . $e->getMessage() . "</div>";
}

// Occupancy rates percentages
$occupancy_percentage = ($rooms_total > 0) ? round(($rooms_occupied / $rooms_total) * 100) : 0;
$available_percentage = ($rooms_total > 0) ? round(($rooms_available / $rooms_total) * 100) : 0;
$maintenance_percentage = ($rooms_total > 0) ? round(($rooms_maintenance / $rooms_total) * 100) : 0;
?>

<!-- Stats Cards -->
<section class="admin-stats-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-info">
            <h3>Total Revenue</h3>
            <p class="value gold"><?php echo formatCurrency($total_revenue); ?></p>
        </div>
        <div class="admin-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-info">
            <h3>Active Guests</h3>
            <p class="value"><?php echo $active_bookings; ?></p>
        </div>
        <div class="admin-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-info">
            <h3>Pending Approvals</h3>
            <p class="value" style="color: #f59e0b;"><?php echo $pending_bookings; ?></p>
        </div>
        <div class="admin-stat-icon" style="color: #f59e0b; background: rgba(245, 158, 11, 0.05); border-color: rgba(245, 158, 11, 0.15);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-info">
            <h3>Registered Guests</h3>
            <p class="value"><?php echo $total_guests; ?></p>
        </div>
        <div class="admin-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        </div>
    </div>
</section>

<!-- Dashboard Analytics & Quick list Grid -->
<div class="admin-dashboard-grid">
    <!-- Left panel: Recent Reservations -->
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Recent Reservation Requests</h2>
            <a href="bookings.php" class="admin-panel-link">View All Bookings →</a>
        </div>
        
        <?php if (empty($recent_bookings)): ?>
            <p style="color: var(--text-admin-muted); text-align: center; padding: 2rem 0;">No reservation records found.</p>
        <?php else: ?>
            <div class="custom-table-container">
                <table class="custom-table" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th>Guest / Email</th>
                            <th>Room / Type</th>
                            <th>Stay Period</th>
                            <th>Billing</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $rb): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($rb['customer_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($rb['customer_email']); ?></small>
                                </td>
                                <td>
                                    <strong>Room <?php echo htmlspecialchars($rb['room_number']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($rb['room_type_name']); ?></small>
                                </td>
                                <td>
                                    <span><?php echo date('M d', strtotime($rb['check_in'])); ?> - <?php echo date('M d', strtotime($rb['check_out'])); ?></span>
                                </td>
                                <td>
                                    <strong style="color: var(--primary-admin);"><?php echo formatCurrency($rb['total_price']); ?></strong>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $rb['booking_status']; ?>" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">
                                        <?php echo htmlspecialchars($rb['booking_status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Right Panel: Occupancy breakdown charts -->
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Room Inventory Status</h2>
            <a href="rooms.php" class="admin-panel-link">Configure Rooms</a>
        </div>
        
        <div class="chart-container">
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="font-size: 2.5rem; font-weight: 700; color: var(--primary-admin);"><?php echo $occupancy_percentage; ?>%</div>
                <div style="font-size: 0.85rem; color: var(--text-admin-muted); text-transform: uppercase; letter-spacing: 1px;">Live Room Occupancy</div>
            </div>
            
            <div class="chart-bar-list">
                <!-- Booked rooms bar -->
                <div class="chart-bar-item">
                    <div class="chart-bar-label">
                        <span>Booked / Occupied (<?php echo $rooms_occupied; ?>/<?php echo $rooms_total; ?>)</span>
                        <strong><?php echo $occupancy_percentage; ?>%</strong>
                    </div>
                    <div class="chart-bar-bg">
                        <div class="chart-bar-fill" style="width: <?php echo $occupancy_percentage; ?>%;"></div>
                    </div>
                </div>
                
                <!-- Available rooms bar -->
                <div class="chart-bar-item">
                    <div class="chart-bar-label">
                        <span>Available Clean Rooms (<?php echo $rooms_available; ?>/<?php echo $rooms_total; ?>)</span>
                        <strong><?php echo $available_percentage; ?>%</strong>
                    </div>
                    <div class="chart-bar-bg">
                        <div class="chart-bar-fill" style="width: <?php echo $available_percentage; ?>%; background: linear-gradient(90deg, #10b981, #34d399);"></div>
                    </div>
                </div>
                
                <!-- Maintenance rooms bar -->
                <div class="chart-bar-item">
                    <div class="chart-bar-label">
                        <span>Out of Order / Maintenance (<?php echo $rooms_maintenance; ?>/<?php echo $rooms_total; ?>)</span>
                        <strong><?php echo $maintenance_percentage; ?>%</strong>
                    </div>
                    <div class="chart-bar-bg">
                        <div class="chart-bar-fill" style="width: <?php echo $maintenance_percentage; ?>%; background: linear-gradient(90deg, #ef4444, #f87171);"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
