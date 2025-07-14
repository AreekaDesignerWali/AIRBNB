<?php
// Enable comprehensive error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Start output buffering to catch any unexpected output
ob_start();

// Initialize variables to prevent undefined variable errors
$properties = [];
$property_types = [];
$filters = [];
$error = '';
$debug_info = [];
$db_connected = false;

try {
    $debug_info[] = "Starting properties.php execution";
    $debug_info[] = "PHP Version: " . PHP_VERSION;
    $debug_info[] = "Current time: " . date('Y-m-d H:i:s');
    
    // Check if db.php file exists
    if (!file_exists('db.php')) {
        throw new Exception("Database configuration file (db.php) not found in current directory: " . __DIR__);
    }
    
    $debug_info[] = "db.php file found, attempting to include";
    
    // Include database file with error handling
    try {
        require_once 'db.php';
        $debug_info[] = "db.php included successfully";
    } catch (Exception $include_error) {
        throw new Exception("Failed to include db.php: " . $include_error->getMessage());
    }
    
    // Check if database connection exists
    if (!isset($db)) {
        throw new Exception("Database object (\$db) not found after including db.php");
    }
    
    $debug_info[] = "Database object found";
    
    // Test database connection
    try {
        $test_connection = $db->getConnection();
        if ($test_connection) {
            $db_connected = true;
            $debug_info[] = "Database connection test successful";
        } else {
            throw new Exception("Database connection test failed - no connection object");
        }
    } catch (Exception $conn_error) {
        throw new Exception("Database connection test failed: " . $conn_error->getMessage());
    }
    
    // Get and sanitize search parameters
    $filters = [
        'city' => isset($_GET['location']) ? trim($_GET['location']) : '',
        'min_price' => isset($_GET['min_price']) ? (int)$_GET['min_price'] : '',
        'max_price' => isset($_GET['max_price']) ? (int)$_GET['max_price'] : '',
        'guests' => isset($_GET['guests']) ? (int)$_GET['guests'] : '',
        'property_type' => isset($_GET['property_type']) ? (int)$_GET['property_type'] : '',
        'sort' => isset($_GET['sort']) ? trim($_GET['sort']) : 'rating'
    ];
    
    $debug_info[] = "Filters processed: " . json_encode($filters);
    
    // Get properties with error handling
    try {
        $debug_info[] = "Attempting to fetch properties";
        $properties = $db->getProperties($filters);
        $debug_info[] = "Properties fetched successfully. Count: " . count($properties);
    } catch (Exception $prop_error) {
        $error = "Failed to fetch properties: " . $prop_error->getMessage();
        $debug_info[] = "ERROR fetching properties: " . $prop_error->getMessage();
        $properties = []; // Ensure it's an array
    }
    
    // Get property types with error handling
    try {
        $debug_info[] = "Attempting to fetch property types";
        $property_types = $db->getPropertyTypes();
        $debug_info[] = "Property types fetched successfully. Count: " . count($property_types);
    } catch (Exception $types_error) {
        $error .= ($error ? " | " : "") . "Failed to fetch property types: " . $types_error->getMessage();
        $debug_info[] = "ERROR fetching property types: " . $types_error->getMessage();
        $property_types = []; // Ensure it's an array
    }
    
} catch (Exception $main_error) {
    $error = "System Error: " . $main_error->getMessage();
    $debug_info[] = "FATAL ERROR: " . $main_error->getMessage();
    
    // Ensure arrays are initialized even on error
    $properties = [];
    $property_types = [];
    $filters = [
        'city' => '',
        'min_price' => '',
        'max_price' => '',
        'guests' => '',
        'property_type' => '',
        'sort' => 'rating'
    ];
}

// Clean output buffer
ob_end_clean();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Properties - StayBnb</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Debug Panel Styles */
        .debug-panel {
            background: #1a1a1a;
            color: #00ff00;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.85rem;
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
        }

        .debug-toggle {
            background: #007bff;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            margin: 0.5rem 0;
            font-weight: bold;
        }

        .system-status {
            background: #e3f2fd;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            border-left: 4px solid #2196f3;
        }

        .status-item {
            display: inline-block;
            margin-right: 1rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .status-ok {
            background: #c8e6c9;
            color: #2e7d32;
        }

        .status-error {
            background: #ffcdd2;
            color: #c62828;
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

        /* Alert Styles */
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

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        /* Search Bar */
        .search-section {
            background: white;
            padding: 2rem 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .search-container {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .search-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
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
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }

        .form-input:focus {
            border-color: #ff5a5f;
        }

        .search-btn {
            background: #ff5a5f;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background 0.3s;
            height: fit-content;
        }

        .search-btn:hover {
            background: #e04e53;
        }

        /* Filters */
        .filters-section {
            background: white;
            padding: 1.5rem 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .filters-container {
            display: flex;
            gap: 1.5rem;
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
            padding: 0.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.3s;
        }

        .filter-select:focus, .filter-input:focus {
            border-color: #ff5a5f;
        }

        /* Results Section */
        .results-section {
            padding: 2rem 0;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .results-count {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }

        .clear-filters {
            background: transparent;
            color: #ff5a5f;
            border: 2px solid #ff5a5f;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .clear-filters:hover {
            background: #ff5a5f;
            color: white;
        }

        /* Properties Grid */
        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        .property-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .property-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
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
            line-height: 1.4;
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
            gap: 0.3rem;
        }

        .stars {
            color: #ffc107;
            font-size: 1.1rem;
        }

        .rating-text {
            font-size: 0.9rem;
            color: #666;
        }

        .property-price {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .price-main {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
        }

        .price-unit {
            font-size: 0.9rem;
            font-weight: 400;
            color: #666;
        }

        .book-btn {
            background: #ff5a5f;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .book-btn:hover {
            background: #e04e53;
        }

        /* No Results */
        .no-results {
            text-align: center;
            padding: 4rem 0;
        }

        .no-results h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #666;
        }

        .no-results p {
            color: #888;
            margin-bottom: 2rem;
        }

        /* Fallback/Error State */
        .error-state {
            text-align: center;
            padding: 4rem 0;
            background: white;
            border-radius: 15px;
            margin: 2rem 0;
        }

        .error-state h2 {
            color: #dc3545;
            margin-bottom: 1rem;
        }

        .error-state p {
            color: #666;
            margin-bottom: 2rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .search-form {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .filters-container {
                flex-direction: column;
                align-items: stretch;
            }

            .results-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .properties-grid {
                grid-template-columns: 1fr;
            }

            .nav-links {
                display: none;
            }

            .property-specs {
                flex-direction: column;
                gap: 0.5rem;
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
        </nav>
    </header>

    <main class="container">
        <!-- Debug Controls -->
        <button class="debug-toggle" onclick="toggleDebug()">🔧 Toggle Debug Info</button>
        
        <!-- System Status -->
        <div class="system-status">
            <strong>System Status:</strong>
            <span class="status-item <?php echo file_exists('db.php') ? 'status-ok' : 'status-error'; ?>">
                DB File: <?php echo file_exists('db.php') ? '✓ Found' : '✗ Missing'; ?>
            </span>
            <span class="status-item <?php echo $db_connected ? 'status-ok' : 'status-error'; ?>">
                DB Connection: <?php echo $db_connected ? '✓ Connected' : '✗ Failed'; ?>
            </span>
            <span class="status-item <?php echo empty($error) ? 'status-ok' : 'status-error'; ?>">
                Errors: <?php echo empty($error) ? '✓ None' : '✗ Present'; ?>
            </span>
            <span class="status-item status-ok">
                Properties: <?php echo count($properties); ?> loaded
            </span>
        </div>

        <!-- Debug Panel -->
        <div class="debug-panel" id="debugPanel" style="display: none;">
            <strong>🐛 DEBUG INFORMATION:</strong>
            <?php foreach ($debug_info as $info): ?>
<?php echo htmlspecialchars($info); ?>
            <?php endforeach; ?>

            <strong>📊 SYSTEM INFO:</strong>
            PHP Version: <?php echo PHP_VERSION; ?>
            Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>
            Document Root: <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?>
            Current Directory: <?php echo __DIR__; ?>
            Script Name: <?php echo $_SERVER['SCRIPT_NAME'] ?? 'Unknown'; ?>
            
            <strong>🔍 REQUEST DATA:</strong>
            GET Parameters: <?php echo empty($_GET) ? 'None' : htmlspecialchars(http_build_query($_GET)); ?>
            Request Method: <?php echo $_SERVER['REQUEST_METHOD'] ?? 'Unknown'; ?>
            Request URI: <?php echo $_SERVER['REQUEST_URI'] ?? 'Unknown'; ?>
            
            <strong>📁 FILE CHECKS:</strong>
            db.php exists: <?php echo file_exists('db.php') ? 'YES' : 'NO'; ?>
            index.php exists: <?php echo file_exists('index.php') ? 'YES' : 'NO'; ?>
            Current file: <?php echo __FILE__; ?>
        </div>

        <!-- Error Display -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <strong>⚠️ System Error:</strong><br>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Main Content -->
        <?php if (!$db_connected): ?>
            <div class="error-state">
                <h2>🔌 Database Connection Failed</h2>
                <p>Unable to connect to the database. Please check your configuration.</p>
                <div class="alert alert-info">
                    <strong>Troubleshooting Steps:</strong><br>
                    1. Verify db.php file exists and has correct credentials<br>
                    2. Check if MySQL service is running<br>
                    3. Verify database name and user permissions<br>
                    4. Check server error logs for more details
                </div>
            </div>
        <?php else: ?>
            <!-- Search Section -->
            <section class="search-section">
                <div class="search-container">
                    <form class="search-form" id="searchForm">
                        <div class="form-group">
                            <label>Where</label>
                            <input type="text" class="form-input" name="location" 
                                   placeholder="Search destinations" 
                                   value="<?php echo htmlspecialchars($filters['city']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Check-in</label>
                            <input type="date" class="form-input" name="checkin" 
                                   value="<?php echo htmlspecialchars($_GET['checkin'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Check-out</label>
                            <input type="date" class="form-input" name="checkout" 
                                   value="<?php echo htmlspecialchars($_GET['checkout'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Guests</label>
                            <input type="number" class="form-input" name="guests" 
                                   placeholder="Add guests" min="1" max="16" 
                                   value="<?php echo $filters['guests'] ?: ''; ?>">
                        </div>
                        
                        <button type="submit" class="search-btn">Search</button>
                    </form>
                </div>
            </section>

            <!-- Filters Section -->
            <section class="filters-section">
                <div class="filters-container">
                    <div class="filter-group">
                        <label>Property Type</label>
                        <select class="filter-select" id="propertyType">
                            <option value="">All Types</option>
                            <?php if (!empty($property_types)): ?>
                                <?php foreach ($property_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>" 
                                            <?php echo ($filters['property_type'] == $type['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Min Price</label>
                        <input type="number" class="filter-input" id="minPrice" 
                               placeholder="$0" min="0" value="<?php echo $filters['min_price'] ?: ''; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Max Price</label>
                        <input type="number" class="filter-input" id="maxPrice" 
                               placeholder="$1000" min="0" value="<?php echo $filters['max_price'] ?: ''; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Sort By</label>
                        <select class="filter-select" id="sortBy">
                            <option value="rating" <?php echo ($filters['sort'] == 'rating') ? 'selected' : ''; ?>>Best Rated</option>
                            <option value="price_low" <?php echo ($filters['sort'] == 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo ($filters['sort'] == 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- Results Section -->
            <section class="results-section">
                <div class="results-header">
                    <div class="results-count">
                        <?php echo count($properties); ?> properties found
                        <?php if (!empty($filters['city'])): ?>
                            in "<?php echo htmlspecialchars($filters['city']); ?>"
                        <?php endif; ?>
                    </div>
                    <button class="clear-filters" onclick="clearAllFilters()">Clear All Filters</button>
                </div>

                <?php if (empty($properties) && empty($error)): ?>
                    <div class="no-results">
                        <h3>No properties found</h3>
                        <p>Try adjusting your search criteria or filters to find more options.</p>
                        <button class="search-btn" onclick="clearAllFilters()">Clear Filters</button>
                    </div>
                <?php elseif (!empty($properties)): ?>
                    <div class="properties-grid">
                        <?php foreach ($properties as $property): ?>
                            <div class="property-card" onclick="viewProperty(<?php echo $property['id']; ?>)">
                                <img src="<?php echo $property['primary_image'] ?: '/placeholder.svg?height=250&width=350'; ?>" 
                                     alt="<?php echo htmlspecialchars($property['title']); ?>" 
                                     class="property-image"
                                     onerror="this.src='/placeholder.svg?height=250&width=350'">
                                
                                <div class="property-info">
                                    <div class="property-location"><?php echo htmlspecialchars($property['location'] ?? 'Location not available'); ?></div>
                                    <h3 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h3>
                                    <div class="property-type"><?php echo htmlspecialchars($property['property_type'] ?? 'Property'); ?></div>
                                    
                                    <div class="property-details">
                                        <div class="property-specs">
                                            <span><?php echo $property['max_guests']; ?> guests</span>
                                            <span><?php echo $property['bedrooms']; ?> bedrooms</span>
                                            <span><?php echo $property['bathrooms']; ?> baths</span>
                                        </div>
                                        
                                        <div class="property-rating">
                                            <span class="stars">★</span>
                                            <span><?php echo number_format($property['rating'], 1); ?></span>
                                            <span class="rating-text">(<?php echo $property['review_count']; ?>)</span>
                                        </div>
                                    </div>
                                    
                                    <div class="property-price">
                                        <div>
                                            <span class="price-main">$<?php echo number_format($property['price_per_night'], 0); ?></span>
                                            <span class="price-unit">/ night</span>
                                        </div>
                                        <button class="book-btn" onclick="event.stopPropagation(); bookProperty(<?php echo $property['id']; ?>)">
                                            Book Now
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <script>
        // Debug panel toggle
        function toggleDebug() {
            const panel = document.getElementById('debugPanel');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        }

        // Show debug panel automatically if there are errors
        <?php if ($error): ?>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('debugPanel').style.display = 'block';
            });
        <?php endif; ?>

        // Search form submission
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            applySearch();
        });

        function applySearch() {
            const formData = new FormData(document.getElementById('searchForm'));
            const params = new URLSearchParams();
            
            // Add search parameters
            for (let [key, value] of formData.entries()) {
                if (value) params.append(key, value);
            }
            
            // Add filter parameters
            const propertyType = document.getElementById('propertyType').value;
            const minPrice = document.getElementById('minPrice').value;
            const maxPrice = document.getElementById('maxPrice').value;
            const sortBy = document.getElementById('sortBy').value;
            
            if (propertyType) params.append('property_type', propertyType);
            if (minPrice) params.append('min_price', minPrice);
            if (maxPrice) params.append('max_price', maxPrice);
            if (sortBy) params.append('sort', sortBy);
            
            window.location.href = 'properties.php?' + params.toString();
        }

        // Filter functionality
        function applyFilters() {
            // Get current search parameters
            const urlParams = new URLSearchParams(window.location.search);
            const params = new URLSearchParams();
            
            // Preserve search parameters
            if (urlParams.get('location')) params.append('location', urlParams.get('location'));
            if (urlParams.get('checkin')) params.append('checkin', urlParams.get('checkin'));
            if (urlParams.get('checkout')) params.append('checkout', urlParams.get('checkout'));
            if (urlParams.get('guests')) params.append('guests', urlParams.get('guests'));
            
            // Add filter parameters
            const propertyType = document.getElementById('propertyType').value;
            const minPrice = document.getElementById('minPrice').value;
            const maxPrice = document.getElementById('maxPrice').value;
            const sortBy = document.getElementById('sortBy').value;
            
            if (propertyType) params.append('property_type', propertyType);
            if (minPrice) params.append('min_price', minPrice);
            if (maxPrice) params.append('max_price', maxPrice);
            if (sortBy) params.append('sort', sortBy);
            
            window.location.href = 'properties.php?' + params.toString();
        }

        // Add event listeners to filters
        document.getElementById('propertyType')?.addEventListener('change', applyFilters);
        document.getElementById('minPrice')?.addEventListener('change', applyFilters);
        document.getElementById('maxPrice')?.addEventListener('change', applyFilters);
        document.getElementById('sortBy')?.addEventListener('change', applyFilters);

        // Clear all filters
        function clearAllFilters() {
            window.location.href = 'properties.php';
        }

        // View property function
        function viewProperty(propertyId) {
            window.location.href = 'property-detail.php?id=' + propertyId;
        }

        // Book property function
        function bookProperty(propertyId) {
            const urlParams = new URLSearchParams(window.location.search);
            const params = new URLSearchParams();
            
            params.append('property_id', propertyId);
            if (urlParams.get('checkin')) params.append('checkin', urlParams.get('checkin'));
            if (urlParams.get('checkout')) params.append('checkout', urlParams.get('checkout'));
            if (urlParams.get('guests')) params.append('guests', urlParams.get('guests'));
            
            window.location.href = 'booking.php?' + params.toString();
        }

        // Set minimum dates
        const today = new Date().toISOString().split('T')[0];
        const checkinInput = document.querySelector('input[name="checkin"]');
        if (checkinInput) {
            checkinInput.min = today;
            
            checkinInput.addEventListener('change', function() {
                const checkinDate = new Date(this.value);
                checkinDate.setDate(checkinDate.getDate() + 1);
                const checkoutInput = document.querySelector('input[name="checkout"]');
                if (checkoutInput) {
                    checkoutInput.min = checkinDate.toISOString().split('T')[0];
                }
            });
        }

        // Error handling for images
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('.property-image');
            images.forEach(img => {
                img.addEventListener('error', function() {
                    this.src = '/placeholder.svg?height=250&width=350';
                });
            });
        });
    </script>
</body>
</html>
