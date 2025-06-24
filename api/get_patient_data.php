<?php
require_once __DIR__ . '/../config/init.php';

header('Content-Type: application/json');

// Get direct database connection
$db = new PDO("sqlite:" . __DIR__ . "/../database/dialysis_management.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("PRAGMA foreign_keys = ON");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $action = sanitize_input($_GET['action'] ?? '');
    
    switch ($action) {
        case 'list':
            // Get all patients for dropdown lists
            $stmt = $db->prepare("SELECT id, file_number, name_english FROM patients ORDER BY name_english ASC");
            $stmt->execute();
            $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'patients' => $patients]);
            break;
            
        case 'details':
            // Get detailed patient information
            $patient_id = (int)($_GET['id'] ?? 0);
            
            if (empty($patient_id)) {
                throw new Exception('Patient ID is required');
            }
            
            $stmt = $db->prepare("SELECT * FROM patients WHERE id = ?");
            $stmt->execute([$patient_id]);
            $patient = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$patient) {
                throw new Exception('Patient not found');
            }
            
            echo json_encode(['success' => true, 'patient' => $patient]);
            break;
            
        case 'search':
        default:
            // Search patients by file number or name
            $query = sanitize_input($_GET['q'] ?? $_GET['search'] ?? '');
            
            if (strlen($query) < 2) {
                echo json_encode(['success' => true, 'patients' => []]);
                break;
            }
            
            $searchTerm = '%' . $query . '%';
            
            $sql = "SELECT id, file_number, name_english, age, gender, room_number, group_type 
                    FROM patients 
                    WHERE file_number LIKE ? OR name_english LIKE ? 
                    ORDER BY name_english ASC 
                    LIMIT 10";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$searchTerm, $searchTerm]);
            $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'patients' => $patients]);
            break;
    }
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
