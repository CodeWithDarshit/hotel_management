<?php
$page_title = "Room Inventory";
$page_subtitle = "Add physical rooms, adjust categories, and trigger maintenance modes";

require_once 'includes/header.php';

$alert_msg = "";
$alert_type = "";

// 1. Handle Room Add POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
    $room_number = sanitizeInput($_POST['room_number']);
    $room_type_id = (int)$_POST['room_type_id'];
    $status = sanitizeInput($_POST['status']);
    
    if (empty($room_number) || $room_type_id <= 0) {
        $alert_msg = "Error: Please fill in all room details.";
        $alert_type = "danger";
    } else {
        try {
            // Check if room number already exists
            $stmt = $pdo->prepare("SELECT id FROM rooms WHERE room_number = ?");
            $stmt->execute([$room_number]);
            if ($stmt->rowCount() > 0) {
                $alert_msg = "Error: Room number $room_number already exists.";
                $alert_type = "danger";
            } else {
                $ins = $pdo->prepare("INSERT INTO rooms (room_number, room_type_id, status) VALUES (?, ?, ?)");
                $ins->execute([$room_number, $room_type_id, $status]);
                $alert_msg = "Success: Room $room_number added to inventory.";
                $alert_type = "success";
            }
        } catch (PDOException $e) {
            $alert_msg = "Database Error: " . $e->getMessage();
            $alert_type = "danger";
        }
    }
}

// 2. Handle Room Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $room_id = (int)$_POST['room_id'];
    $new_status = sanitizeInput($_POST['status']);
    
    try {
        $up = $pdo->prepare("UPDATE rooms SET status = ? WHERE id = ?");
        $up->execute([$new_status, $room_id]);
        $alert_msg = "Success: Room status updated.";
        $alert_type = "success";
    } catch (PDOException $e) {
        $alert_msg = "Error: " . $e->getMessage();
        $alert_type = "danger";
    }
}

// 3. Handle Room Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_room'])) {
    $room_id = (int)$_POST['room_id'];
    
    try {
        $del = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
        $del->execute([$room_id]);
        $alert_msg = "Success: Room removed from inventory.";
        $alert_type = "success";
    } catch (PDOException $e) {
        $alert_msg = "Error removing room: " . $e->getMessage();
        $alert_type = "danger";
    }
}

// 4. Load Room Inventory Data
$rooms = [];
$room_types = [];
try {
    $rooms = $pdo->query("SELECT r.id as room_id, r.room_number, r.status as room_status, rt.name as room_type_name, rt.base_price 
                          FROM rooms r 
                          JOIN room_types rt ON r.room_type_id = rt.id 
                          ORDER BY r.room_number ASC")->fetchAll();
                          
    $room_types = $pdo->query("SELECT id, name FROM room_types")->fetchAll();
} catch (PDOException $e) {
    $alert_msg = "Error fetching data: " . $e->getMessage();
    $alert_type = "danger";
}
?>

<!-- Alerts -->
<?php if (!empty($alert_msg)): ?>
    <div class="alert alert-<?php echo $alert_type; ?>" style="margin-bottom: 2rem;">
        <?php echo $alert_msg; ?>
    </div>
<?php endif; ?>

<!-- Header Actions -->
<div style="display: flex; justify-content: flex-end; margin-bottom: 2rem;">
    <button class="btn-admin-action" onclick="openAdminModal('add_room_modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Add Room
    </button>
</div>

<!-- Add Room Modal Drawer -->
<div id="add_room_modal" class="admin-modal">
    <div class="admin-modal-content">
        <span class="admin-modal-close" onclick="closeAdminModal('add_room_modal')">&times;</span>
        <h2>Add Physical Room</h2>
        <form action="rooms.php" method="POST">
            <div class="form-group">
                <label for="room_number">Room Number *</label>
                <input type="text" id="room_number" name="room_number" class="form-control" placeholder="e.g. 204" required>
            </div>
            
            <div class="form-group">
                <label for="room_type_id">Room Category *</label>
                <select id="room_type_id" name="room_type_id" class="form-control" style="background: var(--bg-admin-main);" required>
                    <option value="">Select Category...</option>
                    <?php foreach ($room_types as $rt): ?>
                        <option value="<?php echo $rt['id']; ?>"><?php echo htmlspecialchars($rt['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="status">Initial Status</label>
                <select id="status" name="status" class="form-control" style="background: var(--bg-admin-main);">
                    <option value="available">Available (Cleaned)</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="booked">Booked (Occupied)</option>
                </select>
            </div>
            
            <button type="submit" name="add_room" class="btn-primary auth-btn">Register Room</button>
        </form>
    </div>
</div>

<!-- Rooms List Table -->
<div class="admin-panel">
    <?php if (empty($rooms)): ?>
        <p style="color: var(--text-admin-muted); text-align: center; padding: 3rem 0;">No physical rooms registered in the system database.</p>
    <?php else: ?>
        <div class="custom-table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Room Number</th>
                        <th>Category Type</th>
                        <th>Base Cost / Night</th>
                        <th>Status</th>
                        <th>Status Control</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $r): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--text-admin-main); font-size: 1.1rem;">Room <?php echo htmlspecialchars($r['room_number']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($r['room_type_name']); ?></td>
                            <td><strong style="color: var(--primary-admin);"><?php echo formatCurrency($r['base_price']); ?></strong></td>
                            <td>
                                <?php 
                                $badge_class = $r['room_status'];
                                if ($r['room_status'] === 'available') $badge_class = 'checked_in'; // matching styles colors
                                if ($r['room_status'] === 'maintenance') $badge_class = 'cancelled';
                                ?>
                                <span class="status-badge <?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars($r['room_status']); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display: flex; gap: 0.5rem; align-items: center;">
                                    <input type="hidden" name="room_id" value="<?php echo $r['room_id']; ?>">
                                    <select name="status" class="form-control" style="padding: 0.3rem 0.5rem; font-size: 0.8rem; width: 130px; background: var(--bg-admin-main);">
                                        <option value="available" <?php echo ($r['room_status'] === 'available') ? 'selected' : ''; ?>>Available</option>
                                        <option value="booked" <?php echo ($r['room_status'] === 'booked') ? 'selected' : ''; ?>>Booked</option>
                                        <option value="maintenance" <?php echo ($r['room_status'] === 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                    </select>
                                    <button type="submit" name="update_status" class="action-btn edit" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">Set</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete Room <?php echo $r['room_number']; ?>?');">
                                    <input type="hidden" name="room_id" value="<?php echo $r['room_id']; ?>">
                                    <button type="submit" name="delete_room" class="action-btn cancel" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Dialog Script Inclusions -->
<script src="../assets/js/main.js"></script>

<?php require_once 'includes/footer.php'; ?>
