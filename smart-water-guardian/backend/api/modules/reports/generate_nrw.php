<?php
/**
 * Generate NRW (Non-Revenue Water) Report
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

// Get input
$data = json_decode(file_get_contents('php://input'), true);

$start_date = isset($data['start_date']) ? $data['start_date'] : date('Y-m-01');
$end_date = isset($data['end_date']) ? $data['end_date'] : date('Y-m-t');
$suburb = isset($data['suburb']) ? $data['suburb'] : null;

// Get database connection
$db = Database::getInstance()->getConnection();

try {
    // Build query conditions
    $conditions = "ws.supply_date BETWEEN :start_date AND :end_date";
    $params = [
        ':start_date' => $start_date,
        ':end_date' => $end_date
    ];
    
    if ($suburb) {
        $conditions .= " AND ws.suburb = :suburb";
        $params[':suburb'] = $suburb;
    }
    
    // Total water supplied
    $supplied_query = "SELECT COALESCE(SUM(ws.volume_supplied), 0) as total_supplied
                       FROM water_supply ws
                       WHERE $conditions";
    $stmt = $db->prepare($supplied_query);
    foreach ($params as $key => $value) {
        $stmt->bindParam($key, $value);
    }
    $stmt->execute();
    $total_supplied = $stmt->fetch()['total_supplied'];
    
    // Total billed (consumed)
    $billed_query = "SELECT COALESCE(SUM(r.volume), 0) as total_billed
                     FROM readings r
                     WHERE r.reading_time >= :start_date 
                     AND r.reading_time <= :end_date + INTERVAL 1 DAY";
    $stmt = $db->prepare($billed_query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $total_billed = $stmt->fetch()['total_billed'];
    
    // NRW calculation
    $nrw_volume = $total_supplied - $total_billed;
    $nrw_percentage = $total_supplied > 0 ? 
        round(($nrw_volume / $total_supplied) * 100, 2) : 0;
    
    // Breakdown by suburb
    $breakdown_query = "SELECT 
                            ws.suburb,
                            SUM(ws.volume_supplied) as supplied,
                            COALESCE(SUM(r.volume), 0) as billed,
                            (SUM(ws.volume_supplied) - COALESCE(SUM(r.volume), 0)) as nrw,
                            ROUND(((SUM(ws.volume_supplied) - COALESCE(SUM(r.volume), 0)) / 
                                   SUM(ws.volume_supplied)) * 100, 2) as nrw_percentage
                        FROM water_supply ws
                        LEFT JOIN readings r ON DATE(r.reading_time) = ws.supply_date
                        WHERE $conditions
                        GROUP BY ws.suburb
                        ORDER BY nrw DESC";
    $stmt = $db->prepare($breakdown_query);
    foreach ($params as $key => $value) {
        $stmt->bindParam($key, $value);
    }
    $stmt->execute();
    $breakdown = $stmt->fetchAll();
    
    // Daily NRW trend
    $trend_query = "SELECT 
                        ws.supply_date,
                        ws.volume_supplied as supplied,
                        COALESCE(SUM(r.volume), 0) as billed,
                        (ws.volume_supplied - COALESCE(SUM(r.volume), 0)) as nrw,
                        ROUND(((ws.volume_supplied - COALESCE(SUM(r.volume), 0)) / 
                               ws.volume_supplied) * 100, 2) as nrw_percentage
                    FROM water_supply ws
                    LEFT JOIN readings r ON DATE(r.reading_time) = ws.supply_date
                    WHERE $conditions
                    GROUP BY ws.supply_date
                    ORDER BY ws.supply_date ASC";
    $stmt = $db->prepare($trend_query);
    foreach ($params as $key => $value) {
        $stmt->bindParam($key, $value);
    }
    $stmt->execute();
    $daily_trend = $stmt->fetchAll();
    
    // Monthly summary (if date range > 30 days)
    $monthly_summary = [];
    $date_diff = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
    if ($date_diff > 30) {
        $monthly_query = "SELECT 
                              DATE_FORMAT(ws.supply_date, '%Y-%m') as month,
                              SUM(ws.volume_supplied) as supplied,
                              COALESCE(SUM(r.volume), 0) as billed,
                              (SUM(ws.volume_supplied) - COALESCE(SUM(r.volume), 0)) as nrw,
                              ROUND(((SUM(ws.volume_supplied) - COALESCE(SUM(r.volume), 0)) / 
                                     SUM(ws.volume_supplied)) * 100, 2) as nrw_percentage
                          FROM water_supply ws
                          LEFT JOIN readings r ON DATE(r.reading_time) = ws.supply_date
                          WHERE $conditions
                          GROUP BY DATE_FORMAT(ws.supply_date, '%Y-%m')
                          ORDER BY month ASC";
        $stmt = $db->prepare($monthly_query);
        foreach ($params as $key => $value) {
            $stmt->bindParam($key, $value);
        }
        $stmt->execute();
        $monthly_summary = $stmt->fetchAll();
    }
    
    // Financial impact
    $avg_tariff = 25.00; // Average tariff per kL
    $nrw_financial_impact = $nrw_volume * $avg_tariff / 1000; // Convert to kL
    
    echo json_encode([
        'success' => true,
        'report' => [
            'generated_at' => date('Y-m-d H:i:s'),
            'period' => [
                'start' => $start_date,
                'end' => $end_date,
                'days' => $date_diff + 1
            ],
            'summary' => [
                'total_supplied' => round($total_supplied, 2),
                'total_billed' => round($total_billed, 2),
                'nrw_volume' => round($nrw_volume, 2),
                'nrw_percentage' => $nrw_percentage,
                'financial_impact' => round($nrw_financial_impact, 2)
            ],
            'breakdown' => $breakdown,
            'daily_trend' => $daily_trend,
            'monthly_summary' => $monthly_summary
        ]
    ]);
    
} catch (Exception $e) {
    error_log("NRW report error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error generating report: ' . $e->getMessage()
    ]);
}
?>
