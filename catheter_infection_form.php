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

                            <select class="form-select patient-selector" id="patientSelect" onchange="loadPatientInfections()" required>
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
                            <form id="infectionForm" method="POST" action="api/save_catheter_infection.php" data-ajax="true">
                                <input type="hidden" name="patient_id" value="<?php echo $selected_patient_id; ?>">
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Infection Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="infection_date" required>
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
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-warning btn-lg me-2">
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
function loadPatientInfections() {
    const patientId = document.getElementById('patientSelect').value;
    if (patientId) {
        window.location.href = 'catheter_infection_form.php?patient_id=' + patientId;
    }
}

function addOrganism() {
    const select = document.getElementById('organismSelect');
    const input = document.getElementById('organismInput');
    
    if (select.value) {
        input.value = select.value;
        select.value = '';
    }
}

function clearInfectionForm() {
    document.getElementById('infectionForm').reset();
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
    
    const currentDate = dateCell ? dateCell.textContent.trim() : '';
    const currentOrganism = organismCell ? organismCell.textContent.trim() : '';
    
    // Prompt for new values
    const newDate = prompt('Edit infection date (YYYY-MM-DD):', currentDate);
    if (newDate === null) return; // User cancelled
    
    const newOrganism = prompt('Edit organism:', currentOrganism);
    if (newOrganism === null) return; // User cancelled
    
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

// Ensure patient selection is maintained after page load
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const patientId = urlParams.get('patient_id');
    const patientSelect = document.getElementById('patientSelect');
    
    if (patientId && patientSelect) {
        // Force browser to refresh and show selected option
        patientSelect.style.display = 'none';
        patientSelect.offsetHeight; // Force reflow
        patientSelect.style.display = 'block';
        
        // Set the dropdown value
        patientSelect.value = patientId;
        
        // Force visual update
        patientSelect.blur();
        patientSelect.focus();
        patientSelect.blur();
        
        console.log('Patient selection set to:', patientId);
        
        // Verify selection worked
        if (patientSelect.value === patientId) {
            console.log('Patient dropdown correctly shows:', patientSelect.options[patientSelect.selectedIndex].text);
        } else {
            console.log('Warning: Dropdown selection failed');
        }
    }
});

// Form submission handling - only add listener if form exists
document.addEventListener('DOMContentLoaded', function() {
    const infectionForm = document.getElementById('infectionForm');
    if (infectionForm) {
        let isSubmitting = false;
        let lastSubmissionTime = 0;
        
        infectionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const now = Date.now();
            // Prevent submissions within 2 seconds of each other
            if (isSubmitting || (now - lastSubmissionTime) < 2000) {
                console.log('Duplicate submission blocked');
                return false;
            }
            
            isSubmitting = true;
            lastSubmissionTime = now;
            
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton ? submitButton.textContent : '';
            
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.style.pointerEvents = 'none';
                submitButton.textContent = 'Saving...';
            }
            
            const formData = new FormData(this);
            console.log('Submitting infection form...');
            
            fetch('api/save_catheter_infection.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Infection record saved successfully!');
                    clearInfectionForm();
                    setTimeout(() => location.reload(), 500);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the infection record.');
            })
            .finally(() => {
                setTimeout(() => {
                    isSubmitting = false;
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.style.pointerEvents = 'auto';
                        submitButton.textContent = originalText;
                    }
                }, 1000);
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
