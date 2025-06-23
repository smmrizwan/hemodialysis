<?php
require_once 'config/init.php';
$page_title = 'Medical Background';
include 'includes/header.php';

// Get direct database connection
$db = new PDO("sqlite:" . __DIR__ . "/database/dialysis_management.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("PRAGMA foreign_keys = ON");

// Get patient list for selection
try {
    $stmt = $db->prepare("SELECT id, file_number, name_english FROM patients ORDER BY name_english");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Medical background form: Found " . count($patients) . " patients");
} catch(PDOException $e) {
    error_log("Medical background form database error: " . $e->getMessage());
    $patients = [];
}

$selected_patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : '';
$background_data = [];

// Get existing background data if patient selected
if ($selected_patient_id) {
    try {
        $stmt = $db->prepare("SELECT * FROM medical_background WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$selected_patient_id]);
        $background_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If no data found, initialize empty array
        if (!$background_data) {
            $background_data = [];
        }
        
        error_log("Medical background form: Found background data for patient " . $selected_patient_id . ": " . print_r($background_data, true));
    } catch(PDOException $e) {
        error_log("Medical background form error: " . $e->getMessage());
        $background_data = [];
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>
                        <i class="fas fa-history me-2"></i>Medical Background
                    </h4>
                    <a href="dashboard.php" class="btn btn-secondary float-end">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
                <div class="card-body">
                    <!-- Patient Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Select Patient <span class="text-danger">*</span></label>
                            <select class="form-select patient-selector" id="patientSelect" onchange="loadPatientBackground()" required>
                                <option value="">Choose a patient...</option>
                                <?php foreach ($patients as $patient): ?>
                                    <option value="<?php echo $patient['id']; ?>" 
                                            <?php echo ($selected_patient_id == $patient['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($patient['name_english']) . ' (' . htmlspecialchars($patient['file_number']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <?php if ($selected_patient_id): ?>
                    <!-- Patient Selection Confirmation -->
                    <?php
                    $selected_patient = null;
                    foreach ($patients as $patient) {
                        if ($patient['id'] == $selected_patient_id) {
                            $selected_patient = $patient;
                            break;
                        }
                    }
                    ?>
                    <?php if ($selected_patient): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-user-check me-2"></i>
                        <strong>Selected Patient:</strong> <?php echo htmlspecialchars($selected_patient['name_english']); ?> 
                        (File #<?php echo htmlspecialchars($selected_patient['file_number']); ?>)
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Medical Background Form -->
                    <form id="backgroundForm" method="POST" action="api/save_medical_background.php" data-ajax="true">
                        <input type="hidden" name="patient_id" value="<?php echo $selected_patient_id; ?>">
                        
                        <!-- History of Dialysis & Transplant -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-procedures me-2"></i>Previous Renal Replacement Therapy
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- History of PD -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card border-info">
                                            <div class="card-header bg-info text-white">
                                                <h6 class="mb-0">Peritoneal Dialysis (PD)</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" name="history_pd" id="history_pd" 
                                                           <?php echo ($background_data && $background_data['history_pd']) ? 'checked' : ''; ?>
                                                           onchange="togglePDDates()">
                                                    <label class="form-check-label" for="history_pd">
                                                        <strong>History of Peritoneal Dialysis</strong>
                                                    </label>
                                                </div>
                                                
                                                <div id="pdDates" style="display: <?php echo ($background_data && $background_data['history_pd']) ? 'block' : 'none'; ?>;">
                                                    <div class="row">
                                                        <div class="col-md-6 mb-2">
                                                            <label class="form-label">Start Date</label>
                                                            <input type="date" class="form-control" name="pd_start_date" 
                                                                   value="<?php echo $background_data['pd_start_date'] ?? ''; ?>">
                                                        </div>
                                                        <div class="col-md-6 mb-2">
                                                            <label class="form-label">End Date</label>
                                                            <input type="date" class="form-control" name="pd_end_date" 
                                                                   value="<?php echo $background_data['pd_end_date'] ?? ''; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- History of Transplant -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card border-success">
                                            <div class="card-header bg-success text-white">
                                                <h6 class="mb-0">Renal Transplant</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" name="history_transplant" id="history_transplant"
                                                           <?php echo ($background_data && $background_data['history_transplant']) ? 'checked' : ''; ?>
                                                           onchange="toggleTransplantDates()">
                                                    <label class="form-check-label" for="history_transplant">
                                                        <strong>History of Renal Transplant</strong>
                                                    </label>
                                                </div>
                                                
                                                <div id="transplantDates" style="display: <?php echo ($background_data && $background_data['history_transplant']) ? 'block' : 'none'; ?>;">
                                                    <div class="row">
                                                        <div class="col-md-6 mb-2">
                                                            <label class="form-label">Transplant Date</label>
                                                            <input type="date" class="form-control" name="transplant_start_date" 
                                                                   value="<?php echo $background_data['transplant_start_date'] ?? ''; ?>">
                                                        </div>
                                                        <div class="col-md-6 mb-2">
                                                            <label class="form-label">Failure Date</label>
                                                            <input type="date" class="form-control" name="transplant_end_date" 
                                                                   value="<?php echo $background_data['transplant_end_date'] ?? ''; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Medical Conditions -->
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="fas fa-stethoscope me-2"></i>Medical Conditions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Diabetes Mellitus -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card border-danger">
                                            <div class="card-header bg-danger text-white">
                                                <h6 class="mb-0">Diabetes Mellitus</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" name="history_dm" id="history_dm"
                                                           <?php echo ($background_data && $background_data['history_dm']) ? 'checked' : ''; ?>
                                                           onchange="toggleDMDuration()">
                                                    <label class="form-check-label" for="history_dm">
                                                        <strong>History of Diabetes Mellitus</strong>
                                                    </label>
                                                </div>
                                                
                                                <div id="dmDuration" style="display: <?php echo ($background_data && $background_data['history_dm']) ? 'block' : 'none'; ?>;">
                                                    <label class="form-label">Duration (Years)</label>
                                                    <input type="number" class="form-control" name="dm_duration_years" min="0" max="100"
                                                           value="<?php echo $background_data['dm_duration_years'] ?? ''; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Hypertension -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card border-primary">
                                            <div class="card-header bg-primary text-white">
                                                <h6 class="mb-0">Hypertension</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" name="history_htn" id="history_htn"
                                                           <?php echo ($background_data && $background_data['history_htn']) ? 'checked' : ''; ?>
                                                           onchange="toggleHTNDuration()">
                                                    <label class="form-check-label" for="history_htn">
                                                        <strong>History of Hypertension</strong>
                                                    </label>
                                                </div>
                                                
                                                <div id="htnDuration" style="display: <?php echo ($background_data && $background_data['history_htn']) ? 'block' : 'none'; ?>;">
                                                    <label class="form-label">Duration (Years)</label>
                                                    <input type="number" class="form-control" name="htn_duration_years" min="0" max="100"
                                                           value="<?php echo $background_data['htn_duration_years'] ?? ''; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Cardiac Problem -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card border-warning">
                                            <div class="card-header bg-warning text-dark">
                                                <h6 class="mb-0">Cardiac Problems</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" name="history_cardiac" id="history_cardiac"
                                                           <?php echo ($background_data && $background_data['history_cardiac']) ? 'checked' : ''; ?>
                                                           onchange="toggleCardiacDuration()">
                                                    <label class="form-check-label" for="history_cardiac">
                                                        <strong>History of Cardiac Problems</strong>
                                                    </label>
                                                </div>
                                                
                                                <div id="cardiacDuration" style="display: <?php echo ($background_data && $background_data['history_cardiac']) ? 'block' : 'none'; ?>;">
                                                    <label class="form-label">Duration (Years)</label>
                                                    <input type="number" class="form-control" name="cardiac_duration_years" min="0" max="100"
                                                           value="<?php echo $background_data['cardiac_duration_years'] ?? ''; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Residual Renal Function -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card border-info">
                                            <div class="card-header bg-info text-white">
                                                <h6 class="mb-0">Residual Renal Function</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" name="residual_renal_function" id="residual_renal_function"
                                                           <?php echo ($background_data && $background_data['residual_renal_function']) ? 'checked' : ''; ?>
                                                           onchange="toggleResidualUrine()">
                                                    <label class="form-check-label" for="residual_renal_function">
                                                        <strong>Has Residual Renal Function</strong>
                                                    </label>
                                                </div>
                                                
                                                <div id="residualUrine" style="display: <?php echo ($background_data && $background_data['residual_renal_function']) ? 'block' : 'none'; ?>;">
                                                    <label class="form-label">Urine Output (ml/day)</label>
                                                    <input type="number" class="form-control" name="residual_urine_ml" min="0" max="5000"
                                                           value="<?php echo $background_data['residual_urine_ml'] ?? ''; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Medical Information -->
                        <div class="card mb-4">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-clipboard-list me-2"></i>Additional Medical Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Primary Cause of CKD-V</label>
                                        <input type="text" class="form-control" name="primary_ckd_cause" 
                                               value="<?php echo htmlspecialchars($background_data['primary_ckd_cause'] ?? ''); ?>"
                                               placeholder="e.g., Diabetic Nephropathy, Hypertensive Nephrosclerosis">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Last Abdominal US Date</label>
                                        <input type="date" class="form-control" name="last_us_date" 
                                               value="<?php echo $background_data['last_us_date'] ?? ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Last Abdominal US Findings</label>
                                        <textarea class="form-control" name="last_us_findings" rows="3"
                                                  placeholder="Enter significant ultrasound findings"><?php echo htmlspecialchars($background_data['last_us_findings'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Last Echocardiography Date</label>
                                        <input type="date" class="form-control" name="last_echo_date" 
                                               value="<?php echo $background_data['last_echo_date'] ?? ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Last Echocardiography Findings</label>
                                        <textarea class="form-control" name="last_echo_findings" rows="3"
                                                  placeholder="Enter significant echocardiography findings"><?php echo htmlspecialchars($background_data['last_echo_findings'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Other Medical and Surgical History</label>
                                        <textarea class="form-control" name="other_history" rows="4"
                                                  placeholder="Enter other significant medical and surgical history"><?php echo htmlspecialchars($background_data['other_history'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-lg me-2">
                                    <i class="fas fa-save me-2"></i>Save Medical Background
                                </button>
                                <button type="button" class="btn btn-secondary btn-lg" onclick="clearBackgroundForm()">
                                    <i class="fas fa-eraser me-2"></i>Clear Form
                                </button>
                                <button type="button" class="btn btn-info btn-lg" onclick="generateBackgroundReport()">
                                    <i class="fas fa-file-alt me-2"></i>Generate Report
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadPatientBackground() {
    const patientId = document.getElementById('patientSelect').value;
    if (patientId) {
        window.location.href = 'medical_background_form.php?patient_id=' + patientId;
    }
}

function togglePDDates() {
    const checkbox = document.getElementById('history_pd');
    const datesDiv = document.getElementById('pdDates');
    datesDiv.style.display = checkbox.checked ? 'block' : 'none';
}

function toggleTransplantDates() {
    const checkbox = document.getElementById('history_transplant');
    const datesDiv = document.getElementById('transplantDates');
    datesDiv.style.display = checkbox.checked ? 'block' : 'none';
}

function toggleDMDuration() {
    const checkbox = document.getElementById('history_dm');
    const durationDiv = document.getElementById('dmDuration');
    durationDiv.style.display = checkbox.checked ? 'block' : 'none';
}

function toggleHTNDuration() {
    const checkbox = document.getElementById('history_htn');
    const durationDiv = document.getElementById('htnDuration');
    durationDiv.style.display = checkbox.checked ? 'block' : 'none';
}

function toggleCardiacDuration() {
    const checkbox = document.getElementById('history_cardiac');
    const durationDiv = document.getElementById('cardiacDuration');
    durationDiv.style.display = checkbox.checked ? 'block' : 'none';
}

function toggleResidualUrine() {
    const checkbox = document.getElementById('residual_renal_function');
    const urineDiv = document.getElementById('residualUrine');
    urineDiv.style.display = checkbox.checked ? 'block' : 'none';
}

function clearBackgroundForm() {
    if (confirm('Are you sure you want to clear all medical background data?')) {
        document.getElementById('backgroundForm').reset();
        // Hide all conditional sections
        document.getElementById('pdDates').style.display = 'none';
        document.getElementById('transplantDates').style.display = 'none';
        document.getElementById('dmDuration').style.display = 'none';
        document.getElementById('htnDuration').style.display = 'none';
        document.getElementById('cardiacDuration').style.display = 'none';
        document.getElementById('residualUrine').style.display = 'none';
    }
}

function generateBackgroundReport() {
    const patientId = '<?php echo $selected_patient_id; ?>';
    if (patientId) {
        window.open(`patient_report.php?id=${patientId}&section=background`, '_blank');
    }
}

// Form submission handling - wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    const backgroundForm = document.getElementById('backgroundForm');
    if (backgroundForm) {
        let isSubmitting = false;
        
        backgroundForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (isSubmitting) {
                return false;
            }
            
            isSubmitting = true;
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            }
            
            const formData = new FormData(this);
            
            fetch('api/save_medical_background.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Medical background saved successfully!');
                    // Clear unsaved changes flag before redirect
                    if (typeof FormManager !== 'undefined') {
                        FormManager.unsavedChanges = false;
                    }
                    window.onbeforeunload = null;
                    // Redirect to same page with patient ID to refresh data
                    const patientId = '<?php echo $selected_patient_id; ?>';
                    window.location.href = 'medical_background_form.php?patient_id=' + patientId;
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the medical background.');
            })
            .finally(() => {
                isSubmitting = false;
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-save me-2"></i>Save Medical Background';
                }
            });
        });
    }
    
    // Patient selection maintenance
    const urlParams = new URLSearchParams(window.location.search);
    const patientId = urlParams.get('patient_id');
    const patientSelect = document.getElementById('patientSelect');
    
    if (patientId && patientSelect) {
        patientSelect.value = patientId;
        console.log('Patient selection set to:', patientId);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
