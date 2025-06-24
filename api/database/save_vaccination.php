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
    
    // Collect vaccination data
    $vaccination_data = [
        'hepatitis_b_completed' => isset($_POST['hepatitis_b_completed']) ? 1 : 0,
        'hepatitis_b_date' => !empty($_POST['hepatitis_b_date']) ? sanitize_input($_POST['hepatitis_b_date']) : null,
        'hepatitis_b_series' => !empty($_POST['hepatitis_b_series']) ? sanitize_input($_POST['hepatitis_b_series']) : null,
        'flu_vaccine_completed' => isset($_POST['flu_vaccine_completed']) ? 1 : 0,
        'flu_vaccine_date' => !empty($_POST['flu_vaccine_date']) ? sanitize_input($_POST['flu_vaccine_date']) : null,
        'ppv23_completed' => isset($_POST['ppv23_completed']) ? 1 : 0,
        'ppv23_date' => !empty($_POST['ppv23_date']) ? sanitize_input($_POST['ppv23_date']) : null,
        'rsv_completed' => isset($_POST['rsv_completed']) ? 1 : 0,
        'rsv_date' => !empty($_POST['rsv_date']) ? sanitize_input($_POST['rsv_date']) : null,
        'rsv_recommendation' => !empty($_POST['rsv_recommendation']) ? sanitize_input($_POST['rsv_recommendation']) : null
    ];
    
    // Validate date fields
    $date_fields = ['hepatitis_b_date', 'flu_vaccine_date', 'ppv23_date', 'rsv_date'];
    foreach ($date_fields as $field) {
        if ($vaccination_data[$field] && !strtotime($vaccination_data[$field])) {
            throw new Exception("Invalid date format for {$field}");
        }
    }
    
    // Business logic validations
    if ($vaccination_data['hepatitis_b_completed'] && !$vaccination_data['hepatitis_b_date']) {
        throw new Exception('Hepatitis B completion date is required when marked as completed');
    }
    
    if ($vaccination_data['flu_vaccine_completed'] && !$vaccination_data['flu_vaccine_date']) {
        throw new Exception('Flu vaccine date is required when marked as completed');
    }
    
    if ($vaccination_data['ppv23_completed'] && !$vaccination_data['ppv23_date']) {
        throw new Exception('PPV23 vaccine date is required when marked as completed');
    }
    
    if ($vaccination_data['rsv_completed'] && !$vaccination_data['rsv_date']) {
        throw new Exception('RSV vaccine date is required when marked as completed');
    }
    
    // Set default RSV recommendation if not completed
    if (!$vaccination_data['rsv_completed'] && !$vaccination_data['rsv_recommendation']) {
        $vaccination_data['rsv_recommendation'] = '1 dose of (Arexvy or Abrysvo or mResvia). Additional doses not recommended.';
    }
    
    // Check if record exists
    $stmt = $db->prepare("SELECT id FROM vaccinations WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing record
        $sql = "UPDATE vaccinations SET 
                hepatitis_b_completed = ?, hepatitis_b_date = ?, hepatitis_b_series = ?,
                flu_vaccine_completed = ?, flu_vaccine_date = ?,
                ppv23_completed = ?, ppv23_date = ?,
                rsv_completed = ?, rsv_date = ?, rsv_recommendation = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE patient_id = ?";
        
        $params = [
            $vaccination_data['hepatitis_b_completed'],
            $vaccination_data['hepatitis_b_date'],
            $vaccination_data['hepatitis_b_series'],
            $vaccination_data['flu_vaccine_completed'],
            $vaccination_data['flu_vaccine_date'],
            $vaccination_data['ppv23_completed'],
            $vaccination_data['ppv23_date'],
            $vaccination_data['rsv_completed'],
            $vaccination_data['rsv_date'],
            $vaccination_data['rsv_recommendation'],
            $patient_id
        ];
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($params);
        
        error_log("Vaccination UPDATE: Result=" . ($result ? 'true' : 'false') . ", Patient ID=$patient_id, Affected rows=" . $stmt->rowCount());
        
        echo json_encode(['success' => true, 'message' => 'Vaccination data updated successfully']);
        
    } else {
        // Insert new record
        $sql = "INSERT INTO vaccinations (
                patient_id, hepatitis_b_completed, hepatitis_b_date, hepatitis_b_series,
                flu_vaccine_completed, flu_vaccine_date,
                ppv23_completed, ppv23_date,
                rsv_completed, rsv_date, rsv_recommendation
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $patient_id,
            $vaccination_data['hepatitis_b_completed'],
            $vaccination_data['hepatitis_b_date'],
            $vaccination_data['hepatitis_b_series'],
            $vaccination_data['flu_vaccine_completed'],
            $vaccination_data['flu_vaccine_date'],
            $vaccination_data['ppv23_completed'],
            $vaccination_data['ppv23_date'],
            $vaccination_data['rsv_completed'],
            $vaccination_data['rsv_date'],
            $vaccination_data['rsv_recommendation']
        ];
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($params);
        
        error_log("Vaccination INSERT: Result=" . ($result ? 'true' : 'false') . ", Patient ID=$patient_id, Affected rows=" . $stmt->rowCount());
        error_log("Vaccination INSERT data: " . print_r($vaccination_data, true));
        
        echo json_encode(['success' => true, 'message' => 'Vaccination data saved successfully']);
    }
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
