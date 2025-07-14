<?php
require_once 'db.php';

$booking_id = $_GET['booking_id'] ?? 0;

if (!$booking_id) {
    header('Location: index.php');
    exit;
}

// Get booking details
$booking = $db->getBookingById($booking_id);
if (!$booking) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - StayBnb</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .confirmation-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: white;
            font-size: 2rem;
        }

        .confirmation-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 1rem;
        }

        .confirmation-subtitle {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2rem;
        }

        .booking-details {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #555;
        }

        .detail-value {
            color: #333;
            font-weight: 500;
        }

        .booking-id {
            background: #e3f2fd;
            color: #1976d2;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-family: monospace;
            font-weight: bold;
        }

        .total-amount {
            font-size: 1.3rem;
            font-weight: bold;
            color: #28a745;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #ff5a5f;
            color: white;
        }

        .btn-primary:hover {
            background: #e04e53;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 2px solid #e0e0e0;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }

        .important-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1rem;
            margin: 2rem 0;
            text-align: left;
        }

        .important-info h4 {
            color: #856404;
            margin-bottom: 0.5rem;
        }

        .important-info p {
            color: #856404;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .confirmation-card {
                padding: 2rem;
                margin: 1rem;
            }

            .confirmation-title {
                font-size: 2rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .confirmation-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .success-icon {
            animation: fadeInUp 0.8s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="confirmation-card">
            <div class="success-icon">✓</div>
            
            <h1 class="confirmation-title">Booking Confirmed!</h1>
            <p class="confirmation-subtitle">
                Your reservation has been successfully confirmed. We've sent a confirmation email to 
                <strong><?php echo htmlspecialchars($booking['guest_email']); ?></strong>
            </p>

            <div class="booking-details">
                <div class="detail-row">
                    <span class="detail-label">Booking ID:</span>
                    <span class="booking-id">#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Property:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($booking['property_title']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Location:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($booking['city'] . ', ' . $booking['state']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Guest Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($booking['guest_name']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Check-in:</span>
                    <span class="detail-value"><?php echo date('F j, Y', strtotime($booking['check_in_date'])); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Check-out:</span>
                    <span class="detail-value"><?php echo date('F j, Y', strtotime($booking['check_out_date'])); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Guests:</span>
                    <span class="detail-value"><?php echo $booking['guests']; ?> guest<?php echo $booking['guests'] > 1 ? 's' : ''; ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Total Nights:</span>
                    <span class="detail-value"><?php echo $booking['total_nights']; ?> night<?php echo $booking['total_nights'] > 1 ? 's' : ''; ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Total Amount:</span>
                    <span class="total-amount">$<?php echo number_format($booking['total_amount'], 2); ?></span>
                </div>
            </div>

            <div class="important-info">
                <h4>Important Information:</h4>
                <p>• Please save this confirmation for your records</p>
                <p>• Check-in time is typically 3:00 PM</p>
                <p>• Check-out time is typically 11:00 AM</p>
                <p>• Contact your host if you need to arrange different times</p>
                <p>• Cancellation policy applies as per property terms</p>
            </div>

            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">Book Another Stay</a>
                <a href="properties.php" class="btn btn-secondary">Browse Properties</a>
            </div>
        </div>
    </div>

    <script>
        // Auto-redirect after 30 seconds (optional)
        setTimeout(function() {
            if (confirm('Would you like to return to the homepage?')) {
                window.location.href = 'index.php';
            }
        }, 30000);

        // Print functionality
        function printConfirmation() {
            window.print();
        }

        // Add print button if needed
        document.addEventListener('DOMContentLoaded', function() {
            const actionButtons = document.querySelector('.action-buttons');
            const printBtn = document.createElement('button');
            printBtn.className = 'btn btn-secondary';
            printBtn.textContent = 'Print Confirmation';
            printBtn.onclick = printConfirmation;
            actionButtons.appendChild(printBtn);
        });
    </script>
</body>
</html>
