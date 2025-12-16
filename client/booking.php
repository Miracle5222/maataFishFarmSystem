<?php
include 'partials/header.php';
include '../config/db.php';

$reservation_type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : '';
$error_message = '';
$success_message = '';

// Check for success message from handler
if (isset($_GET['success'])) {
    $success_message = "Thank you! Your reservation has been successfully submitted. Our team will contact you shortly to confirm the details.";
}

// Check for error message
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}
?>

<main>
    <section style="padding: 40px 20px;">
        <div class="container">
            <h1 style="color: #27ae60; margin-bottom: 30px;">Reserve Your Table</h1>

            <?php if ($error_message): ?>
                <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 30px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 30px;">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <div style="max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <form method="POST" action="../handlers/booking_handler.php" style="display: grid; gap: 20px;">

                    <div>
                        <label for="name" style="display: block; font-weight: 600; margin-bottom: 8px; color: #27ae60;">Full Name *</label>
                        <input type="text" id="name" name="name" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    </div>

                    <div>
                        <label for="email" style="display: block; font-weight: 600; margin-bottom: 8px; color: #27ae60;">Email Address *</label>
                        <input type="email" id="email" name="email" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    </div>

                    <div>
                        <label for="phone" style="display: block; font-weight: 600; margin-bottom: 8px; color: #27ae60;">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    </div>

                    <div>
                        <label for="reservation_type" style="display: block; font-weight: 600; margin-bottom: 8px; color: #27ae60;">Reservation Type *</label>
                        <select id="reservation_type" name="reservation_type" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                            <option value="">-- Select Reservation Type --</option>
                            <option value="dine-in">Dine-In</option>
                            <option value="farm visit">Farm Visit</option>
                            <option value="private-event">Private Event</option>

                        </select>
                    </div>

                    <div>
                        <label for="num_guests" style="display: block; font-weight: 600; margin-bottom: 8px; color: #27ae60;">Number of Guests *</label>
                        <input type="number" id="num_guests" name="num_guests" min="1" max="200" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    </div>

                    <div>
                        <label for="reservation_date" style="display: block; font-weight: 600; margin-bottom: 8px; color: #27ae60;">Preferred Date *</label>
                        <input type="date" id="reservation_date" name="reservation_date" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    </div>

                    <div>
                        <label for="reservation_time" style="display: block; font-weight: 600; margin-bottom: 8px; color: #27ae60;">Preferred Time *</label>
                        <input type="time" id="reservation_time" name="reservation_time" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    </div>

                    <div>
                        <label for="special_requests" style="display: block; font-weight: 600; margin-bottom: 8px; color: #27ae60;">Special Requests / Preferences</label>
                        <textarea id="special_requests" name="special_requests" rows="3" placeholder="e.g., Birthday celebration, special menu requests, etc." style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; font-family: 'Roboto', sans-serif;"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="padding: 12px; font-size: 16px; margin-top: 10px;">
                        <i class="fas fa-check"></i> Complete Reservation
                    </button>

                </form>
            </div>

            <div style="max-width: 600px; margin: 30px auto; padding: 20px; background-color: #e8f5e9; border-radius: 8px; border-left: 4px solid #27ae60;">
                <h3 style="color: #27ae60; margin-bottom: 10px;"><i class="fas fa-info-circle"></i> Reservation Information</h3>
                <ul style="list-style: none; color: #666;">
                    <li style="margin-bottom: 8px;"><strong>✓</strong> Entrance Fee: ₱30 per person</li>
                    <li style="margin-bottom: 8px;"><strong>✓</strong> We will confirm your reservation via phone within 2 hours</li>
                    <li style="margin-bottom: 8px;"><strong>✓</strong> Private events and celebrations welcome</li>
                    <li style="margin-bottom: 8px;"><strong>✓</strong> Fresh-cooked meals available daily</li>
                    <li style="margin-bottom: 8px;"><strong>✓</strong> Farm Visit: Tour our 2-hectare fish farm and learn about sustainable aquaculture</li>
                    <li><strong>✓</strong> Can also order through Facebook Page or direct call</li>
                </ul>
            </div>

            <div style="max-width: 600px; margin: 30px auto; padding: 20px; background-color: #f0f8f5; border-radius: 8px; border-left: 4px solid #52be80;">
                <h3 style="color: #27ae60; margin-bottom: 10px;"><i class="fas fa-fish"></i> Fish Purchase</h3>
                <p style="color: #666; margin-bottom: 10px;">
                    Want to buy fresh fish directly? All fish are available at <strong>₱200/kg</strong>:
                </p>
                <ul style="list-style: none; color: #666; margin-bottom: 10px;">
                    <li style="margin-bottom: 5px;"><strong>• Tilapia</strong> - Most in-demand</li>
                    <li style="margin-bottom: 5px;"><strong>• Catfish (Hito)</strong> - Perfect for cooking</li>
                    <li style="margin-bottom: 5px;"><strong>• Japanese Koi</strong> - Premium option</li>

                </ul>
                <p style="color: #27ae60; font-weight: 600;">Call or message us for direct fish orders!</p>
            </div>

            <div style="max-width: 600px; margin: 30px auto; padding: 20px; background-color: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3;">
                <h3 style="color: #1976d2; margin-bottom: 10px;"><i class="fas fa-leaf"></i> Visit Our Farm</h3>
                <p style="color: #666; margin-bottom: 15px;">
                    Experience authentic farm-to-table dining and learn about our sustainable aquaculture practices.
                </p>
                <p style="color: #666; margin-bottom: 10px;"><strong>What to expect:</strong></p>
                <ul style="list-style: none; color: #666;">
                    <li style="margin-bottom: 5px;"><strong>• Farm Tour:</strong> See our fish ponds and learn about cultivation techniques</li>
                    <li style="margin-bottom: 5px;"><strong>• Dining Area:</strong> Enjoy fresh meals in our on-site restaurant</li>
                    <li style="margin-bottom: 5px;"><strong>• Gift Shop:</strong> Purchase fresh fish and farm products</li>
                    <li style="margin-bottom: 5px;"><strong>• Photo Opportunities:</strong> Beautiful farm scenery for memorable photos</li>
                    <li style="margin-bottom: 5px;"><strong>• Educational Programs:</strong> Learn about fish farming and sustainability</li>
                </ul>
                <p style="color: #1976d2; font-weight: 600; margin-top: 10px;">Perfect for families, groups, and educational visits!</p>
            </div>

        </div>
    </section>
</main>

<?php include 'partials/footer.php'; ?>