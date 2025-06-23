<?php
require_once 'config/init.php';
$page_title = 'HD Prescription';
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
    error_log("Prescription form: Found " . count($patients) . " patients");
} catch(PDOException $e) {
    error_log("Prescription form database error: " . $e->getMessage());
    $patients = [];
}

$selected_patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : '';
$prescription_data = [];

// Get existing prescription data if patient selected
if ($selected_patient_id) {
    try {
        $stmt = $db->prepare("SELECT * FROM hd_prescription WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$selected_patient_id]);
        $prescription_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If no data found, initialize empty array
        if (!$prescription_data) {
            $prescription_data = [];
        }
        
        error_log("Prescription form: Found prescription data for patient " . $selected_patient_id . ": " . print_r($prescription_data, true));
    } catch(PDOException $e) {
        error_log("Prescription form error: " . $e->getMessage());
        $prescription_data = [];
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>
                        <i class="fas fa-prescription me-2"></i>Current Hemodialysis Prescription
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
                            <select class="form-select patient-selector" id="patientSelect" onchange="loadPatientPrescription()" required>
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
                    
                    <!-- Prescription Form -->
                    <form id="prescriptionForm" method="POST" action="api/save_prescription.php" data-ajax="true">
                        <input type="hidden" name="patient_id" value="<?php echo $selected_patient_id; ?>">
                        
                        <!-- Basic Prescription Settings -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-cogs me-2"></i>Basic Prescription Settings
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Dialysis Modality</label>
                                        <input type="text" class="form-control" name="dialysis_modality" 
                                               value="<?php echo htmlspecialchars($prescription_data['dialysis_modality'] ?? 'Hemodialysis'); ?>" readonly>
                                        <div class="form-text">Default is Hemodialysis</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Dialyzer Type</label>
                                        <select class="form-select" name="dialyzer" required>
                                            <option value="low flux" <?php echo ($prescription_data && $prescription_data['dialyzer'] == 'low flux') ? 'selected' : 'selected'; ?>>Low Flux</option>
                                            <option value="high flux" <?php echo ($prescription_data && $prescription_data['dialyzer'] == 'high flux') ? 'selected' : ''; ?>>High Flux</option>
                                        </select>
                                        <div class="form-text">Default is Low Flux</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Frequency</label>
                                        <select class="form-select" name="frequency" required>
                                            <option value="once weekly" <?php echo ($prescription_data && $prescription_data['frequency'] == 'once weekly') ? 'selected' : ''; ?>>Once Weekly</option>
                                            <option value="twice weekly" <?php echo ($prescription_data && $prescription_data['frequency'] == 'twice weekly') ? 'selected' : ''; ?>>Twice Weekly</option>
                                            <option value="thrice weekly" <?php echo ($prescription_data && $prescription_data['frequency'] == 'thrice weekly') ? 'selected' : 'selected'; ?>>Thrice Weekly</option>
                                        </select>
                                        <div class="form-text">Default is Thrice Weekly</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Duration (hours)</label>
                                        <input type="number" class="form-control" name="duration" step="0.1" min="2" max="8"
                                               value="<?php echo $prescription_data['duration'] ?? '4.0'; ?>" required>
                                        <div class="form-text">Default is 4 hours</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Current Vascular Access</label>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="vascular_access" id="permcath" value="permcath"
                                                           <?php echo ($prescription_data && $prescription_data['vascular_access'] == 'permcath') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="permcath">Permcath</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="vascular_access" id="temp_catheter" value="temporary HD catheter"
                                                           <?php echo ($prescription_data && $prescription_data['vascular_access'] == 'temporary HD catheter') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="temp_catheter">Temporary HD Catheter</label>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="vascular_access" id="av_fistula" value="AV fistula"
                                                           <?php echo ($prescription_data && $prescription_data['vascular_access'] == 'AV fistula') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="av_fistula">AV Fistula</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="vascular_access" id="av_graft" value="AV graft"
                                                           <?php echo ($prescription_data && $prescription_data['vascular_access'] == 'AV graft') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="av_graft">AV Graft</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Anticoagulation & Flow Rates -->
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-tint me-2"></i>Anticoagulation & Flow Rates
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Heparin Initial Dose (units)</label>
                                        <input type="number" class="form-control" name="heparin_initial" step="0.01"
                                               value="<?php echo $prescription_data['heparin_initial'] ?? '2000'; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Heparin Maintenance (units/hr)</label>
                                        <input type="number" class="form-control" name="heparin_maintenance" step="0.01"
                                               value="<?php echo $prescription_data['heparin_maintenance'] ?? '1000'; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Blood Flow Rate (ml/min)</label>
                                        <input type="number" class="form-control" name="blood_flow_rate" min="200" max="500"
                                               value="<?php echo $prescription_data['blood_flow_rate'] ?? '300'; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Dialysate Flow Rate (ml/min)</label>
                                        <input type="number" class="form-control" name="dialysate_flow_rate" min="300" max="800"
                                               value="<?php echo $prescription_data['dialysate_flow_rate'] ?? '500'; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Fluid Management -->
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="fas fa-weight me-2"></i>Fluid Management
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Dry Body Weight (kg)</label>
                                        <input type="number" class="form-control" name="dry_body_weight" step="0.1"
                                               value="<?php echo $prescription_data['dry_body_weight'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Ultrafiltration Rate (ml/kg/hr)</label>
                                        <input type="number" class="form-control" name="ultrafiltration" step="0.1" min="0" max="13"
                                               value="<?php echo $prescription_data['ultrafiltration'] ?? '13.0'; ?>">
                                        <div class="form-text">Default is 13 ml/kg/hr, range: 0-13</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Dialysate Composition -->
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-flask me-2"></i>Dialysate Composition
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Sodium (mEq/L)</label>
                                        <input type="number" class="form-control" name="sodium" step="0.1" min="130" max="145"
                                               value="<?php echo $prescription_data['sodium'] ?? '135.0'; ?>">
                                        <div class="form-text">Default: 135, Range: 130-145</div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Potassium (mmol/L)</label>
                                        <input type="number" class="form-control" name="potassium" step="0.1" min="0" max="4"
                                               value="<?php echo $prescription_data['potassium'] ?? '2.0'; ?>">
                                        <div class="form-text">Default: 2.0</div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Calcium (mmol/L)</label>
                                        <input type="number" class="form-control" name="calcium" step="0.1" min="1.0" max="2.0"
                                               value="<?php echo $prescription_data['calcium'] ?? '1.5'; ?>">
                                        <div class="form-text">Default: 1.5</div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Bicarbonate (mmol/L)</label>
                                        <input type="number" class="form-control" name="bicarbonate" step="0.1" min="25" max="40"
                                               value="<?php echo $prescription_data['bicarbonate'] ?? '35.0'; ?>">
                                        <div class="form-text">Default: 35</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Catheter Lock Solution -->
                        <div class="card mb-4">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-lock me-2"></i>Catheter Lock Solution
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label">Catheter Lock Type</label>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="catheter_lock" id="heparin_lock" value="heparin"
                                                           <?php echo ($prescription_data && $prescription_data['catheter_lock'] == 'heparin') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="heparin_lock">Heparin</label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="catheter_lock" id="alteplase_lock" value="alteplase"
                                                           <?php echo ($prescription_data && $prescription_data['catheter_lock'] == 'alteplase') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="alteplase_lock">Alteplase</label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="catheter_lock" id="saline_lock" value="normal saline"
                                                           <?php echo ($prescription_data && $prescription_data['catheter_lock'] == 'normal saline') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="saline_lock">Normal Saline</label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="catheter_lock" id="antibiotic_lock" value="antibiotic lock"
                                                           <?php echo ($prescription_data && $prescription_data['catheter_lock'] == 'antibiotic lock') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="antibiotic_lock">Antibiotic Lock</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Prescription Summary -->
                        <?php if ($prescription_data): ?>
                        <div class="card mb-4">
                            <div class="card-header bg-dark text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-clipboard-check me-2"></i>Current Prescription Summary
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>Modality:</strong></td>
                                                <td><?php echo htmlspecialchars($prescription_data['dialysis_modality']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Frequency:</strong></td>
                                                <td><?php echo htmlspecialchars($prescription_data['frequency']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Duration:</strong></td>
                                                <td><?php echo $prescription_data['duration']; ?> hours</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Dialyzer:</strong></td>
                                                <td><?php echo htmlspecialchars($prescription_data['dialyzer']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Vascular Access:</strong></td>
                                                <td><?php echo htmlspecialchars($prescription_data['vascular_access']); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>Dry Weight:</strong></td>
                                                <td><?php echo $prescription_data['dry_body_weight']; ?> kg</td>
                                            </tr>
                                            <tr>
                                                <td><strong>UF Rate:</strong></td>
                                                <td><?php echo $prescription_data['ultrafiltration']; ?> ml/kg/hr</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Blood Flow:</strong></td>
                                                <td><?php echo $prescription_data['blood_flow_rate']; ?> ml/min</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Dialysate Flow:</strong></td>
                                                <td><?php echo $prescription_data['dialysate_flow_rate']; ?> ml/min</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Catheter Lock:</strong></td>
                                                <td><?php echo htmlspecialchars($prescription_data['catheter_lock']); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-lg me-2">
                                    <i class="fas fa-save me-2"></i>Save Prescription
                                </button>
                                <button type="button" class="btn btn-warning btn-lg me-2" onclick="resetToDefaults()">
                                    <i class="fas fa-undo me-2"></i>Reset to Defaults
                                </button>
                                <button type="button" class="btn btn-info btn-lg" onclick="generatePrescriptionReport()">
                                    <i class="fas fa-print me-2"></i>Print Prescription
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
function loadPatientPrescription() {
    const patientId = document.getElementById('patientSelect').value;
    if (patientId) {
        window.location.href = 'prescription_form.php?patient_id=' + patientId;
    }
}

function resetToDefaults() {
    if (confirm('Are you sure you want to reset all values to defaults?')) {
        // Reset to default values
        document.querySelector('select[name="dialyzer"]').value = 'low flux';
        document.querySelector('select[name="frequency"]').value = 'thrice weekly';
        document.querySelector('input[name="duration"]').value = '4.0';
        document.querySelector('input[name="heparin_initial"]').value = '2000';
        document.querySelector('input[name="heparin_maintenance"]').value = '1000';
        document.querySelector('input[name="blood_flow_rate"]').value = '300';
        document.querySelector('input[name="dialysate_flow_rate"]').value = '500';
        document.querySelector('input[name="ultrafiltration"]').value = '13.0';
        document.querySelector('input[name="sodium"]').value = '135.0';
        document.querySelector('input[name="potassium"]').value = '2.0';
        document.querySelector('input[name="calcium"]').value = '1.5';
        document.querySelector('input[name="bicarbonate"]').value = '35.0';
    }
}

function generatePrescriptionReport() {
    const patientId = '<?php echo $selected_patient_id; ?>';
    if (patientId) {
        window.open(`patient_report.php?id=${patientId}&section=prescription`, '_blank');
    }
}

// Form submission handling - wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    const prescriptionForm = document.getElementById('prescriptionForm');
    if (prescriptionForm) {
        let isSubmitting = false;
        
        prescriptionForm.addEventListener('submit', function(e) {
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
            
            fetch('api/save_prescription.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Prescription saved successfully!');
                    // Clear unsaved changes flag before redirect
                    if (typeof FormManager !== 'undefined') {
                        FormManager.unsavedChanges = false;
                    }
                    window.onbeforeunload = null;
                    // Redirect to same page with patient ID to refresh data
                    const patientId = '<?php echo $selected_patient_id; ?>';
                    window.location.href = 'prescription_form.php?patient_id=' + patientId;
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the prescription.');
            })
            .finally(() => {
                isSubmitting = false;
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-save me-2"></i>Save Prescription';
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

// Validate numeric ranges
document.addEventListener('input', function(e) {
    if (e.target.type === 'number') {
        const min = parseFloat(e.target.min);
        const max = parseFloat(e.target.max);
        const value = parseFloat(e.target.value);
        
        if (!isNaN(min) && !isNaN(max) && !isNaN(value)) {
            if (value < min || value > max) {
                e.target.classList.add('is-invalid');
            } else {
                e.target.classList.remove('is-invalid');
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
