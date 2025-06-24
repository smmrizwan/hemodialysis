<?php
require_once '../config/init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        throw new Exception('Laboratory record ID is required');
    }
    
    $lab_id = (int)$input['id'];
    
    if (empty($lab_id)) {
        throw new Exception('Invalid laboratory record ID');
    }
    
    // Check if laboratory record exists
    $stmt = $db->prepare("SELECT id, patient_id FROM laboratory_data WHERE id = ?");
    $stmt->execute([$lab_id]);
    $lab_record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lab_record) {
        throw new Exception('Laboratory record not found');
    }
    
    // Delete the laboratory record
    $stmt = $db->prepare("DELETE FROM laboratory_data WHERE id = ?");
    $stmt->execute([$lab_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Laboratory record deleted successfully']);
    } else {
        throw new Exception('Failed to delete laboratory record');
    }
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
