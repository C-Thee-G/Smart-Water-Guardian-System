<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/database.php';
require_once '../../middleware/auth_middleware.php';

$auth = new AuthMiddleware();
$user = $auth->validateToken();

if ($user['role'] !== 'municipal' && $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Total active meters
    $meters_query = "SELECT COUNT(*) as total FROM meters WHERE is_active = 1";
    $stmt = $db->query($meters_query);
    $total_meters = $stmt->fetch()['total'];
    
    // Online meters
    $online_query = "SELECT COUNT(*) as online FROM meters WHERE status = 'online' AND is_active = 1";
    $stmt = $db->query($online_query);
    $online_meters = $stmt->fetch()['online'];
    
    // Total consumers
    $consumers_query = "SELECT COUNT(*) as total FROM users WHERE role = 'consumer' AND is_active = 1";
    $stmt = $db->query($consumers_query);
    $total_consumers = $stmt->fetch()['total'];
    
    // Today's total water usage
    $today_query = "SELECT COALESCE(SUM(volume), 0) as total 
                    FROM readings 
                    WHERE DATE(reading_time) = CURDATE()";
    $stmt = $db->query($today_query);
    $today_usage = $stmt->fetch()['total'];
    
    // NRW Calculation (last 30 days)
    $nrw_query = "SELECT 
                    COALESCE(SUM(ws.volume_supplied), 0) as total_supplied,
                    COALESCE(SUM(r.volume), 0) as total_billed
                  FROM water_supply ws
                  LEFT JOIN readings r ON DATE(r.reading_time) = ws.supply_date
                  WHERE ws.supply_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $stmt = $db->query($nrw_query);
    $nrw_data = $stmt->fetch();
    
    $total_supplied = $nrw_data['total_supplied'];
    $total_billed = $nrw_data['total_billed'];
    $nrw_percentage = $total_supplied > 0 ? 
        round((($total_supplied - $total_billed) / $total_supplied) * 100, 2) : 0;
    $nrw_volume = $total_supplied - $total_billed;
    
    // Top 10 NRW areas
    $top_areas_query = "SELECT 
                          ws.suburb,
                          SUM(ws.volume_supplied) as supplied,
                          COALESCE(SUM(r.volume), 0) as billed
                        FROM water_supply ws
                        LEFT JOIN readings r ON DATE(r.reading_time) = ws.supply_date
                        WHERE ws.supply_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                        GROUP BY ws.suburb
                        ORDER BY (SUM(ws.volume_supplied) - COALESCE(SUM(r.volume), 0)) DESC
                        LIMIT 10";
    $stmt = $db->query($top_areas_query);
    $top_areas = $stmt->fetchAll();
    
    // Daily usage trend (last 7 days)
    $trend_query = "SELECT 
                      DATE(reading_time) as date,
                      SUM(volume) as total
                    FROM readings
                    WHERE reading_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    GROUP BY DATE(reading_time)
                    ORDER BY date ASC";
    $stmt = $db->query($trend_query);
    $daily_trend = $stmt->fetchAll();
    
    // Recent alerts
    $alerts_query = "SELECT a.*, u.name, u.surname 
                     FROM alerts a
                     JOIN users u ON u.id = a.user_id
                     WHERE a.status = 'active'
                     ORDER BY a.created_at DESC
                     LIMIT 10";
    $stmt = $db->query($alerts_query);
    $recent_alerts = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'summary' => [
                'total_meters' => $total_meters,
                'online_meters' => $online_meters,
                'total_consumers' => $total_consumers,
                'today_usage' => round($today_usage, 2),
                'nrw_percentage' => $nrw_percentage,
                'nrw_volume' => round($nrw_volume, 2)
            ],
            'top_nrw_areas' => $top_areas,
            'daily_trend' => $daily_trend,
            'recent_alerts' => $recent_alerts
        ]
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
