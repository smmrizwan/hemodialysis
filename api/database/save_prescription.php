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
    
    // Collect prescription data
    $prescription_data = [
        'dialysis_modality' => sanitize_input($_POST['dialysis_modality'] ?? 'Hemodialysis'),
        'dialyzer' => sanitize_input($_POST['dialyzer']),
        'frequency' => sanitize_input($_POST['frequency']),
        'duration' => !empty($_POST['duration']) ? (float)$_POST['duration'] : null,
        'vascular_access' => !empty($_POST['vascular_access']) ? sanitize_input($_POST['vascular_access']) : null,
        'heparin_initial' => !empty($_POST['heparin_initial']) ? (float)$_POST['heparin_initial'] : null,
        'heparin_maintenance' => !empty($_POST['heparin_maintenance']) ? (float)$_POST['heparin_maintenance'] : null,
        'blood_flow_rate' => !empty($_POST['blood_flow_rate']) ? (int)$_POST['blood_flow_rate'] : null,
        'dialysate_flow_rate' => !empty($_POST['dialysate_flow_rate']) ? (int)$_POST['dialysate_flow_rate'] : null,
        'dry_body_weight' => !empty($_POST['dry_body_weight']) ? (float)$_POST['dry_body_weight'] : null,
        'ultrafiltration' => !empty($_POST['ultrafiltration']) ? (float)$_POST['ultrafiltration'] : 13.0,
        'sodium' => !empty($_POST['sodium']) ? (float)$_POST['sodium'] : 135.0,
        'potassium' => !empty($_POST['potassium']) ? (float)$_POST['potassium'] : 2.0,
        'calcium' => !empty($_POST['calcium']) ? (float)$_POST['calcium'] : 1.5,
        'bicarbonate' => !empty($_POST['bicarbonate']) ? (float)$_POST['bicarbonate'] : 35.0,
        'catheter_lock' => !empty($_POST['catheter_lock']) ? sanitize_input($_POST['catheter_lock']) : null
    ];
    
    // Validate required fields
    if (empty($prescription_data['dialyzer']) || empty($prescription_data['frequency'])) {
        throw new Exception('Dialyzer and frequency are required fields');
    }
    
    // Validate numeric ranges
    if ($prescription_data['duration'] && ($prescription_data['duration'] < 2 || $prescription_data['duration'] > 8)) {
        throw new Exception('Duration must be between 2 and 8 hours');
    }
    
    if ($prescription_data['blood_flow_rate'] && ($prescription_data['blood_flow_rate'] < 200 || $prescription_data['blood_flow_rate'] > 500)) {
        throw new Exception('Blood flow rate must be between 200 and 500 ml/min');
    }
    
    if ($prescription_data['dialysate_flow_rate'] && ($prescription_data['dialysate_flow_rate'] < 300 || $prescription_data['dialysate_flow_rate'] > 800)) {
        throw new Exception('Dialysate flow rate must be between 300 and 800 ml/min');
    }
    
    if ($prescription_data['ultrafiltration'] && ($prescription_data['ultrafiltration'] < 0 || $prescription_data['ultrafiltration'] > 13)) {
        throw new Exception('Ultrafiltration rate must be between 0 and 13 ml/kg/hr');
    }
    
    if ($prescription_data['sodium'] && ($prescription_data['sodium'] < 130 || $prescription_data['sodium'] > 145)) {
        throw new Exception('Sodium must be between 130 and 145 mEq/L');
    }
    
    if ($prescription_data['potassium'] && ($prescription_data['potassium'] < 0 || $prescription_data['potassium'] > 4)) {
        throw new Exception('Potassium must be between 0 and 4 mmol/L');
    }
    
    if ($prescription_data['calcium'] && ($prescription_data['calcium'] < 1.0 || $prescription_data['calcium'] > 2.0)) {
        throw new Exception('Calcium must be between 1.0 and 2.0 mmol/L');
    }
    
    if ($prescription_data['bicarbonate'] && ($prescription_data['bicarbonate'] < 25 || $prescription_data['bicarbonate'] > 40)) {
        throw new Exception('Bicarbonate must be between 25 and 40 mmol/L');
    }
    
    // Check if record exists
    $stmt = $db->prepare("SELECT id FROM hd_prescription WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing record
        $sql = "UPDATE hd_prescription SET 
                dialysis_modality = ?, dialyzer = ?, frequency = ?, duration = ?,
                vascular_access = ?, heparin_initial = ?, heparin_maintenance = ?,
                blood_flow_rate = ?, dialysate_flow_rate = ?, dry_body_weight = ?,
                ultrafiltration = ?, sodium = ?, potassium = ?, calcium = ?,
                bicarbonate = ?, catheter_lock = ?, updated_at = CURRENT_TIMESTAMP
                WHERE patient_id = ?";
        
        $params = array_values($prescription_data);
        $params[] = $patient_id;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode(['success' => true, 'message' => 'Prescription updated successfully']);
        
    } else {
        // Insert new record
        $sql = "INSERT INTO hd_prescription (
                patient_id, dialysis_modality, dialyzer, frequency, duration,
                vascular_access, heparin_initial, heparin_maintenance,
                blood_flow_rate, dialysate_flow_rate, dry_body_weight,
                ultrafiltration, sodium, potassium, calcium,
                bicarbonate, catheter_lock
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [$patient_id];
        $params = array_merge($params, array_values($prescription_data));
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode(['success' => true, 'message' => 'Prescription saved successfully']);
    }
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
