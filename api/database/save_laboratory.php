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
    $test_date = sanitize_input($_POST['test_date']);
    
    if (empty($patient_id) || empty($test_date)) {
        throw new Exception('Patient ID and test date are required');
    }
    
    // Check if patient exists
    $stmt = $db->prepare("SELECT id FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Patient not found');
    }
    
    // Check if lab data already exists for this date
    $stmt = $db->prepare("SELECT id FROM laboratory_data WHERE patient_id = ? AND test_date = ?");
    $stmt->execute([$patient_id, $test_date]);
    $existing = $stmt->fetch();
    
    // Collect and sanitize lab data
    $lab_data = [
        'hb' => !empty($_POST['hb']) ? (float)$_POST['hb'] : null,
        'mcv' => !empty($_POST['mcv']) ? (float)$_POST['mcv'] : null,
        'iron' => !empty($_POST['iron']) ? (float)$_POST['iron'] : null,
        'tibc' => !empty($_POST['tibc']) ? (float)$_POST['tibc'] : null,
        'tsat' => !empty($_POST['tsat']) ? (float)$_POST['tsat'] : null,
        'ferritin' => !empty($_POST['ferritin']) ? (float)$_POST['ferritin'] : null,
        'hb_change_percent' => !empty($_POST['hb_change_percent']) ? (float)$_POST['hb_change_percent'] : null,
        'vitamin_d' => !empty($_POST['vitamin_d']) ? (float)$_POST['vitamin_d'] : null,
        'calcium' => !empty($_POST['calcium']) ? (float)$_POST['calcium'] : null,
        'phosphorus' => !empty($_POST['phosphorus']) ? (float)$_POST['phosphorus'] : null,
        'albumin' => !empty($_POST['albumin']) ? (float)$_POST['albumin'] : null,
        'pth' => !empty($_POST['pth']) ? (float)$_POST['pth'] : null,
        'corrected_calcium' => !empty($_POST['corrected_calcium']) ? (float)$_POST['corrected_calcium'] : null,
        'ca_phos_product' => !empty($_POST['ca_phos_product']) ? (float)$_POST['ca_phos_product'] : null,
        'hbsag' => !empty($_POST['hbsag']) ? sanitize_input($_POST['hbsag']) : null,
        'anti_hcv' => !empty($_POST['anti_hcv']) ? sanitize_input($_POST['anti_hcv']) : null,
        'hiv' => !empty($_POST['hiv']) ? sanitize_input($_POST['hiv']) : null,
        'hbsab' => !empty($_POST['hbsab']) ? (float)$_POST['hbsab'] : null,
        'pre_dialysis_bun' => !empty($_POST['pre_dialysis_bun']) ? (float)$_POST['pre_dialysis_bun'] : null,
        'post_dialysis_bun' => !empty($_POST['post_dialysis_bun']) ? (float)$_POST['post_dialysis_bun'] : null,
        'dialysis_duration' => !empty($_POST['dialysis_duration']) ? (float)$_POST['dialysis_duration'] : null,
        'ultrafiltrate_volume' => !empty($_POST['ultrafiltrate_volume']) ? (float)$_POST['ultrafiltrate_volume'] : null,
        'post_dialysis_weight' => !empty($_POST['post_dialysis_weight']) ? (float)$_POST['post_dialysis_weight'] : null,
        'urr' => !empty($_POST['urr']) ? (float)$_POST['urr'] : null,
        'kt_v' => !empty($_POST['kt_v']) ? (float)$_POST['kt_v'] : null
    ];
    
    // Recalculate TSAT if iron and TIBC are provided
    if ($lab_data['iron'] && $lab_data['tibc']) {
        $lab_data['tsat'] = round(($lab_data['iron'] * 100) / $lab_data['tibc'], 2);
    }
    
    // Recalculate corrected calcium if calcium and albumin are provided
    if ($lab_data['calcium'] && $lab_data['albumin']) {
        $lab_data['corrected_calcium'] = round($lab_data['calcium'] + 0.02 * (40 - $lab_data['albumin']), 3);
    }
    
    // Recalculate Ca×Phos product if corrected calcium and phosphorus are provided
    if ($lab_data['corrected_calcium'] && $lab_data['phosphorus']) {
        $ca_mg_dl = $lab_data['corrected_calcium'] * 4.008;
        $phos_mg_dl = $lab_data['phosphorus'] * 3.097;
        $lab_data['ca_phos_product'] = round($ca_mg_dl * $phos_mg_dl, 2);
    }
    
    // Recalculate URR if BUN values are provided
    if ($lab_data['pre_dialysis_bun'] && $lab_data['post_dialysis_bun'] !== null) {
        $lab_data['urr'] = round((($lab_data['pre_dialysis_bun'] - $lab_data['post_dialysis_bun']) / $lab_data['pre_dialysis_bun']) * 100, 2);
    }
    
    // Recalculate Kt/V if necessary values are provided
    if ($lab_data['pre_dialysis_bun'] && $lab_data['post_dialysis_bun'] && 
        $lab_data['dialysis_duration'] && $lab_data['post_dialysis_weight']) {
        $lab_data['kt_v'] = round(-log($lab_data['post_dialysis_bun'] / $lab_data['pre_dialysis_bun']) + 
                                 (4 * ($lab_data['pre_dialysis_bun'] - $lab_data['post_dialysis_bun'])) / 
                                 ($lab_data['pre_dialysis_bun'] * 100), 2);
    }
    
    // Calculate hemoglobin change from previous records
    if ($lab_data['hb']) {
        $stmt = $db->prepare("SELECT hb FROM laboratory_data WHERE patient_id = ? AND test_date < ? AND hb IS NOT NULL ORDER BY test_date DESC LIMIT 1");
        $stmt->execute([$patient_id, $test_date]);
        $prev_lab = $stmt->fetch();
        
        if ($prev_lab && $prev_lab['hb']) {
            $lab_data['hb_change_percent'] = round((($lab_data['hb'] - $prev_lab['hb']) / $prev_lab['hb']) * 100, 2);
        }
    }
    
    if ($existing) {
        // Update existing record
        $sql = "UPDATE laboratory_data SET 
                hb = ?, mcv = ?, iron = ?, tibc = ?, tsat = ?, ferritin = ?, hb_change_percent = ?,
                vitamin_d = ?, calcium = ?, phosphorus = ?, albumin = ?, pth = ?, 
                corrected_calcium = ?, ca_phos_product = ?,
                hbsag = ?, anti_hcv = ?, hiv = ?, hbsab = ?,
                pre_dialysis_bun = ?, post_dialysis_bun = ?, dialysis_duration = ?,
                ultrafiltrate_volume = ?, post_dialysis_weight = ?, urr = ?, kt_v = ?
                WHERE id = ?";
        
        $params = array_values($lab_data);
        $params[] = $existing['id'];
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode(['success' => true, 'message' => 'Laboratory data updated successfully']);
        
    } else {
        // Insert new record
        $sql = "INSERT INTO laboratory_data (
                patient_id, test_date, hb, mcv, iron, tibc, tsat, ferritin, hb_change_percent,
                vitamin_d, calcium, phosphorus, albumin, pth, corrected_calcium, ca_phos_product,
                hbsag, anti_hcv, hiv, hbsab, pre_dialysis_bun, post_dialysis_bun, dialysis_duration,
                ultrafiltrate_volume, post_dialysis_weight, urr, kt_v
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [$patient_id, $test_date];
        $params = array_merge($params, array_values($lab_data));
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode(['success' => true, 'message' => 'Laboratory data saved successfully']);
    }
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
