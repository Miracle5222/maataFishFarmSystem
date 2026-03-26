<?php
include 'config/db.php';
include 'auth_admin.php';

$error_message = '';
$success_message = '';

// Check for success message
if (isset($_GET['success'])) {
    $success_message = "Walk-in customer has been successfully checked in to the cottage. The rental is now active.";
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
    // Use the time slots directly from cottages table
    foreach ($cottages as $cottage) {
        $cid = $cottage['id'];
        $key = $cottage['available_time_start'] . '-' . $cottage['available_time_end'];
        if (!isset($cottage_slots[$cid])) {
            $cottage_slots[$cid] = [];
        }
        if (!isset($cottage_slots[$cid][$key])) {
            $cottage_slots[$cid][$key] = [
                'start' => $cottage['available_time_start'],
                'end' => $cottage['available_time_end']
            ];
        }
    }
} catch (Exception $e) {
    // Handle error silently
}

include 'partials/head.php';
?>
<!-- [ Layout sidenav ] Start -->
<?php include 'partials/sidenav.php'; ?>
<!-- [ Layout sidenav ] End -->
<!-- [ Layout navbar ] Start -->
<?php include 'partials/navbar.php'; ?>
<!-- [ Layout navbar ] End -->
<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <div class="page-heading mb-4">
            <h4 class="font-weight-bold py-3 mb-0">
                <i class="feather icon-home"></i> Walk-In Cottage Rental
            </h4>
            <div class="text-muted small mt-0 d-block breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php"><i class="feather icon-home"></i></a></li>
                    <li class="breadcrumb-item"><a href="reservations_list.php">Reservations</a></li>
                    <li class="breadcrumb-item active">Walk-In Cottage Rental</li>
                </ol>
            </div>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
                <i class="feather icon-alert-circle" style="color: #721c24;"></i> 
                <strong style="color: #721c24;">Error:</strong> <span style="color: #721c24;"><?php echo $error_message; ?></span>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="color: #721c24;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;">
                <i class="feather icon-check-circle" style="color: #155724;"></i> 
                <strong style="color: #155724;">Success!</strong> <span style="color: #155724;"><?php echo $success_message; ?></span>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="color: #155724;">
                    <span aria-hidden="true">&times;</span>
                </button>
                <hr>
                <a href="manual_cottage_reservation.php" class="btn btn-sm btn-primary">
                    <i class="feather icon-plus"></i> Check In Another Customer
                </a>
                <a href="reservations_list.php" class="btn btn-sm btn-secondary">
                    <i class="feather icon-list"></i> View All Reservations
                </a>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="card-header-title">
                    <i class="feather icon-info"></i> Check-In Walk-In Customer to Cottage
                </h5>
                <p class="text-muted small mt-2">Register a walk-in customer who wants to rent/occupy a cottage immediately. Fill out the form below to process the check-in.</p>
            </div>
            <div class="card-body">
                <form method="POST" action="handlers/manual_cottage_reservation_handler.php" id="manualReservationForm">

                    <!-- Customer Information Section -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="font-weight-bold text-primary mb-3">
                                <i class="feather icon-user"></i> Customer Information
                            </h6>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="customer_name" class="form-label font-weight-600">Customer Name *</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" placeholder="Enter customer full name" required>
                            <small class="text-muted">The guest's full name</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="contact_phone" class="form-label font-weight-600">Phone Number *</label>
                            <input type="tel" class="form-control" id="contact_phone" name="contact_phone" placeholder="e.g., 09123456789" required>
                            <small class="text-muted">Customer's contact phone number</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="contact_email" class="form-label font-weight-600">Email Address</label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email" placeholder="e.g., customer@example.com">
                            <small class="text-muted">Customer's email (optional)</small>
                        </div>
                    </div>

                    <!-- Reservation Details Section -->
                    <div class="row mb-4 mt-4">
                        <div class="col-md-12">
                            <h6 class="font-weight-bold text-primary mb-3">
                                <i class="feather icon-calendar"></i> Check-In Details
                            </h6>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cottage_id" class="form-label font-weight-600">Select Cottage *</label>
                            <select id="cottage_id" name="cottage_id" class="form-control" required onchange="showCottageDetails(this.value)">
                                <option value="">-- Choose a Cottage --</option>
                                <?php foreach ($cottages as $cottage): ?>
                                    <option value="<?php echo $cottage['id']; ?>" data-date-from="<?php echo $cottage['available_date_from']; ?>" data-date-to="<?php echo $cottage['available_date_to']; ?>">
                                        Cottage <?php echo htmlspecialchars($cottage['cottage_number']); ?> (₱<?php echo number_format($cottage['price'], 2); ?> per stay)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Select which cottage the customer wants to rent</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="num_guests" class="form-label font-weight-600">Number of Guests *</label>
                            <input type="number" class="form-control" id="num_guests" name="num_guests" min="1" max="200" required placeholder="e.g., 5">
                            <small class="text-muted">How many people will occupy the cottage?</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="reservation_date" class="form-label font-weight-600">Check-In Date *</label>
                            <input type="date" class="form-control" id="reservation_date" name="reservation_date" required onchange="updateTimeSlots()">
                            <small class="text-muted">The date of check-in (today or future date)</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="reservation_time" class="form-label font-weight-600">Check-In Time *</label>
                            <select id="reservation_time" name="reservation_time" class="form-control" required>
                                <option value="">-- Select a Time Slot --</option>
                            </select>
                            <small class="text-muted">Available time slot for check-in</small>
                        </div>
                    </div>

                    <!-- Cottage Image Display -->
                    <div id="cottageImageDisplay" style="display: none;" class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="font-weight-bold text-primary mb-3">
                                <i class="feather icon-image"></i> Cottage Preview
                            </h6>
                            <div id="cottageImages"></div>
                        </div>
                    </div>

                    <!-- Additional Notes Section -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="font-weight-bold text-primary mb-3">
                                <i class="feather icon-file-text"></i> Additional Information
                            </h6>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="special_requests" class="form-label font-weight-600">Special Requests / Notes</label>
                            <textarea class="form-control" id="special_requests" name="special_requests" rows="3" placeholder="e.g., Extra pillows, late checkout request, special arrangements, etc."></textarea>
                            <small class="text-muted">Any special requests or notes for this reservation</small>
                        </div>
                    </div>

                    <!-- Payment Status Section -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="font-weight-bold text-primary mb-3">
                                <i class="feather icon-credit-card"></i> Occupancy Status
                            </h6>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label font-weight-600">Status *</label>
                            <select id="status" name="status" class="form-control" required>
                                <option value="confirmed">Checked In (Active)</option>
                                <option value="pending">Pending Check-In</option>
                                <option value="completed">Checked Out</option>
                            </select>
                            <small class="text-muted">Set the current occupancy status</small>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="feather icon-check-circle"></i> Check-In Customer
                            </button>
                            <a href="reservations_list.php" class="btn btn-secondary">
                                <i class="feather icon-x"></i> Cancel
                            </a>
                        </div>
                    </div>

                </form>
            </div>
        </div>

        <!-- Information Card -->
        <div class="card mt-4">
            <div class="card-body bg-light">
                <h6 class="font-weight-bold text-success mb-3">
                    <i class="feather icon-info"></i> Walk-In Rental Information
                </h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <strong>✓</strong> Use this form to check in walk-in customers who want to rent cottages immediately or for future dates
                    </li>
                    <li class="mb-2">
                        <strong>✓</strong> This is for occupancy/rental tracking, not advance reservations
                    </li>
                    <li class="mb-2">
                        <strong>✓</strong> All fields marked with * (asterisk) are required
                    </li>
                    <li class="mb-2">
                        <strong>✓</strong> Only available cottages and time slots are shown
                    </li>
                    <li class="mb-2">
                        <strong>✓</strong> The system prevents double-booking of cottages
                    </li>
                    <li class="mb-2">
                        <strong>✓</strong> Activity logs will record this walk-in rental transaction
                    </li>
                    <li class="mb-2">
                        <strong>✓</strong> Status options: "Checked In (Active)" for current occupancy, "Pending Check-In" for future dates, "Checked Out" when customer leaves
                    </li>
                    <li>
                        <strong>✓</strong> You can edit or cancel the rental from the Reservations list page
                    </li>
                </ul>
            </div>
        </div>

    </div>
    <!-- [ content ] End -->
</div>
<!-- [ Layout content ] End -->

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
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
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
    fetch(`handlers/get_cottage_images.php?cottage_id=${cottageId}`)
        .then(response => response.json())
        .then(images => {
            if (images.length > 0) {
                let imagesHtml = '';
                if (images.length == 1) {
                    // Single image: show full cover style
                    const image = images[0];
                    const imageUrl = `assets/img/cottages/${image.filename}`;
                    const isMain = image.is_main ? ' <small class="text-success">(Main)</small>' : '';
                    imagesHtml = `
                        <div class="position-relative">
                            <img src="${imageUrl}" alt="Cottage ${cottage.cottage_number}" class="img-fluid rounded" style="width: 100%; height: 300px; object-fit: cover; border: 2px solid ${image.is_main ? '#27ae60' : '#ddd'};" onerror="this.style.display='none'">
                            ${isMain}
                        </div>
                    `;
                } else {
                    // Multiple images: grid
                    imagesHtml = '<div class="row">';
                    images.forEach(image => {
                        const imageUrl = `assets/img/cottages/${image.filename}`;
                        const isMain = image.is_main ? ' <small class="text-success">(Main)</small>' : '';
                        imagesHtml += `
                            <div class="col-md-6 col-lg-4 mb-2">
                                <div class="position-relative">
                                    <img src="${imageUrl}" alt="Cottage ${cottage.cottage_number}" class="img-fluid rounded" style="width: 100%; height: 180px; object-fit: cover; border: 2px solid ${image.is_main ? '#27ae60' : '#ddd'};" onerror="this.style.display='none'">
                                    ${isMain}
                                </div>
                            </div>
                        `;
                    });
                    imagesHtml += '</div>';
                }
                imagesDiv.innerHTML = imagesHtml;
            } else {
                imagesDiv.innerHTML = '<p class="text-muted font-italic">No images available for this cottage</p>';
            }
        })
        .catch(error => {
            console.error('Error fetching images:', error);
            imagesDiv.innerHTML = '<p class="text-muted font-italic">Error loading images</p>';
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
    fetch(`handlers/get_booked_slots.php?cottage_id=${cottageId}&date=${dateInput.value}`)
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

// Initialize date input with minimum date (today)
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('reservation_date');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;
        dateInput.value = today; // Set default to today for walk-in check-in
    }

    // Form validation
    const form = document.getElementById('manualReservationForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const cottageId = document.getElementById('cottage_id').value;
            const reservationDate = document.getElementById('reservation_date').value;
            const reservationTime = document.getElementById('reservation_time').value;

            if (!cottageId) {
                e.preventDefault();
                alert('Please select a cottage');
                return;
            }

            if (!reservationDate) {
                e.preventDefault();
                alert('Please select a check-in date');
                return;
            }

            if (!reservationTime) {
                e.preventDefault();
                alert('Please select a check-in time slot');
                return;
            }
        });
    }
});
</script>
