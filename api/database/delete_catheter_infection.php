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
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        throw new Exception('Infection ID is required');
    }
    
    $infection_id = (int)$input['id'];
    
    if (empty($infection_id)) {
        throw new Exception('Invalid infection ID');
    }
    
    // Check if infection exists
    $stmt = $db->prepare("SELECT id, patient_id FROM catheter_infections WHERE id = ?");
    $stmt->execute([$infection_id]);
    $infection = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$infection) {
        throw new Exception('Infection record not found');
    }
    
    // Delete the infection record
    $stmt = $db->prepare("DELETE FROM catheter_infections WHERE id = ?");
    $stmt->execute([$infection_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Catheter infection record deleted successfully']);
    } else {
        throw new Exception('Failed to delete infection record');
    }
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
