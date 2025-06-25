<?php
require_once '../config/init.php';

// Set content type and error reporting
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start output buffering
ob_start();

// Log request
error_log("=== CATHETER INFECTION UPDATE REQUEST ===");
error_log("POST data: " . print_r($_POST, true));

// Get database connection
try {
    $db = new PDO("sqlite:" . __DIR__ . "/../database/dialysis_management.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("PRAGMA foreign_keys = ON");
} catch (PDOException $e) {
    ob_end_clean();
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
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $infection_date = isset($_POST['infection_date']) ? trim($_POST['infection_date']) : '';
    $organism = isset($_POST['organism']) ? trim($_POST['organism']) : '';
    $antibiotic_used = isset($_POST['antibiotic_used']) ? trim($_POST['antibiotic_used']) : '';
    
    if (empty($id) || empty($infection_date)) {
        throw new Exception('ID and infection date are required');
    }
    
    // Validate date
    $dateTime = DateTime::createFromFormat('Y-m-d', $infection_date);
    if (!$dateTime || $dateTime->format('Y-m-d') !== $infection_date) {
        throw new Exception('Invalid infection date format');
    }
    
    // Check if record exists
    $stmt = $db->prepare("SELECT id FROM catheter_infections WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        throw new Exception('Infection record not found');
    }
    
    // Update the record
    $sql = "UPDATE catheter_infections SET infection_date = ?, organism = ?, antibiotic_used = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    
    error_log("Executing update: $sql with params: date=$infection_date, organism='$organism', antibiotic='$antibiotic_used', id=$id");
    
    $result = $stmt->execute([$infection_date, $organism, $antibiotic_used, $id]);
    
    if ($result) {
        ob_end_clean();
        echo json_encode([
            'success' => true, 
            'message' => 'Infection record updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update infection record');
    }
    
} catch(Exception $e) {
    ob_end_clean();
    error_log("Update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch(PDOException $e) {
    ob_end_clean();
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>