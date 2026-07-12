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

$data = json_decode(file_get_contents('php://input'), true);
$start_date = $data['start_date'] ?? date('Y-m-01');
$end_date = $data['end_date'] ?? date('Y-m-t');

$db = Database::getInstance()->getConnection();

try {
    // Total water supplied
    $supplied_query = "SELECT 
                         suburb,
                         SUM(volume_supplied) as total_supplied
                       FROM water_supply
                       WHERE supply_date BETWEEN :start_date AND :end_date
                       GROUP BY suburb";
    $stmt = $db->prepare($supplied_query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $supplied_data = $stmt->fetchAll();
    
    // Total billed usage
    $billed_query = "SELECT 
                       COALESCE(SUM(volume), 0) as total_billed
                     FROM readings
                     WHERE DATE(reading_time) BETWEEN :start_date AND :end_date";
    $stmt = $db->prepare($billed_query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $billed = $stmt->fetch();
    
    $total_billed = $billed['total_billed'];
    $total_supplied = array_sum(array_column($supplied_data, 'total_supplied'));
    
    $nrw_volume = $total_supplied - $total_billed;
    $nrw_percentage = $total_supplied > 0 ? 
        round(($nrw_volume / $total_supplied) * 100, 2) : 0;
    
    // Get consumption breakdown by suburb
    $breakdown_query = "SELECT 
                          ws.suburb,
                          SUM(ws.volume_supplied) as supplied,
                          COALESCE(SUM(r.volume), 0) as billed,
                          (SUM(ws.volume_supplied) - COALESCE(SUM(r.volume), 0)) as nrw,
                          ROUND(((SUM(ws.volume_supplied) - COALESCE(SUM(r.volume), 0)) / 
                                 SUM(ws.volume_supplied)) * 100, 2) as nrw_percentage
                        FROM water_supply ws
                        LEFT JOIN readings r ON DATE(r.reading_time) = ws.supply_date
                        WHERE ws.supply_date BETWEEN :start_date AND :end_date
                        GROUP BY ws.suburb
                        ORDER BY nrw DESC";
    $stmt = $db->prepare($breakdown_query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $breakdown = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'report' => [
            'period' => [
                'start' => $start_date,
                'end' => $end_date
            ],
            'summary' => [
                'total_supplied' => round($total_supplied, 2),
                'total_billed' => round($total_billed, 2),
                'nrw_volume' => round($nrw_volume, 2),
                'nrw_percentage' => $nrw_percentage
            ],
            'breakdown' => $breakdown
        ]
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
