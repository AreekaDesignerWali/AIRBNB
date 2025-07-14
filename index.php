<?php
require_once 'db.php';

// Get featured properties
$featured_properties = $db->getProperties(['sort' => 'rating']);
$featured_properties = array_slice($featured_properties, 0, 6);

// Get property types for filters
$property_types = $db->getPropertyTypes();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StayBnb - Find Your Perfect Stay</title>
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

        /* Header Styles */
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

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        /* Search Form */
        .search-container {
            background: white;
            border-radius: 50px;
            padding: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 800px;
            margin: 0 auto;
        }

        .search-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 1px;
            align-items: center;
        }

        .search-input {
            padding: 1rem 1.5rem;
            border: none;
            outline: none;
            font-size: 1rem;
            background: transparent;
        }

        .search-input:first-child {
            border-radius: 40px 0 0 40px;
        }

        .search-btn {
            background: #ff5a5f;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 40px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background 0.3s;
        }

        .search-btn:hover {
            background: #e04e53;
        }

        /* Filters */
        .filters {
            background: #f8f9fa;
            padding: 2rem 0;
        }

        .filter-container {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
        }

        .filter-select, .filter-input {
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }

        .filter-select:focus, .filter-input:focus {
            border-color: #ff5a5f;
        }

        /* Property Grid */
        .properties-section {
            padding: 4rem 0;
        }

        .section-title {
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 3rem;
            color: #333;
        }

        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .property-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .property-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
        }

        .property-info {
            padding: 1.5rem;
        }

        .property-location {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .property-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .property-type {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .property-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .property-specs {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .property-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stars {
            color: #ffc107;
        }

        .property-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
        }

        .price-unit {
            font-size: 0.9rem;
            font-weight: 400;
            color: #666;
        }

        /* Footer */
        footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 2rem 0;
            margin-top: 4rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .search-form {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .search-input {
                border-radius: 8px !important;
            }

            .filter-container {
                flex-direction: column;
                align-items: stretch;
            }

            .properties-grid {
                grid-template-columns: 1fr;
            }

            .nav-links {
                display: none;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #ff5a5f;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
        </nav>
    </header>

    <section class="hero">
        <div class="container">
            <h1>Find Your Perfect Stay</h1>
            <p>Discover amazing places to stay around the world</p>
            
            <div class="search-container">
                <form class="search-form" id="searchForm">
                    <input type="text" class="search-input" name="location" placeholder="Where are you going?" required>
                    <input type="date" class="search-input" name="checkin" required>
                    <input type="date" class="search-input" name="checkout" required>
                    <input type="number" class="search-input" name="guests" placeholder="Guests" min="1" max="16" value="1">
                    <button type="submit" class="search-btn">Search</button>
                </form>
            </div>
        </div>
    </section>

    <section class="filters">
        <div class="container">
            <div class="filter-container">
                <div class="filter-group">
                    <label>Property Type</label>
                    <select class="filter-select" id="propertyType">
                        <option value="">All Types</option>
                        <?php foreach ($property_types as $type): ?>
                            <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Min Price</label>
                    <input type="number" class="filter-input" id="minPrice" placeholder="$0" min="0">
                </div>
                
                <div class="filter-group">
                    <label>Max Price</label>
                    <input type="number" class="filter-input" id="maxPrice" placeholder="$1000" min="0">
                </div>
                
                <div class="filter-group">
                    <label>Sort By</label>
                    <select class="filter-select" id="sortBy">
                        <option value="rating">Best Rated</option>
                        <option value="price_low">Price: Low to High</option>
                        <option value="price_high">Price: High to Low</option>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <section class="properties-section">
        <div class="container">
            <h2 class="section-title">Featured Properties</h2>
            <div class="properties-grid" id="propertiesGrid">
                <?php foreach ($featured_properties as $property): ?>
                    <div class="property-card" onclick="viewProperty(<?php echo $property['id']; ?>)">
                        <img src="<?php echo $property['primary_image'] ?: '/placeholder.svg?height=250&width=350'; ?>" 
                             alt="<?php echo htmlspecialchars($property['title']); ?>" 
                             class="property-image">
                        
                        <div class="property-info">
                            <div class="property-location"><?php echo htmlspecialchars($property['location']); ?></div>
                            <h3 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h3>
                            <div class="property-type"><?php echo htmlspecialchars($property['property_type']); ?></div>
                            
                            <div class="property-details">
                                <div class="property-specs">
                                    <span><?php echo $property['max_guests']; ?> guests</span>
                                    <span><?php echo $property['bedrooms']; ?> bedrooms</span>
                                    <span><?php echo $property['bathrooms']; ?> baths</span>
                                </div>
                                
                                <div class="property-rating">
                                    <span class="stars">★</span>
                                    <span><?php echo number_format($property['rating'], 1); ?></span>
                                    <span>(<?php echo $property['review_count']; ?>)</span>
                                </div>
                            </div>
                            
                            <div class="property-price">
                                $<?php echo number_format($property['price_per_night'], 0); ?>
                                <span class="price-unit">/ night</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2024 StayBnb. All rights reserved. | Your perfect stay awaits.</p>
        </div>
    </footer>

    <script>
        // Search form submission
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const params = new URLSearchParams();
            
            for (let [key, value] of formData.entries()) {
                if (value) params.append(key, value);
            }
            
            window.location.href = 'properties.php?' + params.toString();
        });

        // Filter functionality
        function applyFilters() {
            const propertyType = document.getElementById('propertyType').value;
            const minPrice = document.getElementById('minPrice').value;
            const maxPrice = document.getElementById('maxPrice').value;
            const sortBy = document.getElementById('sortBy').value;
            
            const params = new URLSearchParams();
            if (propertyType) params.append('property_type', propertyType);
            if (minPrice) params.append('min_price', minPrice);
            if (maxPrice) params.append('max_price', maxPrice);
            if (sortBy) params.append('sort', sortBy);
            
            window.location.href = 'properties.php?' + params.toString();
        }

        // Add event listeners to filters
        document.getElementById('propertyType').addEventListener('change', applyFilters);
        document.getElementById('minPrice').addEventListener('change', applyFilters);
        document.getElementById('maxPrice').addEventListener('change', applyFilters);
        document.getElementById('sortBy').addEventListener('change', applyFilters);

        // View property function
        function viewProperty(propertyId) {
            window.location.href = 'property-detail.php?id=' + propertyId;
        }

        // Set minimum dates for check-in and check-out
        const today = new Date().toISOString().split('T')[0];
        document.querySelector('input[name="checkin"]').min = today;
        
        document.querySelector('input[name="checkin"]').addEventListener('change', function() {
            const checkinDate = new Date(this.value);
            checkinDate.setDate(checkinDate.getDate() + 1);
            document.querySelector('input[name="checkout"]').min = checkinDate.toISOString().split('T')[0];
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
