    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <h3>AURA HEIGHTS</h3>
                <p>Experience ultra-luxury accommodation and bespoke services nestled in the high valleys, providing absolute peace, relaxation, and unmatched scenic beauty.</p>
                <p style="color: var(--primary); font-weight: 500;">&copy; <?php echo date('Y'); ?> Aura Heights. All Rights Reserved.</p>
            </div>
            <div class="footer-col">
                <h3>QUICK LINKS</h3>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="rooms.php">Rooms & Suites</a></li>
                    <li><a href="login.php">Customer Login</a></li>
                    <li><a href="register.php">Create Account</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>CONTACT US</h3>
                <p style="margin-bottom: 0.8rem;">📍 777 Luxury Ridge Highway, Aspen, CO</p>
                <p style="margin-bottom: 0.8rem;">📞 +1 (800) 555-AURA</p>
                <p style="margin-bottom: 0.8rem;">✉️ reservations@auraheights.com</p>
            </div>
            <div class="footer-col">
                <h3>NEWSLETTER</h3>
                <p>Subscribe to receive seasonal discounts and exclusive experiences.</p>
                <form class="newsletter-form" onsubmit="event.preventDefault(); alert('Thank you for subscribing!');">
                    <input type="email" placeholder="Your Email Address" required>
                    <button type="submit">Join</button>
                </form>
            </div>
        </div>
        <div class="footer-bottom">
            <p>Designed with luxury aesthetics. Secured with modern hashing and PDO parameters.</p>
            <p><a href="admin/login.php" style="color: var(--primary);">Staff Admin Gate</a></p>
        </div>
    </footer>
    
    <!-- Main JS Scripts -->
    <script src="assets/js/main.js"></script>
</body>
</html>
