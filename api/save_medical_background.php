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
    
    // Collect medical background data
    $background_data = [
        'history_pd' => isset($_POST['history_pd']) ? 1 : 0,
        'pd_start_date' => !empty($_POST['pd_start_date']) ? sanitize_input($_POST['pd_start_date']) : null,
        'pd_end_date' => !empty($_POST['pd_end_date']) ? sanitize_input($_POST['pd_end_date']) : null,
        'history_transplant' => isset($_POST['history_transplant']) ? 1 : 0,
        'transplant_start_date' => !empty($_POST['transplant_start_date']) ? sanitize_input($_POST['transplant_start_date']) : null,
        'transplant_end_date' => !empty($_POST['transplant_end_date']) ? sanitize_input($_POST['transplant_end_date']) : null,
        'history_dm' => isset($_POST['history_dm']) ? 1 : 0,
        'dm_duration_years' => !empty($_POST['dm_duration_years']) ? (int)$_POST['dm_duration_years'] : null,
        'history_htn' => isset($_POST['history_htn']) ? 1 : 0,
        'htn_duration_years' => !empty($_POST['htn_duration_years']) ? (int)$_POST['htn_duration_years'] : null,
        'history_cardiac' => isset($_POST['history_cardiac']) ? 1 : 0,
        'cardiac_duration_years' => !empty($_POST['cardiac_duration_years']) ? (int)$_POST['cardiac_duration_years'] : null,
        'residual_renal_function' => isset($_POST['residual_renal_function']) ? 1 : 0,
        'residual_urine_ml' => !empty($_POST['residual_urine_ml']) ? (int)$_POST['residual_urine_ml'] : null,
        'other_history' => !empty($_POST['other_history']) ? sanitize_input($_POST['other_history']) : null,
        'primary_ckd_cause' => !empty($_POST['primary_ckd_cause']) ? sanitize_input($_POST['primary_ckd_cause']) : null,
        'last_us_date' => !empty($_POST['last_us_date']) ? sanitize_input($_POST['last_us_date']) : null,
        'last_us_findings' => !empty($_POST['last_us_findings']) ? sanitize_input($_POST['last_us_findings']) : null,
        'last_echo_date' => !empty($_POST['last_echo_date']) ? sanitize_input($_POST['last_echo_date']) : null,
        'last_echo_findings' => !empty($_POST['last_echo_findings']) ? sanitize_input($_POST['last_echo_findings']) : null
    ];
    
    // Validate date fields
    $date_fields = ['pd_start_date', 'pd_end_date', 'transplant_start_date', 'transplant_end_date', 'last_us_date', 'last_echo_date'];
    foreach ($date_fields as $field) {
        if ($background_data[$field] && !strtotime($background_data[$field])) {
            throw new Exception("Invalid date format for {$field}");
        }
    }
    
    // Validate duration fields
    $duration_fields = ['dm_duration_years', 'htn_duration_years', 'cardiac_duration_years', 'residual_urine_ml'];
    foreach ($duration_fields as $field) {
        if ($background_data[$field] !== null && ($background_data[$field] < 0 || $background_data[$field] > 200)) {
            throw new Exception("Invalid value for {$field}");
        }
    }
    
    // Check if record exists
    $stmt = $db->prepare("SELECT id FROM medical_background WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing record
        $sql = "UPDATE medical_background SET 
                history_pd = ?, pd_start_date = ?, pd_end_date = ?,
                history_transplant = ?, transplant_start_date = ?, transplant_end_date = ?,
                history_dm = ?, dm_duration_years = ?, history_htn = ?, htn_duration_years = ?,
                history_cardiac = ?, cardiac_duration_years = ?, residual_renal_function = ?, 
                residual_urine_ml = ?, other_history = ?, primary_ckd_cause = ?,
                last_us_date = ?, last_us_findings = ?, last_echo_date = ?, last_echo_findings = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE patient_id = ?";
        
        $params = array_values($background_data);
        $params[] = $patient_id;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode(['success' => true, 'message' => 'Medical background updated successfully']);
        
    } else {
        // Insert new record
        $sql = "INSERT INTO medical_background (
                patient_id, history_pd, pd_start_date, pd_end_date,
                history_transplant, transplant_start_date, transplant_end_date,
                history_dm, dm_duration_years, history_htn, htn_duration_years,
                history_cardiac, cardiac_duration_years, residual_renal_function, 
                residual_urine_ml, other_history, primary_ckd_cause,
                last_us_date, last_us_findings, last_echo_date, last_echo_findings
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [$patient_id];
        $params = array_merge($params, array_values($background_data));
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode(['success' => true, 'message' => 'Medical background saved successfully']);
    }
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
