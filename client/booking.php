<?php
// Check if client is logged in BEFORE including header
if (session_status() === PHP_SESSION_NONE) session_start();
$clientLoggedIn = isset($_SESSION['client_id']) && $_SESSION['client_id'];
if (!$clientLoggedIn) {
    header('Location: login.php?next=booking.php');
    exit;
}

// Check verification BEFORE including header (before any output)
include '../config/db.php';
$customer_id = $_SESSION['client_id'];

// Check if customer has verified government ID
$stmt = $conn->prepare('SELECT government_id_verified FROM customers WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer_data = $result->fetch_assoc();
$stmt->close();

// Redirect to verification page if ID not verified
if (!$customer_data || $customer_data['government_id_verified'] != 1) {
    header('Location: id_verification.php?redirect=booking');
    exit;
}

// NOW safe to include header (all header() calls are done)
include 'partials/header.php';

// Get customer info from session
$customer_name = $_SESSION['client_name'];

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

// Fetch available cottages
$cottages = [];
$cottage_slots = [];
try {
    $cottage_query = "SELECT c.*, 
                      (SELECT ci.filename FROM cottage_images ci WHERE ci.cottage_id = c.id AND ci.is_main = 1 LIMIT 1) as main_image,
                      (SELECT COUNT(*) FROM cottage_images ci WHERE ci.cottage_id = c.id) as image_count
                      FROM cottages c 
                      WHERE c.status = 'available' 
                      ORDER BY c.cottage_number ASC";
    $cottage_result = $conn->query($cottage_query);
    if ($cottage_result) {
        while ($cottage = $cottage_result->fetch_assoc()) {
            $cottages[] = $cottage;
        }
    }
    
    // Fetch all availability slots for all cottages
    $availability_query = "SELECT cottage_id, available_time_start, available_time_end FROM cottage_availability ORDER BY cottage_id, available_time_start, available_time_end";
    $availability_result = $conn->query($availability_query);
    
    while ($slot = $availability_result->fetch_assoc()) {
        $cid = $slot['cottage_id'];
        $key = $slot['available_time_start'] . '-' . $slot['available_time_end'];
        if (!isset($cottage_slots[$cid])) {
            $cottage_slots[$cid] = [];
        }
        if (!isset($cottage_slots[$cid][$key])) {
            $cottage_slots[$cid][$key] = [
                'start' => $slot['available_time_start'],
                'end' => $slot['available_time_end']
            ];
        }
    }
} catch (Exception $e) {
    // Handle error silently
}
?>

<main>
    <section style="padding: 40px 20px;">
        <div class="container">
            <h1 style="color: #27ae60; margin-bottom: 30px;">Make a Reservation</h1>

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

            <!-- Reservation Type Selection -->
            <?php if (empty($reservation_type)): ?>
                <div style="max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
                    <h3 style="color: #27ae60; margin-bottom: 20px; text-align: center;">Choose Reservation Type</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                        <div style="border: 2px solid #e9ecef; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s;" onclick="window.location.href='?type=dine-in'">
                            <i class="fas fa-utensils" style="font-size: 48px; color: #27ae60; margin-bottom: 15px;"></i>
                            <h4 style="color: #27ae60; margin-bottom: 10px;">Dine-In</h4>
                            <p style="color: #666; margin: 0;">Enjoy our farm-to-table dining experience</p>
                        </div>
                        <div style="border: 2px solid #e9ecef; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s;" onclick="window.location.href='?type=farm-visit'">
                            <i class="fas fa-tractor" style="font-size: 48px; color: #27ae60; margin-bottom: 15px;"></i>
                            <h4 style="color: #27ae60; margin-bottom: 10px;">Farm Visit</h4>
                            <p style="color: #666; margin: 0;">Tour our sustainable aquaculture farm</p>
                        </div>
                        <div style="border: 2px solid #e9ecef; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s;" onclick="window.location.href='?type=private-events'">
                            <i class="fas fa-birthday-cake" style="font-size: 48px; color: #27ae60; margin-bottom: 15px;"></i>
                            <h4 style="color: #27ae60; margin-bottom: 10px;">Private Events</h4>
                            <p style="color: #666; margin: 0;">Host your special events at our farm</p>
                        </div>
                        <div style="border: 2px solid #e9ecef; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s;" onclick="window.location.href='?type=cottage'">
                            <i class="fas fa-home" style="font-size: 48px; color: #27ae60; margin-bottom: 15px;"></i>
                            <h4 style="color: #27ae60; margin-bottom: 10px;">Cottage Rental</h4>
                            <p style="color: #666; margin: 0;">Rent a private cottage for your stay</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>

            <div style="max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="color: #27ae60; margin: 0;">
                        <?php
                        switch($reservation_type) {
                            case 'dine-in': echo 'Dine-In Reservation'; break;
                            case 'farm-visit': echo 'Farm Visit Reservation'; break;
                            case 'private-events': echo 'Private Event Reservation'; break;
                            case 'cottage': echo 'Cottage Rental Reservation'; break;
                            default: echo 'Reservation';
                        }
                        ?>
                    </h2>
                    <a href="booking.php" style="color: #27ae60; text-decoration: none;"><i class="fas fa-arrow-left"></i> Change Type</a>
                </div>

                <form method="POST" action="../handlers/booking_handler.php" style="display: grid; gap: 20px;" id="reservationForm">

                    <!-- Customer Info (Hidden - using session data) -->
                    <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>">
                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($customer_name); ?>">

                    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #27ae60;">
                        <strong>Booking for:</strong> <?php echo htmlspecialchars($customer_name); ?>
                    </div>

                    <input type="hidden" name="reservation_type" value="<?php echo $reservation_type; ?>">

                    <?php if ($reservation_type === 'cottage'): ?>
                        <!-- Cottage Selection -->
                        <div>
                            <label for="cottage_id" style="display: block; font-weight: 600; margin-bottom: 8px; color: #27ae60;">Select Cottage *</label>
                            <select id="cottage_id" name="cottage_id" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;" onchange="showCottageDetails(this.value)">
                                <option value="">-- Select a Cottage --</option>
                                <?php foreach ($cottages as $cottage): ?>
                                    <option value="<?php echo $cottage['id']; ?>" data-date-from="<?php echo $cottage['available_date_from']; ?>" data-date-to="<?php echo $cottage['available_date_to']; ?>">
                                        Cottage <?php echo htmlspecialchars($cottage['cottage_number']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Cottage Image Display -->
                        <div id="cottageImageDisplay" style="display: none; margin-top: 20px;">
                            <div id="cottageImages"></div>
                        </div>
                    <?php endif; ?>

                    <div>
                        <label for="num_guests" style="display: block; font-weight: 600; margin-bottom: 8px; color: #27ae60;">Number of Guests *</label>
                        <input type="number" id="num_guests" name="num_guests" min="1" max="200" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    </div>

                    <div>
                        <label for="reservation_date" style="display: block; font-weight: 600; margin-bottom: 8px; color: #27ae60;">Select Date *</label>
                        <input type="date" id="reservation_date" name="reservation_date" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;" onchange="updateTimeSlots()">
                    </div>

                    <?php if ($reservation_type === 'cottage'): ?>
                    <div>
                        <label for="reservation_time" style="display: block; font-weight: 600; margin-bottom: 8px; color: #27ae60;">Available Time Slots *</label>
                        <select id="reservation_time" name="reservation_time" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                            <option value="">-- Select a Time Slot --</option>
                        </select>
                    </div>
                    <?php else: ?>
                    <div>
                        <label for="preferred_time" style="display: block; font-weight: 600; margin-bottom: 8px; color: #27ae60;">Preferred Time *</label>
                        <input type="time" id="preferred_time" name="reservation_time" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    </div>
                    <?php endif; ?>

                    <div>
                        <label for="special_requests" style="display: block; font-weight: 600; margin-bottom: 8px; color: #27ae60;">Special Requests / Preferences</label>
                        <textarea id="special_requests" name="special_requests" rows="3" placeholder="e.g., Birthday celebration, special menu requests, etc." style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; font-family: 'Roboto', sans-serif;"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="padding: 12px; font-size: 16px; margin-top: 10px;" id="submitBtn">
                        <i class="fas fa-check"></i> Complete Reservation
                    </button>

                </form>
            </div>
            <?php endif; ?>

            <div style="max-width: 600px; margin: 30px auto; padding: 20px; background-color: #e8f5e9; border-radius: 8px; border-left: 4px solid #27ae60;">
                <h3 style="color: #27ae60; margin-bottom: 10px;"><i class="fas fa-info-circle"></i> Reservation Information</h3>
                <ul style="list-style: none; color: #666;">
                    <li style="margin-bottom: 8px;"><strong>✓</strong> Entrance Fee: ₱30 per person</li>
                    <li style="margin-bottom: 8px;"><strong>✓</strong> We will confirm your reservation via phone within 2 hours</li>
                    <li style="margin-bottom: 8px;"><strong>✓</strong> Private events and celebrations welcome</li>
                    <li style="margin-bottom: 8px;"><strong>✓</strong> Fresh-cooked meals available daily</li>
                    <?php if ($reservation_type === 'cottage'): ?>
                        <li style="margin-bottom: 8px;"><strong>✓</strong> Cottages include basic amenities and kitchen access</li>
                        <li><strong>✓</strong> Minimum 2-hour rental, maximum 12-hour rental per day</li>
                    <?php else: ?>
                        <li><strong>✓</strong> Can also order through Facebook Page or direct call</li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Order Fish Modal -->
            <?php
            // Fetch fish products from DB
            $fishProducts = [];
            try {
                $stmt = $conn->prepare("SELECT id, name, price, unit, stock_quantity FROM products WHERE category = 'fish' AND status = 'available' ORDER BY name ASC");
                if ($stmt) {
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_assoc()) {
                        $fishProducts[] = $row;
                    }
                    $stmt->close();
                }
            } catch (Exception $e) {
                // ignore
            }
            ?>

            <div id="orderFishModal" class="cf-modal" role="dialog" aria-labelledby="orderFishLabel" aria-hidden="true">
                <div class="cf-modal-dialog">
                    <form method="POST" action="../handlers/client_order.php">
                        <div class="cf-modal-header">
                            <strong id="orderFishLabel"><i class="fas fa-fish"></i> Order Fish</strong>
                            <button type="button" class="btn btn-secondary" id="closeOrderFish">✕</button>
                        </div>
                        <div class="cf-modal-body">
                            <div style="display:flex;flex-direction:column;gap:12px;">
                                <label>Your Name *</label>
                                <input type="text" name="customer_name" class="form-control" required>

                                <label>Phone or Email *</label>
                                <input type="text" name="customer_contact" class="form-control" required>

                                <label>Select Fish *</label>
                                <select name="product_id" class="form-control" required>
                                    <option value="">-- Select Fish --</option>
                                    <?php foreach ($fishProducts as $p): ?>
                                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?> — ₱<?php echo number_format($p['price'], 2); ?> / <?php echo $p['unit']; ?> (stock: <?php echo (int)$p['stock_quantity']; ?>)</option>
                                    <?php endforeach; ?>
                                </select>

                                <div style="display:flex; gap:12px;">
                                    <div style="flex:0 0 120px;">
                                        <label>Quantity *</label>
                                        <input type="number" name="quantity" min="1" value="1" class="form-control" required>
                                    </div>
                                    <div style="flex:1;">
                                        <label>Preferred Delivery/Pickup Date</label>
                                        <input type="date" name="delivery_date" class="form-control">
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="cf-modal-footer" style="display:flex; justify-content:flex-end; gap:8px;">
                            <button type="button" class="btn btn-secondary" id="cancelOrderFish">Cancel</button>
                            <button type="submit" class="btn btn-primary">Place Order</button>
                        </div>
                    </form>
                </div>
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

<script>
// Cottage data from PHP
const cottages = <?php echo json_encode($cottages); ?>;
const cottageTimeSlots = <?php echo json_encode($cottage_slots); ?>;

console.log('Cottages:', cottages);
console.log('Cottage Time Slots:', cottageTimeSlots);

// Helper function to format time to 12-hour
function formatTime(time) {
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'pm' : 'am';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes}${ampm}`;
}

// Show cottage image when selected
function showCottageDetails(cottageId) {
    const imageDisplay = document.getElementById('cottageImageDisplay');
    const imagesDiv = document.getElementById('cottageImages');
    const dateInput = document.getElementById('reservation_date');
    const timeSelect = document.getElementById('reservation_time');

    if (!cottageId) {
        imageDisplay.style.display = 'none';
        dateInput.min = '';
        dateInput.max = '';
        timeSelect.innerHTML = '<option value="">-- Select a Time Slot --</option>';
        return;
    }

    const cottage = cottages.find(c => c.id == cottageId);
    if (!cottage) return;

    // Set date restrictions
    const dateFromStr = cottage.available_date_from || cottage.available_date;
    const dateToStr = cottage.available_date_to || cottage.available_date;
    dateInput.min = dateFromStr;
    dateInput.max = dateToStr;

    // Clear time slots until date is selected
    timeSelect.innerHTML = '<option value="">-- Select a Time Slot --</option>';

    // Fetch and show cottage images
    fetch(`../handlers/get_cottage_images.php?cottage_id=${cottageId}`)
        .then(response => response.json())
        .then(images => {
            if (images.length > 0) {
                let imagesHtml = '';
                if (images.length == 1) {
                    // Single image: show full cover style
                    const image = images[0];
                    const imageUrl = `../assets/img/cottages/${image.filename}`;
                    const isMain = image.is_main ? ' <small style="color: #27ae60;">(Main)</small>' : '';
                    imagesHtml = `
                        <div style="position: relative;">
                            <img src="${imageUrl}" alt="Cottage ${cottage.cottage_number}" style="width: 100%; height: 300px; object-fit: cover; border-radius: 5px; border: 2px solid ${image.is_main ? '#27ae60' : '#ddd'};" onerror="this.style.display='none'">
                            ${isMain}
                        </div>
                    `;
                } else {
                    // Multiple images: grid
                    imagesHtml = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">';
                    images.forEach(image => {
                        const imageUrl = `../assets/img/cottages/${image.filename}`;
                        const isMain = image.is_main ? ' <small style="color: #27ae60;">(Main)</small>' : '';
                        imagesHtml += `
                            <div style="position: relative;">
                                <img src="${imageUrl}" alt="Cottage ${cottage.cottage_number}" style="width: 100%; height: 120px; object-fit: cover; border-radius: 5px; border: 2px solid ${image.is_main ? '#27ae60' : '#ddd'};" onerror="this.style.display='none'">
                                ${isMain}
                            </div>
                        `;
                    });
                    imagesHtml += '</div>';
                }
                imagesDiv.innerHTML = imagesHtml;
            } else {
                imagesDiv.innerHTML = '<p style="color: #666; font-style: italic;">No images available</p>';
            }
        })
        .catch(error => {
            console.error('Error fetching images:', error);
            imagesDiv.innerHTML = '<p style="color: #666; font-style: italic;">Error loading images</p>';
        });

    imageDisplay.style.display = 'block';
}

// Update time slots based on selected date
function updateTimeSlots() {
    const cottageId = document.getElementById('cottage_id').value;
    const dateInput = document.getElementById('reservation_date');
    const timeSelect = document.getElementById('reservation_time');

    timeSelect.innerHTML = '<option value="">-- Select a Time Slot --</option>';

    if (!cottageId || !dateInput.value) return;

    const cottage = cottages.find(c => c.id == cottageId);
    if (!cottage || !cottageTimeSlots[cottageId]) return;

    // Fetch booked slots for this cottage and date
    fetch(`../handlers/get_booked_slots.php?cottage_id=${cottageId}&date=${dateInput.value}`)
        .then(response => response.json())
        .then(data => {
            const bookedTimes = data.booked_times || [];
            console.log('Booked times for ' + dateInput.value + ':', bookedTimes);

            // Normalize booked times to HH:MM for robust comparison (ignore seconds)
            const bookedNorm = bookedTimes.map(t => (t || '').toString().trim().slice(0,5));

            // Get available time slots for this cottage
            const slots = cottageTimeSlots[cottageId];

            Object.keys(slots).sort().forEach(key => {
                const slot = slots[key];
                const slotStartNorm = (slot.start || '').toString().trim().slice(0,5);
                // Only show if not booked (compare normalized times)
                if (!bookedNorm.includes(slotStartNorm)) {
                    const startFormatted = formatTime(slot.start);
                    const endFormatted = formatTime(slot.end);
                    const option = document.createElement('option');
                    option.value = slot.start;
                    option.textContent = `${startFormatted} - ${endFormatted}`;
                    timeSelect.appendChild(option);
                } else {
                    console.log('Hiding booked slot (normalized):', slotStartNorm, 'for cottage', cottageId);
                }
            });

            console.log('Available slots displayed:', timeSelect.options.length - 1);
        })
        .catch(error => {
            console.error('Error fetching booked slots:', error);
            // Fallback: show all slots if error
            const slots = cottageTimeSlots[cottageId];
            Object.keys(slots).sort().forEach(key => {
                const slot = slots[key];
                const startFormatted = formatTime(slot.start);
                const endFormatted = formatTime(slot.end);
                const option = document.createElement('option');
                option.value = slot.start;
                option.textContent = `${startFormatted} - ${endFormatted}`;
                timeSelect.appendChild(option);
            });
        });
}

// Initialize date input with minimum date
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('reservation_date');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;
    }
});
</script>