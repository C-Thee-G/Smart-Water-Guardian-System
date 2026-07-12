<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/database.php';
require_once '../../middleware/auth_middleware.php';

$auth = new AuthMiddleware();
$user = $auth->validateToken();

if ($user['role'] !== 'consumer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$db = Database::getInstance()->getConnection();
$user_id = $user['user_id'];

try {
    // Get user properties
    $properties_query = "SELECT p.*, m.id as meter_db_id, m.meter_id, m.status, 
                         m.battery_level, m.last_reading, m.last_reading_time
                         FROM properties p
                         LEFT JOIN meters m ON m.property_id = p.id
                         WHERE p.user_id = :user_id";
    $stmt = $db->prepare($properties_query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $properties = $stmt->fetchAll();
    
    $dashboard_data = [];
    
    foreach ($properties as $property) {
        $property_data = [
            'property_id' => $property['id'],
            'address' => $property['address'],
            'meter_id' => $property['meter_id'],
            'meter_status' => $property['status'] ?? 'not_installed',
            'battery' => $property['battery_level'] ?? null,
            'current_usage' => [
                'flow_rate' => 0,
                'today_total' => 0,
                'unit' => 'L/min'
            ]
        ];
        
        if ($property['meter_db_id']) {
            // Get current flow rate
            $current_query = "SELECT flow_rate, volume, reading_time 
                              FROM readings 
                              WHERE meter_id = :meter_id 
                              ORDER BY reading_time DESC LIMIT 1";
            $stmt = $db->prepare($current_query);
            $stmt->bindParam(':meter_id', $property['meter_db_id']);
            $stmt->execute();
            $current = $stmt->fetch();
            
            if ($current) {
                $property_data['current_usage']['flow_rate'] = floatval($current['flow_rate']);
                $property_data['last_update'] = $current['reading_time'];
            }
            
            // Get today's total
            $today_query = "SELECT COALESCE(SUM(volume), 0) as total 
                            FROM readings 
                            WHERE meter_id = :meter_id 
                            AND DATE(reading_time) = CURDATE()";
            $stmt = $db->prepare($today_query);
            $stmt->bindParam(':meter_id', $property['meter_db_id']);
            $stmt->execute();
            $today = $stmt->fetch();
            $property_data['current_usage']['today_total'] = floatval($today['total']);
            
            // Get weekly usage
            $weekly_query = "SELECT DATE(reading_time) as date, SUM(volume) as total 
                             FROM readings 
                             WHERE meter_id = :meter_id 
                             AND reading_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                             GROUP BY DATE(reading_time)
                             ORDER BY date ASC";
            $stmt = $db->prepare($weekly_query);
            $stmt->bindParam(':meter_id', $property['meter_db_id']);
            $stmt->execute();
            $property_data['weekly_usage'] = $stmt->fetchAll();
            
            // Get monthly usage
            $monthly_query = "SELECT DAY(reading_time) as day, SUM(volume) as total 
                              FROM readings 
                              WHERE meter_id = :meter_id 
                              AND MONTH(reading_time) = MONTH(CURDATE())
                              AND YEAR(reading_time) = YEAR(CURDATE())
                              GROUP BY DAY(reading_time)
                              ORDER BY day ASC";
            $stmt = $db->prepare($monthly_query);
            $stmt->bindParam(':meter_id', $property['meter_db_id']);
            $stmt->execute();
            $property_data['monthly_usage'] = $stmt->fetchAll();
            
            // Get active alerts
            $alerts_query = "SELECT id, alert_type, message, severity, created_at 
                             FROM alerts 
                             WHERE user_id = :user_id AND status = 'active'
                             ORDER BY created_at DESC LIMIT 5";
            $stmt = $db->prepare($alerts_query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $property_data['active_alerts'] = $stmt->fetchAll();
        }
        
        $dashboard_data['properties'][] = $property_data;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $dashboard_data
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
