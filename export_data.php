<?php
require_once 'config/init.php';

// Set content type and headers based on export type
$export_type = $_GET['type'] ?? '';

if (!in_array($export_type, ['excel', 'mysql'])) {
    die('Invalid export type');
}

try {
    $pdo = $GLOBALS['db'];
    
    if ($export_type === 'excel') {
        // Export as CSV (Excel compatible)
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="dialysis_patients_data_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Export Patients Data
        fputcsv($output, ['=== PATIENTS DATA ===']);
        fputcsv($output, [
            'ID', 'File Number', 'Name English', 'Name Arabic', 'Date of Birth', 'Age', 
            'Gender', 'Contact Number', 'Room Number', 'Group', 'Shift', 'Height', 'Weight', 
            'BMI', 'BSA', 'Registration Date'
        ]);
        
        $stmt = $pdo->query("SELECT * FROM patients ORDER BY id");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'] ?? '',
                $row['file_number'] ?? '',
                $row['name_english'] ?? '',
                $row['name_arabic'] ?? '',
                $row['date_of_birth'] ?? '',
                $row['age'] ?? '',
                $row['gender'] ?? '',
                $row['contact_number'] ?? '',
                $row['room_number'] ?? '',
                $row['group_type'] ?? '',
                $row['shift_type'] ?? '',
                $row['height_cm'] ?? '',
                $row['weight_kg'] ?? '',
                $row['bmi'] ?? '',
                $row['bsa'] ?? '',
                $row['registration_date'] ?? ''
            ]);
        }
        
        // Add empty rows
        fputcsv($output, []);
        fputcsv($output, []);
        
        // Export Laboratory Data
        fputcsv($output, ['=== LABORATORY DATA ===']);
        fputcsv($output, [
            'ID', 'Patient ID', 'Patient Name', 'Test Date', 'Hemoglobin', 'Hematocrit', 
            'Iron', 'TIBC', 'Transferrin Saturation', 'Ferritin', 'Creatinine', 'Urea', 
            'Calcium', 'Phosphorus', 'PTH', 'Albumin', 'Total Protein'
        ]);
        
        $stmt = $pdo->query("
            SELECT l.*, p.name_english as patient_name 
            FROM laboratory_data l 
            LEFT JOIN patients p ON l.patient_id = p.id 
            ORDER BY l.test_date DESC
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'] ?? '',
                $row['patient_id'] ?? '',
                $row['patient_name'] ?? '',
                $row['test_date'] ?? '',
                $row['hemoglobin'] ?? '',
                $row['hematocrit'] ?? '',
                $row['iron'] ?? '',
                $row['tibc'] ?? '',
                $row['transferrin_saturation'] ?? '',
                $row['ferritin'] ?? '',
                $row['creatinine'] ?? '',
                $row['urea'] ?? '',
                $row['calcium'] ?? '',
                $row['phosphorus'] ?? '',
                $row['pth'] ?? '',
                $row['albumin'] ?? '',
                $row['total_protein'] ?? ''
            ]);
        }
        
        // Add empty rows
        fputcsv($output, []);
        fputcsv($output, []);
        
        // Export Quarterly Lab Data if exists
        $quarterly_check = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='quarterly_lab_data'");
        if ($quarterly_check->fetchColumn() > 0) {
            fputcsv($output, ['=== QUARTERLY LABORATORY DATA ===']);
            fputcsv($output, [
                'ID', 'Patient ID', 'Patient Name', 'Quarter Year', 'Date 1', 'Date 2', 'Date 3',
                'Hb 1 (g/L)', 'Hb 2 (g/L)', 'Hb 3 (g/L)', 'Hb Diff 1-2 (%)', 'Hb Change 1-2 (%)',
                'MCV 1', 'MCV 2', 'MCV 3', 'Iron 1', 'Iron 2', 'Iron 3',
                'TIBC 1', 'TIBC 2', 'TIBC 3', 'TSAT 1', 'TSAT 2', 'TSAT 3',
                'Ferritin 1', 'Ferritin 2', 'Ferritin 3', 'WBC 1', 'WBC 2', 'WBC 3',
                'Platelets 1', 'Platelets 2', 'Platelets 3',
                'Calcium 1 (mmol/L)', 'Calcium 2 (mmol/L)', 'Calcium 3 (mmol/L)',
                'Phosphorus 1 (mmol/L)', 'Phosphorus 2 (mmol/L)', 'Phosphorus 3 (mmol/L)',
                'Albumin 1', 'Albumin 2', 'Albumin 3',
                'PTH 1 (Pmol/L)', 'PTH 2 (Pmol/L)', 'PTH 3 (Pmol/L)',
                'PTH 1 (pg/mL)', 'PTH 2 (pg/mL)', 'PTH 3 (pg/mL)',
                'Vitamin D 1', 'Vitamin D 2', 'Vitamin D 3',
                'Corrected Ca 1', 'Corrected Ca 2', 'Corrected Ca 3',
                'Ca×Phos 1', 'Ca×Phos 2', 'Ca×Phos 3',
                'Sodium 1', 'Sodium 2', 'Sodium 3',
                'Potassium 1', 'Potassium 2', 'Potassium 3',
                'Uric Acid 1', 'Uric Acid 2', 'Uric Acid 3',
                'Creatinine 1', 'Creatinine 2', 'Creatinine 3',
                'Pre-BUN 1', 'Pre-BUN 2', 'Pre-BUN 3',
                'Post-BUN 1', 'Post-BUN 2', 'Post-BUN 3',
                'Duration 1', 'Duration 2', 'Duration 3',
                'UF Volume 1', 'UF Volume 2', 'UF Volume 3',
                'Post-Weight 1', 'Post-Weight 2', 'Post-Weight 3',
                'URR 1', 'URR 2', 'URR 3',
                'Kt/V 1', 'Kt/V 2', 'Kt/V 3',
                'HBsAg 1', 'HBsAg 2', 'HBsAg 3', 'Anti HCV 1', 'Anti HCV 2', 'Anti HCV 3',
                'HIV 1', 'HIV 2', 'HIV 3', 'HBsAb 1', 'HBsAb 2', 'HBsAb 3'
            ]);
            
            $stmt = $pdo->query("
                SELECT q.*, p.name_english as patient_name 
                FROM quarterly_lab_data q 
                LEFT JOIN patients p ON q.patient_id = p.id 
                ORDER BY q.quarter_year DESC
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['id'] ?? '',
                    $row['patient_id'] ?? '',
                    $row['patient_name'] ?? '',
                    $row['quarter_year'] ?? '',
                    $row['date_1'] ?? '',
                    $row['date_2'] ?? '',
                    $row['date_3'] ?? '',
                    $row['hb_1'] ?? '',
                    $row['hb_2'] ?? '',
                    $row['hb_3'] ?? '',
                    $row['hb_diff_1_2'] ?? '',
                    $row['hb_change_1_2'] ?? '',
                    $row['mcv_1'] ?? '',
                    $row['mcv_2'] ?? '',
                    $row['mcv_3'] ?? '',
                    $row['iron_1'] ?? '',
                    $row['iron_2'] ?? '',
                    $row['iron_3'] ?? '',
                    $row['tibc_1'] ?? '',
                    $row['tibc_2'] ?? '',
                    $row['tibc_3'] ?? '',
                    $row['tsat_1'] ?? '',
                    $row['tsat_2'] ?? '',
                    $row['tsat_3'] ?? '',
                    $row['ferritin_1'] ?? '',
                    $row['ferritin_2'] ?? '',
                    $row['ferritin_3'] ?? '',
                    $row['wbc_1'] ?? '',
                    $row['wbc_2'] ?? '',
                    $row['wbc_3'] ?? '',
                    $row['platelets_1'] ?? '',
                    $row['platelets_2'] ?? '',
                    $row['platelets_3'] ?? '',
                    $row['calcium_1'] ?? '',
                    $row['calcium_2'] ?? '',
                    $row['calcium_3'] ?? '',
                    $row['phosphorus_1'] ?? '',
                    $row['phosphorus_2'] ?? '',
                    $row['phosphorus_3'] ?? '',
                    $row['albumin_1'] ?? '',
                    $row['albumin_2'] ?? '',
                    $row['albumin_3'] ?? '',
                    $row['pth_pmol_1'] ?? '',
                    $row['pth_pmol_2'] ?? '',
                    $row['pth_pmol_3'] ?? '',
                    $row['pth_pgml_1'] ?? '',
                    $row['pth_pgml_2'] ?? '',
                    $row['pth_pgml_3'] ?? '',
                    $row['vitamin_d_1'] ?? '',
                    $row['vitamin_d_2'] ?? '',
                    $row['vitamin_d_3'] ?? '',
                    $row['corrected_calcium_1'] ?? '',
                    $row['corrected_calcium_2'] ?? '',
                    $row['corrected_calcium_3'] ?? '',
                    $row['ca_phos_product_1'] ?? '',
                    $row['ca_phos_product_2'] ?? '',
                    $row['ca_phos_product_3'] ?? '',
                    $row['sodium_1'] ?? '',
                    $row['sodium_2'] ?? '',
                    $row['sodium_3'] ?? '',
                    $row['potassium_1'] ?? '',
                    $row['potassium_2'] ?? '',
                    $row['potassium_3'] ?? '',
                    $row['uric_acid_1'] ?? '',
                    $row['uric_acid_2'] ?? '',
                    $row['uric_acid_3'] ?? '',
                    $row['creatinine_1'] ?? '',
                    $row['creatinine_2'] ?? '',
                    $row['creatinine_3'] ?? '',
                    $row['pre_dialysis_bun_1'] ?? '',
                    $row['pre_dialysis_bun_2'] ?? '',
                    $row['pre_dialysis_bun_3'] ?? '',
                    $row['post_dialysis_bun_1'] ?? '',
                    $row['post_dialysis_bun_2'] ?? '',
                    $row['post_dialysis_bun_3'] ?? '',
                    $row['dialysis_duration_1'] ?? '',
                    $row['dialysis_duration_2'] ?? '',
                    $row['dialysis_duration_3'] ?? '',
                    $row['ultrafiltrate_volume_1'] ?? '',
                    $row['ultrafiltrate_volume_2'] ?? '',
                    $row['ultrafiltrate_volume_3'] ?? '',
                    $row['post_dialysis_weight_1'] ?? '',
                    $row['post_dialysis_weight_2'] ?? '',
                    $row['post_dialysis_weight_3'] ?? '',
                    $row['urr_1'] ?? '',
                    $row['urr_2'] ?? '',
                    $row['urr_3'] ?? '',
                    $row['kt_v_1'] ?? '',
                    $row['kt_v_2'] ?? '',
                    $row['kt_v_3'] ?? '',
                    $row['hbsag_1'] ?? '',
                    $row['hbsag_2'] ?? '',
                    $row['hbsag_3'] ?? '',
                    $row['anti_hcv_1'] ?? '',
                    $row['anti_hcv_2'] ?? '',
                    $row['anti_hcv_3'] ?? '',
                    $row['hiv_1'] ?? '',
                    $row['hiv_2'] ?? '',
                    $row['hiv_3'] ?? '',
                    $row['hbsab_1'] ?? '',
                    $row['hbsab_2'] ?? '',
                    $row['hbsab_3'] ?? ''
                ]);
            }
            
            // Add empty rows
            fputcsv($output, []);
            fputcsv($output, []);
        }
        
        // Export Medical Background
        fputcsv($output, ['=== MEDICAL BACKGROUND ===']);
        fputcsv($output, [
            'ID', 'Patient ID', 'Patient Name', 'Previous Dialysis', 'Dialysis Duration',
            'Previous Transplant', 'Transplant Date', 'Access Type', 'Access Date',
            'Diabetes', 'Hypertension', 'Heart Disease', 'Other Conditions'
        ]);
        
        $stmt = $pdo->query("
            SELECT m.*, p.name_english as patient_name 
            FROM medical_background m 
            LEFT JOIN patients p ON m.patient_id = p.id 
            ORDER BY m.id
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'] ?? '',
                $row['patient_id'] ?? '',
                $row['patient_name'] ?? '',
                $row['previous_dialysis'] ?? '',
                $row['dialysis_duration'] ?? '',
                $row['previous_transplant'] ?? '',
                $row['transplant_date'] ?? '',
                $row['access_type'] ?? '',
                $row['access_date'] ?? '',
                $row['diabetes'] ?? '',
                $row['hypertension'] ?? '',
                $row['heart_disease'] ?? '',
                $row['other_conditions'] ?? ''
            ]);
        }
        
        // Add empty rows
        fputcsv($output, []);
        fputcsv($output, []);
        
        // Export Catheter Infections
        fputcsv($output, ['=== CATHETER INFECTIONS ===']);
        fputcsv($output, [
            'ID', 'Patient ID', 'Patient Name', 'Infection Date', 'Organism', 
            'Treatment', 'Outcome', 'Notes'
        ]);
        
        $stmt = $pdo->query("
            SELECT c.*, p.name_english as patient_name 
            FROM catheter_infections c 
            LEFT JOIN patients p ON c.patient_id = p.id 
            ORDER BY c.infection_date DESC
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'] ?? '',
                $row['patient_id'] ?? '',
                $row['patient_name'] ?? '',
                $row['infection_date'] ?? '',
                $row['organism'] ?? '',
                $row['treatment'] ?? '',
                $row['outcome'] ?? '',
                $row['notes'] ?? ''
            ]);
        }
        
        // Add empty rows
        fputcsv($output, []);
        fputcsv($output, []);
        
        // Export Dialysis Complications
        fputcsv($output, ['=== DIALYSIS COMPLICATIONS ===']);
        fputcsv($output, [
            'ID', 'Patient ID', 'Patient Name', 'Hypotension', 'Hypertension', 
            'Muscle Cramps', 'Nausea/Vomiting', 'Headache', 'Chest Pain', 
            'Pruritus', 'Fever/Chills', 'Dyspnea', 'Seizures', 'Arrhythmias'
        ]);
        
        try {
            $stmt = $pdo->query("
                SELECT c.*, p.name_english as patient_name 
                FROM dialysis_complications c 
                LEFT JOIN patients p ON c.patient_id = p.id 
                ORDER BY c.id DESC
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['id'] ?? '',
                    $row['patient_id'] ?? '',
                    $row['patient_name'] ?? '',
                    $row['hypotension'] ?? '',
                    $row['hypertension'] ?? '',
                    $row['muscle_cramps'] ?? '',
                    $row['nausea_vomiting'] ?? '',
                    $row['headache'] ?? '',
                    $row['chest_pain'] ?? '',
                    $row['pruritus'] ?? '',
                    $row['fever_chills'] ?? '',
                    $row['dyspnea'] ?? '',
                    $row['seizures'] ?? '',
                    $row['arrhythmias'] ?? ''
                ]);
            }
        } catch (Exception $e) {
            fputcsv($output, ['Error loading complications data: ' . $e->getMessage()]);
        }
        
        // Add empty rows
        fputcsv($output, []);
        fputcsv($output, []);
        
        // Add empty rows
        fputcsv($output, []);
        fputcsv($output, []);
        
        // Export HD Prescription
        fputcsv($output, ['=== HD PRESCRIPTION ===']);
        fputcsv($output, [
            'ID', 'Patient ID', 'Patient Name', 'Dialysis Modality', 'Dialyzer', 
            'Frequency', 'Duration', 'Vascular Access', 'Blood Flow Rate', 'Dialysate Flow Rate', 
            'Dry Body Weight', 'Ultrafiltration', 'Heparin Initial', 'Heparin Maintenance',
            'Sodium', 'Potassium', 'Calcium', 'Bicarbonate', 'Catheter Lock'
        ]);
        
        try {
            $stmt = $pdo->query("
                SELECT h.*, p.name_english as patient_name 
                FROM hd_prescription h 
                LEFT JOIN patients p ON h.patient_id = p.id 
                ORDER BY h.created_at DESC
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['id'] ?? '',
                    $row['patient_id'] ?? '',
                    $row['patient_name'] ?? '',
                    $row['dialysis_modality'] ?? '',
                    $row['dialyzer'] ?? '',
                    $row['frequency'] ?? '',
                    $row['duration'] ?? '',
                    $row['vascular_access'] ?? '',
                    $row['blood_flow_rate'] ?? '',
                    $row['dialysate_flow_rate'] ?? '',
                    $row['dry_body_weight'] ?? '',
                    $row['ultrafiltration'] ?? '',
                    $row['heparin_initial'] ?? '',
                    $row['heparin_maintenance'] ?? '',
                    $row['sodium'] ?? '',
                    $row['potassium'] ?? '',
                    $row['calcium'] ?? '',
                    $row['bicarbonate'] ?? '',
                    $row['catheter_lock'] ?? ''
                ]);
            }
        } catch (Exception $e) {
            fputcsv($output, ['Error loading HD prescription data: ' . $e->getMessage()]);
        }
        
        // Add empty rows
        fputcsv($output, []);
        fputcsv($output, []);
        
        // Export Medications
        fputcsv($output, ['=== MEDICATIONS ===']);
        fputcsv($output, [
            'ID', 'Patient ID', 'Patient Name', 'Medication Date', 'Medication Name', 'Dosage', 
            'Frequency', 'Route', 'Row Order'
        ]);
        
        try {
            $stmt = $pdo->query("
                SELECT m.*, p.name_english as patient_name 
                FROM medications m 
                LEFT JOIN patients p ON m.patient_id = p.id 
                ORDER BY m.created_at DESC
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['id'] ?? '',
                    $row['patient_id'] ?? '',
                    $row['patient_name'] ?? '',
                    $row['medication_date'] ?? '',
                    $row['medication_name'] ?? '',
                    $row['dosage'] ?? '',
                    $row['frequency'] ?? '',
                    $row['route'] ?? '',
                    $row['row_order'] ?? ''
                ]);
            }
        } catch (Exception $e) {
            fputcsv($output, ['Error loading medications data: ' . $e->getMessage()]);
        }
        
        // Add empty rows
        fputcsv($output, []);
        fputcsv($output, []);
        
        // Export Vaccinations
        fputcsv($output, ['=== VACCINATIONS ===']);
        fputcsv($output, [
            'ID', 'Patient ID', 'Patient Name', 'Hepatitis B Date', 'Flu Vaccine Date', 
            'COVID-19 Date', 'Pneumococcal Date', 'Other Vaccines', 'Notes'
        ]);
        
        try {
            $stmt = $pdo->query("
                SELECT v.*, p.name_english as patient_name 
                FROM vaccinations v 
                LEFT JOIN patients p ON v.patient_id = p.id 
                ORDER BY v.id
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['id'] ?? '',
                    $row['patient_id'] ?? '',
                    $row['patient_name'] ?? '',
                    $row['hepatitis_b_date'] ?? '',
                    $row['flu_vaccine_date'] ?? '',
                    $row['covid19_date'] ?? '',
                    $row['pneumococcal_date'] ?? '',
                    $row['other_vaccines'] ?? '',
                    $row['notes'] ?? ''
                ]);
            }
        } catch (Exception $e) {
            fputcsv($output, ['Error loading vaccinations data: ' . $e->getMessage()]);
        }
        
        fclose($output);
        
    } elseif ($export_type === 'mysql') {
        // Export as MySQL dump
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="dialysis_patients_mysql_dump_' . date('Y-m-d') . '.sql"');
        
        echo "-- Dialysis Patient Management System MySQL Export\n";
        echo "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        echo "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        // Get all tables
        $tables = ['patients', 'laboratory_data', 'medical_background', 'catheter_infections', 
                  'dialysis_complications', 'hd_prescription', 'medications', 'vaccinations'];
        
        // Check if quarterly_lab_data table exists
        $quarterly_check = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='quarterly_lab_data'");
        if ($quarterly_check->fetchColumn() > 0) {
            $tables[] = 'quarterly_lab_data';
        }
        
        foreach ($tables as $table) {
            // Check if table exists
            $table_check = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='$table'");
            if ($table_check->fetchColumn() == 0) {
                continue;
            }
            
            echo "-- Table structure for table `$table`\n";
            echo "DROP TABLE IF EXISTS `$table`;\n";
            
            // Get table schema
            $schema = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='$table'")->fetchColumn();
            
            // Convert SQLite to MySQL syntax
            $mysql_schema = str_replace([
                'INTEGER PRIMARY KEY AUTOINCREMENT',
                'AUTOINCREMENT',
                'TEXT',
                'DATETIME DEFAULT CURRENT_TIMESTAMP',
                'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
            ], [
                'INT AUTO_INCREMENT PRIMARY KEY',
                'AUTO_INCREMENT',
                'VARCHAR(255)',
                'DATETIME DEFAULT CURRENT_TIMESTAMP',
                'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
            ], $schema);
            
            echo $mysql_schema . ";\n\n";
            
            // Export data
            echo "-- Dumping data for table `$table`\n";
            
            $stmt = $pdo->query("SELECT * FROM $table");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $column_list = '`' . implode('`, `', $columns) . '`';
                
                echo "INSERT INTO `$table` ($column_list) VALUES\n";
                
                $values = [];
                foreach ($rows as $row) {
                    $escaped_values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $escaped_values[] = 'NULL';
                        } else {
                            $escaped_values[] = "'" . addslashes($value) . "'";
                        }
                    }
                    $values[] = '(' . implode(', ', $escaped_values) . ')';
                }
                
                echo implode(",\n", $values) . ";\n\n";
            }
        }
        
        echo "SET FOREIGN_KEY_CHECKS = 1;\n";
        echo "-- Export completed\n";
    }
    
} catch (Exception $e) {
    die('Export failed: ' . $e->getMessage());
}
?>