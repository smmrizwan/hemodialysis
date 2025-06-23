<?php
require_once 'config/init.php';
$page_title = 'Vaccinations';
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
    error_log("Vaccination form: Found " . count($patients) . " patients");
} catch(PDOException $e) {
    error_log("Vaccination form database error: " . $e->getMessage());
    $patients = [];
}

$selected_patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : '';
$vaccination_data = [];

// Get existing vaccination data if patient selected
if ($selected_patient_id) {
    try {
        $stmt = $db->prepare("SELECT * FROM vaccinations WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$selected_patient_id]);
        $vaccination_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If no data found, initialize empty array
        if (!$vaccination_data) {
            $vaccination_data = [];
        }
        
        error_log("Vaccination form: Found vaccination data for patient " . $selected_patient_id . ": " . print_r($vaccination_data, true));
    } catch(PDOException $e) {
        error_log("Vaccination form error: " . $e->getMessage());
        $vaccination_data = [];
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>
                        <i class="fas fa-syringe me-2"></i>Vaccination Management
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
                            <select class="form-select patient-selector" id="patientSelect" onchange="loadPatientVaccinations()" required>
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
                    
                    <!-- Vaccination Form -->
                    <form id="vaccinationForm" method="POST" action="api/save_vaccination.php" data-ajax="true">
                        <input type="hidden" name="patient_id" value="<?php echo $selected_patient_id; ?>">
                        
                        <!-- Hepatitis B Vaccination -->
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="fas fa-shield-virus me-2"></i>Hepatitis B Vaccination
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="hepatitis_b_completed" id="hepatitis_b_completed"
                                                   <?php echo ($vaccination_data && $vaccination_data['hepatitis_b_completed']) ? 'checked' : ''; ?>
                                                   onchange="toggleHepBDetails()">
                                            <label class="form-check-label" for="hepatitis_b_completed">
                                                <strong>Has the patient completed Hepatitis B vaccination?</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="hepBDetails" style="display: <?php echo ($vaccination_data && $vaccination_data['hepatitis_b_completed']) ? 'block' : 'none'; ?>;">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Completion Date</label>
                                            <input type="date" class="form-control" name="hepatitis_b_date" 
                                                   value="<?php echo $vaccination_data['hepatitis_b_date'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="hepBRecommendations" style="display: <?php echo ($vaccination_data && !$vaccination_data['hepatitis_b_completed']) ? 'block' : 'none'; ?>;">
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-info-circle me-2"></i>Vaccination Recommendations:</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="hepatitis_b_series" id="recombivax" value="Three dose Recombivax HB"
                                                           <?php echo ($vaccination_data && $vaccination_data['hepatitis_b_series'] == 'Three dose Recombivax HB') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="recombivax">
                                                        <strong>Three dose series Recombivax HB</strong><br>
                                                        <small class="text-muted">At 0, 1, 6 months<br>
                                                        Note: Use Dialysis Formulation 1 mL = 40 mcg</small>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="hepatitis_b_series" id="engerix" value="Four dose Engerix-B"
                                                           <?php echo ($vaccination_data && $vaccination_data['hepatitis_b_series'] == 'Four dose Engerix-B') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="engerix">
                                                        <strong>Four dose series Engerix-B</strong><br>
                                                        <small class="text-muted">At 0, 1, 2, and 6 months<br>
                                                        Note: Use 2 mL dose instead of normal adult dose of 1 mL</small>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Annual Flu Vaccine -->
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-lungs-virus me-2"></i>Annual Flu Vaccine (Influenza)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="flu_vaccine_completed" id="flu_vaccine_completed"
                                                   <?php echo ($vaccination_data && $vaccination_data['flu_vaccine_completed']) ? 'checked' : ''; ?>
                                                   onchange="toggleFluDetails()">
                                            <label class="form-check-label" for="flu_vaccine_completed">
                                                <strong>Has the patient completed Annual flu vaccine (Influenza)?</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="fluDetails" style="display: <?php echo ($vaccination_data && $vaccination_data['flu_vaccine_completed']) ? 'block' : 'none'; ?>;">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Vaccination Date</label>
                                            <input type="date" class="form-control" name="flu_vaccine_date" 
                                                   value="<?php echo $vaccination_data['flu_vaccine_date'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="fluRecommendation" style="display: <?php echo ($vaccination_data && !$vaccination_data['flu_vaccine_completed']) ? 'block' : 'none'; ?>;">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Recommendation:</strong> Annual influenza vaccination is strongly recommended for all dialysis patients due to increased risk of complications.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- PPV23 Vaccine -->
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-bacteria me-2"></i>PPV23 Vaccine (Pneumococcal Conjugate 20-valent)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="ppv23_completed" id="ppv23_completed"
                                                   <?php echo ($vaccination_data && $vaccination_data['ppv23_completed']) ? 'checked' : ''; ?>
                                                   onchange="togglePPV23Details()">
                                            <label class="form-check-label" for="ppv23_completed">
                                                <strong>Has the patient completed PPV23 vaccine (Pneumococcal conjugate 20-valent)?</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="ppv23Details" style="display: <?php echo ($vaccination_data && $vaccination_data['ppv23_completed']) ? 'block' : 'none'; ?>;">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Vaccination Date</label>
                                            <input type="date" class="form-control" name="ppv23_date" 
                                                   value="<?php echo $vaccination_data['ppv23_date'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="ppv23Recommendation" style="display: <?php echo ($vaccination_data && !$vaccination_data['ppv23_completed']) ? 'block' : 'none'; ?>;">
                                    <div class="alert alert-success">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Recommendation:</strong> PPV23 vaccination is recommended for dialysis patients to prevent pneumococcal infections.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Respiratory Syncytial Virus Vaccine -->
                        <div class="card mb-4">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-virus me-2"></i>Respiratory Syncytial Virus (RSV) Vaccine
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="rsv_completed" id="rsv_completed"
                                                   <?php echo ($vaccination_data && $vaccination_data['rsv_completed']) ? 'checked' : ''; ?>
                                                   onchange="toggleRSVDetails()">
                                            <label class="form-check-label" for="rsv_completed">
                                                <strong>Has the patient completed Respiratory Syncytial virus vaccine?</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="rsvDetails" style="display: <?php echo ($vaccination_data && $vaccination_data['rsv_completed']) ? 'block' : 'none'; ?>;">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Vaccination Date</label>
                                            <input type="date" class="form-control" name="rsv_date" 
                                                   value="<?php echo $vaccination_data['rsv_date'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="rsvRecommendation" style="display: <?php echo ($vaccination_data && !$vaccination_data['rsv_completed']) ? 'block' : 'none'; ?>;">
                                    <div class="alert alert-danger">
                                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Vaccination Recommendation:</h6>
                                        <p class="mb-2"><strong>1 dose of:</strong></p>
                                        <ul class="mb-2">
                                            <li>Arexvy, or</li>
                                            <li>Abrysvo, or</li>
                                            <li>mResvia</li>
                                        </ul>
                                        <p class="mb-0"><small><strong>Note:</strong> Additional doses not recommended.</small></p>
                                        <input type="hidden" name="rsv_recommendation" value="1 dose of (Arexvy or Abrysvo or mResvia). Additional doses not recommended.">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Vaccination Summary -->
                        <?php if ($vaccination_data): ?>
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>Vaccination Status Summary
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php
                                    $vaccinations = [
                                        'hepatitis_b_completed' => 'Hepatitis B',
                                        'flu_vaccine_completed' => 'Annual Flu',
                                        'ppv23_completed' => 'PPV23',
                                        'rsv_completed' => 'RSV'
                                    ];
                                    
                                    $completed_count = 0;
                                    foreach ($vaccinations as $key => $name) {
                                        if ($vaccination_data[$key]) {
                                            $completed_count++;
                                        }
                                    }
                                    
                                    $completion_percentage = ($completed_count / count($vaccinations)) * 100;
                                    ?>
                                    
                                    <div class="col-md-4">
                                        <div class="card bg-<?php echo $completion_percentage == 100 ? 'success' : ($completion_percentage >= 50 ? 'warning' : 'danger'); ?> text-white">
                                            <div class="card-body text-center">
                                                <h3><?php echo $completed_count; ?>/<?php echo count($vaccinations); ?></h3>
                                                <p class="mb-0">Completed</p>
                                                <small><?php echo round($completion_percentage); ?>%</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-8">
                                        <h6>Vaccination Status:</h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($vaccinations as $key => $name): ?>
                                                <span class="badge bg-<?php echo $vaccination_data[$key] ? 'success' : 'secondary'; ?> fs-6">
                                                    <i class="fas fa-<?php echo $vaccination_data[$key] ? 'check' : 'times'; ?> me-1"></i>
                                                    <?php echo $name; ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <?php if ($completion_percentage < 100): ?>
                                            <div class="alert alert-warning mt-3 mb-0">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                <strong>Action Required:</strong> Patient has incomplete vaccinations. Please review recommendations above.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-lg me-2">
                                    <i class="fas fa-save me-2"></i>Save Vaccination Data
                                </button>
                                <button type="button" class="btn btn-secondary btn-lg me-2" onclick="clearVaccinationForm()">
                                    <i class="fas fa-eraser me-2"></i>Clear Form
                                </button>
                                <button type="button" class="btn btn-info btn-lg" onclick="generateVaccinationReport()">
                                    <i class="fas fa-certificate me-2"></i>Generate Certificate
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
function loadPatientVaccinations() {
    const patientId = document.getElementById('patientSelect').value;
    if (patientId) {
        window.location.href = 'vaccination_form.php?patient_id=' + patientId;
    }
}

function toggleHepBDetails() {
    const checkbox = document.getElementById('hepatitis_b_completed');
    const details = document.getElementById('hepBDetails');
    const recommendations = document.getElementById('hepBRecommendations');
    
    if (checkbox.checked) {
        details.style.display = 'block';
        recommendations.style.display = 'none';
    } else {
        details.style.display = 'none';
        recommendations.style.display = 'block';
    }
}

function toggleFluDetails() {
    const checkbox = document.getElementById('flu_vaccine_completed');
    const details = document.getElementById('fluDetails');
    const recommendation = document.getElementById('fluRecommendation');
    
    if (checkbox.checked) {
        details.style.display = 'block';
        recommendation.style.display = 'none';
    } else {
        details.style.display = 'none';
        recommendation.style.display = 'block';
    }
}

function togglePPV23Details() {
    const checkbox = document.getElementById('ppv23_completed');
    const details = document.getElementById('ppv23Details');
    const recommendation = document.getElementById('ppv23Recommendation');
    
    if (checkbox.checked) {
        details.style.display = 'block';
        recommendation.style.display = 'none';
    } else {
        details.style.display = 'none';
        recommendation.style.display = 'block';
    }
}

function toggleRSVDetails() {
    const checkbox = document.getElementById('rsv_completed');
    const details = document.getElementById('rsvDetails');
    const recommendation = document.getElementById('rsvRecommendation');
    
    if (checkbox.checked) {
        details.style.display = 'block';
        recommendation.style.display = 'none';
    } else {
        details.style.display = 'none';
        recommendation.style.display = 'block';
    }
}

function clearVaccinationForm() {
    if (confirm('Are you sure you want to clear all vaccination data?')) {
        document.getElementById('vaccinationForm').reset();
        // Hide all details sections
        document.getElementById('hepBDetails').style.display = 'none';
        document.getElementById('hepBRecommendations').style.display = 'none';
        document.getElementById('fluDetails').style.display = 'none';
        document.getElementById('fluRecommendation').style.display = 'none';
        document.getElementById('ppv23Details').style.display = 'none';
        document.getElementById('ppv23Recommendation').style.display = 'none';
        document.getElementById('rsvDetails').style.display = 'none';
        document.getElementById('rsvRecommendation').style.display = 'none';
    }
}

function generateVaccinationReport() {
    const patientId = '<?php echo $selected_patient_id; ?>';
    if (patientId) {
        window.open(`patient_report.php?id=${patientId}&section=vaccinations`, '_blank');
    }
}

// Form submission handling - wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    const vaccinationForm = document.getElementById('vaccinationForm');
    if (vaccinationForm) {
        let isSubmitting = false;
        
        vaccinationForm.addEventListener('submit', function(e) {
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
            
            fetch('api/save_vaccination.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Vaccination data saved successfully!');
                    // Clear unsaved changes flag before redirect
                    if (typeof FormManager !== 'undefined') {
                        FormManager.unsavedChanges = false;
                    }
                    window.onbeforeunload = null;
                    // Redirect to same page with patient ID to refresh data
                    const patientId = '<?php echo $selected_patient_id; ?>';
                    window.location.href = 'vaccination_form.php?patient_id=' + patientId;
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the vaccination data.');
            })
            .finally(() => {
                isSubmitting = false;
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-save me-2"></i>Save Vaccination Data';
                }
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
