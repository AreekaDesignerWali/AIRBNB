<?php
require_once 'db.php';

$property_id = $_GET['id'] ?? 0;

if (!$property_id) {
    header('Location: properties.php');
    exit;
}

// Get property details
$property = $db->getPropertyById($property_id);
if (!$property) {
    header('Location: properties.php');
    exit;
}

// Get property images, amenities, and reviews
$images = $db->getPropertyImages($property_id);
$amenities = $db->getPropertyAmenities($property_id);
$reviews = $db->getPropertyReviews($property_id, 5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['title']); ?> - StayBnb</title>
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
            background-color: #fff;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
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

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #ff5a5f;
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

        /* Property Header */
        .property-header {
            padding: 2rem 0 1rem;
        }

        .property-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .property-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .rating-badge {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            background: #f8f9fa;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
        }

        .stars {
            color: #ffc107;
        }

        .location {
            color: #666;
            font-size: 1rem;
        }

        /* Image Gallery */
        .image-gallery {
            margin-bottom: 2rem;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .main-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
        }

        .thumbnail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            padding: 15px;
            background: #f8f9fa;
        }

        .thumbnail {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .thumbnail:hover {
            transform: scale(1.05);
        }

        /* Property Content */
        .property-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .property-details {
            background: white;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #333;
        }

        .host-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .host-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(45deg, #ff5a5f, #ff8e91);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .host-details h3 {
            font-size: 1.2rem;
            margin-bottom: 0.3rem;
        }

        .host-details p {
            color: #666;
            font-size: 0.9rem;
        }

        .property-specs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .spec-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .spec-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #ff5a5f;
        }

        .spec-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.3rem;
        }

        .description {
            margin-bottom: 2rem;
            line-height: 1.8;
            color: #555;
        }

        .amenities-section {
            margin-bottom: 2rem;
        }

        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.8rem;
        }

        .amenity-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .amenity-icon {
            width: 24px;
            height: 24px;
            background: #ff5a5f;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
        }

        /* Booking Card */
        .booking-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 100px;
            height: fit-content;
        }

        .price-display {
            text-align: center;
            margin-bottom: 2rem;
        }

        .price-main {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
        }

        .price-unit {
            font-size: 1rem;
            color: #666;
        }

        .booking-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .date-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #555;
        }

        .form-input {
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

        .price-breakdown {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .price-total {
            border-top: 2px solid #e0e0e0;
            padding-top: 0.5rem;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .book-btn {
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

        /* Reviews Section */
        .reviews-section {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid #e0e0e0;
        }

        .reviews-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .review-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
        }

        .review-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .reviewer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .reviewer-info h4 {
            font-size: 1rem;
            margin-bottom: 0.2rem;
        }

        .review-date {
            font-size: 0.8rem;
            color: #666;
        }

        .review-rating {
            display: flex;
            gap: 0.2rem;
            margin-bottom: 0.5rem;
        }

        .review-text {
            color: #555;
            line-height: 1.6;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .property-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .booking-card {
                position: static;
            }

            .date-inputs {
                grid-template-columns: 1fr;
            }

            .property-specs {
                grid-template-columns: repeat(2, 1fr);
            }

            .amenities-grid {
                grid-template-columns: 1fr;
            }

            .reviews-grid {
                grid-template-columns: 1fr;
            }

            .nav-links {
                display: none;
            }

            .property-meta {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="container">
            <a href="index.php" class="logo">StayBnb</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="properties.php">Properties</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <a href="properties.php" class="back-btn">← Back to Properties</a>
        </nav>
    </header>

    <main class="container">
        <section class="property-header">
            <h1 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h1>
            <div class="property-meta">
                <div class="rating-badge">
                    <span class="stars">★</span>
                    <span><?php echo number_format($property['rating'], 1); ?></span>
                    <span>(<?php echo $property['review_count']; ?> reviews)</span>
                </div>
                <div class="location"><?php echo htmlspecialchars($property['full_location']); ?></div>
            </div>
        </section>

        <section class="image-gallery">
            <?php if (!empty($images)): ?>
                <img src="<?php echo $images[0]['image_url']; ?>" 
                     alt="<?php echo htmlspecialchars($property['title']); ?>" 
                     class="main-image" id="mainImage">
                
                <?php if (count($images) > 1): ?>
                    <div class="thumbnail-grid">
                        <?php foreach (array_slice($images, 1) as $image): ?>
                            <img src="<?php echo $image['image_url']; ?>" 
                                 alt="Property image" 
                                 class="thumbnail" 
                                 onclick="changeMainImage('<?php echo $image['image_url']; ?>')">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <img src="/placeholder.svg?height=400&width=800" 
                     alt="<?php echo htmlspecialchars($property['title']); ?>" 
                     class="main-image">
            <?php endif; ?>
        </section>

        <section class="property-content">
            <div class="property-details">
                <div class="host-info">
                    <div class="host-avatar">
                        <?php echo strtoupper(substr($property['host_first_name'], 0, 1)); ?>
                    </div>
                    <div class="host-details">
                        <h3>Hosted by <?php echo htmlspecialchars($property['host_first_name'] . ' ' . $property['host_last_name']); ?></h3>
                        <p><?php echo htmlspecialchars($property['property_type']); ?></p>
                    </div>
                </div>

                <div class="property-specs">
                    <div class="spec-item">
                        <div class="spec-number"><?php echo $property['max_guests']; ?></div>
                        <div class="spec-label">Guests</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-number"><?php echo $property['bedrooms']; ?></div>
                        <div class="spec-label">Bedrooms</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-number"><?php echo $property['beds']; ?></div>
                        <div class="spec-label">Beds</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-number"><?php echo $property['bathrooms']; ?></div>
                        <div class="spec-label">Bathrooms</div>
                    </div>
                </div>

                <div class="description">
                    <h2 class="section-title">About this place</h2>
                    <p><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
                </div>

                <?php if (!empty($amenities)): ?>
                    <div class="amenities-section">
                        <h2 class="section-title">What this place offers</h2>
                        <div class="amenities-grid">
                            <?php foreach ($amenities as $amenity): ?>
                                <div class="amenity-item">
                                    <div class="amenity-icon">✓</div>
                                    <span><?php echo htmlspecialchars($amenity['name']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="booking-card">
                <div class="price-display">
                    <span class="price-main">$<?php echo number_format($property['price_per_night'], 0); ?></span>
                    <span class="price-unit">/ night</span>
                </div>

                <form class="booking-form" id="bookingForm">
                    <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                    
                    <div class="date-inputs">
                        <div class="form-group">
                            <label>Check-in</label>
                            <input type="date" class="form-input" name="checkin" 
                                   value="<?php echo $_GET['checkin'] ?? ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Check-out</label>
                            <input type="date" class="form-input" name="checkout" 
                                   value="<?php echo $_GET['checkout'] ?? ''; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Guests</label>
                        <select class="form-input" name="guests" required>
                            <?php for ($i = 1; $i <= $property['max_guests']; $i++): ?>
                                <option value="<?php echo $i; ?>" 
                                        <?php echo (($_GET['guests'] ?? 1) == $i) ? 'selected' : ''; ?>>
                                    <?php echo $i; ?> guest<?php echo $i > 1 ? 's' : ''; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="price-breakdown" id="priceBreakdown" style="display: none;">
                        <div class="price-row">
                            <span>$<?php echo number_format($property['price_per_night'], 0); ?> × <span id="nightCount">0</span> nights</span>
                            <span id="subtotal">$0</span>
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
                            <span>Total</span>
                            <span id="totalPrice">$0</span>
                        </div>
                    </div>

                    <button type="submit" class="book-btn" id="bookBtn">Check Availability</button>
                </form>
            </div>
        </section>

        <?php if (!empty($reviews)): ?>
            <section class="reviews-section">
                <div class="reviews-header">
                    <h2 class="section-title">Reviews</h2>
                    <div class="rating-badge">
                        <span class="stars">★</span>
                        <span><?php echo number_format($property['rating'], 1); ?></span>
                        <span>(<?php echo $property['review_count']; ?> reviews)</span>
                    </div>
                </div>

                <div class="reviews-grid">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="reviewer-avatar">
                                    <?php echo strtoupper(substr($review['first_name'], 0, 1)); ?>
                                </div>
                                <div class="reviewer-info">
                                    <h4><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></h4>
                                    <div class="review-date"><?php echo date('F Y', strtotime($review['created_at'])); ?></div>
                                </div>
                            </div>
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="stars"><?php echo $i <= $review['rating'] ? '★' : '☆'; ?></span>
                                <?php endfor; ?>
                            </div>
                            <p class="review-text"><?php echo htmlspecialchars($review['review_text']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <script>
        // Property data for calculations
        const propertyData = {
            pricePerNight: <?php echo $property['price_per_night']; ?>,
            cleaningFee: <?php echo $property['cleaning_fee']; ?>,
            serviceFee: <?php echo $property['service_fee']; ?>
        };

        // Change main image
        function changeMainImage(imageUrl) {
            document.getElementById('mainImage').src = imageUrl;
        }

        // Calculate price breakdown
        function calculatePrice() {
            const checkinInput = document.querySelector('input[name="checkin"]');
            const checkoutInput = document.querySelector('input[name="checkout"]');
            const priceBreakdown = document.getElementById('priceBreakdown');
            
            if (checkinInput.value && checkoutInput.value) {
                const checkin = new Date(checkinInput.value);
                const checkout = new Date(checkoutInput.value);
                const timeDiff = checkout.getTime() - checkin.getTime();
                const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
                
                if (nights > 0) {
                    const subtotal = nights * propertyData.pricePerNight;
                    const total = subtotal + propertyData.cleaningFee + propertyData.serviceFee;
                    
                    document.getElementById('nightCount').textContent = nights;
                    document.getElementById('subtotal').textContent = '$' + subtotal.toLocaleString();
                    document.getElementById('totalPrice').textContent = '$' + total.toLocaleString();
                    
                    priceBreakdown.style.display = 'block';
                    document.getElementById('bookBtn').textContent = 'Reserve';
                } else {
                    priceBreakdown.style.display = 'none';
                    document.getElementById('bookBtn').textContent = 'Check Availability';
                }
            } else {
                priceBreakdown.style.display = 'none';
                document.getElementById('bookBtn').textContent = 'Check Availability';
            }
        }

        // Set minimum dates
        const today = new Date().toISOString().split('T')[0];
        document.querySelector('input[name="checkin"]').min = today;

        // Add event listeners
        document.querySelector('input[name="checkin"]').addEventListener('change', function() {
            const checkinDate = new Date(this.value);
            checkinDate.setDate(checkinDate.getDate() + 1);
            document.querySelector('input[name="checkout"]').min = checkinDate.toISOString().split('T')[0];
            calculatePrice();
        });

        document.querySelector('input[name="checkout"]').addEventListener('change', calculatePrice);

        // Handle booking form submission
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const params = new URLSearchParams();
            
            for (let [key, value] of formData.entries()) {
                params.append(key, value);
            }
            
            window.location.href = 'booking.php?' + params.toString();
        });

        // Initial price calculation if dates are pre-filled
        calculatePrice();
    </script>
</body>
</html>
