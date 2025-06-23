<?php
require_once 'config/init.php';

$edit_mode = false;
$patient_data = [];

// Debug: Log access mode
error_log("Patient form accessed - Edit param: " . ($_GET['edit'] ?? 'none'));

// Check if editing existing patient
if (isset($_GET['edit']) && !empty($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_mode = true;
    $patient_id = (int)sanitize_input($_GET['edit']);
    
    try {
        $stmt = $db->prepare("SELECT * FROM patients WHERE id = ?");
        $stmt->execute([$patient_id]);
        $patient_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$patient_data) {
            // If patient not found, redirect to new patient form
            header("Location: patient_form.php");
            exit();
        }
    } catch(PDOException $e) {
        $error_message = "Error fetching patient data: " . $e->getMessage();
        $edit_mode = false; // Fallback to new patient mode
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>
                        <i class="fas fa-user-plus me-2"></i>
                        <?php echo $edit_mode ? 'Edit Patient' : 'Patient Registration'; ?>
                    </h4>
                    <a href="dashboard.php" class="btn btn-secondary float-end">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
                <div class="card-body">
                    <form id="patientForm" method="POST" action="api/save_patient.php">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="patient_id" value="<?php echo $patient_data['id']; ?>">
                        <?php endif; ?>
                        
                        <!-- Patient Identification Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-id-card me-2"></i>Patient Identification
                                </h5>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="date" value="<?php echo $edit_mode ? $patient_data['created_at'] : date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">File Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="file_number" 
                                       value="<?php echo $edit_mode ? htmlspecialchars($patient_data['file_number']) : ''; ?>" 
                                       pattern="[0-9]{5,11}" title="File number must be 5-11 digits" required>
                                <div class="form-text">Enter 5-11 digit numeric file number</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Name in English <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name_english" 
                                       value="<?php echo $edit_mode ? htmlspecialchars($patient_data['name_english']) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="date_of_birth" id="dateOfBirth"
                                       value="<?php echo $edit_mode ? $patient_data['date_of_birth'] : ''; ?>" 
                                       onchange="calculateAge()" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Age (Years)</label>
                                <input type="number" class="form-control" name="age" id="calculatedAge" readonly>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Gender <span class="text-danger">*</span></label>
                                <select class="form-select" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo ($edit_mode && $patient_data['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($edit_mode && $patient_data['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" name="contact_number" 
                                       value="<?php echo $edit_mode ? htmlspecialchars($patient_data['contact_number']) : ''; ?>"
                                       pattern="[0-9]{10}" title="Enter 10 digit contact number" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Room Number <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="room_number" min="1" max="23"
                                       value="<?php echo $edit_mode ? $patient_data['room_number'] : ''; ?>" required>
                                <div class="form-text">Room number from 1 to 23</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Group <span class="text-danger">*</span></label>
                                <select class="form-select" name="group_type" required>
                                    <option value="">Select Group</option>
                                    <option value="SAT" <?php echo ($edit_mode && $patient_data['group_type'] == 'SAT') ? 'selected' : ''; ?>>SAT</option>
                                    <option value="SUN" <?php echo ($edit_mode && $patient_data['group_type'] == 'SUN') ? 'selected' : ''; ?>>SUN</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Shift <span class="text-danger">*</span></label>
                                <select class="form-select" name="shift_type" required>
                                    <option value="">Select Shift</option>
                                    <option value="1st" <?php echo ($edit_mode && $patient_data['shift_type'] == '1st') ? 'selected' : ''; ?>>1st</option>
                                    <option value="2nd" <?php echo ($edit_mode && $patient_data['shift_type'] == '2nd') ? 'selected' : ''; ?>>2nd</option>
                                    <option value="3rd" <?php echo ($edit_mode && $patient_data['shift_type'] == '3rd') ? 'selected' : ''; ?>>3rd</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Height (cm)</label>
                                <input type="number" class="form-control" name="height_cm" id="height" step="0.01"
                                       value="<?php echo $edit_mode ? $patient_data['height_cm'] : ''; ?>" 
                                       onchange="calculateBMI(); calculateBSA();">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Weight (kg)</label>
                                <input type="number" class="form-control" name="weight_kg" id="weight" step="0.01"
                                       value="<?php echo $edit_mode ? $patient_data['weight_kg'] : ''; ?>" 
                                       onchange="calculateBMI(); calculateBSA();">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">BMI</label>
                                <input type="number" class="form-control" name="bmi" id="calculatedBMI" step="0.01" readonly>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Body Surface Area (m²)</label>
                                <input type="number" class="form-control" name="bsa" id="calculatedBSA" step="0.01" readonly>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Blood Group</label>
                                <select class="form-select" name="blood_group">
                                    <option value="">Select Blood Group</option>
                                    <option value="O+" <?php echo ($edit_mode && $patient_data['blood_group'] == 'O+') ? 'selected' : ''; ?>>O+</option>
                                    <option value="O-" <?php echo ($edit_mode && $patient_data['blood_group'] == 'O-') ? 'selected' : ''; ?>>O-</option>
                                    <option value="A+" <?php echo ($edit_mode && $patient_data['blood_group'] == 'A+') ? 'selected' : ''; ?>>A+</option>
                                    <option value="A-" <?php echo ($edit_mode && $patient_data['blood_group'] == 'A-') ? 'selected' : ''; ?>>A-</option>
                                    <option value="B+" <?php echo ($edit_mode && $patient_data['blood_group'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                                    <option value="B-" <?php echo ($edit_mode && $patient_data['blood_group'] == 'B-') ? 'selected' : ''; ?>>B-</option>
                                    <option value="AB+" <?php echo ($edit_mode && $patient_data['blood_group'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                    <option value="AB-" <?php echo ($edit_mode && $patient_data['blood_group'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Date of Hemodialysis Initiation</label>
                                <input type="date" class="form-control" name="dialysis_initiation_date" id="dialysisDate"
                                       value="<?php echo $edit_mode ? $patient_data['dialysis_initiation_date'] : ''; ?>"
                                       onchange="calculateDialysisDuration()">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Months on Hemodialysis</label>
                                <input type="number" class="form-control" name="dialysis_months" id="dialysisMonths" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Years on Hemodialysis</label>
                                <input type="number" class="form-control" name="dialysis_years" id="dialysisYears" readonly>
                            </div>
                        </div>
                        
                        <!-- Medical Problems Section -->
                        <div class="row mb-4 mt-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-stethoscope me-2"></i>Medical Problems
                                </h5>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Chronic Problems</label>
                                <div class="input-group">
                                    <select class="form-select" id="chronicProblemsSelect">
                                        <option value="">Add from common problems</option>
                                        <?php foreach ($common_problems as $problem): ?>
                                            <option value="<?php echo htmlspecialchars($problem); ?>"><?php echo htmlspecialchars($problem); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" onclick="addProblem('chronic')">Add</button>
                                </div>
                                <textarea class="form-control mt-2" name="chronic_problems" id="chronicProblems" rows="3"
                                          placeholder="Enter chronic problems, separated by commas"><?php echo $edit_mode ? htmlspecialchars($patient_data['chronic_problems']) : ''; ?></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Acute Problems</label>
                                <div class="input-group">
                                    <select class="form-select" id="acuteProblemsSelect">
                                        <option value="">Add from common problems</option>
                                        <?php foreach ($common_problems as $problem): ?>
                                            <option value="<?php echo htmlspecialchars($problem); ?>"><?php echo htmlspecialchars($problem); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" onclick="addProblem('acute')">Add</button>
                                </div>
                                <textarea class="form-control mt-2" name="acute_problems" id="acuteProblems" rows="3"
                                          placeholder="Enter acute problems, separated by commas"><?php echo $edit_mode ? htmlspecialchars($patient_data['acute_problems']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Blood Pressure Section -->
                        <div class="row mb-4 mt-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-heartbeat me-2"></i>Average Blood Pressure
                                </h5>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Pre-dialysis Systolic BP</label>
                                <input type="number" class="form-control" name="pre_systolic_bp" id="preSystolic"
                                       value="<?php echo $edit_mode ? $patient_data['pre_systolic_bp'] : ''; ?>"
                                       onchange="calculateMAP('pre')">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Pre-dialysis Diastolic BP</label>
                                <input type="number" class="form-control" name="pre_diastolic_bp" id="preDiastolic"
                                       value="<?php echo $edit_mode ? $patient_data['pre_diastolic_bp'] : ''; ?>"
                                       onchange="calculateMAP('pre')">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Pre-dialysis MAP</label>
                                <input type="number" class="form-control" name="pre_map" id="preMAP" step="0.01" readonly>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Post-dialysis Systolic BP</label>
                                <input type="number" class="form-control" name="post_systolic_bp" id="postSystolic"
                                       value="<?php echo $edit_mode ? $patient_data['post_systolic_bp'] : ''; ?>"
                                       onchange="calculateMAP('post')">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Post-dialysis Diastolic BP</label>
                                <input type="number" class="form-control" name="post_diastolic_bp" id="postDiastolic"
                                       value="<?php echo $edit_mode ? $patient_data['post_diastolic_bp'] : ''; ?>"
                                       onchange="calculateMAP('post')">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Post-dialysis MAP</label>
                                <input type="number" class="form-control" name="post_map" id="postMAP" step="0.01" readonly>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Pulse (per minute)</label>
                                <input type="number" class="form-control" name="pulse_rate"
                                       value="<?php echo $edit_mode ? $patient_data['pulse_rate'] : ''; ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Temperature (°C)</label>
                                <input type="number" class="form-control" name="temperature" step="0.1"
                                       value="<?php echo $edit_mode ? $patient_data['temperature'] : ''; ?>">
                            </div>
                        </div>
                        
                        <!-- Vascular Access Section -->
                        <div class="row mb-4 mt-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-sitemap me-2"></i>Vascular Access
                                </h5>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="av_fistula" id="avFistula"
                                           <?php echo ($edit_mode && $patient_data['av_fistula']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="avFistula">
                                        AV Fistula
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="catheter" id="catheter"
                                           <?php echo ($edit_mode && $patient_data['catheter']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="catheter">
                                        Catheter
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-lg me-2">
                                    <i class="fas fa-save me-2"></i>
                                    <?php echo $edit_mode ? 'Update Patient' : 'Save Patient'; ?>
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

<script src="assets/js/calculations.js"></script>
<script>
// Initialize calculations if editing
<?php if ($edit_mode): ?>
document.addEventListener('DOMContentLoaded', function() {
    calculateAge();
    calculateBMI();
    calculateMAP('pre');
    calculateMAP('post');
    calculateDialysisDuration();
});
<?php endif; ?>

function addProblem(type) {
    const selectId = type + 'ProblemsSelect';
    const textareaId = type + 'Problems';
    
    const select = document.getElementById(selectId);
    const textarea = document.getElementById(textareaId);
    
    if (select.value) {
        const currentValue = textarea.value;
        const newValue = currentValue ? currentValue + ', ' + select.value : select.value;
        textarea.value = newValue;
        select.value = '';
    }
}

// Form submission handling
document.getElementById('patientForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('api/save_patient.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            FormManager.resetUnsavedChanges(); // Clear unsaved changes flag
            alert('Patient saved successfully!');
            // Always redirect to dashboard to avoid edit mode confusion
            window.location.href = 'dashboard.php';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the patient.');
    });
});
</script>

<?php include 'includes/footer.php'; ?>
