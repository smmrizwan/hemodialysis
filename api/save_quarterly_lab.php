<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

require_once '../config/database_sqlite.php';

// Get direct database connection
$db = new PDO("sqlite:" . __DIR__ . "/../database/dialysis_management.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("PRAGMA foreign_keys = ON");

try {
    // Create quarterly_lab_data table if it doesn't exist
    $createTable = "
    CREATE TABLE IF NOT EXISTS quarterly_lab_data (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        patient_id INTEGER NOT NULL,
        quarter_year TEXT NOT NULL,
        date_1 DATE,
        date_2 DATE,
        date_3 DATE,
        
        -- Hematology
        hb_1 DECIMAL(5,2),
        hb_2 DECIMAL(5,2),
        hb_3 DECIMAL(5,2),
        hb_diff_1_2 DECIMAL(5,2),
        hb_change_1_2 DECIMAL(5,2),
        mcv_1 DECIMAL(5,2),
        mcv_2 DECIMAL(5,2),
        mcv_3 DECIMAL(5,2),
        iron_1 DECIMAL(5,2),
        iron_2 DECIMAL(5,2),
        iron_3 DECIMAL(5,2),
        tibc_1 DECIMAL(5,2),
        tibc_2 DECIMAL(5,2),
        tibc_3 DECIMAL(5,2),
        tsat_1 DECIMAL(5,2),
        tsat_2 DECIMAL(5,2),
        tsat_3 DECIMAL(5,2),
        ferritin_1 DECIMAL(7,2),
        ferritin_2 DECIMAL(7,2),
        ferritin_3 DECIMAL(7,2),
        wbc_1 DECIMAL(5,2),
        wbc_2 DECIMAL(5,2),
        wbc_3 DECIMAL(5,2),
        platelets_1 DECIMAL(6,2),
        platelets_2 DECIMAL(6,2),
        platelets_3 DECIMAL(6,2),
        
        -- Chemistry
        calcium_1 DECIMAL(5,2),
        calcium_2 DECIMAL(5,2),
        calcium_3 DECIMAL(5,2),
        phosphorus_1 DECIMAL(5,2),
        phosphorus_2 DECIMAL(5,2),
        phosphorus_3 DECIMAL(5,2),
        albumin_1 DECIMAL(5,2),
        albumin_2 DECIMAL(5,2),
        albumin_3 DECIMAL(5,2),
        pth_pmol_1 DECIMAL(7,2),
        pth_pmol_2 DECIMAL(7,2),
        pth_pmol_3 DECIMAL(7,2),
        pth_pgml_1 DECIMAL(7,2),
        pth_pgml_2 DECIMAL(7,2),
        pth_pgml_3 DECIMAL(7,2),
        vitamin_d_1 DECIMAL(5,2),
        vitamin_d_2 DECIMAL(5,2),
        vitamin_d_3 DECIMAL(5,2),
        corrected_calcium_1 DECIMAL(5,2),
        corrected_calcium_2 DECIMAL(5,2),
        corrected_calcium_3 DECIMAL(5,2),
        ca_phos_product_1 DECIMAL(5,2),
        ca_phos_product_2 DECIMAL(5,2),
        ca_phos_product_3 DECIMAL(5,2),
        
        -- Additional Chemistry
        sodium_1 DECIMAL(5,2),
        sodium_2 DECIMAL(5,2),
        sodium_3 DECIMAL(5,2),
        potassium_1 DECIMAL(5,2),
        potassium_2 DECIMAL(5,2),
        potassium_3 DECIMAL(5,2),
        uric_acid_1 DECIMAL(6,1),
        uric_acid_2 DECIMAL(6,1),
        uric_acid_3 DECIMAL(6,1),
        creatinine_1 DECIMAL(6,1),
        creatinine_2 DECIMAL(6,1),
        creatinine_3 DECIMAL(6,1),
        
        -- Dialysis Parameters
        pre_dialysis_bun_1 DECIMAL(5,2),
        pre_dialysis_bun_2 DECIMAL(5,2),
        pre_dialysis_bun_3 DECIMAL(5,2),
        post_dialysis_bun_1 DECIMAL(5,2),
        post_dialysis_bun_2 DECIMAL(5,2),
        post_dialysis_bun_3 DECIMAL(5,2),
        dialysis_duration_1 DECIMAL(4,2),
        dialysis_duration_2 DECIMAL(4,2),
        dialysis_duration_3 DECIMAL(4,2),
        ultrafiltrate_volume_1 DECIMAL(5,2),
        ultrafiltrate_volume_2 DECIMAL(5,2),
        ultrafiltrate_volume_3 DECIMAL(5,2),
        post_dialysis_weight_1 DECIMAL(5,2),
        post_dialysis_weight_2 DECIMAL(5,2),
        post_dialysis_weight_3 DECIMAL(5,2),
        urr_1 DECIMAL(5,2),
        urr_2 DECIMAL(5,2),
        urr_3 DECIMAL(5,2),
        kt_v_1 DECIMAL(5,2),
        kt_v_2 DECIMAL(5,2),
        kt_v_3 DECIMAL(5,2),
        
        -- Serology
        hbsag_1 TEXT,
        hbsag_2 TEXT,
        hbsag_3 TEXT,
        anti_hcv_1 TEXT,
        anti_hcv_2 TEXT,
        anti_hcv_3 TEXT,
        hiv_1 TEXT,
        hiv_2 TEXT,
        hiv_3 TEXT,
        hbsab_1 INTEGER,
        hbsab_2 INTEGER,
        hbsab_3 INTEGER,
        
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id)
    )";
    
    $db->exec($createTable);
    
    // Get form data
    $patient_id = (int)$_POST['patient_id'];
    $quarter_year = trim($_POST['quarter_year']);
    
    if (!$patient_id) {
        echo json_encode(['success' => false, 'message' => 'Patient ID is required']);
        exit;
    }
    
    if (!$quarter_year) {
        echo json_encode(['success' => false, 'message' => 'Quarter/Year is required']);
        exit;
    }
    
    // Prepare data array
    $quarterly_data = [
        'patient_id' => $patient_id,
        'quarter_year' => $quarter_year,
        'date_1' => $_POST['date_1'] ?? null,
        'date_2' => $_POST['date_2'] ?? null,
        'date_3' => $_POST['date_3'] ?? null,
        
        // Hematology
        'hb_1' => $_POST['hb_1'] ?? null,
        'hb_2' => $_POST['hb_2'] ?? null,
        'hb_3' => $_POST['hb_3'] ?? null,
        'hb_diff_1_2' => $_POST['hb_diff_1_2'] ?? null,
        'hb_change_1_2' => $_POST['hb_change_1_2'] ?? null,
        'mcv_1' => $_POST['mcv_1'] ?? null,
        'mcv_2' => $_POST['mcv_2'] ?? null,
        'mcv_3' => $_POST['mcv_3'] ?? null,
        'iron_1' => $_POST['iron_1'] ?? null,
        'iron_2' => $_POST['iron_2'] ?? null,
        'iron_3' => $_POST['iron_3'] ?? null,
        'tibc_1' => $_POST['tibc_1'] ?? null,
        'tibc_2' => $_POST['tibc_2'] ?? null,
        'tibc_3' => $_POST['tibc_3'] ?? null,
        'tsat_1' => $_POST['tsat_1'] ?? null,
        'tsat_2' => $_POST['tsat_2'] ?? null,
        'tsat_3' => $_POST['tsat_3'] ?? null,
        'ferritin_1' => $_POST['ferritin_1'] ?? null,
        'ferritin_2' => $_POST['ferritin_2'] ?? null,
        'ferritin_3' => $_POST['ferritin_3'] ?? null,
        'wbc_1' => $_POST['wbc_1'] ?? null,
        'wbc_2' => $_POST['wbc_2'] ?? null,
        'wbc_3' => $_POST['wbc_3'] ?? null,
        'platelets_1' => $_POST['platelets_1'] ?? null,
        'platelets_2' => $_POST['platelets_2'] ?? null,
        'platelets_3' => $_POST['platelets_3'] ?? null,
        
        // Chemistry
        'calcium_1' => $_POST['calcium_1'] ?? null,
        'calcium_2' => $_POST['calcium_2'] ?? null,
        'calcium_3' => $_POST['calcium_3'] ?? null,
        'phosphorus_1' => $_POST['phosphorus_1'] ?? null,
        'phosphorus_2' => $_POST['phosphorus_2'] ?? null,
        'phosphorus_3' => $_POST['phosphorus_3'] ?? null,
        'albumin_1' => $_POST['albumin_1'] ?? null,
        'albumin_2' => $_POST['albumin_2'] ?? null,
        'albumin_3' => $_POST['albumin_3'] ?? null,
        'pth_pmol_1' => $_POST['pth_pmol_1'] ?? null,
        'pth_pmol_2' => $_POST['pth_pmol_2'] ?? null,
        'pth_pmol_3' => $_POST['pth_pmol_3'] ?? null,
        'pth_pgml_1' => $_POST['pth_pgml_1'] ?? null,
        'pth_pgml_2' => $_POST['pth_pgml_2'] ?? null,
        'pth_pgml_3' => $_POST['pth_pgml_3'] ?? null,
        'vitamin_d_1' => $_POST['vitamin_d_1'] ?? null,
        'vitamin_d_2' => $_POST['vitamin_d_2'] ?? null,
        'vitamin_d_3' => $_POST['vitamin_d_3'] ?? null,
        'corrected_calcium_1' => $_POST['corrected_calcium_1'] ?? null,
        'corrected_calcium_2' => $_POST['corrected_calcium_2'] ?? null,
        'corrected_calcium_3' => $_POST['corrected_calcium_3'] ?? null,
        'ca_phos_product_1' => $_POST['ca_phos_product_1'] ?? null,
        'ca_phos_product_2' => $_POST['ca_phos_product_2'] ?? null,
        'ca_phos_product_3' => $_POST['ca_phos_product_3'] ?? null,
        
        // Additional Chemistry
        'sodium_1' => $_POST['sodium_1'] ?? null,
        'sodium_2' => $_POST['sodium_2'] ?? null,
        'sodium_3' => $_POST['sodium_3'] ?? null,
        'potassium_1' => $_POST['potassium_1'] ?? null,
        'potassium_2' => $_POST['potassium_2'] ?? null,
        'potassium_3' => $_POST['potassium_3'] ?? null,
        'uric_acid_1' => $_POST['uric_acid_1'] ?? null,
        'uric_acid_2' => $_POST['uric_acid_2'] ?? null,
        'uric_acid_3' => $_POST['uric_acid_3'] ?? null,
        'creatinine_1' => $_POST['creatinine_1'] ?? null,
        'creatinine_2' => $_POST['creatinine_2'] ?? null,
        'creatinine_3' => $_POST['creatinine_3'] ?? null,
        
        // Dialysis Parameters
        'pre_dialysis_bun_1' => $_POST['pre_dialysis_bun_1'] ?? null,
        'pre_dialysis_bun_2' => $_POST['pre_dialysis_bun_2'] ?? null,
        'pre_dialysis_bun_3' => $_POST['pre_dialysis_bun_3'] ?? null,
        'post_dialysis_bun_1' => $_POST['post_dialysis_bun_1'] ?? null,
        'post_dialysis_bun_2' => $_POST['post_dialysis_bun_2'] ?? null,
        'post_dialysis_bun_3' => $_POST['post_dialysis_bun_3'] ?? null,
        'dialysis_duration_1' => $_POST['dialysis_duration_1'] ?? null,
        'dialysis_duration_2' => $_POST['dialysis_duration_2'] ?? null,
        'dialysis_duration_3' => $_POST['dialysis_duration_3'] ?? null,
        'ultrafiltrate_volume_1' => $_POST['ultrafiltrate_volume_1'] ?? null,
        'ultrafiltrate_volume_2' => $_POST['ultrafiltrate_volume_2'] ?? null,
        'ultrafiltrate_volume_3' => $_POST['ultrafiltrate_volume_3'] ?? null,
        'post_dialysis_weight_1' => $_POST['post_dialysis_weight_1'] ?? null,
        'post_dialysis_weight_2' => $_POST['post_dialysis_weight_2'] ?? null,
        'post_dialysis_weight_3' => $_POST['post_dialysis_weight_3'] ?? null,
        'urr_1' => $_POST['urr_1'] ?? null,
        'urr_2' => $_POST['urr_2'] ?? null,
        'urr_3' => $_POST['urr_3'] ?? null,
        'kt_v_1' => $_POST['kt_v_1'] ?? null,
        'kt_v_2' => $_POST['kt_v_2'] ?? null,
        'kt_v_3' => $_POST['kt_v_3'] ?? null,
        
        // Serology (radio buttons for HBsAg, Anti HCV, HIV; numeric for HBsAb)
        'hbsag_1' => $_POST['hbsag_1'] ?? null,
        'hbsag_2' => $_POST['hbsag_2'] ?? null,
        'hbsag_3' => $_POST['hbsag_3'] ?? null,
        'anti_hcv_1' => $_POST['anti_hcv_1'] ?? null,
        'anti_hcv_2' => $_POST['anti_hcv_2'] ?? null,
        'anti_hcv_3' => $_POST['anti_hcv_3'] ?? null,
        'hiv_1' => $_POST['hiv_1'] ?? null,
        'hiv_2' => $_POST['hiv_2'] ?? null,
        'hiv_3' => $_POST['hiv_3'] ?? null,
        'hbsab_1' => $_POST['hbsab_1'] ?? null,
        'hbsab_2' => $_POST['hbsab_2'] ?? null,
        'hbsab_3' => $_POST['hbsab_3'] ?? null
    ];
    
    // Check if updating existing record
    if (isset($_POST['quarterly_id']) && !empty($_POST['quarterly_id'])) {
        $quarterly_id = (int)$_POST['quarterly_id'];
        
        // Build UPDATE query
        $updateFields = [];
        $updateValues = [];
        
        foreach ($quarterly_data as $field => $value) {
            $updateFields[] = "$field = ?";
            $updateValues[] = $value;
        }
        
        $updateValues[] = $quarterly_id; // For WHERE clause
        
        $sql = "UPDATE quarterly_lab_data SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($updateValues);
        
        if ($result) {
            error_log("Quarterly lab data UPDATED: ID=$quarterly_id, Patient ID=$patient_id, Quarter=$quarter_year");
            echo json_encode(['success' => true, 'message' => 'Quarterly laboratory data updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update quarterly laboratory data']);
        }
        
    } else {
        // Check for duplicate quarter/year for same patient
        $stmt = $db->prepare("SELECT id FROM quarterly_lab_data WHERE patient_id = ? AND quarter_year = ?");
        $stmt->execute([$patient_id, $quarter_year]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'Quarterly data for this patient and quarter already exists']);
            exit;
        }
        
        // Build INSERT query
        $fields = array_keys($quarterly_data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO quarterly_lab_data (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute(array_values($quarterly_data));
        
        if ($result) {
            $quarterly_id = $db->lastInsertId();
            error_log("Quarterly lab data INSERTED: ID=$quarterly_id, Patient ID=$patient_id, Quarter=$quarter_year");
            echo json_encode(['success' => true, 'message' => 'Quarterly laboratory data saved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save quarterly laboratory data']);
        }
    }
    
} catch(Exception $e) {
    error_log("Quarterly lab save error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch(PDOException $e) {
    error_log("Quarterly lab PDO error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
}
?>