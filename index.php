<?php
require_once 'config.php';

// Check if database has room types to verify if setup was run
$db_initialized = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'room_types'");
    if ($stmt->rowCount() > 0) {
        $stmt_count = $pdo->query("SELECT COUNT(*) FROM room_types");
        if ($stmt_count->fetchColumn() > 0) {
            $db_initialized = true;
        }
    }
} catch (PDOException $e) {
    // Keep it false
}

// Fetch Room Types if DB is initialized
$room_types = [];
if ($db_initialized) {
    try {
        $stmt = $pdo->query("SELECT * FROM room_types ORDER BY base_price ASC");
        $room_types = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Handle silently
    }
}

require_once 'includes/header.php';
?>

<!-- Hero Banner -->
<section class="hero">
    <img src="assets/images/hero_bg.png" alt="Aura Heights Luxury Resort" class="hero-video-fallback">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h3>Welcome to Aura Heights Resort & Spa</h3>
        <h1>Unparalleled Luxury At New Altitudes</h1>
        <p>A sanctuary of serenity designed for those who appreciate the finer details of life. Explore our curated rooms, world-class amenities, and exquisite mountain views.</p>
        <a href="rooms.php" class="btn-primary">Explore Our Suites</a>
    </div>
</section>

<!-- Database Setup Banner for new users -->
<?php if (!$db_initialized): ?>
<div style="background: rgba(197, 168, 128, 0.1); border: 1px solid var(--primary); max-width: 1000px; margin: 2rem auto; padding: 2rem; border-radius: 8px; text-align: center; box-shadow: var(--shadow-md);">
    <h3 style="color: var(--primary); margin-bottom: 0.5rem; font-size: 1.4rem;">⚙️ Setup Required</h3>
    <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">It looks like the system database and mock tables have not been initialized yet.</p>
    <a href="setup.php" class="btn-primary" style="padding: 0.8rem 2rem;">Run Database Installer</a>
</div>
<?php endif; ?>

<!-- Booking Check Search Bar -->
<div class="search-bar-container">
    <form action="rooms.php" method="GET" class="search-bar">
        <div class="search-field">
            <label for="check_in">Check-In</label>
            <input type="date" id="check_in" name="check_in" required>
        </div>
        <div class="search-field">
            <label for="check_out">Check-Out</label>
            <input type="date" id="check_out" name="check_out" required>
        </div>
        <div class="search-field">
            <label for="guests">Guests</label>
            <select id="guests" name="guests">
                <option value="1">1 Guest</option>
                <option value="2" selected>2 Guests</option>
                <option value="3">3 Guests</option>
                <option value="4">4 Guests</option>
            </select>
        </div>
        <button type="submit" class="btn-primary" style="width: 100%; border-radius: 4px; padding: 0.85rem;">Check Availability</button>
    </form>
</div>

<!-- Room Categories Showcase -->
<?php if ($db_initialized): ?>
<section class="section">
    <div class="section-header">
        <h4>Accommodations</h4>
        <h2>Suites & Sanctuary Rooms</h2>
    </div>
    
    <div class="room-grid">
        <?php foreach ($room_types as $rt): 
            $amenities = json_decode($rt['amenities'], true) ?: [];
        ?>
            <div class="room-card">
                <div class="room-img-container">
                    <img src="<?php echo htmlspecialchars($rt['image_url']); ?>" alt="<?php echo htmlspecialchars($rt['name']); ?>">
                    <span class="room-badge">Max Occupancy: <?php echo htmlspecialchars($rt['max_occupancy']); ?></span>
                </div>
                <div class="room-content">
                    <h3><?php echo htmlspecialchars($rt['name']); ?></h3>
                    <p><?php echo htmlspecialchars($rt['description']); ?></p>
                    
                    <div class="room-amenities">
                        <?php foreach (array_slice($amenities, 0, 4) as $amenity): ?>
                            <span class="amenity-tag"><?php echo htmlspecialchars($amenity); ?></span>
                        <?php endforeach; ?>
                        <?php if (count($amenities) > 4): ?>
                            <span class="amenity-tag">+<?php echo count($amenities) - 4; ?> more</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="room-footer">
                        <div class="room-price">
                            <?php echo formatCurrency($rt['base_price']); ?> <span>/ Night</span>
                        </div>
                        <a href="room-details.php?id=<?php echo $rt['id']; ?>" class="room-link">
                            View Details 
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Luxury Amenities Services -->
<section style="background-color: var(--bg-surface); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
    <div class="section">
        <div class="section-header">
            <h4>Elite Experiences</h4>
            <h2>Curated Services & Amenities</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 3rem;">
            <div style="text-align: center; padding: 2rem;">
                <div style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;">🍷</div>
                <h3 style="font-size: 1.4rem; margin-bottom: 0.8rem;">Michelin Fine Dining</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.7;">Indulge your palate in gastronomy designed by award-winning chefs, showcasing organic high-altitude produce and vintage labels.</p>
            </div>
            <div style="text-align: center; padding: 2rem;">
                <div style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;">💆‍♀️</div>
                <h3 style="font-size: 1.4rem; margin-bottom: 0.8rem;">Summit Wellness Spa</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.7;">Recharge your mind and body with holistic volcanic stone therapies, sensory mineral steam rooms, and expert massage therapists.</p>
            </div>
            <div style="text-align: center; padding: 2rem;">
                <div style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;">🏊‍♂️</div>
                <h3 style="font-size: 1.4rem; margin-bottom: 0.8rem;">Heated Infinity Pool</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.7;">Relax in our thermal alpine infinity pool, featuring panoramic vistas of the snow-capped mountain ranges and pool-side cocktail service.</p>
            </div>
        </div>
    </div>
</section>

<!-- Guest Testimonials -->
<section class="section" style="max-width: 800px; text-align: center;">
    <div class="section-header">
        <h4>Testimonials</h4>
        <h2>Resort Reviews</h2>
    </div>
    
    <div style="background: var(--bg-surface); border: 1px solid var(--border-color); padding: 3rem; border-radius: 12px; position: relative;">
        <span style="font-size: 5rem; color: rgba(212, 175, 55, 0.1); position: absolute; top: -10px; left: 20px; font-family: serif;">“</span>
        <p style="font-size: 1.2rem; font-style: italic; margin-bottom: 1.5rem; line-height: 1.8;">"Our stay at Aura Heights was absolutely magical. The Presidential Gold Suite was a masterpiece of design, and the mountain view at sunrise was unforgettable. The staff handles everything with impeccable service. We will return every winter!"</p>
        <h4 style="color: var(--primary); font-size: 1rem; text-transform: uppercase; letter-spacing: 1px;">- Marcus & Evelyn V., New York</h4>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
