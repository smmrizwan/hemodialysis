<?php
require_once 'config/init.php';
$page_title = 'Medications';
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
    error_log("Medications form: Found " . count($patients) . " patients");
} catch(PDOException $e) {
    error_log("Medications form database error: " . $e->getMessage());
    $patients = [];
}

$selected_patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : '';
$medications_data = [];

// Get existing medications data if patient selected
if ($selected_patient_id) {
    try {
        $stmt = $db->prepare("SELECT * FROM medications WHERE patient_id = ? ORDER BY row_order ASC");
        $stmt->execute([$selected_patient_id]);
        $medications_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Medications form: Found " . count($medications_data) . " medications for patient " . $selected_patient_id);
    } catch(PDOException $e) {
        error_log("Medications form error: " . $e->getMessage());
        $medications_data = [];
    }
}

// Common medications list for dialysis patients
$common_medications = [
    'Epoetin alfa (Epogen)', 'Inj. Mircera', 'Darbepoetin alfa (Aranesp)', 'Iron sucrose (Venofer)',
    'Ferric gluconate (Ferrlecit)', 'Calcitriol (Rocaltrol)', 'Paricalcitol (Zemplar)',
    'Inj. Etelcalcetide', 'Tab. Cinacalcet', 'Cap. Alfacalcidol', 'Sevelamer (Renagel)', 
    'Calcium carbonate', 'Lanthanum carbonate (Fosrenol)', 'Tab. Folic acid', 'Tab. Multivitamin',
    'Lisinopril', 'Amlodipine', 'Metoprolol', 'Furosemide', 'Atorvastatin',
    'Insulin', 'Metformin', 'Aspirin', 'Heparin', 'Warfarin', 'Pantoprazole'
];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>
                        <i class="fas fa-pills me-2"></i>Medications Management
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
                            
                            <select class="form-select patient-selector" id="patientSelect" onchange="loadPatientMedications()" required>
                                <option value="">Choose a patient...</option>
                                <?php foreach ($patients as $patient): ?>
                                    <?php $is_selected = ($selected_patient_id && (int)$selected_patient_id === (int)$patient['id']); ?>
                                    <option value="<?php echo htmlspecialchars($patient['id']); ?>"<?php if ($is_selected) echo ' selected'; ?>>
                                        <?php echo htmlspecialchars($patient['name_english']) . ' (' . htmlspecialchars($patient['file_number']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <?php if ($selected_patient_id): ?>
                    <!-- Date Selection -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Medication Date <span class="text-danger">*</span></label>
                            <input type="month" class="form-control" id="medicationDate" name="medication_date" 
                                   value="<?php echo date('Y-m'); ?>" required>
                            <small class="form-text text-muted">Select month and year for this medication record</small>
                        </div>
                    </div>
                    
                    <!-- Medications Form -->
                    <form id="medicationsForm" method="POST" action="api/save_medications.php" data-ajax="true">
                        <input type="hidden" name="patient_id" value="<?php echo $selected_patient_id; ?>">
                        <input type="hidden" name="medication_date" id="hiddenMedicationDate" value="<?php echo date('Y-m'); ?>">
                        
                        <div class="card">
                            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-list me-2"></i>Current Medications (15 rows maximum)
                                </h5>
                                <button type="button" class="btn btn-light btn-sm" onclick="addMedicationRow()">
                                    <i class="fas fa-plus me-1"></i>Add Row
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="medicationsTable">
                                        <thead class="table-dark">
                                            <tr>
                                                <th style="width: 5%">#</th>
                                                <th style="width: 35%">Medication Name</th>
                                                <th style="width: 20%">Dosage</th>
                                                <th style="width: 20%">Frequency</th>
                                                <th style="width: 15%">Route</th>
                                                <th style="width: 5%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="medicationsTableBody">
                                            <?php 
                                            // Ensure we have at least 15 rows
                                            $medication_rows = [];
                                            for ($i = 0; $i < 15; $i++) {
                                                if (isset($medications_data[$i])) {
                                                    $medication_rows[$i] = $medications_data[$i];
                                                } else {
                                                    $medication_rows[$i] = [
                                                        'id' => '',
                                                        'medication_name' => '',
                                                        'dosage' => '',
                                                        'frequency' => '',
                                                        'route' => ''
                                                    ];
                                                }
                                            }
                                            
                                            foreach ($medication_rows as $index => $medication): ?>
                                            <tr>
                                                <td class="align-middle">
                                                    <strong><?php echo $index + 1; ?></strong>
                                                    <input type="hidden" name="medications[<?php echo $index; ?>][id]" value="<?php echo $medication['id']; ?>">
                                                    <input type="hidden" name="medications[<?php echo $index; ?>][row_order]" value="<?php echo $index + 1; ?>">
                                                </td>
                                                <td>
                                                    <div class="input-group">
                                                        <select class="form-select medication-select" data-row="<?php echo $index; ?>" onchange="selectMedication(<?php echo $index; ?>)">
                                                            <option value="">Select from list...</option>
                                                            <?php foreach ($common_medications as $med): ?>
                                                                <option value="<?php echo htmlspecialchars($med); ?>" 
                                                                        <?php echo ($medication['medication_name'] == $med) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($med); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearMedicationSelect(<?php echo $index; ?>)">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                    <input type="text" class="form-control mt-1" name="medications[<?php echo $index; ?>][medication_name]" 
                                                           id="medication_name_<?php echo $index; ?>" placeholder="Or type medication name"
                                                           value="<?php echo htmlspecialchars($medication['medication_name']); ?>">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="medications[<?php echo $index; ?>][dosage]" 
                                                           placeholder="e.g., 5mg, 10ml" value="<?php echo htmlspecialchars($medication['dosage']); ?>">
                                                </td>
                                                <td>
                                                    <select class="form-select" name="medications[<?php echo $index; ?>][frequency]">
                                                        <option value="">Select frequency</option>
                                                        <option value="Once daily" <?php echo ($medication['frequency'] == 'Once daily') ? 'selected' : ''; ?>>Once daily</option>
                                                        <option value="Twice daily" <?php echo ($medication['frequency'] == 'Twice daily') ? 'selected' : ''; ?>>Twice daily</option>
                                                        <option value="Three times daily" <?php echo ($medication['frequency'] == 'Three times daily') ? 'selected' : ''; ?>>Three times daily</option>
                                                        <option value="Four times daily" <?php echo ($medication['frequency'] == 'Four times daily') ? 'selected' : ''; ?>>Four times daily</option>
                                                        <option value="Every other day" <?php echo ($medication['frequency'] == 'Every other day') ? 'selected' : ''; ?>>Every other day</option>
                                                        <option value="Once weekly" <?php echo ($medication['frequency'] == 'Once weekly') ? 'selected' : ''; ?>>Once weekly</option>
                                                        <option value="Twice weekly" <?php echo ($medication['frequency'] == 'Twice weekly') ? 'selected' : ''; ?>>Twice weekly</option>
                                                        <option value="Thrice weekly" <?php echo ($medication['frequency'] == 'Thrice weekly') ? 'selected' : ''; ?>>Thrice weekly</option>
                                                        <option value="Once every two weeks" <?php echo ($medication['frequency'] == 'Once every two weeks') ? 'selected' : ''; ?>>Once every two weeks</option>
                                                        <option value="Weekly" <?php echo ($medication['frequency'] == 'Weekly') ? 'selected' : ''; ?>>Weekly</option>
                                                        <option value="Monthly" <?php echo ($medication['frequency'] == 'Monthly') ? 'selected' : ''; ?>>Monthly</option>
                                                        <option value="STAT" <?php echo ($medication['frequency'] == 'STAT') ? 'selected' : ''; ?>>STAT</option>
                                                        <option value="As needed" <?php echo ($medication['frequency'] == 'As needed') ? 'selected' : ''; ?>>As needed</option>
                                                        <option value="With dialysis" <?php echo ($medication['frequency'] == 'With dialysis') ? 'selected' : ''; ?>>With dialysis</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select class="form-select" name="medications[<?php echo $index; ?>][route]">
                                                        <option value="">Select route</option>
                                                        <option value="PO" <?php echo ($medication['route'] == 'PO') ? 'selected' : ''; ?>>PO (Oral)</option>
                                                        <option value="IV" <?php echo ($medication['route'] == 'IV') ? 'selected' : ''; ?>>IV (Intravenous)</option>
                                                        <option value="s/c" <?php echo ($medication['route'] == 's/c') ? 'selected' : ''; ?>>s/c (Subcutaneous)</option>
                                                        <option value="IM" <?php echo ($medication['route'] == 'IM') ? 'selected' : ''; ?>>IM (Intramuscular)</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearMedicationRow(<?php echo $index; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Medication Summary -->
                        <div class="card mt-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>Medication Summary
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row" id="medicationSummary">
                                    <div class="col-md-3">
                                        <div class="card bg-primary text-white">
                                            <div class="card-body text-center">
                                                <h3 id="totalMeds">0</h3>
                                                <p class="mb-0">Total Medications</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-success text-white">
                                            <div class="card-body text-center">
                                                <h3 id="oralMeds">0</h3>
                                                <p class="mb-0">Oral (PO)</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-warning text-dark">
                                            <div class="card-body text-center">
                                                <h3 id="ivMeds">0</h3>
                                                <p class="mb-0">IV Medications</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-danger text-white">
                                            <div class="card-body text-center">
                                                <h3 id="dialysisMeds">0</h3>
                                                <p class="mb-0">With Dialysis</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h6>Current Medications by Category:</h6>
                                        <div id="medicationCategories"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-success btn-lg me-2">
                                    <i class="fas fa-save me-2"></i>Save All Medications
                                </button>
                                <button type="button" class="btn btn-warning btn-lg me-2" onclick="clearAllMedications()">
                                    <i class="fas fa-eraser me-2"></i>Clear All
                                </button>
                                <button type="button" class="btn btn-info btn-lg me-2" onclick="printMedicationList()">
                                    <i class="fas fa-print me-2"></i>Print List
                                </button>
                                <button type="button" class="btn btn-secondary btn-lg" onclick="importMedications()">
                                    <i class="fas fa-file-import me-2"></i>Import Template
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
function loadPatientMedications() {
    const patientId = document.getElementById('patientSelect').value;
    if (patientId) {
        window.location.href = 'medications_form.php?patient_id=' + patientId;
    }
}

function selectMedication(rowIndex) {
    const select = document.querySelector(`select[data-row="${rowIndex}"]`);
    const input = document.getElementById(`medication_name_${rowIndex}`);
    
    if (select && input && select.value) {
        input.value = select.value;
        updateMedicationSummary();
    }
}

function clearMedicationSelect(rowIndex) {
    const select = document.querySelector(`select[data-row="${rowIndex}"]`);
    if (select) {
        select.value = '';
    }
}

function clearMedicationRow(rowIndex) {
    if (confirm('Clear this medication row?')) {
        const nameInput = document.getElementById(`medication_name_${rowIndex}`);
        const dosageInput = document.querySelector(`input[name="medications[${rowIndex}][dosage]"]`);
        const frequencySelect = document.querySelector(`select[name="medications[${rowIndex}][frequency]"]`);
        const routeSelect = document.querySelector(`select[name="medications[${rowIndex}][route]"]`);
        
        if (nameInput) nameInput.value = '';
        if (dosageInput) dosageInput.value = '';
        if (frequencySelect) frequencySelect.value = '';
        if (routeSelect) routeSelect.value = '';
        
        clearMedicationSelect(rowIndex);
        updateMedicationSummary();
    }
}

function addMedicationRow() {
    // Find first empty row
    for (let i = 0; i < 15; i++) {
        const nameInput = document.getElementById(`medication_name_${i}`);
        if (!nameInput.value.trim()) {
            nameInput.focus();
            return;
        }
    }
    alert('All 15 medication rows are in use. Please clear a row first.');
}

function clearAllMedications() {
    if (confirm('Are you sure you want to clear all medications? This cannot be undone.')) {
        for (let i = 0; i < 15; i++) {
            clearMedicationRow(i);
        }
    }
}

function updateMedicationSummary() {
    let totalMeds = 0;
    let oralMeds = 0;
    let ivMeds = 0;
    let dialysisMeds = 0;
    
    const categories = {};
    
    for (let i = 0; i < 15; i++) {
        const nameInput = document.getElementById(`medication_name_${i}`);
        const routeSelect = document.querySelector(`select[name="medications[${i}][route]"]`);
        const frequencySelect = document.querySelector(`select[name="medications[${i}][frequency]"]`);
        
        if (nameInput && nameInput.value.trim()) {
            totalMeds++;
            
            // Count by route
            if (routeSelect && routeSelect.value === 'PO') oralMeds++;
            else if (routeSelect && routeSelect.value === 'IV') ivMeds++;
            
            // Count dialysis medications
            if (frequencySelect && frequencySelect.value === 'With dialysis') dialysisMeds++;
            
            // Categorize medications
            const medName = nameInput.value.toLowerCase();
            let category = 'Other';
            
            if (medName.includes('epoetin') || medName.includes('darbepoetin')) {
                category = 'ESA (Anemia)';
            } else if (medName.includes('iron') || medName.includes('ferric')) {
                category = 'Iron Supplements';
            } else if (medName.includes('calcitriol') || medName.includes('paricalcitol')) {
                category = 'Vitamin D Analogs';
            } else if (medName.includes('sevelamer') || medName.includes('calcium carbonate')) {
                category = 'Phosphate Binders';
            } else if (medName.includes('amlodipine') || medName.includes('lisinopril') || medName.includes('metoprolol')) {
                category = 'Cardiovascular';
            } else if (medName.includes('insulin') || medName.includes('metformin')) {
                category = 'Diabetes';
            } else if (medName.includes('heparin') || medName.includes('warfarin')) {
                category = 'Anticoagulants';
            }
            
            categories[category] = (categories[category] || 0) + 1;
        }
    }
    
    // Update summary cards - only if elements exist
    const totalMedsEl = document.getElementById('totalMeds');
    const oralMedsEl = document.getElementById('oralMeds');
    const ivMedsEl = document.getElementById('ivMeds');
    const dialysisMedsEl = document.getElementById('dialysisMeds');
    
    if (totalMedsEl) totalMedsEl.textContent = totalMeds;
    if (oralMedsEl) oralMedsEl.textContent = oralMeds;
    if (ivMedsEl) ivMedsEl.textContent = ivMeds;
    if (dialysisMedsEl) dialysisMedsEl.textContent = dialysisMeds;
    
    // Update categories - only if element exists
    const categoriesDiv = document.getElementById('medicationCategories');
    if (categoriesDiv) {
        categoriesDiv.innerHTML = '';
        
        for (const [category, count] of Object.entries(categories)) {
            const badge = document.createElement('span');
            badge.className = 'badge bg-secondary me-2 mb-1 fs-6';
            badge.textContent = `${category}: ${count}`;
            categoriesDiv.appendChild(badge);
        }
        
        if (Object.keys(categories).length === 0) {
            categoriesDiv.innerHTML = '<span class="text-muted">No medications entered</span>';
        }
    }
}

function printMedicationList() {
    const patientId = '<?php echo $selected_patient_id; ?>';
    if (patientId) {
        window.open(`patient_report.php?id=${patientId}&section=medications`, '_blank');
    }
}

function importMedications() {
    // Define medication templates for common dialysis scenarios
    const templates = {
        'anemia_management': {
            name: 'Anemia Management Protocol',
            medications: [
                { name: 'Epoetin alfa (Epogen)', dosage: '4000 units', frequency: 'Thrice weekly', route: 'IV' },
                { name: 'Iron sucrose (Venofer)', dosage: '100mg', frequency: 'With dialysis', route: 'IV' },
                { name: 'Tab. Folic acid', dosage: '5mg', frequency: 'Once daily', route: 'PO' },
                { name: 'Tab. Multivitamin', dosage: '1 tablet', frequency: 'Once daily', route: 'PO' }
            ]
        },
        'bone_mineral': {
            name: 'Bone & Mineral Disorders',
            medications: [
                { name: 'Calcitriol (Rocaltrol)', dosage: '0.25mcg', frequency: 'Once daily', route: 'PO' },
                { name: 'Sevelamer (Renagel)', dosage: '800mg', frequency: 'Three times daily', route: 'PO' },
                { name: 'Calcium carbonate', dosage: '500mg', frequency: 'Twice daily', route: 'PO' },
                { name: 'Tab. Cinacalcet', dosage: '30mg', frequency: 'Once daily', route: 'PO' }
            ]
        },
        'cardiovascular': {
            name: 'Cardiovascular Protection',
            medications: [
                { name: 'Lisinopril', dosage: '10mg', frequency: 'Once daily', route: 'PO' },
                { name: 'Amlodipine', dosage: '5mg', frequency: 'Once daily', route: 'PO' },
                { name: 'Metoprolol', dosage: '25mg', frequency: 'Twice daily', route: 'PO' },
                { name: 'Atorvastatin', dosage: '20mg', frequency: 'Once daily', route: 'PO' }
            ]
        },
        'diabetes_dialysis': {
            name: 'Diabetes Management for Dialysis',
            medications: [
                { name: 'Insulin', dosage: 'Per sliding scale', frequency: 'As needed', route: 'IV' },
                { name: 'Aspirin', dosage: '81mg', frequency: 'Once daily', route: 'PO' },
                { name: 'Pantoprazole', dosage: '40mg', frequency: 'Once daily', route: 'PO' },
                { name: 'Furosemide', dosage: '40mg', frequency: 'Twice daily', route: 'PO' }
            ]
        },
        'anticoagulation': {
            name: 'Anticoagulation Protocol',
            medications: [
                { name: 'Heparin', dosage: '5000 units', frequency: 'With dialysis', route: 'IV' },
                { name: 'Warfarin', dosage: '2.5mg', frequency: 'Once daily', route: 'PO' },
                { name: 'Aspirin', dosage: '81mg', frequency: 'Once daily', route: 'PO' }
            ]
        },
        'new_patient': {
            name: 'New Dialysis Patient Starter Pack',
            medications: [
                { name: 'Epoetin alfa (Epogen)', dosage: '3000 units', frequency: 'Thrice weekly', route: 'IV' },
                { name: 'Iron sucrose (Venofer)', dosage: '100mg', frequency: 'With dialysis', route: 'IV' },
                { name: 'Calcitriol (Rocaltrol)', dosage: '0.25mcg', frequency: 'Once daily', route: 'PO' },
                { name: 'Sevelamer (Renagel)', dosage: '800mg', frequency: 'Three times daily', route: 'PO' },
                { name: 'Tab. Folic acid', dosage: '5mg', frequency: 'Once daily', route: 'PO' },
                { name: 'Tab. Multivitamin', dosage: '1 tablet', frequency: 'Once daily', route: 'PO' }
            ]
        }
    };
    
    // Create modal HTML
    const modalHTML = `
        <div class="modal fade" id="importTemplateModal" tabindex="-1" aria-labelledby="importTemplateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="importTemplateModalLabel">
                            <i class="fas fa-file-import me-2"></i>Import Medication Template
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">Select a pre-defined medication template to quickly populate the form:</p>
                        <div class="row">
                            ${Object.entries(templates).map(([key, template]) => `
                                <div class="col-md-6 mb-3">
                                    <div class="card template-card" data-template="${key}" style="cursor: pointer; border: 2px solid #dee2e6;">
                                        <div class="card-body">
                                            <h6 class="card-title text-primary">${template.name}</h6>
                                            <small class="text-muted">${template.medications.length} medications</small>
                                            <ul class="list-unstyled mt-2 mb-0" style="font-size: 0.85em;">
                                                ${template.medications.slice(0, 3).map(med => `
                                                    <li><i class="fas fa-pill me-1"></i>${med.name}</li>
                                                `).join('')}
                                                ${template.medications.length > 3 ? '<li class="text-muted">+ ' + (template.medications.length - 3) + ' more...</li>' : ''}
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="applyTemplate" disabled>Apply Template</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if present
    const existingModal = document.getElementById('importTemplateModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Add event listeners
    let selectedTemplate = null;
    
    document.querySelectorAll('.template-card').forEach(card => {
        card.addEventListener('click', function() {
            // Remove previous selection
            document.querySelectorAll('.template-card').forEach(c => {
                c.style.border = '2px solid #dee2e6';
                c.style.backgroundColor = '';
            });
            
            // Select this card
            this.style.border = '2px solid #0d6efd';
            this.style.backgroundColor = '#f8f9fa';
            selectedTemplate = this.dataset.template;
            
            // Enable apply button
            document.getElementById('applyTemplate').disabled = false;
        });
    });
    
    document.getElementById('applyTemplate').addEventListener('click', function() {
        if (selectedTemplate && templates[selectedTemplate]) {
            applyMedicationTemplate(templates[selectedTemplate]);
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('importTemplateModal'));
            modal.hide();
        }
    });
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('importTemplateModal'));
    modal.show();
}

function applyMedicationTemplate(template) {
    if (confirm(`This will replace current medications with "${template.name}" template. Continue?`)) {
        // Clear existing medications first
        clearAllMedications();
        
        // Apply template medications
        template.medications.forEach((med, index) => {
            if (index < 15) { // Limit to available rows
                const nameInput = document.getElementById(`medication_name_${index}`);
                const dosageInput = document.querySelector(`input[name="medications[${index}][dosage]"]`);
                const frequencySelect = document.querySelector(`select[name="medications[${index}][frequency]"]`);
                const routeSelect = document.querySelector(`select[name="medications[${index}][route]"]`);
                
                if (nameInput) nameInput.value = med.name;
                if (dosageInput) dosageInput.value = med.dosage;
                if (frequencySelect) frequencySelect.value = med.frequency;
                if (routeSelect) routeSelect.value = med.route;
                
                // Update dropdown selection
                const select = document.querySelector(`select[data-row="${index}"]`);
                if (select) {
                    // Find matching option
                    for (let option of select.options) {
                        if (option.value === med.name) {
                            select.value = med.name;
                            break;
                        }
                    }
                }
            }
        });
        
        // Update summary
        updateMedicationSummary();
        
        alert(`Successfully applied "${template.name}" template with ${template.medications.length} medications.`);
    }
}

// Form submission handling - only add listener if form exists
document.addEventListener('DOMContentLoaded', function() {
    const medicationsForm = document.getElementById('medicationsForm');
    if (medicationsForm) {
        let isSubmitting = false;
        
        medicationsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (isSubmitting) {
                return false;
            }
            
            isSubmitting = true;
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Saving...';
            }
            
            const formData = new FormData(this);
            
            fetch('api/save_medications.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Medications saved successfully!');
                    // Clear unsaved changes flag before redirect
                    if (typeof FormManager !== 'undefined') {
                        FormManager.unsavedChanges = false;
                    }
                    window.onbeforeunload = null;
                    // Redirect to same page with patient ID to refresh data
                    const patientId = '<?php echo $selected_patient_id; ?>';
                    window.location.href = 'medications_form.php?patient_id=' + patientId;
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the medications.');
            })
            .finally(() => {
                isSubmitting = false;
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Save All Medications';
                }
            });
        });
    }
    
    // Sync medication date with hidden field
    const medicationDateField = document.getElementById('medicationDate');
    const hiddenMedicationDate = document.getElementById('hiddenMedicationDate');
    
    if (medicationDateField && hiddenMedicationDate) {
        medicationDateField.addEventListener('change', function() {
            hiddenMedicationDate.value = this.value;
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

// Auto-update summary when inputs change
document.addEventListener('input', function(e) {
    if (e.target.closest('#medicationsTable')) {
        updateMedicationSummary();
    }
});

document.addEventListener('change', function(e) {
    if (e.target.closest('#medicationsTable')) {
        updateMedicationSummary();
    }
});

// Completely disable unsaved changes warning for medications form
window.addEventListener('DOMContentLoaded', function() {
    // Override the global FormManager if it exists
    if (typeof FormManager !== 'undefined') {
        FormManager.unsavedChanges = false;
        FormManager.setupUnsavedChangesWarning = function() {
            // Disable this function for medications form
        };
    }
    
    // Remove any existing beforeunload listeners
    window.onbeforeunload = null;
    
    // Add our own that does nothing
    window.addEventListener('beforeunload', function(e) {
        // Never show unsaved changes warning
        return undefined;
    });
});

// Initialize summary on page load
setTimeout(function() {
    updateMedicationSummary();
}, 100);
</script>

<?php include 'includes/footer.php'; ?>
