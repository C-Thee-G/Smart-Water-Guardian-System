<?php
/**
 * IoT Data Ingestion API
 * Receives data from ESP32 devices
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/firebase.php';
require_once '../../middleware/cors.php';

// Handle CORS
$cors = new CORS();
$cors->handle();

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON data'
    ]);
    exit;
}

// Validate API key
if (!isset($data['api_key']) || $data['api_key'] !== API_KEY) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid API key'
    ]);
    exit;
}

// Validate required fields
$required_fields = ['meter_id', 'flow_rate', 'volume'];
foreach ($required_fields as $field) {
    if (!isset($data[$field])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => ucfirst($field) . ' is required'
        ]);
        exit;
    }
}

// Get database connection
$db = Database::getInstance()->getConnection();
$firebase = new Firebase();

try {
    // Sanitize input
    $meter_id = trim($data['meter_id']);
    $flow_rate = floatval($data['flow_rate']);
    $volume = floatval($data['volume']);
    $battery = isset($data['battery']) ? floatval($data['battery']) : 100;
    $timestamp = isset($data['timestamp']) ? $data['timestamp'] : date('Y-m-d H:i:s');
    
    // Get meter details
    $meter_query = "SELECT m.*, p.user_id, p.id as property_id 
                    FROM meters m 
                    LEFT JOIN properties p ON p.id = m.property_id 
                    WHERE m.meter_id = :meter_id AND m.is_active = 1";
    $stmt = $db->prepare($meter_query);
    $stmt->bindParam(':meter_id', $meter_id);
    $stmt->execute();
    $meter = $stmt->fetch();
    
    if (!$meter) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Meter not found or inactive'
        ]);
        exit;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Insert reading
    $reading_query = "INSERT INTO readings (meter_id, flow_rate, volume, battery_level, reading_time) 
                      VALUES (:meter_id, :flow_rate, :volume, :battery, :reading_time)";
    $stmt = $db->prepare($reading_query);
    $stmt->bindParam(':meter_id', $meter['id']);
    $stmt->bindParam(':flow_rate', $flow_rate);
    $stmt->bindParam(':volume', $volume);
    $stmt->bindParam(':battery', $battery);
    $stmt->bindParam(':reading_time', $timestamp);
    $stmt->execute();
    
    $reading_id = $db->lastInsertId();
    
    // Update meter status
    $update_query = "UPDATE meters SET 
                     last_reading = :volume,
                     last_reading_time = :reading_time,
                     battery_level = :battery,
                     signal_strength = :signal,
                     status = 'online',
                     updated_at = NOW()
                     WHERE id = :meter_id";
    $stmt = $db->prepare($update_query);
    $stmt->bindParam(':volume', $volume);
    $stmt->bindParam(':reading_time', $timestamp);
    $stmt->bindParam(':battery', $battery);
    $signal = isset($data['signal']) ? intval($data['signal']) : 80;
    $stmt->bindParam(':signal', $signal);
    $stmt->bindParam(':meter_id', $meter['id']);
    $stmt->execute();
    
    // Push to Firebase for real-time updates
    $firebase_data = [
        'flow_rate' => $flow_rate,
        'volume' => $volume,
        'battery' => $battery,
        'timestamp' => $timestamp,
        'status' => 'online',
        'signal' => $signal
    ];
    $firebase->pushRealtimeReading($meter_id, $firebase_data);
    
    // Check for anomalies
    $anomalies = [];
    $alerts_created = [];
    
    // 1. Check for leak (continuous high flow)
    if ($flow_rate > LEAK_FLOW_RATE_THRESHOLD && $flow_rate <= CRITICAL_LEAK_FLOW_RATE) {
        $anomalies[] = [
            'type' => 'leak',
            'severity' => 'high',
            'message' => 'Possible leak detected: ' . number_format($flow_rate, 1) . ' L/min',
            'threshold' => LEAK_FLOW_RATE_THRESHOLD
        ];
    } elseif ($flow_rate > CRITICAL_LEAK_FLOW_RATE) {
        $anomalies[] = [
            'type' => 'critical_leak',
            'severity' => 'critical',
            'message' => 'CRITICAL LEAK: ' . number_format($flow_rate, 1) . ' L/min - Immediate action required!',
            'threshold' => CRITICAL_LEAK_FLOW_RATE
        ];
    }
    
    // 2. Check for high usage
    if ($flow_rate > HIGH_USAGE_THRESHOLD && $flow_rate <= LEAK_FLOW_RATE_THRESHOLD) {
        $anomalies[] = [
            'type' => 'high_usage',
            'severity' => 'warning',
            'message' => 'High water usage detected: ' . number_format($flow_rate, 1) . ' L/min',
            'threshold' => HIGH_USAGE_THRESHOLD
        ];
    }
    
    // 3. Check battery
    if ($battery < LOW_BATTERY_THRESHOLD) {
        $anomalies[] = [
            'type' => 'battery_low',
            'severity' => 'warning',
            'message' => 'Low battery: ' . number_format($battery, 1) . '% remaining - Please replace soon',
            'threshold' => LOW_BATTERY_THRESHOLD
        ];
    }
    
    // Process anomalies
    foreach ($anomalies as $anomaly) {
        $alert_id = 'ALT_' . uniqid();
        
        $alert_query = "INSERT INTO alerts (id, user_id, meter_id, alert_type, message, severity, status) 
                        VALUES (:id, :user_id, :meter_id, :type, :message, :severity, 'active')";
        $stmt = $db->prepare($alert_query);
        $stmt->bindParam(':id', $alert_id);
        $stmt->bindParam(':user_id', $meter['user_id']);
        $stmt->bindParam(':meter_id', $meter['id']);
        $stmt->bindParam(':type', $anomaly['type']);
        $stmt->bindParam(':message', $anomaly['message']);
        $stmt->bindParam(':severity', $anomaly['severity']);
        $stmt->execute();
        
        $alerts_created[] = $alert_id;
        
        // Push alert to Firebase
        $firebase_alert = [
            'id' => $alert_id,
            'type' => $anomaly['type'],
            'message' => $anomaly['message'],
            'severity' => $anomaly['severity'],
            'timestamp' => date('Y-m-d H:i:s'),
            'read' => false
        ];
        $firebase->pushAlert($meter['user_id'], $firebase_alert);
        
        // Send notification (email, push, SMS)
        // sendNotification($meter['user_id'], $anomaly['message'], $anomaly['type']);
    }
    
    // Commit transaction
    $db->commit();
    
    // Update consumer dashboard
    $this->updateConsumerDashboard($meter['user_id'], $meter['property_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Data ingested successfully',
        'reading_id' => $reading_id,
        'anomalies_detected' => count($anomalies),
        'alerts_created' => $alerts_created,
        'alerts' => $anomalies
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    error_log("Ingest error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error processing data: ' . $e->getMessage()
    ]);
}

/**
 * Update consumer dashboard data
 */
function updateConsumerDashboard($user_id, $property_id) {
    // This would update aggregated data for the consumer dashboard
    // Could be implemented as a separate function
}
?>
