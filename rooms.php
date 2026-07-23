<?php
require_once 'config.php';

// Check database initialization
$db_initialized = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'room_types'");
    $db_initialized = ($stmt->rowCount() > 0);
} catch (PDOException $e) {}

$rooms = [];
$check_in = isset($_GET['check_in']) ? sanitizeInput($_GET['check_in']) : '';
$check_out = isset($_GET['check_out']) ? sanitizeInput($_GET['check_out']) : '';
$guests = isset($_GET['guests']) ? (int)$_GET['guests'] : 0;
$type_filter = isset($_GET['type']) ? (int)$_GET['type'] : 0;

$all_types = [];

if ($db_initialized) {
    try {
        // Fetch all categories for filter options
        $all_types = $pdo->query("SELECT id, name FROM room_types")->fetchAll();

        // Build query to fetch Room Types with dynamic availability checks
        $sql = "SELECT rt.*, 
                (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.id AND r.status != 'maintenance') as total_rooms
                FROM room_types rt WHERE 1=1";
        
        $params = [];

        // Filter by occupancy
        if ($guests > 0) {
            $sql .= " AND rt.max_occupancy >= :guests";
            $params[':guests'] = $guests;
        }

        // Filter by Room Type ID
        if ($type_filter > 0) {
            $sql .= " AND rt.id = :type_id";
            $params[':type_id'] = $type_filter;
        }

        // Filter by date overlap if dates are selected
        if (!empty($check_in) && !empty($check_out)) {
            // Find room types that have AT LEAST ONE physical room not booked during this time frame
            $sql .= " AND rt.id IN (
                SELECT DISTINCT r.room_type_id 
                FROM rooms r 
                WHERE r.status != 'maintenance' 
                AND r.id NOT IN (
                    SELECT DISTINCT b.room_id 
                    FROM bookings b 
                    WHERE b.status NOT IN ('cancelled', 'checked_out') 
                    AND b.check_in < :checkout 
                    AND b.check_out > :checkin
                )
            )";
            $params[':checkin'] = $check_in;
            $params[':checkout'] = $check_out;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rooms = $stmt->fetchAll();

    } catch (PDOException $e) {
        $error = "Error querying room data: " . $e->getMessage();
    }
}

require_once 'includes/header.php';
?>

<div class="section-header" style="margin-top: 4rem; margin-bottom: 2rem;">
    <h4>Aura Heights Accommodations</h4>
    <h2>Rooms, Suites & Villas</h2>
    <p style="color: var(--text-secondary); max-width: 600px; margin: 1rem auto 0 auto;">Select from our handcrafted luxury suites. Input check-in and check-out dates to view accurate real-time room availability and pricing.</p>
</div>

<!-- Filters Panel -->
<div style="max-width: 1200px; margin: 0 auto 3rem auto; padding: 0 2rem;">
    <form action="rooms.php" method="GET" style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 8px; padding: 1.5rem; display: flex; flex-wrap: wrap; gap: 1.5rem; justify-content: space-between; align-items: flex-end;">
        <div style="flex: 1; min-width: 180px;" class="search-field">
            <label for="check_in">Check-in</label>
            <input type="date" id="check_in" name="check_in" value="<?php echo htmlspecialchars($check_in); ?>">
        </div>
        
        <div style="flex: 1; min-width: 180px;" class="search-field">
            <label for="check_out">Check-out</label>
            <input type="date" id="check_out" name="check_out" value="<?php echo htmlspecialchars($check_out); ?>">
        </div>
        
        <div style="flex: 1; min-width: 120px;" class="search-field">
            <label for="guests">Guests</label>
            <select id="guests" name="guests">
                <option value="0">Any Guests</option>
                <option value="1" <?php echo ($guests === 1) ? 'selected' : ''; ?>>1 Guest</option>
                <option value="2" <?php echo ($guests === 2) ? 'selected' : ''; ?>>2 Guests</option>
                <option value="3" <?php echo ($guests === 3) ? 'selected' : ''; ?>>3 Guests</option>
                <option value="4" <?php echo ($guests === 4) ? 'selected' : ''; ?>>4+ Guests</option>
            </select>
        </div>

        <div style="flex: 1; min-width: 180px;" class="search-field">
            <label for="type">Suite Category</label>
            <select id="type" name="type">
                <option value="0">All Suites</option>
                <?php foreach ($all_types as $t): ?>
                    <option value="<?php echo $t['id']; ?>" <?php echo ($type_filter === (int)$t['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div style="display: flex; gap: 0.8rem; min-width: 200px;">
            <button type="submit" class="btn-primary" style="flex: 1; padding: 0.8rem;">Apply Filter</button>
            <a href="rooms.php" class="btn-secondary" style="padding: 0.8rem 1.2rem; text-align: center; font-size: 0.9rem;">Reset</a>
        </div>
    </form>
</div>

<!-- Rooms List Grid -->
<div class="section" style="padding-top: 0;">
    <?php if (empty($rooms)): ?>
        <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 8px; padding: 4rem 2rem; text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🛌</div>
            <h3>No Luxury Rooms Available</h3>
            <p style="color: var(--text-secondary); margin-top: 0.5rem;">Try adjusting your check-in dates or decreasing the guest count parameters.</p>
        </div>
    <?php else: ?>
        <div class="room-grid">
            <?php foreach ($rooms as $r): 
                $amenities = json_decode($r['amenities'], true) ?: [];
                
                // Construct details page parameters to forward availability check
                $details_url = "room-details.php?id=" . $r['id'];
                if (!empty($check_in) && !empty($check_out)) {
                    $details_url .= "&check_in=" . urlencode($check_in) . "&check_out=" . urlencode($check_out) . "&guests=" . $guests;
                }
            ?>
                <div class="room-card">
                    <div class="room-img-container">
                        <img src="<?php echo htmlspecialchars($r['image_url']); ?>" alt="<?php echo htmlspecialchars($r['name']); ?>">
                        <span class="room-badge">Max Occupancy: <?php echo htmlspecialchars($r['max_occupancy']); ?> Guests</span>
                    </div>
                    <div class="room-content">
                        <h3><?php echo htmlspecialchars($r['name']); ?></h3>
                        <p><?php echo htmlspecialchars($r['description']); ?></p>
                        
                        <div class="room-amenities">
                            <?php foreach ($amenities as $amenity): ?>
                                <span class="amenity-tag"><?php echo htmlspecialchars($amenity); ?></span>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="room-footer">
                            <div class="room-price">
                                <?php echo formatCurrency($r['base_price']); ?> <span>/ Night</span>
                            </div>
                            <a href="<?php echo $details_url; ?>" class="btn-primary" style="padding: 0.6rem 1.2rem; font-size: 0.85rem; border-radius: 4px; box-shadow: none;">Book Now</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
