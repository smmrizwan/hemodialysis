<?php
require_once '../config/init.php';

header('Content-Type: application/json');

// Get direct database connection
$db = new PDO("sqlite:" . __DIR__ . "/../database/dialysis_management.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("PRAGMA foreign_keys = ON");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $patient_id = (int)$_POST['patient_id'];
    
    if (empty($patient_id)) {
        throw new Exception('Patient ID is required');
    }
    
    // Check if patient exists
    $stmt = $db->prepare("SELECT id FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Patient not found');
    }
    
    // Collect complications data
    $complications = [
        'hypotension' => isset($_POST['hypotension']) ? 1 : 0,
        'hypertension' => isset($_POST['hypertension']) ? 1 : 0,
        'muscle_cramps' => isset($_POST['muscle_cramps']) ? 1 : 0,
        'nausea_vomiting' => isset($_POST['nausea_vomiting']) ? 1 : 0,
        'headache' => isset($_POST['headache']) ? 1 : 0,
        'chest_pain' => isset($_POST['chest_pain']) ? 1 : 0,
        'pruritus' => isset($_POST['pruritus']) ? 1 : 0,
        'fever_chills' => isset($_POST['fever_chills']) ? 1 : 0,
        'dyspnea' => isset($_POST['dyspnea']) ? 1 : 0,
        'seizures' => isset($_POST['seizures']) ? 1 : 0,
        'arrhythmias' => isset($_POST['arrhythmias']) ? 1 : 0
    ];
    
    // Check if record exists
    $stmt = $db->prepare("SELECT id FROM dialysis_complications WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing record
        $sql = "UPDATE dialysis_complications SET 
                hypotension = ?, hypertension = ?, muscle_cramps = ?, nausea_vomiting = ?, 
                headache = ?, chest_pain = ?, pruritus = ?, fever_chills = ?, 
                dyspnea = ?, seizures = ?, arrhythmias = ?, updated_at = CURRENT_TIMESTAMP
                WHERE patient_id = ?";
        
        $params = array_values($complications);
        $params[] = $patient_id;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode(['success' => true, 'message' => 'Complications data updated successfully']);
        
    } else {
        // Insert new record
        $sql = "INSERT INTO dialysis_complications (
                patient_id, hypotension, hypertension, muscle_cramps, nausea_vomiting, 
                headache, chest_pain, pruritus, fever_chills, dyspnea, seizures, arrhythmias
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [$patient_id];
        $params = array_merge($params, array_values($complications));
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode(['success' => true, 'message' => 'Complications data saved successfully']);
    }
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
