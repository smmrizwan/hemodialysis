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
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Delete existing medications for this patient
        $stmt = $db->prepare("DELETE FROM medications WHERE patient_id = ?");
        $stmt->execute([$patient_id]);
        
        // Process medications array
        if (isset($_POST['medications']) && is_array($_POST['medications'])) {
            $insertCount = 0;
            
            foreach ($_POST['medications'] as $index => $medication) {
                $med_name = trim($medication['medication_name'] ?? '');
                $dosage = trim($medication['dosage'] ?? '');
                $frequency = trim($medication['frequency'] ?? '');
                $route = trim($medication['route'] ?? '');
                $row_order = (int)($medication['row_order'] ?? $index + 1);
                
                // Only insert if medication name is provided
                if (!empty($med_name)) {
                    // Validate route if provided
                    if (!empty($route) && !in_array($route, ['PO', 'IV', 's/c', 'IM'])) {
                        throw new Exception("Invalid route '{$route}' for medication '{$med_name}'");
                    }
                    
                    // Validate frequency if provided
                    $valid_frequencies = [
                        'Once daily', 'Twice daily', 'Three times daily', 'Four times daily',
                        'Every other day', 'Weekly', 'Monthly', 'As needed', 'With dialysis'
                    ];
                    if (!empty($frequency) && !in_array($frequency, $valid_frequencies)) {
                        throw new Exception("Invalid frequency '{$frequency}' for medication '{$med_name}'");
                    }
                    
                    // Insert medication
                    $sql = "INSERT INTO medications (patient_id, medication_name, dosage, frequency, route, row_order) 
                            VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $patient_id,
                        $med_name,
                        !empty($dosage) ? $dosage : null,
                        !empty($frequency) ? $frequency : null,
                        !empty($route) ? $route : null,
                        $row_order
                    ]);
                    
                    $insertCount++;
                }
            }
            
            // Commit transaction
            $db->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => "Medications saved successfully. {$insertCount} medications recorded."
            ]);
            
        } else {
            // No medications provided, just delete existing ones
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'All medications cleared successfully']);
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
