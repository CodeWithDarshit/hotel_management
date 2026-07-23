// Main JavaScript features for Hotel Management System

document.addEventListener('DOMContentLoaded', function() {
    // 1. Navbar scroll effect
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // 2. Date Input Constraints (prevent past bookings)
    const checkInInput = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');

    if (checkInInput && checkOutInput) {
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        checkInInput.min = today;
        checkOutInput.min = today;

        // When check-in changes, update check-out minimum to check-in + 1 day
        checkInInput.addEventListener('change', () => {
            if (checkInInput.value) {
                const nextDay = new Date(checkInInput.value);
                nextDay.setDate(nextDay.getDate() + 1);
                checkOutInput.min = nextDay.toISOString().split('T')[0];
                
                // If check-out is currently before check-in, clear it or update it
                if (checkOutInput.value && checkOutInput.value <= checkInInput.value) {
                    checkOutInput.value = checkOutInput.min;
                }
            }
            calculateBookingTotal();
        });

        checkOutInput.addEventListener('change', calculateBookingTotal);
    }

    // 3. Booking Cost Auto Calculator
    function calculateBookingTotal() {
        const checkInVal = checkInInput.value;
        const checkOutVal = checkOutInput.value;
        const priceElement = document.getElementById('base_price_val');
        const summaryDiv = document.getElementById('booking_summary');
        
        if (!checkInVal || !checkOutVal || !priceElement || !summaryDiv) return;

        const checkInDate = new Date(checkInVal);
        const checkOutDate = new Date(checkOutVal);
        const basePrice = parseFloat(priceElement.dataset.price);

        // Calculate difference in milliseconds, then convert to days
        const diffTime = checkOutDate - checkInDate;
        const nights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (nights > 0 && !isNaN(basePrice)) {
            const subtotal = basePrice * nights;
            const tax = subtotal * 0.12; // 12% tax/service charge
            const total = subtotal + tax;

            // Populate fields
            document.getElementById('sum_nights').innerText = nights + (nights === 1 ? ' night' : ' nights');
            document.getElementById('sum_subtotal').innerText = '$' + subtotal.toFixed(2);
            document.getElementById('sum_tax').innerText = '$' + tax.toFixed(2);
            document.getElementById('sum_total').innerText = '$' + total.toFixed(2);
            
            // Set hidden field value if present
            const hiddenTotalInput = document.getElementById('total_price_input');
            if (hiddenTotalInput) {
                hiddenTotalInput.value = total.toFixed(2);
            }

            // Show summary section
            summaryDiv.style.display = 'block';
        } else {
            summaryDiv.style.display = 'none';
        }
    }

    // 4. Client-side booking form validation
    const bookingForm = document.getElementById('booking_form');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            const checkInVal = checkInInput.value;
            const checkOutVal = checkOutInput.value;
            if (!checkInVal || !checkOutVal) {
                e.preventDefault();
                alert('Please select both Check-in and Check-out dates.');
                return;
            }

            const checkInDate = new Date(checkInVal);
            const checkOutDate = new Date(checkOutVal);
            if (checkOutDate <= checkInDate) {
                e.preventDefault();
                alert('Check-out date must be after the Check-in date.');
            }
        });
    }
});

// Admin Modal Toggle helper
function openAdminModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeAdminModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}
