<?php
require_once __DIR__ . '/../config/init.php';

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
    // Start transaction for data consistency
    $db->beginTransaction();
    
    // Check if editing existing patient
    $is_edit = !empty($_POST['patient_id']);
    $patient_id = $is_edit ? (int)$_POST['patient_id'] : null;
    
    // Sanitize and validate input
    $file_number = sanitize_input($_POST['file_number']);
    $name_english = sanitize_input($_POST['name_english']);
    $date_of_birth = sanitize_input($_POST['date_of_birth']);
    $gender = sanitize_input($_POST['gender']);
    $contact_number = sanitize_input($_POST['contact_number']);
    $room_number = (int)$_POST['room_number'];
    $group_type = sanitize_input($_POST['group_type']);
    $shift_type = sanitize_input($_POST['shift_type']);
    
    error_log("Patient save attempt - Name: $name_english, File: $file_number");
    
    // Optional fields
    $height_cm = !empty($_POST['height_cm']) ? (float)$_POST['height_cm'] : null;
    $weight_kg = !empty($_POST['weight_kg']) ? (float)$_POST['weight_kg'] : null;
    $blood_group = !empty($_POST['blood_group']) ? sanitize_input($_POST['blood_group']) : null;
    $dialysis_initiation_date = !empty($_POST['dialysis_initiation_date']) ? sanitize_input($_POST['dialysis_initiation_date']) : null;
    $chronic_problems = !empty($_POST['chronic_problems']) ? sanitize_input($_POST['chronic_problems']) : null;
    $acute_problems = !empty($_POST['acute_problems']) ? sanitize_input($_POST['acute_problems']) : null;
    
    // Blood pressure fields
    $pre_systolic_bp = !empty($_POST['pre_systolic_bp']) ? (int)$_POST['pre_systolic_bp'] : null;
    $pre_diastolic_bp = !empty($_POST['pre_diastolic_bp']) ? (int)$_POST['pre_diastolic_bp'] : null;
    $post_systolic_bp = !empty($_POST['post_systolic_bp']) ? (int)$_POST['post_systolic_bp'] : null;
    $post_diastolic_bp = !empty($_POST['post_diastolic_bp']) ? (int)$_POST['post_diastolic_bp'] : null;
    
    $pulse_rate = !empty($_POST['pulse_rate']) ? (int)$_POST['pulse_rate'] : null;
    $temperature = !empty($_POST['temperature']) ? (float)$_POST['temperature'] : null;
    
    $av_fistula = isset($_POST['av_fistula']) ? 1 : 0;
    $catheter = isset($_POST['catheter']) ? 1 : 0;
    
    // Validate required fields
    if (empty($file_number) || empty($name_english) || empty($date_of_birth) || 
        empty($gender) || empty($contact_number) || empty($group_type) || empty($shift_type)) {
        throw new Exception('All required fields must be filled');
    }
    
    // Validate file number format
    if (!preg_match('/^[0-9]{5,11}$/', $file_number)) {
        throw new Exception('File number must be 5-11 digits');
    }
    
    // Validate contact number
    if (!preg_match('/^[0-9]{10}$/', $contact_number)) {
        throw new Exception('Contact number must be 10 digits');
    }
    
    // Validate room number
    if ($room_number < 1 || $room_number > 23) {
        throw new Exception('Room number must be between 1 and 23');
    }
    
    // Calculate age
    $age = calculate_age($date_of_birth);
    
    // Calculate BMI
    $bmi = null;
    if ($height_cm && $weight_kg) {
        $bmi = calculate_bmi($height_cm, $weight_kg);
    }
    
    // Calculate BSA
    $bsa = null;
    if ($height_cm && $weight_kg) {
        $bsa = calculate_bsa($height_cm, $weight_kg);
    }
    
    // Calculate MAP values
    $pre_map = null;
    if ($pre_systolic_bp && $pre_diastolic_bp) {
        $pre_map = calculate_map($pre_systolic_bp, $pre_diastolic_bp);
    }
    
    $post_map = null;
    if ($post_systolic_bp && $post_diastolic_bp) {
        $post_map = calculate_map($post_systolic_bp, $post_diastolic_bp);
    }
    
    // Calculate dialysis duration
    $dialysis_months = null;
    $dialysis_years = null;
    if ($dialysis_initiation_date) {
        $duration = calculate_dialysis_duration($dialysis_initiation_date);
        $dialysis_months = $duration['months'];
        $dialysis_years = $duration['years'];
    }
    
    if ($is_edit) {
        // Check if file number is already used by another patient
        $stmt = $db->prepare("SELECT id FROM patients WHERE file_number = ? AND id != ?");
        $stmt->execute([$file_number, $patient_id]);
        if ($stmt->fetch()) {
            throw new Exception('File number already exists for another patient');
        }
        
        // Update existing patient
        $sql = "UPDATE patients SET 
                file_number = ?, name_english = ?, date_of_birth = ?, age = ?, gender = ?,
                contact_number = ?, room_number = ?, group_type = ?, shift_type = ?,
                height_cm = ?, weight_kg = ?, bmi = ?, bsa = ?, blood_group = ?,
                dialysis_initiation_date = ?, dialysis_months = ?, dialysis_years = ?,
                chronic_problems = ?, acute_problems = ?,
                pre_systolic_bp = ?, pre_diastolic_bp = ?, pre_map = ?,
                post_systolic_bp = ?, post_diastolic_bp = ?, post_map = ?,
                pulse_rate = ?, temperature = ?, av_fistula = ?, catheter = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $params = [
            $file_number, $name_english, $date_of_birth, $age, $gender,
            $contact_number, $room_number, $group_type, $shift_type,
            $height_cm, $weight_kg, $bmi, $bsa, $blood_group,
            $dialysis_initiation_date, $dialysis_months, $dialysis_years,
            $chronic_problems, $acute_problems,
            $pre_systolic_bp, $pre_diastolic_bp, $pre_map,
            $post_systolic_bp, $post_diastolic_bp, $post_map,
            $pulse_rate, $temperature, $av_fistula, $catheter,
            $patient_id
        ];
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            // Commit the transaction for updates
            $db->commit();
            error_log("Patient updated successfully: ID = $patient_id, Name = $name_english");
            echo json_encode(['success' => true, 'message' => 'Patient updated successfully', 'patient_id' => $patient_id]);
        } else {
            error_log("Failed to execute patient update statement");
            throw new Exception('Failed to update patient in database');
        }
        
    } else {
        // Check if file number already exists
        $stmt = $db->prepare("SELECT id FROM patients WHERE file_number = ?");
        $stmt->execute([$file_number]);
        if ($stmt->fetch()) {
            throw new Exception('File number already exists');
        }
        
        // Insert new patient
        $sql = "INSERT INTO patients (
                file_number, name_english, date_of_birth, age, gender,
                contact_number, room_number, group_type, shift_type,
                height_cm, weight_kg, bmi, bsa, blood_group,
                dialysis_initiation_date, dialysis_months, dialysis_years,
                chronic_problems, acute_problems,
                pre_systolic_bp, pre_diastolic_bp, pre_map,
                post_systolic_bp, post_diastolic_bp, post_map,
                pulse_rate, temperature, av_fistula, catheter
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $file_number, $name_english, $date_of_birth, $age, $gender,
            $contact_number, $room_number, $group_type, $shift_type,
            $height_cm, $weight_kg, $bmi, $bsa, $blood_group,
            $dialysis_initiation_date, $dialysis_months, $dialysis_years,
            $chronic_problems, $acute_problems,
            $pre_systolic_bp, $pre_diastolic_bp, $pre_map,
            $post_systolic_bp, $post_diastolic_bp, $post_map,
            $pulse_rate, $temperature, $av_fistula, $catheter
        ];
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            $patient_id = $db->lastInsertId();
            
            // Verify the patient was actually inserted
            $verify_stmt = $db->prepare("SELECT COUNT(*) as count FROM patients WHERE id = ?");
            $verify_stmt->execute([$patient_id]);
            $verify_count = $verify_stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($verify_count > 0) {
                // Commit the transaction
                $db->commit();
                error_log("Patient saved successfully: ID = $patient_id, Name = $name_english, Verified = YES");
                echo json_encode(['success' => true, 'message' => 'Patient saved successfully', 'patient_id' => $patient_id]);
            } else {
                error_log("Patient save failed verification: ID = $patient_id, Name = $name_english");
                $db->rollback();
                throw new Exception('Patient save failed verification');
            }
        } else {
            error_log("Failed to execute patient insert statement");
            throw new Exception('Failed to save patient to database');
        }
    }
    
} catch(Exception $e) {
    // Rollback transaction on any error
    if ($db->inTransaction()) {
        $db->rollback();
    }
    error_log("Patient save exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch(PDOException $e) {
    // Rollback transaction on database error
    if ($db->inTransaction()) {
        $db->rollback();
    }
    error_log("Patient save database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
