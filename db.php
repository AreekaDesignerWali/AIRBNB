<?php
// Database configuration and connection
class Database {
    private $host = 'localhost';
    private $dbname = 'dbx5qtolrljaph';
    private $username = 'uc7ggok7oyoza';
    private $password = 'gqypavorhbbc';
    private $pdo;
    
    public function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }a
    public function getConnection() {
        return $this->pdo;
    }
    
    // Get all properties with filters
    public function getProperties($filters = []) {
        $sql = "SELECT p.*, pt.name as property_type, 
                       pi.image_url as primary_image,
                       CONCAT(p.city, ', ', p.state) as location
                FROM properties p 
                LEFT JOIN property_types pt ON p.property_type_id = pt.id
                LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
                WHERE p.is_active = 1";
        
        $params = [];
        
        if (!empty($filters['city'])) {
            $sql .= " AND (p.city LIKE :city OR p.state LIKE :city OR p.country LIKE :city)";
            $params[':city'] = '%' . $filters['city'] . '%';
        }
        
        if (!empty($filters['min_price'])) {
            $sql .= " AND p.price_per_night >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $sql .= " AND p.price_per_night <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
        
        if (!empty($filters['guests'])) {
            $sql .= " AND p.max_guests >= :guests";
            $params[':guests'] = $filters['guests'];
        }
        
        if (!empty($filters['property_type'])) {
            $sql .= " AND p.property_type_id = :property_type";
            $params[':property_type'] = $filters['property_type'];
        }
        
        // Add sorting
        $sort = $filters['sort'] ?? 'rating';
        switch ($sort) {
            case 'price_low':
                $sql .= " ORDER BY p.price_per_night ASC";
                break;
            case 'price_high':
                $sql .= " ORDER BY p.price_per_night DESC";
                break;
            case 'rating':
                $sql .= " ORDER BY p.rating DESC, p.review_count DESC";
                break;
            default:
                $sql .= " ORDER BY p.created_at DESC";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    // Get property by ID with all details
    public function getPropertyById($id) {
        $sql = "SELECT p.*, pt.name as property_type,
                       u.first_name as host_first_name, u.last_name as host_last_name,
                       CONCAT(p.city, ', ', p.state, ', ', p.country) as full_location
                FROM properties p 
                LEFT JOIN property_types pt ON p.property_type_id = pt.id
                LEFT JOIN users u ON p.host_id = u.id
                WHERE p.id = ? AND p.is_active = 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // Get property images
    public function getPropertyImages($property_id) {
        $sql = "SELECT * FROM property_images WHERE property_id = ? ORDER BY sort_order";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$property_id]);
        return $stmt->fetchAll();
    }
    
    // Get property amenities
    public function getPropertyAmenities($property_id) {
        $sql = "SELECT a.* FROM amenities a 
                JOIN property_amenities pa ON a.id = pa.amenity_id 
                WHERE pa.property_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$property_id]);
        return $stmt->fetchAll();
    }
    
    // Get property reviews
    public function getPropertyReviews($property_id, $limit = 10) {
        $sql = "SELECT r.*, u.first_name, u.last_name, u.profile_image
                FROM reviews r 
                JOIN users u ON r.reviewer_id = u.id
                WHERE r.property_id = ? 
                ORDER BY r.created_at DESC 
                LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$property_id, $limit]);
        return $stmt->fetchAll();
    }
    
    // FIXED: Create booking with proper parameter handling
    public function createBookingFixed($property_id, $guest_id, $check_in_date, $check_out_date, 
                                      $guests, $total_nights, $subtotal, $cleaning_fee, $service_fee, 
                                      $total_amount, $guest_name, $guest_email, $guest_phone, $special_requests) {
        
        $sql = "INSERT INTO bookings (
                    property_id, guest_id, check_in_date, check_out_date, 
                    guests, total_nights, subtotal, cleaning_fee, service_fee, total_amount, 
                    guest_name, guest_email, guest_phone, special_requests, booking_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $property_id,
                $guest_id,
                $check_in_date,
                $check_out_date,
                $guests,
                $total_nights,
                $subtotal,
                $cleaning_fee,
                $service_fee,
                $total_amount,
                $guest_name,
                $guest_email,
                $guest_phone,
                $special_requests
            ]);
            
            if ($result) {
                return $this->pdo->lastInsertId();
            } else {
                throw new Exception("Failed to insert booking record");
            }
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    
    // Original create booking method (kept for compatibility)
    public function createBooking($data) {
        $sql = "INSERT INTO bookings (property_id, guest_id, check_in_date, check_out_date, 
                guests, total_nights, subtotal, cleaning_fee, service_fee, total_amount, 
                guest_name, guest_email, guest_phone, special_requests) 
                VALUES (:property_id, :guest_id, :check_in_date, :check_out_date, 
                :guests, :total_nights, :subtotal, :cleaning_fee, :service_fee, :total_amount,
                :guest_name, :guest_email, :guest_phone, :special_requests)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }
    
    // Get booking by ID
    public function getBookingById($id) {
        $sql = "SELECT b.*, p.title as property_title, p.address, p.city, p.state
                FROM bookings b 
                JOIN properties p ON b.property_id = p.id
                WHERE b.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // Get all property types
    public function getPropertyTypes() {
        $sql = "SELECT * FROM property_types ORDER BY name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Check availability
    public function checkAvailability($property_id, $check_in, $check_out) {
        $sql = "SELECT COUNT(*) as booking_count FROM bookings 
                WHERE property_id = ? 
                AND booking_status IN ('confirmed', 'pending')
                AND (
                    (check_in_date <= ? AND check_out_date > ?) OR
                    (check_in_date < ? AND check_out_date >= ?) OR
                    (check_in_date >= ? AND check_out_date <= ?)
                )";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $property_id,
            $check_in, $check_in,
            $check_out, $check_out,
            $check_in, $check_out
        ]);
        
        $result = $stmt->fetch();
        return $result['booking_count'] == 0;
    }
}

// Create global database instance
$db = new Database();
?>
