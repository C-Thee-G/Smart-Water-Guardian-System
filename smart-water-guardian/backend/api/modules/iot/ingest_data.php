<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/database.php';
require_once '../../config/firebase.php';
require_once '../../config/constants.php';

$data = json_decode(file_get_contents('php://input'), true);

// Verify API key
if (!isset($data['api_key']) || $data['api_key'] !== API_KEY) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid API key']);
    exit;
}

// Validate required fields
$required = ['meter_id', 'flow_rate', 'volume'];
foreach ($required as $field) {
    if (!isset($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => ucfirst($field) . ' required']);
        exit;
    }
}

$db = Database::getInstance()->getConnection();

try {
    $meter_id = $data['meter_id'];
    $flow_rate = floatval($data['flow_rate']);
    $volume = floatval($data['volume']);
    $battery = floatval($data['battery'] ?? 100);
    $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
    
    // Get meter details
    $meter_query = "SELECT m.*, p.user_id FROM meters m 
                    LEFT JOIN properties p ON p.id = m.property_id 
                    WHERE m.meter_id = :meter_id AND m.is_active = 1";
    $stmt = $db->prepare($meter_query);
    $stmt->bindParam(':meter_id', $meter_id);
    $stmt->execute();
    $meter = $stmt->fetch();
    
    if (!$meter) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Meter not found or inactive']);
        exit;
    }
    
    // Insert reading
    $reading_query = "INSERT INTO readings (meter_id, flow_rate, volume, battery_level, reading_time) 
                      VALUES (:meter_id, :flow_rate, :volume, :battery, :timestamp)";
    $stmt = $db->prepare($reading_query);
    $stmt->bindParam(':meter_id', $meter['id']);
    $stmt->bindParam(':flow_rate', $flow_rate);
    $stmt->bindParam(':volume', $volume);
    $stmt->bindParam(':battery', $battery);
    $stmt->bindParam(':timestamp', $timestamp);
    $stmt->execute();
    
    // Update meter
    $update_query = "UPDATE meters SET last_reading = :volume, last_reading_time = :timestamp, 
                     battery_level = :battery, status = 'online' WHERE id = :meter_id";
    $stmt = $db->prepare($update_query);
    $stmt->bindParam(':volume', $volume);
    $stmt->bindParam(':timestamp', $timestamp);
    $stmt->bindParam(':battery', $battery);
    $stmt->bindParam(':meter_id', $meter['id']);
    $stmt->execute();
    
    // Push to Firebase
    $firebase = new Firebase();
    $firebase->pushRealtimeReading($meter_id, [
        'flow_rate' => $flow_rate,
        'volume' => $volume,
        'battery' => $battery,
        'timestamp' => $timestamp,
        'status' => 'online'
    ]);
    
    // Check for anomalies
    $anomalies = [];
    
    // Check for leak
    if ($flow_rate > LEAK_FLOW_RATE_THRESHOLD && $flow_rate <= CRITICAL_LEAK_FLOW_RATE) {
        $anomalies[] = [
            'type' => 'leak',
            'severity' => 'high',
            'message' => 'Possible leak detected: ' . $flow_rate . ' L/min'
        ];
    } elseif ($flow_rate > CRITICAL_LEAK_FLOW_RATE) {
        $anomalies[] = [
            'type' => 'critical_leak',
            'severity' => 'critical',
            'message' => 'CRITICAL LEAK: ' . $flow_rate . ' L/min - Immediate action required!'
        ];
    }
    
    // Check battery
    if ($battery < LOW_BATTERY_THRESHOLD) {
        $anomalies[] = [
            'type' => 'battery_low',
            'severity' => 'warning',
            'message' => 'Low battery: ' . $battery . '% remaining'
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
        
        // Push alert to Firebase
        $firebase->pushAlert($meter['user_id'], [
            'id' => $alert_id,
            'type' => $anomaly['type'],
            'message' => $anomaly['message'],
            'severity' => $anomaly['severity'],
            'timestamp' => date('Y-m-d H:i:s'),
            'read' => false
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Data ingested successfully',
        'anomalies_detected' => count($anomalies),
        'alerts' => $anomalies
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
