<?php
/**
 * Municipal Dashboard API
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
$auth = new AuthMiddleware(['municipal', 'admin']);
$user = $auth->validate();

if (!$user) {
    exit;
}

// Get database connection
$db = Database::getInstance()->getConnection();

try {
    // === SUMMARY STATISTICS ===
    
    // Total active meters
    $meters_query = "SELECT COUNT(*) as total FROM meters WHERE is_active = 1";
    $stmt = $db->query($meters_query);
    $total_meters = $stmt->fetch()['total'];
    
    // Online meters
    $online_query = "SELECT COUNT(*) as online FROM meters 
                     WHERE status = 'online' AND is_active = 1";
    $stmt = $db->query($online_query);
    $online_meters = $stmt->fetch()['online'];
    
    // Offline meters
    $offline_query = "SELECT COUNT(*) as offline FROM meters 
                      WHERE status = 'offline' AND is_active = 1";
    $stmt = $db->query($offline_query);
    $offline_meters = $stmt->fetch()['offline'];
    
    // Total consumers
    $consumers_query = "SELECT COUNT(*) as total FROM users 
                        WHERE role = 'consumer' AND is_active = 1";
    $stmt = $db->query($consumers_query);
    $total_consumers = $stmt->fetch()['total'];
    
    // Today's total water usage
    $today_query = "SELECT COALESCE(SUM(volume), 0) as total 
                    FROM readings 
                    WHERE DATE(reading_time) = CURDATE()";
    $stmt = $db->query($today_query);
    $today_usage = $stmt->fetch()['total'];
    
    // This month's total usage
    $month_query = "SELECT COALESCE(SUM(volume), 0) as total 
                    FROM readings 
                    WHERE MONTH(reading_time) = MONTH(CURDATE())
                    AND YEAR(reading_time) = YEAR(CURDATE())";
    $stmt = $db->query($month_query);
    $month_usage = $stmt->fetch()['total'];
    
    // === NRW CALCULATION ===
    
    // Get total water supplied (last 30 days)
    $supplied_query = "SELECT COALESCE(SUM(volume_supplied), 0) as total 
                       FROM water_supply 
                       WHERE supply_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $stmt = $db->query($supplied_query);
    $total_supplied = $stmt->fetch()['total'];
    
    // Get total billed (consumed) (last 30 days)
    $billed_query = "SELECT COALESCE(SUM(volume), 0) as total 
                     FROM readings 
                     WHERE reading_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $stmt = $db->query($billed_query);
    $total_billed = $stmt->fetch()['total'];
    
    $nrw_volume = $total_supplied - $total_billed;
    $nrw_percentage = $total_supplied > 0 ? 
        round(($nrw_volume / $total_supplied) * 100, 2) : 0;
    
    // === TOP NRW AREAS ===
    $top_areas_query = "SELECT 
                            ws.suburb,
                            SUM(ws.volume_supplied) as supplied,
                            COALESCE(SUM(r.volume), 0) as billed,
                            (SUM(ws.volume_supplied) - COALESCE(SUM(r.volume), 0)) as nrw,
                            ROUND(((SUM(ws.volume_supplied) - COALESCE(SUM(r.volume), 0)) / 
                                   SUM(ws.volume_supplied)) * 100, 2) as nrw_percentage
                        FROM water_supply ws
                        LEFT JOIN readings r ON DATE(r.reading_time) = ws.supply_date
                        WHERE ws.supply_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                        GROUP BY ws.suburb
                        ORDER BY nrw DESC
                        LIMIT 10";
    $stmt = $db->query($top_areas_query);
    $top_areas = $stmt->fetchAll();
    
    // === DAILY USAGE TREND (last 7 days) ===
    $trend_query = "SELECT 
                        DATE(reading_time) as date,
                        COALESCE(SUM(volume), 0) as total
                    FROM readings
                    WHERE reading_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    GROUP BY DATE(reading_time)
                    ORDER BY date ASC";
    $stmt = $db->query($trend_query);
    $daily_trend = $stmt->fetchAll();
    
    // === HOURLY USAGE (last 24 hours) ===
    $hourly_query = "SELECT 
                         HOUR(reading_time) as hour,
                         COALESCE(SUM(volume), 0) as total
                     FROM readings
                     WHERE reading_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                     GROUP BY HOUR(reading_time)
                     ORDER BY hour ASC";
    $stmt = $db->query($hourly_query);
    $hourly_usage = $stmt->fetchAll();
    
    // === RECENT ALERTS ===
    $alerts_query = "SELECT 
                         a.*, 
                         u.name, 
                         u.surname,
                         u.email,
                         m.meter_id
                     FROM alerts a
                     JOIN users u ON u.id = a.user_id
                     LEFT JOIN meters m ON m.id = a.meter_id
                     WHERE a.status = 'active'
                     ORDER BY a.created_at DESC
                     LIMIT 20";
    $stmt = $db->query($alerts_query);
    $recent_alerts = $stmt->fetchAll();
    
    // === ALERT STATISTICS ===
    $alert_stats_query = "SELECT 
                             alert_type,
                             severity,
                             COUNT(*) as count
                         FROM alerts
                         WHERE status = 'active'
                         GROUP BY alert_type, severity";
    $stmt = $db->query($alert_stats_query);
    $alert_stats = $stmt->fetchAll();
    
    // === CONSUMPTION BY PROPERTY TYPE ===
    $type_query = "SELECT 
                       p.property_type,
                       COALESCE(SUM(r.volume), 0) as total_usage
                   FROM readings r
                   JOIN meters m ON m.id = r.meter_id
                   JOIN properties p ON p.id = m.property_id
                   WHERE r.reading_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                   GROUP BY p.property_type";
    $stmt = $db->query($type_query);
    $consumption_by_type = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'summary' => [
                'total_meters' => intval($total_meters),
                'online_meters' => intval($online_meters),
                'offline_meters' => intval($offline_meters),
                'online_percentage' => $total_meters > 0 ? 
                    round(($online_meters / $total_meters) * 100, 2) : 0,
                'total_consumers' => intval($total_consumers),
                'today_usage' => round($today_usage, 2),
                'month_usage' => round($month_usage, 2),
                'nrw_percentage' => $nrw_percentage,
                'nrw_volume' => round($nrw_volume, 2),
                'total_supplied' => round($total_supplied, 2),
                'total_billed' => round($total_billed, 2)
            ],
            'top_n
