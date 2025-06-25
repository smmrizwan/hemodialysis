<?php
require_once '../config/init.php';

// Set content type and error reporting
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start output buffering to prevent any accidental output
ob_start();

// Log all incoming data for debugging
error_log("=== CATHETER INFECTION SAVE REQUEST ===");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));

// Get direct database connection
try {
    $db = new PDO("sqlite:" . __DIR__ . "/../database/dialysis_management.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("PRAGMA foreign_keys = ON");
    error_log("Database connection successful");
} catch (PDOException $e) {
    ob_end_clean();
    error_log("Database connection failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Get and validate input data
    $patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $infection_date = isset($_POST['infection_date']) ? trim($_POST['infection_date']) : '';
    $organism = isset($_POST['organism']) ? trim($_POST['organism']) : '';
    $antibiotic_used = isset($_POST['antibiotic_used']) ? trim($_POST['antibiotic_used']) : '';
    
    // Debug logging
    error_log("Processed data:");
    error_log("- Patient ID: " . $patient_id);
    error_log("- Date: " . $infection_date);
    error_log("- Organism: '" . $organism . "'");
    error_log("- Antibiotic: '" . $antibiotic_used . "'");
    
    if (empty($patient_id) || empty($infection_date)) {
        throw new Exception('Patient ID and infection date are required');
    }
    
    // Check if patient exists
    $stmt = $db->prepare("SELECT id, name_english FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$patient) {
        throw new Exception('Patient not found');
    }
    error_log("Patient found: " . $patient['name_english']);
    
    // Validate date
    $dateTime = DateTime::createFromFormat('Y-m-d', $infection_date);
    if (!$dateTime || $dateTime->format('Y-m-d') !== $infection_date) {
        throw new Exception('Invalid infection date format');
    }
    
    // Check for duplicate (same patient and date)
    $stmt = $db->prepare("SELECT id FROM catheter_infections WHERE patient_id = ? AND infection_date = ?");
    $stmt->execute([$patient_id, $infection_date]);
    if ($stmt->fetch()) {
        ob_end_clean();
        error_log("Duplicate record found - returning success");
        echo json_encode(['success' => true, 'message' => 'Record for this date already exists']);
        exit;
    }
    
    // Insert new infection record with antibiotic field
    $sql = "INSERT INTO catheter_infections (patient_id, infection_date, organism, antibiotic_used) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    
    error_log("Executing SQL: " . $sql);
    error_log("With parameters: patient_id=$patient_id, infection_date=$infection_date, organism='$organism', antibiotic='$antibiotic_used'");
    
    $result = $stmt->execute([$patient_id, $infection_date, $organism, $antibiotic_used]);
    
    if ($result) {
        $infection_id = $db->lastInsertId();
        error_log("Successfully saved catheter infection with ID: " . $infection_id);
        
        // Verify the record was saved correctly
        $verify_stmt = $db->prepare("SELECT * FROM catheter_infections WHERE id = ?");
        $verify_stmt->execute([$infection_id]);
        $saved_record = $verify_stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Saved record verification: " . print_r($saved_record, true));
        
        ob_end_clean();
        echo json_encode([
            'success' => true, 
            'message' => 'Catheter infection record saved successfully', 
            'id' => $infection_id,
            'debug_info' => [
                'saved_organism' => $saved_record['organism'],
                'saved_antibiotic' => $saved_record['antibiotic_used']
            ]
        ]);
    } else {
        throw new Exception('Failed to save infection record');
    }
    
} catch(Exception $e) {
    ob_end_clean();
    error_log("Catheter infection save error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch(PDOException $e) {
    ob_end_clean();
    error_log("Catheter infection database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>