<?php
// Enable comprehensive error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Start output buffering to catch any unexpected output
ob_start();

try {
    // Check if db.php exists
    if (!file_exists('db.php')) {
        throw new Exception("Database configuration file (db.php) not found!");
    }
    
    require_once 'db.php';
    a    // Initialize variables with default values
    $property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;
    $checkin = isset($_GET['checkin']) ? trim($_GET['checkin']) : '';
    $checkout = isset($_GET['checkout']) ? trim($_GET['checkout']) : '';
    $guests = isset($_GET['guests']) ? (int)$_GET['guests'] : 1;
    
    $error = '';
    $success = '';
    $debug_info = [];
    
    // Debug information
    $debug_info[] = "Property ID: " . $property_id;
    $debug_info[] = "Check-in: " . $checkin;
    $debug_info[] = "Check-out: " . $checkout;
    $debug_info[] = "Guests: " . $guests;
    
    // Validate required parameters
    if (!$property_id || !$checkin || !$checkout) {
        $error = "Missing required booking parameters. Please start from the property page.";
        $debug_info[] = "ERROR: Missing required parameters";
    }
    
    // Initialize property variable
    $property = null;
    $nights = 0;
    $subtotal = 0;
    $total = 0;
    $available = false;
    
    if (!$error && $property_id) {
        try {
            // Check if database connection exists
            if (!isset($db) || !$db) {
                throw new Exception("Database connection not established!");
            }
            
            // Get property details
            $property = $db->getPropertyById($property_id);
            $debug_info[] = "Property query executed";
            
            if (!$property) {
                $error = "Property not found or no longer available.";
                $debug_info[] = "ERROR: Property not found with ID: " . $property_id;
            } else {
                $debug_info[] = "Property found: " . $property['title'];
                
                // Validate and calculate dates
                try {
                    $checkin_date = new DateTime($checkin);
                    $checkout_date = new DateTime($checkout);
                    $nights = $checkin_date->diff($checkout_date)->days;
                    
                    $debug_info[] = "Date calculation successful. Nights: " . $nights;
                    
                    if ($nights <= 0) {
                        $error = "Invalid date range. Check-out must be after check-in.";
                        $debug_info[] = "ERROR: Invalid date range";
                    } else {
                        // Calculate pricing
                        $subtotal = $nights * $property['price_per_night'];
                        $total = $subtotal + $property['cleaning_fee'] + $property['service_fee'];
                        
                        $debug_info[] = "Price calculation: Subtotal=$subtotal, Total=$total";
                        
                        // Check availability
                        $available = $db->checkAvailability($property_id, $checkin, $checkout);
                        $debug_info[] = "Availability check: " . ($available ? "Available" : "Not available");
                    }
                } catch (Exception $date_error) {
                    $error = "Invalid date format: " . $date_error->getMessage();
                    $debug_info[] = "ERROR: Date parsing failed - " . $date_error->getMessage();
                }
            }
        } catch (Exception $db_error) {
            $error = "Database error: " . $db_error->getMessage();
            $debug_info[] = "ERROR: Database error - " . $db_error->getMessage();
        }
    }
    
    // Handle form submission
    if ($_POST && !$error && $available && $property) {
        try {
            $debug_info[] = "Processing form submission";
            
            // Validate form data
            $guest_name = isset($_POST['guest_name']) ? trim($_POST['guest_name']) : '';
            $guest_email = isset($_POST['guest_email']) ? trim($_POST['guest_email']) : '';
            $guest_phone = isset($_POST['guest_phone']) ? trim($_POST['guest_phone']) : '';
            $special_requests = isset($_POST['special_requests']) ? trim($_POST['special_requests']) : '';
            
            if (empty($guest_name) || empty($guest_email)) {
                throw new Exception("Name and email are required fields.");
            }
            
            if (!filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Please enter a valid email address.");
            }
            
            // Double-check availability before booking
            $available = $db->checkAvailability($property_id, $checkin, $checkout);
            if (!$available) {
                throw new Exception("Sorry, this property is no longer available for the selected dates.");
            }
            
            // Create booking using corrected method
            $debug_info[] = "Creating booking with corrected parameters";
            
            $booking_id = $db->createBookingFixed(
                $property_id,
                1, // guest_id - default for now
                $checkin,
                $checkout,
                $guests,
                $nights,
                $subtotal,
                $property['cleaning_fee'],
                $property['service_fee'],
                $total,
                $guest_name,
                $guest_email,
                $guest_phone,
                $special_requests
            );
            
            $debug_info[] = "Booking created with ID: " . $booking_id;
            
            if ($booking_id) {
                // Redirect to confirmation page
                header('Location: confirmation.php?booking_id=' . $booking_id);
                exit;
            } else {
                throw new Exception("Failed to create booking. Please try again.");
            }
            
        } catch (Exception $booking_error) {
            $error = $booking_error->getMessage();
            $debug_info[] = "ERROR: Booking failed - " . $booking_error->getMessage();
        }
    }
    
} catch (Exception $main_error) {
    $error = "System error: " . $main_error->getMessage();
    $debug_info[] = "FATAL ERROR: " . $main_error->getMessage();
}

// Clean output buffer
ob_end_clean();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Your Stay - StayBnb</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Debug Panel */
        .debug-panel {
            background: #1a1a1a;
            color: #00ff00;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.9rem;
            max-height: 200px;
            overflow-y: auto;
        }

        .debug-toggle {
            background: #007bff;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 1rem;
        }

        /* Header */
        header {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: #ff5a5f;
            text-decoration: none;
        }

        .back-btn {
            background: #f8f9fa;
            color: #333;
            border: 2px solid #e0e0e0;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }

        /* Error/Success Messages */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        /* Main Content */
        .booking-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 3rem;
            padding: 2rem 0;
        }

        .booking-form-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #333;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #555;
        }

        .form-input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }

        .form-input:focus {
            border-color: #ff5a5f;
        }

        .form-textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
            resize: vertical;
            min-height: 100px;
        }

        .form-textarea:focus {
            border-color: #ff5a5f;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* Booking Summary */
        .booking-summary {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .property-preview {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .property-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
            background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
        }

        .property-info h3 {
            font-size: 1.1rem;
            margin-bottom: 0.3rem;
        }

        .property-location {
            color: #666;
            font-size: 0.9rem;
        }

        .booking-details {
            margin-bottom: 2rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
            padding: 0.5rem 0;
        }

        .detail-label {
            font-weight: 600;
            color: #555;
        }

        .detail-value {
            color: #333;
        }

        .price-breakdown {
            border-top: 2px solid #f0f0f0;
            padding-top: 1.5rem;
            margin-bottom: 2rem;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
        }

        .price-total {
            border-top: 2px solid #e0e0e0;
            padding-top: 1rem;
            margin-top: 1rem;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .book-btn {
            width: 100%;
            background: #ff5a5f;
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .book-btn:hover {
            background: #e04e53;
        }

        .book-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        /* Availability Status */
        .availability-status {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
        }

        .available {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .unavailable {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .booking-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .booking-summary {
                position: static;
                order: -1;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .property-preview {
                flex-direction: column;
                text-align: center;
            }

            .property-image {
                width: 100%;
                height: 150px;
            }
        }

        .system-info {
            background: #e3f2fd;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <header>
        <nav class="container">
            <a href="index.php" class="logo">StayBnb</a>
            <?php if ($property_id): ?>
                <a href="property-detail.php?id=<?php echo $property_id; ?>" class="back-btn">← Back to Property</a>
            <?php else: ?>
                <a href="properties.php" class="back-btn">← Back to Properties</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="container">
        <!-- Debug Information -->
        <button class="debug-toggle" onclick="toggleDebug()">Toggle Debug Info</button>
        <div class="debug-panel" id="debugPanel" style="display: none;">
            <strong>Debug Information:</strong><br>
            <?php foreach ($debug_info as $info): ?>
                <?php echo htmlspecialchars($info); ?><br>
            <?php endforeach; ?>
            <br><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
            <strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?><br>
            <strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>GET Parameters:</strong> <?php echo htmlspecialchars(http_build_query($_GET)); ?><br>
            <strong>POST Data:</strong> <?php echo $_POST ? 'Form submitted' : 'No form data'; ?><br>
        </div>

        <!-- System Information -->
        <div class="system-info">
            <strong>System Status:</strong> 
            Database: <?php echo isset($db) ? '✓ Connected' : '✗ Not Connected'; ?> | 
            Property ID: <?php echo $property_id ?: 'Not Set'; ?> | 
            Property Data: <?php echo $property ? '✓ Loaded' : '✗ Not Found'; ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!$property_id || !$checkin || !$checkout): ?>
            <div class="alert alert-warning">
                <strong>Missing Information:</strong> Please start your booking from a property page with valid dates.
                <br><a href="properties.php">Browse Properties</a>
            </div>
        <?php elseif (!$property): ?>
            <div class="alert alert-error">
                <strong>Property Not Found:</strong> The requested property could not be found.
                <br><a href="properties.php">Browse Other Properties</a>
            </div>
        <?php else: ?>
            <div class="booking-container">
                <div class="booking-form-section">
                    <h1 class="section-title">Complete Your Booking</h1>

                    <div class="availability-status <?php echo $available ? 'available' : 'unavailable'; ?>">
                        <?php if ($available): ?>
                            ✓ Available for your selected dates
                        <?php else: ?>
                            ✗ Not available for selected dates
                        <?php endif; ?>
                    </div>

                    <?php if ($available): ?>
                        <form method="POST" id="bookingForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="guest_name">Full Name *</label>
                                    <input type="text" id="guest_name" name="guest_name" class="form-input" 
                                           value="<?php echo htmlspecialchars($_POST['guest_name'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="guest_email">Email Address *</label>
                                    <input type="email" id="guest_email" name="guest_email" class="form-input" 
                                           value="<?php echo htmlspecialchars($_POST['guest_email'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="guest_phone">Phone Number</label>
                                <input type="tel" id="guest_phone" name="guest_phone" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['guest_phone'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="special_requests">Special Requests</label>
                                <textarea id="special_requests" name="special_requests" class="form-textarea" 
                                          placeholder="Any special requests or notes for your host..."><?php echo htmlspecialchars($_POST['special_requests'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>
                                    <input type="checkbox" required> 
                                    I agree to the terms and conditions and cancellation policy
                                </label>
                            </div>

                            <button type="submit" class="book-btn">Confirm Booking</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            This property is not available for your selected dates. Please choose different dates or select another property.
                        </div>
                        <button class="book-btn" onclick="history.back()">Choose Different Dates</button>
                    <?php endif; ?>
                </div>

                <div class="booking-summary">
                    <div class="property-preview">
                        <img src="/placeholder.svg?height=80&width=80" alt="Property" class="property-image">
                        <div class="property-info">
                            <h3><?php echo htmlspecialchars($property['title']); ?></h3>
                            <div class="property-location"><?php echo htmlspecialchars($property['full_location'] ?? 'Location not available'); ?></div>
                        </div>
                    </div>

                    <div class="booking-details">
                        <div class="detail-row">
                            <span class="detail-label">Check-in:</span>
                            <span class="detail-value"><?php echo $checkin ? date('M j, Y', strtotime($checkin)) : 'Not set'; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Check-out:</span>
                            <span class="detail-value"><?php echo $checkout ? date('M j, Y', strtotime($checkout)) : 'Not set'; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Guests:</span>
                            <span class="detail-value"><?php echo $guests; ?> guest<?php echo $guests > 1 ? 's' : ''; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Nights:</span>
                            <span class="detail-value"><?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?></span>
                        </div>
                    </div>

                    <?php if ($nights > 0): ?>
                        <div class="price-breakdown">
                            <div class="price-row">
                                <span>$<?php echo number_format($property['price_per_night'], 0); ?> × <?php echo $nights; ?> nights</span>
                                <span>$<?php echo number_format($subtotal, 0); ?></span>
                            </div>
                            <div class="price-row">
                                <span>Cleaning fee</span>
                                <span>$<?php echo number_format($property['cleaning_fee'], 0); ?></span>
                            </div>
                            <div class="price-row">
                                <span>Service fee</span>
                                <span>$<?php echo number_format($property['service_fee'], 0); ?></span>
                            </div>
                            <div class="price-row price-total">
                                <span>Total (USD)</span>
                                <span>$<?php echo number_format($total, 0); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        function toggleDebug() {
            const panel = document.getElementById('debugPanel');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        }

        // Form validation
        document.getElementById('bookingForm')?.addEventListener('submit', function(e) {
            const name = document.getElementById('guest_name').value.trim();
            const email = document.getElementById('guest_email').value.trim();
            
            if (!name || !email) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return;
            }
            
            // Show loading state
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
        });

        // Auto-format phone number
        document.getElementById('guest_phone')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 6) {
                value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
            } else if (value.length >= 3) {
                value = value.replace(/(\d{3})(\d{3})/, '($1) $2');
            }
            e.target.value = value;
        });

        // Show debug panel by default if there are errors
        <?php if ($error): ?>
            document.getElementById('debugPanel').style.display = 'block';
        <?php endif; ?>
    </script>
</body>
</html>
