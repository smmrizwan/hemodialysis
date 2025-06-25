<?php
require_once 'config/init.php';
$page_title = 'Catheter Infections';
include 'includes/header.php';

// Get direct database connection
$db = new PDO("sqlite:database/dialysis_management.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("PRAGMA foreign_keys = ON");

// Get patient list for selection
try {
    $stmt = $db->prepare("SELECT id, file_number, name_english FROM patients ORDER BY name_english");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Catheter form: Found " . count($patients) . " patients");
} catch(PDOException $e) {
    error_log("Catheter form database error: " . $e->getMessage());
    $patients = [];
}

$selected_patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : '';
$infection_data = [];

// Debug output
error_log("Catheter form: Selected patient ID from URL: " . var_export($selected_patient_id, true));

// Get existing infection data if patient selected
if ($selected_patient_id) {
    try {
        $stmt = $db->prepare("SELECT * FROM catheter_infections WHERE patient_id = ? ORDER BY infection_date DESC");
        $stmt->execute([$selected_patient_id]);
        $infection_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Catheter form: Found " . count($infection_data) . " infections for patient " . $selected_patient_id);
    } catch(PDOException $e) {
        error_log("Catheter form infection query error: " . $e->getMessage());
        $infection_data = [];
    }
}

// Add common organisms list
$common_organisms = [
    'Staphylococcus aureus', 'Staphylococcus epidermidis',
    'Enterococcus faecalis', 'Enterococcus faecium',
    'Pseudomonas aeruginosa', 'Escherichia coli',
    'Klebsiella pneumoniae', 'Candida albicans',
    'Candida glabrata', 'Streptococcus viridans'
];

// Add common antibiotics list
$common_antibiotics = [
    'Inj. Vancomycin',
    'Inj. Meropenem', 
    'Inj. Ceftazidime',
    'Inj. Daptomycin',
    'Inj. Gentamicin',
    'Inj. Amikacin',
    'Inj. Piperacillin-Tazobactam',
    'Inj. Cefepime',
    'Inj. Linezolid',
    'Inj. Teicoplanin'
];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>
                        <i class="fas fa-virus me-2"></i>Catheter Related Infections
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
                            
                            <?php if ($selected_patient_id): ?>
                            <div class="alert alert-success mb-2" id="selectedPatientDisplay">
                                <i class="fas fa-user-check me-2"></i>
                                <strong>Selected:</strong> 
                                <?php 
                                foreach ($patients as $patient) {
                                    if ((int)$patient['id'] === (int)$selected_patient_id) {
                                        echo htmlspecialchars($patient['name_english']) . ' (' . htmlspecialchars($patient['file_number']) . ')';
                                        break;
                                    }
                                }
                                ?>
                            </div>
                            <?php endif; ?>

                            <select class="form-select" id="patientSelect" onchange="loadPatientInfections()" required>
                                <option value="">Choose a patient...</option>
                                <?php if (!empty($patients)): ?>
                                    <?php foreach ($patients as $patient): ?>
                                        <?php 
                                        $is_selected = ($selected_patient_id && (int)$selected_patient_id === (int)$patient['id']); 
                                        ?>
                                        <option value="<?php echo htmlspecialchars($patient['id']); ?>"<?php if ($is_selected) echo ' selected'; ?>>
                                            <?php echo htmlspecialchars($patient['name_english']) . ' (' . htmlspecialchars($patient['file_number']) . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>No patients found</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <?php if ($selected_patient_id): ?>
                    <!-- Add New Infection Form -->
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-plus me-2"></i>Add New Catheter Infection
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="infectionForm" method="POST">
                                <input type="hidden" name="patient_id" value="<?php echo $selected_patient_id; ?>">
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Infection Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="infection_date" id="infection_date" required>
                                    </div>
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label">Organism</label>
                                        <div class="input-group">
                                            <select class="form-select" id="organismSelect">
                                                <option value="">Select common organism...</option>
                                                <?php foreach ($common_organisms as $organism): ?>
                                                    <option value="<?php echo htmlspecialchars($organism); ?>">
                                                        <?php echo htmlspecialchars($organism); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="btn btn-outline-secondary" onclick="addOrganism()">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <input type="text" class="form-control mt-2" name="organism" id="organismInput" 
                                               placeholder="Enter organism name or select from dropdown">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label">Antibiotic Used</label>
                                        <div class="input-group">
                                            <select class="form-select" id="antibioticSelect">
                                                <option value="">Select common antibiotic...</option>
                                                <?php foreach ($common_antibiotics as $antibiotic): ?>
                                                    <option value="<?php echo htmlspecialchars($antibiotic); ?>">
                                                        <?php echo htmlspecialchars($antibiotic); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="btn btn-outline-secondary" onclick="addAntibiotic()" title="Add selected antibiotic">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <input type="text" class="form-control mt-2" name="antibiotic_used" id="antibioticInput" 
                                               placeholder="Enter antibiotic name or select from dropdown">
                                        <small class="text-muted">Select from dropdown or type directly</small>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-warning btn-lg me-2" id="submitBtn">
                                            <i class="fas fa-save me-2"></i>Save Infection Record
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-lg" onclick="clearInfectionForm()">
                                            <i class="fas fa-eraser me-2"></i>Clear Form
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Infection History -->
                    <?php if (!empty($infection_data)): ?>
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>Infection History
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Date</th>
                                            <th>Organism</th>
                                            <th>Antibiotic Used</th>
                                            <th>Days Since Last Infection</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($infection_data as $index => $infection): ?>
                                        <?php
                                        $daysSince = '';
                                        if ($index < count($infection_data) - 1) {
                                            $currentDate = new DateTime($infection['infection_date']);
                                            $previousDate = new DateTime($infection_data[$index + 1]['infection_date']);
                                            $diff = $currentDate->diff($previousDate);
                                            $daysSince = $diff->days . ' days';
                                        }
                                        ?>
                                        <tr data-infection-id="<?php echo $infection['id']; ?>">
                                            <td>
                                                <strong><?php echo format_date($infection['infection_date']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo date('l', strtotime($infection['infection_date'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning text-dark fs-6">
                                                    <?php echo htmlspecialchars($infection['organism'] ?: 'Not specified'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary text-white fs-6">
                                                    <?php echo htmlspecialchars($infection['antibiotic_used'] ?: 'Not specified'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($daysSince): ?>
                                                    <span class="badge bg-info"><?php echo $daysSince; ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">First infection</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="editInfection(<?php echo $infection['id']; ?>)"
                                                            title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteInfection(<?php echo $infection['id']; ?>)"
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Infection Statistics -->
                            <div class="row mt-4">
                                <div class="col-md-3">
                                    <div class="card bg-warning text-dark">
                                        <div class="card-body text-center">
                                            <h3><?php echo count($infection_data); ?></h3>
                                            <p class="mb-0">Total Infections</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-danger text-white">
                                        <div class="card-body text-center">
                                            <?php
                                            $thisYear = date('Y');
                                            $thisYearInfections = array_filter($infection_data, function($inf) use ($thisYear) {
                                                return date('Y', strtotime($inf['infection_date'])) == $thisYear;
                                            });
                                            ?>
                                            <h3><?php echo count($thisYearInfections); ?></h3>
                                            <p class="mb-0">This Year</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body text-center">
                                            <?php
                                            if (!empty($infection_data)) {
                                                $lastInfection = new DateTime($infection_data[0]['infection_date']);
                                                $today = new DateTime();
                                                $daysSinceLast = $today->diff($lastInfection)->days;
                                            } else {
                                                $daysSinceLast = 0;
                                            }
                                            ?>
                                            <h3><?php echo $daysSinceLast; ?></h3>
                                            <p class="mb-0">Days Since Last</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body text-center">
                                            <?php
                                            $organisms = array_column($infection_data, 'organism');
                                            $organisms = array_filter($organisms);
                                            $uniqueOrganisms = array_unique($organisms);
                                            ?>
                                            <h3><?php echo count($uniqueOrganisms); ?></h3>
                                            <p class="mb-0">Different Organisms</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>No catheter infections recorded</strong> for this patient.
                    </div>
                    <?php endif; ?>
                    
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Disable the global form manager for this page to prevent conflicts
if (window.FormManager) {
    window.FormManager.unsavedChanges = false;
}

// Override beforeunload for this page
window.addEventListener('beforeunload', function(e) {
    // Don't show warning for this page
    e.stopImmediatePropagation();
});

function loadPatientInfections() {
    const patientId = document.getElementById('patientSelect').value;
    if (patientId) {
        window.location.href = 'catheter_infection_form.php?patient_id=' + patientId;
    }
}

function addOrganism() {
    const select = document.getElementById('organismSelect');
    const input = document.getElementById('organismInput');
    
    console.log('addOrganism called - select value:', select.value);
    
    if (select.value) {
        // Set the input value
        input.value = select.value;
        console.log('Organism transferred:', select.value);
        
        // Reset the select to show placeholder again
        select.value = '';
        
        // Focus on the input field to show the value was added
        input.focus();
        
        // Trigger input event to ensure any listeners are notified
        const event = new Event('input', { bubbles: true });
        input.dispatchEvent(event);
    }
}

function addAntibiotic() {
    const select = document.getElementById('antibioticSelect');
    const input = document.getElementById('antibioticInput');
    
    console.log('addAntibiotic called - select value:', select.value);
    
    if (select.value) {
        // Set the input value
        input.value = select.value;
        console.log('Antibiotic transferred:', select.value);
        
        // Reset the select to show placeholder again
        select.value = '';
        
        // Focus on the input field to show the value was added
        input.focus();
        
        // Trigger input event to ensure any listeners are notified
        const event = new Event('input', { bubbles: true });
        input.dispatchEvent(event);
    }
}

function clearInfectionForm() {
    document.getElementById('infectionForm').reset();
    // Explicitly clear all fields
    const organismInput = document.getElementById('organismInput');
    const organismSelect = document.getElementById('organismSelect');
    const antibioticInput = document.getElementById('antibioticInput');
    const antibioticSelect = document.getElementById('antibioticSelect');
    
    if (organismInput) organismInput.value = '';
    if (organismSelect) organismSelect.value = '';
    if (antibioticInput) antibioticInput.value = '';
    if (antibioticSelect) antibioticSelect.value = '';
    
    console.log('Form cleared');
}

function editInfection(id) {
    // Get infection data from the row
    const row = document.querySelector(`tr[data-infection-id="${id}"]`);
    if (!row) {
        alert('Error: Cannot find infection record');
        return;
    }
    
    // Extract current values from the row
    const dateCell = row.querySelector('td:first-child strong');
    const organismCell = row.querySelector('td:nth-child(2) .badge');
    const antibioticCell = row.querySelector('td:nth-child(3) .badge');
    
    const currentDate = dateCell ? dateCell.textContent.trim() : '';
    const currentOrganism = organismCell ? organismCell.textContent.trim() : '';
    const currentAntibiotic = antibioticCell ? antibioticCell.textContent.trim() : '';
    
    // Prompt for new values
    const newDate = prompt('Edit infection date (YYYY-MM-DD):', currentDate);
    if (newDate === null) return; // User cancelled
    
    const newOrganism = prompt('Edit organism:', currentOrganism === 'Not specified' ? '' : currentOrganism);
    if (newOrganism === null) return; // User cancelled
    
    const newAntibiotic = prompt('Edit antibiotic used:', currentAntibiotic === 'Not specified' ? '' : currentAntibiotic);
    if (newAntibiotic === null) return; // User cancelled
    
    // Validate date
    if (!newDate || !Date.parse(newDate)) {
        alert('Please enter a valid date in YYYY-MM-DD format');
        return;
    }
    
    // Update the infection record
    const formData = new FormData();
    formData.append('id', id);
    formData.append('infection_date', newDate);
    formData.append('organism', newOrganism);
    formData.append('antibiotic_used', newAntibiotic);
    
    fetch('api/update_catheter_infection.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Infection record updated successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the record.');
    });
}

function deleteInfection(id) {
    if (confirm('Are you sure you want to delete this infection record?')) {
        fetch('api/delete_catheter_infection.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({id: id})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Infection record deleted successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the record.');
        });
    }
}

// Form submission handling - completely custom for this form
document.addEventListener('DOMContentLoaded', function() {
    const infectionForm = document.getElementById('infectionForm');
    if (infectionForm) {
        let isSubmitting = false;
        
        infectionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Prevent multiple submissions
            if (isSubmitting) {
                console.log('Form submission already in progress');
                return false;
            }
            
            // Get form values
            const organismInput = document.getElementById('organismInput');
            const organismValue = organismInput ? organismInput.value : '';
            
            const antibioticInput = document.getElementById('antibioticInput');
            const antibioticValue = antibioticInput ? antibioticInput.value : '';
            
            const patientId = this.querySelector('input[name="patient_id"]').value;
            const infectionDate = this.querySelector('input[name="infection_date"]').value;
            
            // Validate required fields
            if (!patientId || !infectionDate) {
                alert('Patient and infection date are required');
                return false;
            }
            
            isSubmitting = true;
            
            const submitButton = document.getElementById('submitBtn');
            const originalText = submitButton.innerHTML;
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            
            // Create FormData with all fields
            const formData = new FormData();
            formData.append('patient_id', patientId);
            formData.append('infection_date', infectionDate);
            formData.append('organism', organismValue);
            formData.append('antibiotic_used', antibioticValue);
            
            // Debug: Log all form data
            console.log('Form data being submitted:');
            console.log('- patient_id:', patientId);
            console.log('- infection_date:', infectionDate);
            console.log('- organism:', organismValue);
            console.log('- antibiotic_used:', antibioticValue);
            
            fetch('api/save_catheter_infection.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed response:', data);
                    
                    if (data.success) {
                        alert('Infection record saved successfully!');
                        clearInfectionForm();
                        // Reload page after short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    } else {
                        alert('Error: ' + (data.message || 'Unknown error'));
                    }
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response text:', text);
                    alert('Server returned invalid response. Check console for details.');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Network error: ' + error.message);
            })
            .finally(() => {
                // Reset form state
                setTimeout(() => {
                    isSubmitting = false;
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }, 1000);
            });
        });
    }
    
    // Set patient selection on page load
    const urlParams = new URLSearchParams(window.location.search);
    const patientId = urlParams.get('patient_id');
    const patientSelect = document.getElementById('patientSelect');
    
    if (patientId && patientSelect) {
        patientSelect.value = patientId;
        console.log('Patient selection set to:', patientId);
    }
    
    // Add event listener for organism dropdown
    const organismSelect = document.getElementById('organismSelect');
    if (organismSelect) {
        organismSelect.addEventListener('change', function() {
            if (this.value) {
                const input = document.getElementById('organismInput');
                input.value = this.value;
                console.log('Organism auto-transferred:', this.value);
                input.focus();
            }
        });
    }
    
    // Add event listener for antibiotic dropdown
    const antibioticSelect = document.getElementById('antibioticSelect');
    if (antibioticSelect) {
        antibioticSelect.addEventListener('change', function() {
            if (this.value) {
                const input = document.getElementById('antibioticInput');
                input.value = this.value;
                console.log('Antibiotic auto-transferred:', this.value);
                input.focus();
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>