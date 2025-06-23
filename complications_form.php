<?php
require_once 'config/init.php';
$page_title = 'Dialysis Complications';
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
    error_log("Complications form: Found " . count($patients) . " patients");
} catch(PDOException $e) {
    error_log("Complications form database error: " . $e->getMessage());
    $patients = [];
}

$selected_patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : '';
$complications_data = [];

// Get existing complications data if patient selected
if ($selected_patient_id) {
    try {
        $stmt = $db->prepare("SELECT * FROM dialysis_complications WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$selected_patient_id]);
        $complications_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If no data found, initialize empty array
        if (!$complications_data) {
            $complications_data = [];
        }
        
        error_log("Complications form: Found complications data for patient " . $selected_patient_id . ": " . print_r($complications_data, true));
    } catch(PDOException $e) {
        error_log("Complications form error: " . $e->getMessage());
        $complications_data = [];
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>
                        <i class="fas fa-exclamation-circle me-2"></i>Dialysis Complications
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
                            <select class="form-select patient-selector" id="patientSelect" onchange="loadPatientComplications()" required>
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
                    
                    <!-- Complications Form -->
                    <form id="complicationsForm" method="POST" action="api/save_complications.php" data-ajax="true">
                        <input type="hidden" name="patient_id" value="<?php echo $selected_patient_id; ?>">
                        
                        <div class="card">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-list-check me-2"></i>Dialysis Complications Checklist
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Cardiovascular Complications -->
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">
                                            <i class="fas fa-heart me-2"></i>Cardiovascular
                                        </h6>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="hypotension" id="hypotension" 
                                                   <?php echo ($complications_data && $complications_data['hypotension']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="hypotension">
                                                <strong>Hypotension</strong>
                                                <small class="text-muted d-block">Low blood pressure during or after dialysis</small>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="hypertension" id="hypertension"
                                                   <?php echo ($complications_data && $complications_data['hypertension']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="hypertension">
                                                <strong>Hypertension</strong>
                                                <small class="text-muted d-block">High blood pressure during or after dialysis</small>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="chest_pain" id="chest_pain"
                                                   <?php echo ($complications_data && $complications_data['chest_pain']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="chest_pain">
                                                <strong>Chest Pain (Cardiac)</strong>
                                                <small class="text-muted d-block">Cardiac-related chest pain</small>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="arrhythmias" id="arrhythmias"
                                                   <?php echo ($complications_data && $complications_data['arrhythmias']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="arrhythmias">
                                                <strong>Arrhythmias (Palpitations)</strong>
                                                <small class="text-muted d-block">Irregular heartbeat or palpitations</small>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- Neurological & Other Complications -->
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">
                                            <i class="fas fa-brain me-2"></i>Neurological & General
                                        </h6>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="headache" id="headache"
                                                   <?php echo ($complications_data && $complications_data['headache']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="headache">
                                                <strong>Headache</strong>
                                                <small class="text-muted d-block">Head pain during or after dialysis</small>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="seizures" id="seizures"
                                                   <?php echo ($complications_data && $complications_data['seizures']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="seizures">
                                                <strong>Seizures</strong>
                                                <small class="text-muted d-block">Convulsive episodes</small>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="muscle_cramps" id="muscle_cramps"
                                                   <?php echo ($complications_data && $complications_data['muscle_cramps']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="muscle_cramps">
                                                <strong>Muscle Cramps</strong>
                                                <small class="text-muted d-block">Painful muscle contractions</small>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="nausea_vomiting" id="nausea_vomiting"
                                                   <?php echo ($complications_data && $complications_data['nausea_vomiting']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="nausea_vomiting">
                                                <strong>Nausea and Vomiting</strong>
                                                <small class="text-muted d-block">Gastrointestinal symptoms</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr class="my-4">
                                
                                <div class="row">
                                    <!-- Respiratory & Skin Complications -->
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">
                                            <i class="fas fa-lungs me-2"></i>Respiratory & Skin
                                        </h6>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="dyspnea" id="dyspnea"
                                                   <?php echo ($complications_data && $complications_data['dyspnea']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="dyspnea">
                                                <strong>Dyspnea</strong>
                                                <small class="text-muted d-block">Shortness of breath or difficulty breathing</small>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="pruritus" id="pruritus"
                                                   <?php echo ($complications_data && $complications_data['pruritus']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="pruritus">
                                                <strong>Pruritus</strong>
                                                <small class="text-muted d-block">Skin itching</small>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- Systemic Complications -->
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">
                                            <i class="fas fa-thermometer-half me-2"></i>Systemic
                                        </h6>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="fever_chills" id="fever_chills"
                                                   <?php echo ($complications_data && $complications_data['fever_chills']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="fever_chills">
                                                <strong>Fever and Chills</strong>
                                                <small class="text-muted d-block">Elevated temperature with chills</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Complications Summary -->
                        <?php if ($complications_data): ?>
                        <div class="card mt-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>Complications Summary
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php
                                    $total_complications = 0;
                                    $complications_list = [
                                        'hypotension' => 'Hypotension',
                                        'hypertension' => 'Hypertension', 
                                        'muscle_cramps' => 'Muscle Cramps',
                                        'nausea_vomiting' => 'Nausea/Vomiting',
                                        'headache' => 'Headache',
                                        'chest_pain' => 'Chest Pain',
                                        'pruritus' => 'Pruritus',
                                        'fever_chills' => 'Fever/Chills',
                                        'dyspnea' => 'Dyspnea',
                                        'seizures' => 'Seizures',
                                        'arrhythmias' => 'Arrhythmias'
                                    ];
                                    
                                    $active_complications = [];
                                    foreach ($complications_list as $key => $label) {
                                        if ($complications_data[$key]) {
                                            $total_complications++;
                                            $active_complications[] = $label;
                                        }
                                    }
                                    ?>
                                    
                                    <div class="col-md-4">
                                        <div class="card bg-<?php echo $total_complications > 5 ? 'danger' : ($total_complications > 2 ? 'warning' : 'success'); ?> text-white">
                                            <div class="card-body text-center">
                                                <h3><?php echo $total_complications; ?></h3>
                                                <p class="mb-0">Active Complications</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-8">
                                        <?php if (!empty($active_complications)): ?>
                                            <h6>Current Complications:</h6>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php foreach ($active_complications as $complication): ?>
                                                    <span class="badge bg-warning text-dark fs-6"><?php echo $complication; ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-success mb-0">
                                                <i class="fas fa-check-circle me-2"></i>
                                                No complications currently reported for this patient.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-danger btn-lg me-2">
                                    <i class="fas fa-save me-2"></i>Save Complications Data
                                </button>
                                <button type="button" class="btn btn-secondary btn-lg" onclick="clearComplicationsForm()">
                                    <i class="fas fa-eraser me-2"></i>Clear All
                                </button>
                                <button type="button" class="btn btn-info btn-lg" onclick="generateComplicationsReport()">
                                    <i class="fas fa-chart-bar me-2"></i>Generate Report
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
function loadPatientComplications() {
    const patientId = document.getElementById('patientSelect').value;
    if (patientId) {
        window.location.href = 'complications_form.php?patient_id=' + patientId;
    }
}

function clearComplicationsForm() {
    if (confirm('Are you sure you want to clear all complications data?')) {
        const checkboxes = document.querySelectorAll('#complicationsForm input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
    }
}

function generateComplicationsReport() {
    const patientId = '<?php echo $selected_patient_id; ?>';
    if (patientId) {
        window.open(`patient_report.php?id=${patientId}&section=complications`, '_blank');
    }
}

// Form submission handling - wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    const complicationsForm = document.getElementById('complicationsForm');
    if (complicationsForm) {
        let isSubmitting = false;
        
        complicationsForm.addEventListener('submit', function(e) {
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
            
            fetch('api/save_complications.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Complications data saved successfully!');
                    // Clear unsaved changes flag before redirect
                    if (typeof FormManager !== 'undefined') {
                        FormManager.unsavedChanges = false;
                    }
                    window.onbeforeunload = null;
                    // Redirect to same page with patient ID to refresh data
                    const patientId = '<?php echo $selected_patient_id; ?>';
                    window.location.href = 'complications_form.php?patient_id=' + patientId;
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the complications data.');
            })
            .finally(() => {
                isSubmitting = false;
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-save me-2"></i>Save Complications Data';
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

// Add visual feedback for checkbox changes
document.addEventListener('change', function(e) {
    if (e.target.type === 'checkbox' && e.target.closest('#complicationsForm')) {
        const label = e.target.closest('.form-check').querySelector('.form-check-label');
        if (e.target.checked) {
            label.classList.add('text-danger', 'fw-bold');
        } else {
            label.classList.remove('text-danger', 'fw-bold');
        }
    }
});

// Initialize form state
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('#complicationsForm input[type="checkbox"]:checked');
    checkboxes.forEach(checkbox => {
        const label = checkbox.closest('.form-check').querySelector('.form-check-label');
        label.classList.add('text-danger', 'fw-bold');
    });
});
</script>

<?php include 'includes/footer.php'; ?>
