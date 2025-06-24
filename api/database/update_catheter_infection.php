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
    $infection_id = (int)$_POST['id'];
    $infection_date = sanitize_input($_POST['infection_date']);
    $organism = sanitize_input($_POST['organism']);
    
    if (empty($infection_id) || empty($infection_date)) {
        throw new Exception('Infection ID and date are required');
    }
    
    // Validate date
    if (!strtotime($infection_date)) {
        throw new Exception('Invalid infection date');
    }
    
    // Check if infection record exists
    $stmt = $db->prepare("SELECT id, patient_id FROM catheter_infections WHERE id = ?");
    $stmt->execute([$infection_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing) {
        throw new Exception('Infection record not found');
    }
    
    // Check for duplicate with same patient, date, and organism (excluding current record)
    $stmt = $db->prepare("SELECT id FROM catheter_infections WHERE patient_id = ? AND infection_date = ? AND organism = ? AND id != ?");
    $stmt->execute([$existing['patient_id'], $infection_date, $organism, $infection_id]);
    if ($stmt->fetch()) {
        throw new Exception('Another infection record with same organism already exists for this date');
    }
    
    // Update infection record
    $sql = "UPDATE catheter_infections SET infection_date = ?, organism = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$infection_date, $organism, $infection_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Catheter infection record updated successfully']);
    } else {
        echo json_encode(['success' => true, 'message' => 'No changes made to the record']);
    }
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>