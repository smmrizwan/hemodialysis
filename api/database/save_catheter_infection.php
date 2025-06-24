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
    $infection_date = sanitize_input($_POST['infection_date']);
    $organism = sanitize_input($_POST['organism']);
    
    if (empty($patient_id) || empty($infection_date)) {
        throw new Exception('Patient ID and infection date are required');
    }
    
    // Check if patient exists
    $stmt = $db->prepare("SELECT id FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Patient not found');
    }
    
    // Validate date
    if (!strtotime($infection_date)) {
        throw new Exception('Invalid infection date');
    }
    
    // Check for exact duplicate to prevent double submission
    $stmt = $db->prepare("SELECT id FROM catheter_infections WHERE patient_id = ? AND infection_date = ? AND organism = ?");
    $stmt->execute([$patient_id, $infection_date, $organism]);
    if ($stmt->fetch()) {
        // Return success for duplicates to prevent error messages on double-click
        echo json_encode(['success' => true, 'message' => 'Record already exists']);
        exit;
    }
    
    // Insert new infection record
    $sql = "INSERT INTO catheter_infections (patient_id, infection_date, organism) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$patient_id, $infection_date, $organism]);
    
    echo json_encode(['success' => true, 'message' => 'Catheter infection record saved successfully']);
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
