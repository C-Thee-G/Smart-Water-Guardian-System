<?php
/**
 * Consumer Dashboard API
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../middleware/auth_middleware.php';
require_once '../../middleware/cors.php';

// Handle CORS
$cors = new CORS();
$cors->handle();

// Authenticate user
$auth = new AuthMiddleware(['consumer']);
$user = $auth->validate();

if (!$user) {
    exit;
}

// Get database connection
$db = Database::getInstance()->getConnection();

try {
    $user_id = $user['user_id'];
    
    // Get user properties with meters
    $properties_query = "SELECT 
                            p.*,
                            m.id as meter_db_id,
                            m.meter_id,
                            m.status as meter_status,
                            m.battery_level,
                            m.last_reading,
                            m.last_reading_time,
                            m.firmware_version
                         FROM properties p
                         LEFT JOIN meters m ON m.property_id = p.id
                         WHERE p.user_id = :user_id
                         ORDER BY p.created_at DESC";
    $stmt = $db->prepare($properties_query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $properties = $stmt->fetchAll();
    
    $dashboard_data = [
        'user' => [
            'id' => $user['user_id'],
            'email' => $user['email'],
            'role' => $user['role']
        ],
        'properties' => []
    ];
    
    foreach ($properties as $property) {
        $property_data = [
            'id' => $property['id'],
            'address' => $property['address'],
            'property_type' => $property['property_type'],
            'latitude' => $property['latitude'],
            'longitude' => $property['longitude'],
            'meter' => [
                'id' => $property['meter_id'],
                'db_id' => $property['meter_db_id'],
                'status' => $property['meter_status'] ?? 'not_installed',
                'battery' => $property['battery_level'],
                'firmware' => $property['firmware_version']
            ],
            'current_usage' => [
                'flow_rate' => 0,
                'today_total' => 0,
                'week_total' => 0,
                'month_total' => 0,
                'unit' => 'L/min'
            ],
            'history' => [
                'weekly' => [],
                'monthly' => [],
                'yearly' => []
            ],
            'alerts' => []
        ];
        
        if ($property['meter_db_id']) {
            $meter_db_id = $property['meter_db_id'];
            
            // Get current flow rate (latest reading)
            $current_query = "SELECT flow_rate, volume, reading_time 
                              FROM readings 
                              WHERE meter_id = :meter_id 
                              ORDER BY reading_time DESC LIMIT 1";
            $stmt = $db->prepare($current_query);
            $stmt->bindParam(':meter_id', $meter_db_id);
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
            $stmt->bindParam(':meter_id', $meter_db_id);
            $stmt->execute();
            $today = $stmt->fetch();
            $property_data['current_usage']['today_total'] = floatval($today['total']);
            
            // Get week total
            $week_query = "SELECT COALESCE(SUM(volume), 0) as total 
                           FROM readings 
                           WHERE meter_id = :meter_id 
                           AND reading_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmt = $db->prepare($week_query);
            $stmt->bindParam(':meter_id', $meter_db_id);
            $stmt->execute();
            $week = $stmt->fetch();
            $property_data['current_usage']['week_total'] = floatval($week['total']);
            
            // Get month total
            $month_query = "SELECT COALESCE(SUM(volume), 0) as total 
                            FROM readings 
                            WHERE meter_id = :meter_id 
                            AND MONTH(reading_time) = MONTH(CURDATE())
                            AND YEAR(reading_time) = YEAR(CURDATE())";
            $stmt = $db->prepare($month_query);
            $stmt->bindParam(':meter_id', $meter_db_id);
            $stmt->execute();
            $month = $stmt->fetch();
            $property_data['current_usage']['month_total'] = floatval($month['total']);
            
            // Get daily usage for the last 7 days (chart)
            $daily_query = "SELECT 
                               DATE(reading_time) as date,
                               SUM(volume) as total
                            FROM readings 
                            WHERE meter_id = :meter_id 
                            AND reading_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                            GROUP BY DATE(reading_time)
                            ORDER BY date ASC";
            $stmt = $db->prepare($daily_query);
            $stmt->bindParam(':meter_id', $meter_db_id);
            $stmt->execute();
            $property_data['history']['weekly'] = $stmt->fetchAll();
            
            // Get monthly usage (last 30 days)
            $monthly_query = "SELECT 
                                 DATE(reading_time) as date,
                                 SUM(volume) as total
                              FROM readings 
                              WHERE meter_id = :meter_id 
                              AND reading_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                              GROUP BY DATE(reading_time)
                              ORDER BY date ASC";
            $stmt = $db->prepare($monthly_query);
            $stmt->bindParam(':meter_id', $meter_db_id);
            $stmt->execute();
            $property_data['history']['monthly'] = $stmt->fetchAll();
            
            // Get active alerts
            $alerts_query = "SELECT id, alert_type, message, severity, created_at 
                             FROM alerts 
                             WHERE user_id = :user_id 
                             AND meter_id = :meter_id 
                             AND status = 'active'
                             ORDER BY created_at DESC 
                             LIMIT 10";
            $stmt = $db->prepare($alerts_query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':meter_id', $meter_db_id);
            $stmt->execute();
            $property_data['alerts'] = $stmt->fetchAll();
        }
        
        $dashboard_data['properties'][] = $property_data;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $dashboard_data
    ]);
    
} catch (Exception $e) {
    error_log("Consumer dashboard error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading dashboard'
    ]);
}
?>
