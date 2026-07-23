<?php
require_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: rooms.php");
    exit;
}

try {
    // Fetch room type details
    $stmt = $pdo->prepare("SELECT * FROM room_types WHERE id = ?");
    $stmt->execute([$id]);
    $room_type = $stmt->fetch();
    
    if (!$room_type) {
        header("Location: rooms.php");
        exit;
    }
    
    $amenities = json_decode($room_type['amenities'], true) ?: [];

} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}

// Prefill dates from GET query if available
$check_in = isset($_GET['check_in']) ? sanitizeInput($_GET['check_in']) : '';
$check_out = isset($_GET['check_out']) ? sanitizeInput($_GET['check_out']) : '';
$guests = isset($_GET['guests']) ? (int)$_GET['guests'] : 1;

require_once 'includes/header.php';
?>

<div class="details-container">
    <!-- Left Panel: Room Specs -->
    <div>
        <div class="details-gallery">
            <img src="<?php echo htmlspecialchars($room_type['image_url']); ?>" alt="<?php echo htmlspecialchars($room_type['name']); ?>">
        </div>
        
        <div class="details-info">
            <h1><?php echo htmlspecialchars($room_type['name']); ?></h1>
            
            <div class="details-meta">
                <span>Category: <strong>Resort Suite</strong></span>
                <span>Max Guests: <strong><?php echo htmlspecialchars($room_type['max_occupancy']); ?> Guests</strong></span>
                <span>Room Service: <strong>24/7 Premium</strong></span>
            </div>
            
            <div class="details-description">
                <p><?php echo htmlspecialchars($room_type['description']); ?></p>
                <p style="margin-top: 1.5rem;">Every suite at Aura Heights is meticulously detailed with volcanic clay plaster walls, double-layered acoustic insulation, custom Italian furnishings, and state-of-the-art climate control technology. Our turn-down service includes local mountain herbal teas and a pillow selection menu for your absolute comfort.</p>
            </div>
            
            <h3 class="details-section-title">Amenities Included</h3>
            <div class="details-amenities-grid">
                <?php foreach ($amenities as $amenity): ?>
                    <div class="details-amenity-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span><?php echo htmlspecialchars($amenity); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3 class="details-section-title">Reservation Policies</h3>
            <div style="color: var(--text-secondary); font-size: 0.95rem;">
                <p style="margin-bottom: 0.8rem;">🕒 <strong>Check-in:</strong> From 3:00 PM onwards. Early check-in can be requested upon booking confirmation.</p>
                <p style="margin-bottom: 0.8rem;">🕒 <strong>Check-out:</strong> Before 11:00 AM. Late check-out requests are subject to room availability.</p>
                <p style="margin-bottom: 0.8rem;">💳 <strong>Cancellation:</strong> Free cancellation up to 48 hours before the arrival date. Late cancellations are subject to a 1-night charge.</p>
            </div>
        </div>
    </div>
    
    <!-- Right Panel: Sticky Booking card -->
    <div>
        <div class="booking-card">
            <div class="booking-card-price">
                <span id="base_price_val" data-price="<?php echo $room_type['base_price']; ?>">
                    <?php echo formatCurrency($room_type['base_price']); ?>
                </span>
                <span>/ Night</span>
            </div>
            
            <?php if (isset($_SESSION['booking_error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['booking_error']; 
                    unset($_SESSION['booking_error']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['booking_success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['booking_success']; 
                    unset($_SESSION['booking_success']);
                    ?>
                </div>
            <?php endif; ?>

            <form id="booking_form" action="book.php" method="POST">
                <input type="hidden" name="room_type_id" value="<?php echo $room_type['id']; ?>">
                <input type="hidden" id="total_price_input" name="total_price" value="">
                
                <div class="form-group">
                    <label for="check_in">Check-in Date</label>
                    <input type="date" id="check_in" name="check_in" class="form-control" value="<?php echo htmlspecialchars($check_in); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="check_out">Check-out Date</label>
                    <input type="date" id="check_out" name="check_out" class="form-control" value="<?php echo htmlspecialchars($check_out); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="guests">Guests Count</label>
                    <select id="guests" name="guests" class="form-control">
                        <?php for ($i = 1; $i <= (int)$room_type['max_occupancy']; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($guests === $i) ? 'selected' : ''; ?>><?php echo $i; ?> <?php echo ($i === 1) ? 'Guest' : 'Guests'; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <!-- Dynamic Cost Breakdowns (handled by main.js) -->
                <div class="booking-summary" id="booking_summary">
                    <div class="booking-summary-row">
                        <span>Stay Duration</span>
                        <strong id="sum_nights">0 nights</strong>
                    </div>
                    <div class="booking-summary-row">
                        <span>Room Cost Subtotal</span>
                        <strong id="sum_subtotal">$0.00</strong>
                    </div>
                    <div class="booking-summary-row">
                        <span>Taxes & Fees (12%)</span>
                        <strong id="sum_tax">$0.00</strong>
                    </div>
                    <div class="booking-summary-row total">
                        <span>Estimated Total</span>
                        <strong id="sum_total">$0.00</strong>
                    </div>
                </div>

                <?php if (isLoggedIn()): ?>
                    <button type="submit" class="btn-primary auth-btn">Confirm Reservation</button>
                <?php else: ?>
                    <a href="login.php?redirect=<?php echo urlencode('room-details.php?id=' . $room_type['id'] . '&check_in=' . $check_in . '&check_out=' . $check_out . '&guests=' . $guests); ?>" class="btn-primary auth-btn" style="text-align: center; display: block;">Log in to Book</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<!-- Trigger initial calculator update on load if dates are filled -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const checkInInput = document.getElementById('check_in');
    if (checkInInput && checkInInput.value) {
        // Dispatch event to recalculate price
        checkInInput.dispatchEvent(new Event('change'));
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
