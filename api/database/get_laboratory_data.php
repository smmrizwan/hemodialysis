<?php
require_once __DIR__ . '/../config/init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $patient_id = (int)($_GET['patient_id'] ?? 0);
    $action = sanitize_input($_GET['action'] ?? 'list');
    
    if (empty($patient_id)) {
        throw new Exception('Patient ID is required');
    }
    
    // Check if patient exists
    $stmt = $db->prepare("SELECT id FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Patient not found');
    }
    
    switch ($action) {
        case 'latest':
            // Get latest laboratory data
            $stmt = $db->prepare("SELECT * FROM laboratory_data WHERE patient_id = ? ORDER BY test_date DESC LIMIT 1");
            $stmt->execute([$patient_id]);
            $lab_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'laboratory_data' => $lab_data]);
            break;
            
        case 'trends':
            // Get laboratory data for trends (last 12 months)
            $stmt = $db->prepare("
                SELECT * FROM laboratory_data 
                WHERE patient_id = ? AND test_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                ORDER BY test_date ASC
            ");
            $stmt->execute([$patient_id]);
            $lab_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate trend data
            $trends = [
                'hb_trend' => [],
                'iron_trend' => [],
                'calcium_trend' => [],
                'phosphorus_trend' => [],
                'urr_trend' => [],
                'ktv_trend' => [],
                'dates' => []
            ];
            
            foreach ($lab_data as $lab) {
                $trends['dates'][] = $lab['test_date'];
                $trends['hb_trend'][] = (float)$lab['hb'];
                $trends['iron_trend'][] = (float)$lab['iron'];
                $trends['calcium_trend'][] = (float)$lab['calcium'];
                $trends['phosphorus_trend'][] = (float)$lab['phosphorus'];
                $trends['urr_trend'][] = (float)$lab['urr'];
                $trends['ktv_trend'][] = (float)$lab['kt_v'];
            }
            
            echo json_encode(['success' => true, 'trends' => $trends]);
            break;
            
        case 'summary':
            // Get laboratory summary statistics
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_tests,
                    AVG(hb) as avg_hb,
                    AVG(iron) as avg_iron,
                    AVG(tsat) as avg_tsat,
                    AVG(calcium) as avg_calcium,
                    AVG(phosphorus) as avg_phosphorus,
                    AVG(urr) as avg_urr,
                    AVG(kt_v) as avg_ktv,
                    MIN(test_date) as first_test,
                    MAX(test_date) as last_test
                FROM laboratory_data 
                WHERE patient_id = ?
            ");
            $stmt->execute([$patient_id]);
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get recent abnormal values
            $stmt = $db->prepare("
                SELECT test_date, 
                       CASE 
                           WHEN hb < 100 THEN CONCAT('Low Hb: ', hb, ' g/L')
                           WHEN hb > 120 THEN CONCAT('High Hb: ', hb, ' g/L')
                           ELSE NULL
                       END as hb_alert,
                       CASE 
                           WHEN tsat < 20 THEN CONCAT('Low TSAT: ', tsat, '%')
                           WHEN tsat > 50 THEN CONCAT('High TSAT: ', tsat, '%')
                           ELSE NULL
                       END as tsat_alert,
                       CASE 
                           WHEN urr < 65 THEN CONCAT('Low URR: ', urr, '%')
                           ELSE NULL
                       END as urr_alert,
                       CASE 
                           WHEN kt_v < 1.2 THEN CONCAT('Low Kt/V: ', kt_v)
                           ELSE NULL
                       END as ktv_alert
                FROM laboratory_data 
                WHERE patient_id = ? 
                ORDER BY test_date DESC 
                LIMIT 5
            ");
            $stmt->execute([$patient_id]);
            $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Filter out null alerts
            $active_alerts = [];
            foreach ($alerts as $alert) {
                $alert_messages = array_filter([
                    $alert['hb_alert'],
                    $alert['tsat_alert'], 
                    $alert['urr_alert'],
                    $alert['ktv_alert']
                ]);
                if (!empty($alert_messages)) {
                    $active_alerts[] = [
                        'date' => $alert['test_date'],
                        'alerts' => $alert_messages
                    ];
                }
            }
            
            echo json_encode([
                'success' => true, 
                'summary' => $summary,
                'alerts' => $active_alerts
            ]);
            break;
            
        case 'list':
        default:
            // Get all laboratory data for patient
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = (int)($_GET['offset'] ?? 0);
            
            $stmt = $db->prepare("
                SELECT * FROM laboratory_data 
                WHERE patient_id = ? 
                ORDER BY test_date DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$patient_id, $limit, $offset]);
            $lab_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM laboratory_data WHERE patient_id = ?");
            $stmt->execute([$patient_id]);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            echo json_encode([
                'success' => true, 
                'laboratory_data' => $lab_data,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ]);
            break;
    }
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
