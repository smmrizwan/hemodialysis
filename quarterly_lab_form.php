<?php
require_once 'config/database_sqlite.php';

// Get direct database connection
$db = new PDO("sqlite:" . __DIR__ . "/database/dialysis_management.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("PRAGMA foreign_keys = ON");

// Get list of patients for dropdown
$patients = [];
try {
    $stmt = $db->prepare("SELECT id, file_number, name_english FROM patients ORDER BY file_number");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching patients: " . $e->getMessage());
}

// Get existing quarterly data if editing
$quarterly_data = null;
$edit_mode = false;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_mode = true;
    $quarterly_id = (int)$_GET['edit'];
    
    try {
        $stmt = $db->prepare("SELECT * FROM quarterly_lab_data WHERE id = ?");
        $stmt->execute([$quarterly_id]);
        $quarterly_data = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error fetching quarterly data: " . $e->getMessage());
    }
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        <?php echo $edit_mode ? 'Edit Quarterly Laboratory Data' : 'Quarterly Laboratory Data Entry'; ?>
                    </h4>
                </div>
                <div class="card-body">
                    
                    <!-- Patient Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Select Patient <span class="text-danger">*</span></label>
                            <select class="form-select" id="patientSelect" name="patient_id" required>
                                <option value="">Choose a patient...</option>
                                <?php foreach ($patients as $patient): ?>
                                    <option value="<?php echo $patient['id']; ?>" 
                                            <?php echo ($edit_mode && $quarterly_data && $quarterly_data['patient_id'] == $patient['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($patient['file_number'] . ' - ' . $patient['name_english']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Quarter/Year <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="quarter_year" 
                                   placeholder="e.g., Q1 2025, Q2 2025" 
                                   value="<?php echo $edit_mode && $quarterly_data ? htmlspecialchars($quarterly_data['quarter_year']) : ''; ?>" required>
                        </div>
                    </div>

                    <!-- Quarterly Lab Data Form -->
                    <form id="quarterlyLabForm" novalidate>
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="quarterly_id" value="<?php echo $quarterly_data['id']; ?>">
                        <?php endif; ?>
                        
                        <input type="hidden" name="patient_id" id="hiddenPatientId" value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['patient_id'] : ''; ?>">
                        <input type="hidden" name="quarter_year" id="hiddenQuarterYear" value="<?php echo $edit_mode && $quarterly_data ? htmlspecialchars($quarterly_data['quarter_year']) : ''; ?>">

                        <!-- Spreadsheet Style Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered quarterly-lab-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 200px; background-color: #f8f9fa;">Test Parameter</th>
                                        <th style="width: 150px; background-color: #e3f2fd;">Date 1</th>
                                        <th style="width: 150px; background-color: #e3f2fd;">Date 2</th>
                                        <th style="width: 150px; background-color: #e3f2fd;">Date 3</th>
                                    </tr>
                                    <tr>
                                        <th></th>
                                        <th>
                                            <input type="date" class="form-control form-control-sm" name="date_1" 
                                                   value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['date_1'] : ''; ?>">
                                        </th>
                                        <th>
                                            <input type="date" class="form-control form-control-sm" name="date_2" 
                                                   value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['date_2'] : ''; ?>">
                                        </th>
                                        <th>
                                            <input type="date" class="form-control form-control-sm" name="date_3" 
                                                   value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['date_3'] : ''; ?>">
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Hematology Section -->
                                    <tr>
                                        <td><strong>Hb (g/L)</strong></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="hb_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['hb_1'] : ''; ?>" 
                                                  onchange="calculateHbChange()"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="hb_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['hb_2'] : ''; ?>" 
                                                  onchange="calculateHbChange()"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="hb_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['hb_3'] : ''; ?>" 
                                                  onchange="calculateHbChange()"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Hb Percentage Difference (Date 1 vs Date 2)</strong></td>
                                        <td colspan="2"><input type="number" step="0.01" class="form-control form-control-sm" name="hb_diff_1_2" 
                                                              value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['hb_diff_1_2'] : ''; ?>" readonly></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Hb Percentage Change (Date 1 vs Date 2)</strong></td>
                                        <td colspan="2"><input type="number" step="0.01" class="form-control form-control-sm" name="hb_change_1_2" 
                                                              value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['hb_change_1_2'] : ''; ?>" readonly></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td><strong>MCV (fL)</strong></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="mcv_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['mcv_1'] : ''; ?>"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="mcv_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['mcv_2'] : ''; ?>"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="mcv_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['mcv_3'] : ''; ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Iron (μmol/L)</strong></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="iron_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['iron_1'] : ''; ?>"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="iron_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['iron_2'] : ''; ?>"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="iron_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['iron_3'] : ''; ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>TIBC (μmol/L)</strong></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="tibc_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['tibc_1'] : ''; ?>"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="tibc_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['tibc_2'] : ''; ?>"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="tibc_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['tibc_3'] : ''; ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>TSAT (%)</strong></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="tsat_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['tsat_1'] : ''; ?>" readonly></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="tsat_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['tsat_2'] : ''; ?>" readonly></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="tsat_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['tsat_3'] : ''; ?>" readonly></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Ferritin (ng/mL)</strong></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="ferritin_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['ferritin_1'] : ''; ?>"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="ferritin_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['ferritin_2'] : ''; ?>"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="ferritin_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['ferritin_3'] : ''; ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>WBC (×10³/μL)</strong></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="wbc_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['wbc_1'] : ''; ?>"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="wbc_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['wbc_2'] : ''; ?>"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="wbc_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['wbc_3'] : ''; ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Platelets (×10³/μL)</strong></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="platelets_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['platelets_1'] : ''; ?>"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="platelets_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['platelets_2'] : ''; ?>"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="platelets_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['platelets_3'] : ''; ?>"></td>
                                    </tr>
                                    <!-- Empty Row -->
                                    <tr><td colspan="4" style="height: 10px; background-color: #f8f9fa;"></td></tr>
                                    
                                    <!-- Chemistry Section -->
                                    <tr>
                                        <td><strong>Calcium (mmol/L)</strong></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="calcium_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['calcium_1'] : ''; ?>" 
                                                  onchange="calculateCorrectedCalcium();"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="calcium_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['calcium_2'] : ''; ?>" 
                                                  onchange="calculateCorrectedCalcium();"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="calcium_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['calcium_3'] : ''; ?>" 
                                                  onchange="calculateCorrectedCalcium();"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Phosphorus (mmol/L)</strong></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="phosphorus_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['phosphorus_1'] : ''; ?>"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="phosphorus_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['phosphorus_2'] : ''; ?>"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="phosphorus_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['phosphorus_3'] : ''; ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Albumin (g/L)</strong></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="albumin_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['albumin_1'] : ''; ?>" 
                                                  onchange="calculateCorrectedCalcium()"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="albumin_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['albumin_2'] : ''; ?>" 
                                                  onchange="calculateCorrectedCalcium()"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="albumin_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['albumin_3'] : ''; ?>" 
                                                  onchange="calculateCorrectedCalcium()"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>PTH (Pmol/L)</strong></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="pth_pmol_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['pth_pmol_1'] : ''; ?>" 
                                                  onchange="calculatePTHConversion()"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="pth_pmol_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['pth_pmol_2'] : ''; ?>" 
                                                  onchange="calculatePTHConversion()"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="pth_pmol_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['pth_pmol_3'] : ''; ?>" 
                                                  onchange="calculatePTHConversion()"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>PTH (pg/mL)</strong></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="pth_pgml_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['pth_pgml_1'] : ''; ?>" readonly></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="pth_pgml_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['pth_pgml_2'] : ''; ?>" readonly></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="pth_pgml_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['pth_pgml_3'] : ''; ?>" readonly></td>
                                    </tr>
                                    <tr>
                                        <td><strong>25-OH-D (ng/mL)</strong></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="vitamin_d_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['vitamin_d_1'] : ''; ?>"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="vitamin_d_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['vitamin_d_2'] : ''; ?>"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="vitamin_d_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['vitamin_d_3'] : ''; ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Corrected Calcium</strong></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="corrected_calcium_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['corrected_calcium_1'] : ''; ?>" readonly></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="corrected_calcium_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['corrected_calcium_2'] : ''; ?>" readonly></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="corrected_calcium_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['corrected_calcium_3'] : ''; ?>" readonly></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Ca × Phosphorus</strong></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="ca_phos_product_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['ca_phos_product_1'] : ''; ?>" readonly></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="ca_phos_product_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['ca_phos_product_2'] : ''; ?>" readonly></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="ca_phos_product_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['ca_phos_product_3'] : ''; ?>" readonly></td>
                                    </tr>
                                    
                                    <!-- Additional Chemistry Section -->
                                    <tr>
                                        <td><strong>Sodium (mmol/L)</strong></td>
                                        <td><input type="number" step="0.1" class="form-control form-control-sm" name="sodium_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['sodium_1'] : ''; ?>"></td>
                                        <td><input type="number" step="0.1" class="form-control form-control-sm" name="sodium_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['sodium_2'] : ''; ?>"></td>
                                        <td><input type="number" step="0.1" class="form-control form-control-sm" name="sodium_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['sodium_3'] : ''; ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Potassium (mmol/L)</strong></td>
                                        <td><input type="number" step="0.1" class="form-control form-control-sm" name="potassium_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['potassium_1'] : ''; ?>"></td>
                                        <td><input type="number" step="0.1" class="form-control form-control-sm" name="potassium_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['potassium_2'] : ''; ?>"></td>
                                        <td><input type="number" step="0.1" class="form-control form-control-sm" name="potassium_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['potassium_3'] : ''; ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Uric Acid (μmol/L)</strong></td>
                                        <td><input type="number" step="1" class="form-control form-control-sm" name="uric_acid_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['uric_acid_1'] : ''; ?>"></td>
                                        <td><input type="number" step="1" class="form-control form-control-sm" name="uric_acid_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['uric_acid_2'] : ''; ?>"></td>
                                        <td><input type="number" step="1" class="form-control form-control-sm" name="uric_acid_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['uric_acid_3'] : ''; ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Creatinine (μmol/L)</strong></td>
                                        <td><input type="number" step="1" class="form-control form-control-sm" name="creatinine_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['creatinine_1'] : ''; ?>"></td>
                                        <td><input type="number" step="1" class="form-control form-control-sm" name="creatinine_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['creatinine_2'] : ''; ?>"></td>
                                        <td><input type="number" step="1" class="form-control form-control-sm" name="creatinine_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['creatinine_3'] : ''; ?>"></td>
                                    </tr>
                                    
                                    <!-- Empty Row -->
                                    <tr><td colspan="4" style="height: 10px; background-color: #e9ecef;"></td></tr>
                                    
                                    <!-- Dialysis Parameters Section -->
                                    <tr class="table-secondary">
                                        <td colspan="4" class="text-center"><strong>DIALYSIS PARAMETERS</strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Pre-dialysis BUN (mmol/L)</strong></td>
                                        <td><input type="number" step="0.1" class="form-control form-control-sm" name="pre_dialysis_bun_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['pre_dialysis_bun_1'] : ''; ?>" 
                                                  onchange="calculateKtVandURR(1)"></td>
                                        <td><input type="number" step="0.1" class="form-control form-control-sm" name="pre_dialysis_bun_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['pre_dialysis_bun_2'] : ''; ?>" 
                                                  onchange="calculateKtVandURR(2)"></td>
                                        <td><input type="number" step="0.1" class="form-control form-control-sm" name="pre_dialysis_bun_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['pre_dialysis_bun_3'] : ''; ?>" 
                                                  onchange="calculateKtVandURR(3)"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Post-dialysis BUN (mmol/L)</strong></td>
                                        <td><input type="number" step="0.1" class="form-control form-control-sm" name="post_dialysis_bun_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['post_dialysis_bun_1'] : ''; ?>" 
                                                  onchange="calculateKtVandURR(1)"></td>
                                        <td><input type="number" step="0.1" class="form-control form-control-sm" name="post_dialysis_bun_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['post_dialysis_bun_2'] : ''; ?>" 
                                                  onchange="calculateKtVandURR(2)"></td>
                                        <td><input type="number" step="0.1" class="form-control form-control-sm" name="post_dialysis_bun_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['post_dialysis_bun_3'] : ''; ?>" 
                                                  onchange="calculateKtVandURR(3)"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Duration (hours)</strong></td>
                                        <td><input type="number" step="0.25" class="form-control form-control-sm" name="dialysis_duration_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['dialysis_duration_1'] : ''; ?>" 
                                                  onchange="calculateKtVandURR(1)"></td>
                                        <td><input type="number" step="0.25" class="form-control form-control-sm" name="dialysis_duration_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['dialysis_duration_2'] : ''; ?>" 
                                                  onchange="calculateKtVandURR(2)"></td>
                                        <td><input type="number" step="0.25" class="form-control form-control-sm" name="dialysis_duration_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['dialysis_duration_3'] : ''; ?>" 
                                                  onchange="calculateKtVandURR(3)"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>UF Volume (L)</strong></td>
                                        <td><input type="number" step="0.1" class="form-control form-control-sm" name="ultrafiltrate_volume_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['ultrafiltrate_volume_1'] : ''; ?>" 
                                                  onchange="calculateKtVandURR(1)"></td>
                                        <td><input type="number" step="0.1" class="form-control form-control-sm" name="ultrafiltrate_volume_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['ultrafiltrate_volume_2'] : ''; ?>" 
                                                  onchange="calculateKtVandURR(2)"></td>
                                        <td><input type="number" step="0.1" class="form-control form-control-sm" name="ultrafiltrate_volume_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['ultrafiltrate_volume_3'] : ''; ?>" 
                                                  onchange="calculateKtVandURR(3)"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Post-dialysis Weight (kg)</strong></td>
                                        <td><input type="number" step="0.1" class="form-control form-control-sm" name="post_dialysis_weight_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['post_dialysis_weight_1'] : ''; ?>" 
                                                  onchange="calculateKtVandURR(1)"></td>
                                        <td><input type="number" step="0.1" class="form-control form-control-sm" name="post_dialysis_weight_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['post_dialysis_weight_2'] : ''; ?>" 
                                                  onchange="calculateKtVandURR(2)"></td>
                                        <td><input type="number" step="0.1" class="form-control form-control-sm" name="post_dialysis_weight_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['post_dialysis_weight_3'] : ''; ?>" 
                                                  onchange="calculateKtVandURR(3)"></td>
                                    </tr>
                                    
                                    <!-- Calculated Values -->
                                    <tr>
                                        <td><strong>URR (%)</strong></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="urr_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['urr_1'] : ''; ?>" readonly style="background-color: #f8f9fa;"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="urr_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['urr_2'] : ''; ?>" readonly style="background-color: #f8f9fa;"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="urr_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['urr_3'] : ''; ?>" readonly style="background-color: #f8f9fa;"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Kt/V</strong></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="kt_v_1" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['kt_v_1'] : ''; ?>" readonly style="background-color: #f8f9fa;"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="kt_v_2" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['kt_v_2'] : ''; ?>" readonly style="background-color: #f8f9fa;"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="kt_v_3" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['kt_v_3'] : ''; ?>" readonly style="background-color: #f8f9fa;"></td>
                                    </tr>
                                    
                                    <!-- Empty Row -->
                                    <tr><td colspan="4" style="height: 10px; background-color: #f8f9fa;"></td></tr>
                                    
                                    <!-- Serology Section -->
                                    <tr>
                                        <td><strong>HBsAg</strong></td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="hbsag_1" id="hbsag_1_pos" value="Positive" 
                                                           <?php echo ($edit_mode && $quarterly_data && $quarterly_data['hbsag_1'] == 'Positive') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="hbsag_1_pos">+</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="hbsag_1" id="hbsag_1_neg" value="Negative" 
                                                           <?php echo ($edit_mode && $quarterly_data && $quarterly_data['hbsag_1'] == 'Negative') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="hbsag_1_neg">-</label>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="hbsag_2" id="hbsag_2_pos" value="Positive" 
                                                           <?php echo ($edit_mode && $quarterly_data && $quarterly_data['hbsag_2'] == 'Positive') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="hbsag_2_pos">+</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="hbsag_2" id="hbsag_2_neg" value="Negative" 
                                                           <?php echo ($edit_mode && $quarterly_data && $quarterly_data['hbsag_2'] == 'Negative') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="hbsag_2_neg">-</label>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="hbsag_3" id="hbsag_3_pos" value="Positive" 
                                                           <?php echo ($edit_mode && $quarterly_data && $quarterly_data['hbsag_3'] == 'Positive') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="hbsag_3_pos">+</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="hbsag_3" id="hbsag_3_neg" value="Negative" 
                                                           <?php echo ($edit_mode && $quarterly_data && $quarterly_data['hbsag_3'] == 'Negative') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="hbsag_3_neg">-</label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Anti HCV</strong></td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="anti_hcv_1" id="anti_hcv_1_pos" value="Positive" 
                                                           <?php echo ($edit_mode && $quarterly_data && $quarterly_data['anti_hcv_1'] == 'Positive') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="anti_hcv_1_pos">+</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="anti_hcv_1" id="anti_hcv_1_neg" value="Negative" 
                                                           <?php echo ($edit_mode && $quarterly_data && $quarterly_data['anti_hcv_1'] == 'Negative') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="anti_hcv_1_neg">-</label>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="anti_hcv_2" id="anti_hcv_2_pos" value="Positive" 
                                                           <?php echo ($edit_mode && $quarterly_data && $quarterly_data['anti_hcv_2'] == 'Positive') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="anti_hcv_2_pos">+</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="anti_hcv_2" id="anti_hcv_2_neg" value="Negative" 
                                                           <?php echo ($edit_mode && $quarterly_data && $quarterly_data['anti_hcv_2'] == 'Negative') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="anti_hcv_2_neg">-</label>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="anti_hcv_3" id="anti_hcv_3_pos" value="Positive" 
                                                           <?php echo ($edit_mode && $quarterly_data && $quarterly_data['anti_hcv_3'] == 'Positive') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="anti_hcv_3_pos">+</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="anti_hcv_3" id="anti_hcv_3_neg" value="Negative" 
                                                           <?php echo ($edit_mode && $quarterly_data && $quarterly_data['anti_hcv_3'] == 'Negative') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="anti_hcv_3_neg">-</label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>HIV</strong></td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="hiv_1" id="hiv_1_pos" value="Positive" 
                                                           <?php echo ($edit_mode && $quarterly_data && $quarterly_data['hiv_1'] == 'Positive') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="hiv_1_pos">+</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="hiv_1" id="hiv_1_neg" value="Negative" 
                                                           <?php echo ($edit_mode && $quarterly_data && $quarterly_data['hiv_1'] == 'Negative') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="hiv_1_neg">-</label>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="hiv_2" id="hiv_2_pos" value="Positive" 
                                                           <?php echo ($edit_mode && $quarterly_data && $quarterly_data['hiv_2'] == 'Positive') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="hiv_2_pos">+</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="hiv_2" id="hiv_2_neg" value="Negative" 
                                                           <?php echo ($edit_mode && $quarterly_data && $quarterly_data['hiv_2'] == 'Negative') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="hiv_2_neg">-</label>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="hiv_3" id="hiv_3_pos" value="Positive" 
                                                           <?php echo ($edit_mode && $quarterly_data && $quarterly_data['hiv_3'] == 'Positive') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="hiv_3_pos">+</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="hiv_3" id="hiv_3_neg" value="Negative" 
                                                           <?php echo ($edit_mode && $quarterly_data && $quarterly_data['hiv_3'] == 'Negative') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="hiv_3_neg">-</label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>HBsAb (mIU/mL)</strong></td>
                                        <td><input type="number" class="form-control form-control-sm" name="hbsab_1" min="0" max="9999" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['hbsab_1'] : ''; ?>"></td>
                                        <td><input type="number" class="form-control form-control-sm" name="hbsab_2" min="0" max="9999" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['hbsab_2'] : ''; ?>"></td>
                                        <td><input type="number" class="form-control form-control-sm" name="hbsab_3" min="0" max="9999" 
                                                  value="<?php echo $edit_mode && $quarterly_data ? $quarterly_data['hbsab_3'] : ''; ?>"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Submit Button -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-lg me-2">
                                    <i class="fas fa-save me-2"></i>
                                    <?php echo $edit_mode ? 'Update Quarterly Data' : 'Save Quarterly Data'; ?>
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.quarterly-lab-table {
    font-size: 0.9rem;
}

.quarterly-lab-table th {
    text-align: center;
    vertical-align: middle;
    font-weight: 600;
}

.quarterly-lab-table td {
    vertical-align: middle;
}

.quarterly-lab-table input,
.quarterly-lab-table select {
    border: 1px solid #ced4da;
    text-align: center;
}

.quarterly-lab-table input:focus,
.quarterly-lab-table select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.quarterly-lab-table input[readonly] {
    background-color: #f8f9fa;
    color: #6c757d;
}

.table-responsive {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}
</style>

<script src="assets/js/calculations.js"></script>
<script>
// Update hidden fields when patient or quarter changes
document.getElementById('patientSelect').addEventListener('change', function() {
    document.getElementById('hiddenPatientId').value = this.value;
});

document.querySelector('input[name="quarter_year"]').addEventListener('input', function() {
    document.getElementById('hiddenQuarterYear').value = this.value;
});

// Calculate TSAT automatically
function calculateTSATQuarterly(column) {
    const ironInput = document.querySelector(`input[name="iron_${column}"]`);
    const tibcInput = document.querySelector(`input[name="tibc_${column}"]`);
    const tsatInput = document.querySelector(`input[name="tsat_${column}"]`);
    
    const iron = parseFloat(ironInput.value) || 0;
    const tibc = parseFloat(tibcInput.value) || 0;
    
    if (iron > 0 && tibc > 0) {
        const tsat = (iron / tibc) * 100;
        tsatInput.value = tsat.toFixed(2);
    } else {
        tsatInput.value = '';
    }
}

// Calculate Corrected Calcium
function calculateCorrectedCalciumQuarterly(column) {
    const calciumInput = document.querySelector(`input[name="calcium_${column}"]`);
    const albuminInput = document.querySelector(`input[name="albumin_${column}"]`);
    const correctedCalciumInput = document.querySelector(`input[name="corrected_calcium_${column}"]`);
    
    const calcium = parseFloat(calciumInput.value) || 0;
    const albumin = parseFloat(albuminInput.value) || 0;
    
    if (calcium > 0 && albumin > 0) {
        const correctedCalcium = calcium + 0.02 * (40 - albumin);
        correctedCalciumInput.value = correctedCalcium.toFixed(2);
    } else {
        correctedCalciumInput.value = '';
    }
}

// Calculate Ca x Phosphorus Product - requires albumin for corrected calcium
function calculateCaPhosProductQuarterly(column) {
    const calciumInput = document.querySelector(`input[name="calcium_${column}"]`);
    const phosphorusInput = document.querySelector(`input[name="phosphorus_${column}"]`);
    const albuminInput = document.querySelector(`input[name="albumin_${column}"]`);
    const productInput = document.querySelector(`input[name="ca_phos_product_${column}"]`);
    
    const calcium = parseFloat(calciumInput.value) || 0;
    const phosphorus = parseFloat(phosphorusInput.value) || 0;
    const albumin = parseFloat(albuminInput.value) || 0;
    
    // Only calculate if ALL three values are present
    if (calcium > 0 && phosphorus > 0 && albumin > 0) {
        // Calculate corrected calcium first
        const correctedCalciumMmol = calcium + 0.02 * (40 - albumin);
        
        // Convert to mg/dL for proper calculation
        const correctedCalciumMgDl = correctedCalciumMmol * 4.008;
        const phosphorusMgDl = phosphorus * 3.097;
        
        // Calculate Ca × Phosphorus product
        const product = correctedCalciumMgDl * phosphorusMgDl;
        productInput.value = product.toFixed(2);
    } else {
        productInput.value = '';
    }
}

// Add event listeners for calculations
for (let i = 1; i <= 3; i++) {
    // TSAT calculation
    document.querySelector(`input[name="iron_${i}"]`).addEventListener('input', () => calculateTSATQuarterly(i));
    document.querySelector(`input[name="tibc_${i}"]`).addEventListener('input', () => calculateTSATQuarterly(i));
    
    // Corrected Calcium calculation
    document.querySelector(`input[name="calcium_${i}"]`).addEventListener('input', () => {
        calculateCorrectedCalciumQuarterly(i);
    });
    document.querySelector(`input[name="albumin_${i}"]`).addEventListener('input', () => {
        calculateCorrectedCalciumQuarterly(i);
        calculateCaPhosProductQuarterly(i);
    });
    
    // No direct Ca x Phos calculation on phosphorus input - only through albumin
}

// Form submission
document.getElementById('quarterlyLabForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const patientId = document.getElementById('hiddenPatientId').value;
    const quarterYear = document.getElementById('hiddenQuarterYear').value;
    
    if (!patientId) {
        alert('Please select a patient');
        return;
    }
    
    if (!quarterYear.trim()) {
        alert('Please enter the quarter/year');
        return;
    }
    
    const formData = new FormData(this);
    
    // Disable submit button to prevent double submission
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
    
    fetch('api/save_quarterly_lab.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                if (typeof FormManager !== 'undefined' && FormManager.resetUnsavedChanges) {
                    FormManager.resetUnsavedChanges();
                }
                alert('Quarterly laboratory data saved successfully!');
                window.location.href = 'dashboard.php';
            } else {
                alert('Error: ' + (data.message || 'Unknown error occurred'));
            }
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', text);
            // If we can't parse JSON but got a response, it might still be successful
            if (text.includes('success') || text.includes('saved')) {
                alert('Data saved successfully!');
                window.location.href = 'dashboard.php';
            } else {
                alert('Error: Invalid response format');
            }
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('An error occurred while saving the data: ' + error.message);
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

// Initialize calculations if editing
<?php if ($edit_mode): ?>
document.addEventListener('DOMContentLoaded', function() {
    for (let i = 1; i <= 3; i++) {
        calculateTSATQuarterly(i);
        calculateCorrectedCalciumQuarterly(i);
        calculateCaPhosProductQuarterly(i);
    }
});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>