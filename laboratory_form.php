<?php
require_once 'config/init.php';
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
    error_log("Laboratory form: Found " . count($patients) . " patients");
} catch(PDOException $e) {
    error_log("Laboratory form database error: " . $e->getMessage());
    $patients = [];
}

$selected_patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : '';
$laboratory_data = [];

// Get existing laboratory data if patient selected
if ($selected_patient_id) {
    try {
        $stmt = $db->prepare("SELECT * FROM laboratory_data WHERE patient_id = ? ORDER BY test_date DESC");
        $stmt->execute([$selected_patient_id]);
        $laboratory_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Laboratory form: Found " . count($laboratory_data) . " lab records for patient " . $selected_patient_id);
    } catch(PDOException $e) {
        error_log("Laboratory form lab data error: " . $e->getMessage());
        $laboratory_data = [];
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>
                        <i class="fas fa-flask me-2"></i>Laboratory Data Management
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
                            <select class="form-select" id="patientSelect" onchange="loadPatientData()" required>
                                <option value="">Choose a patient...</option>
                                <?php foreach ($patients as $patient): ?>
                                    <option value="<?php echo $patient['id']; ?>" 
                                            <?php echo ($selected_patient_id == $patient['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($patient['name_english']) . ' (' . htmlspecialchars($patient['file_number']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Test Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="testDate" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <?php if ($selected_patient_id): ?>
                    <!-- Laboratory Form -->
                    <form id="laboratoryForm" method="POST" action="api/save_laboratory.php">
                        <input type="hidden" name="patient_id" value="<?php echo $selected_patient_id; ?>">
                        <input type="hidden" name="test_date" id="hiddenTestDate">
                        
                        <!-- Anemia Profile Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-tint me-2"></i>Anemia Profile
                                </h5>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Hb (g/L)</label>
                                <input type="number" class="form-control" name="hb" id="hb" step="0.01">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">MCV</label>
                                <input type="number" class="form-control" name="mcv" step="0.01">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Iron</label>
                                <input type="number" class="form-control" name="iron" id="iron" step="0.01" onchange="calculateTSAT()">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">TIBC</label>
                                <input type="number" class="form-control" name="tibc" id="tibc" step="0.01" onchange="calculateTSAT()">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">TSAT (%)</label>
                                <input type="number" class="form-control" name="tsat" id="calculatedTSAT" step="0.01" readonly>
                                <div class="form-text">Formula: (Iron × 100) / TIBC</div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">S/Ferritin</label>
                                <input type="number" class="form-control" name="ferritin" step="0.01">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hb Change from Last Two Months (%)</label>
                                <input type="number" class="form-control" name="hb_change_percent" id="hbChange" step="0.01" readonly>
                                <div class="form-text">Automatically calculated from previous records</div>
                            </div>
                        </div>
                        
                        <!-- Mineral Bone Disease Section -->
                        <div class="row mb-4 mt-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-bone me-2"></i>Mineral Bone Disease
                                </h5>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">25-OH-D</label>
                                <input type="number" class="form-control" name="vitamin_d" step="0.01">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Calcium (mmol/L)</label>
                                <input type="number" class="form-control" name="calcium" id="calcium" step="0.001" onchange="calculateCorrectedCalcium()">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Phosphorus (mmol/L)</label>
                                <input type="number" class="form-control" name="phosphorus" id="phosphorus" step="0.001" onchange="calculateCaPhosProduct()">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Albumin (g/L)</label>
                                <input type="number" class="form-control" name="albumin" id="albumin" step="0.01" onchange="calculateCorrectedCalcium()">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">PTH</label>
                                <input type="number" class="form-control" name="pth" step="0.01">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Corrected Calcium (mmol/L)</label>
                                <input type="number" class="form-control" name="corrected_calcium" id="correctedCalcium" step="0.001" readonly>
                                <div class="form-text">Formula: Total Ca + 0.02 × [40 - Albumin]</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Ca × Phos Product (mg²/dl²)</label>
                                <input type="number" class="form-control" name="ca_phos_product" id="caPhosProduct" step="0.01" readonly>
                                <div class="form-text">Formula: Corrected Ca (mg/dl) × Phos (mg/dl)</div>
                            </div>
                        </div>
                        
                        <!-- Viral Serology Section -->
                        <div class="row mb-4 mt-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-virus me-2"></i>Viral Serology
                                </h5>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">HBsAg</label>
                                <select class="form-select" name="hbsag">
                                    <option value="">Select</option>
                                    <option value="+ve">+ve</option>
                                    <option value="-ve">-ve</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Anti HCV</label>
                                <select class="form-select" name="anti_hcv">
                                    <option value="">Select</option>
                                    <option value="+ve">+ve</option>
                                    <option value="-ve">-ve</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">HIV</label>
                                <select class="form-select" name="hiv">
                                    <option value="">Select</option>
                                    <option value="+ve">+ve</option>
                                    <option value="-ve">-ve</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">HBsAb</label>
                                <input type="number" class="form-control" name="hbsab" step="0.01" max="9999">
                                <div class="form-text">Up to 4 digits</div>
                            </div>
                        </div>
                        
                        <!-- Dialysis Adequacy Section -->
                        <div class="row mb-4 mt-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-chart-line me-2"></i>Dialysis Adequacy
                                </h5>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Pre-dialysis BUN</label>
                                <input type="number" class="form-control" name="pre_dialysis_bun" id="preBUN" step="0.01" onchange="calculateURR()">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Post-dialysis BUN</label>
                                <input type="number" class="form-control" name="post_dialysis_bun" id="postBUN" step="0.01" onchange="calculateURR()">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Dialysis Duration (hours)</label>
                                <input type="number" class="form-control" name="dialysis_duration" id="dialysisDuration" step="0.1" onchange="calculateKtV()">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Ultrafiltrate Volume (L)</label>
                                <input type="number" class="form-control" name="ultrafiltrate_volume" step="0.01">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Post-dialysis Weight (kg)</label>
                                <input type="number" class="form-control" name="post_dialysis_weight" id="postWeight" step="0.01" onchange="calculateKtV()">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">URR (%)</label>
                                <input type="number" class="form-control" name="urr" id="calculatedURR" step="0.01" readonly>
                                <div class="form-text">Formula: ((Pre BUN - Post BUN) / Pre BUN) × 100</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kt/V</label>
                                <input type="number" class="form-control" name="kt_v" id="calculatedKtV" step="0.01" readonly>
                                <div class="form-text">Calculated using standard formula</div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-lg me-2">
                                    <i class="fas fa-save me-2"></i>Save Laboratory Data
                                </button>
                                <button type="button" class="btn btn-info btn-lg me-2" onclick="viewCharts()">
                                    <i class="fas fa-chart-line me-2"></i>View Charts
                                </button>
                                <button type="button" class="btn btn-secondary btn-lg" onclick="clearForm()">
                                    <i class="fas fa-eraser me-2"></i>Clear Form
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Historical Data Table -->
                    <?php if (!empty($laboratory_data)): ?>
                    <div class="row mt-5">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-history me-2"></i>Historical Laboratory Data
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Date</th>
                                            <th>Hb</th>
                                            <th>Iron</th>
                                            <th>TSAT</th>
                                            <th>Ca</th>
                                            <th>Phos</th>
                                            <th>URR</th>
                                            <th>Kt/V</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($laboratory_data as $lab): ?>
                                        <tr>
                                            <td><?php echo format_date($lab['test_date']); ?></td>
                                            <td><?php echo $lab['hb'] ? number_format($lab['hb'], 1) : '-'; ?></td>
                                            <td><?php echo $lab['iron'] ? number_format($lab['iron'], 1) : '-'; ?></td>
                                            <td><?php echo $lab['tsat'] ? number_format($lab['tsat'], 1) . '%' : '-'; ?></td>
                                            <td><?php echo $lab['calcium'] ? number_format($lab['calcium'], 3) : '-'; ?></td>
                                            <td><?php echo $lab['phosphorus'] ? number_format($lab['phosphorus'], 3) : '-'; ?></td>
                                            <td><?php echo $lab['urr'] ? number_format($lab['urr'], 1) . '%' : '-'; ?></td>
                                            <td><?php echo $lab['kt_v'] ? number_format($lab['kt_v'], 2) : '-'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editLabData(<?php echo $lab['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteLabData(<?php echo $lab['id']; ?>)">
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
                    <?php endif; ?>
                    
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Modal -->
<div class="modal fade" id="chartsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Laboratory Data Trends</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <canvas id="hbChart"></canvas>
                    </div>
                    <div class="col-md-6">
                        <canvas id="ironChart"></canvas>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <canvas id="calciumChart"></canvas>
                    </div>
                    <div class="col-md-6">
                        <canvas id="adequacyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/calculations.js"></script>
<script>
let patientLabData = <?php echo !empty($laboratory_data) ? json_encode($laboratory_data) : '[]'; ?>;

function loadPatientData() {
    const patientId = document.getElementById('patientSelect').value;
    if (patientId) {
        window.location.href = 'laboratory_form.php?patient_id=' + patientId;
    }
}

// Update hidden test date when date changes
document.getElementById('testDate').addEventListener('change', function() {
    document.getElementById('hiddenTestDate').value = this.value;
});

// Initialize hidden test date with null checks
const hiddenTestDateElement = document.getElementById('hiddenTestDate');
const testDateElement = document.getElementById('testDate');
if (hiddenTestDateElement && testDateElement) {
    hiddenTestDateElement.value = testDateElement.value;
}

function calculateTSAT() {
    const ironElement = document.getElementById('iron');
    const tibcElement = document.getElementById('tibc');
    const tsatElement = document.getElementById('calculatedTSAT');
    
    if (!ironElement || !tibcElement || !tsatElement) return;
    
    const iron = parseFloat(ironElement.value) || 0;
    const tibc = parseFloat(tibcElement.value) || 0;
    
    if (iron > 0 && tibc > 0) {
        const tsat = (iron * 100) / tibc;
        tsatElement.value = tsat.toFixed(2);
    } else {
        tsatElement.value = '';
    }
}

function calculateCorrectedCalcium() {
    const calciumElement = document.getElementById('calcium');
    const albuminElement = document.getElementById('albumin');
    const correctedElement = document.getElementById('correctedCalcium');
    
    if (!calciumElement || !albuminElement || !correctedElement) return;
    
    const calcium = parseFloat(calciumElement.value) || 0;
    const albumin = parseFloat(albuminElement.value) || 0;
    
    if (calcium > 0 && albumin > 0) {
        const corrected = calcium + 0.02 * (40 - albumin);
        correctedElement.value = corrected.toFixed(3);
        calculateCaPhosProduct();
    } else {
        correctedElement.value = '';
    }
}

function calculateCaPhosProduct() {
    const correctedCaElement = document.getElementById('correctedCalcium');
    const phosphorusElement = document.getElementById('phosphorus');
    const productElement = document.getElementById('caPhosProduct');
    
    if (!correctedCaElement || !phosphorusElement || !productElement) return;
    
    const correctedCa = parseFloat(correctedCaElement.value) || 0;
    const phosphorus = parseFloat(phosphorusElement.value) || 0;
    
    if (correctedCa > 0 && phosphorus > 0) {
        // Convert mmol/L to mg/dl: Ca mmol/L × 4.008 = mg/dl, Phos mmol/L × 3.097 = mg/dl
        const caMgDl = correctedCa * 4.008;
        const phosMgDl = phosphorus * 3.097;
        const product = caMgDl * phosMgDl;
        productElement.value = product.toFixed(2);
    } else {
        productElement.value = '';
    }
}

function calculateURR() {
    const preBUNElement = document.getElementById('preBUN');
    const postBUNElement = document.getElementById('postBUN');
    const urrElement = document.getElementById('calculatedURR');
    
    if (!preBUNElement || !postBUNElement || !urrElement) return;
    
    const preBUN = parseFloat(preBUNElement.value) || 0;
    const postBUN = parseFloat(postBUNElement.value) || 0;
    
    if (preBUN > 0 && postBUN >= 0) {
        const urr = ((preBUN - postBUN) / preBUN) * 100;
        urrElement.value = urr.toFixed(2);
    } else {
        urrElement.value = '';
    }
}

function calculateKtV() {
    const preBUNElement = document.getElementById('preBUN');
    const postBUNElement = document.getElementById('postBUN');
    const durationElement = document.getElementById('dialysisDuration');
    const weightElement = document.getElementById('postWeight');
    const ktvElement = document.getElementById('calculatedKtV');
    
    if (!preBUNElement || !postBUNElement || !durationElement || !weightElement || !ktvElement) return;
    
    const preBUN = parseFloat(preBUNElement.value) || 0;
    const postBUN = parseFloat(postBUNElement.value) || 0;
    const duration = parseFloat(durationElement.value) || 0;
    const weight = parseFloat(weightElement.value) || 0;
    
    if (preBUN > 0 && postBUN > 0 && duration > 0 && weight > 0) {
        // Simplified Kt/V calculation using natural log
        const ktv = -Math.log(postBUN / preBUN) + (4 * (preBUN - postBUN)) / (preBUN * 100);
        ktvElement.value = ktv.toFixed(2);
    } else {
        ktvElement.value = '';
    }
}

function calculateHbChange() {
    const hbElement = document.getElementById('hb');
    const hbChangeElement = document.getElementById('hbChange');
    
    if (!hbElement || !hbChangeElement) return;
    
    const currentHb = parseFloat(hbElement.value) || 0;
    
    if (currentHb > 0 && typeof patientLabData !== 'undefined' && patientLabData.length > 1) {
        // Find previous Hb values from last two months
        const twoMonthsAgo = new Date();
        twoMonthsAgo.setMonth(twoMonthsAgo.getMonth() - 2);
        
        let previousHb = null;
        for (let i = 1; i < patientLabData.length; i++) {
            const testDate = new Date(patientLabData[i].test_date);
            if (testDate >= twoMonthsAgo && patientLabData[i].hb) {
                previousHb = parseFloat(patientLabData[i].hb);
                break;
            }
        }
        
        if (previousHb) {
            const changePercent = ((currentHb - previousHb) / previousHb) * 100;
            hbChangeElement.value = changePercent.toFixed(2);
        }
    }
}

// Calculate Hb change when Hb value changes - with null check
const hbElement = document.getElementById('hb');
if (hbElement) {
    hbElement.addEventListener('change', calculateHbChange);
}

function clearForm() {
    const formElement = document.getElementById('laboratoryForm');
    const testDateElement = document.getElementById('testDate');
    const hiddenTestDateElement = document.getElementById('hiddenTestDate');
    
    if (formElement) {
        formElement.reset();
    }
    
    if (testDateElement) {
        testDateElement.value = '<?php echo date('Y-m-d'); ?>';
    }
    
    if (hiddenTestDateElement && testDateElement) {
        hiddenTestDateElement.value = testDateElement.value;
    }
}

function viewCharts() {
    if (patientLabData.length === 0) {
        alert('No laboratory data available for charts.');
        return;
    }
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('chartsModal'));
    modal.show();
    
    // Create charts after modal is shown
    setTimeout(createCharts, 300);
}

function createCharts() {
    const dates = patientLabData.map(item => item.test_date).reverse();
    
    // Hemoglobin Chart
    const hbData = patientLabData.map(item => item.hb).reverse();
    new Chart(document.getElementById('hbChart'), {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Hemoglobin (g/L)',
                data: hbData,
                borderColor: 'rgb(220, 53, 69)',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Hemoglobin Trend'
                }
            }
        }
    });
    
    // Iron Studies Chart
    const ironData = patientLabData.map(item => item.iron).reverse();
    const tsatData = patientLabData.map(item => item.tsat).reverse();
    new Chart(document.getElementById('ironChart'), {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Iron',
                data: ironData,
                borderColor: 'rgb(13, 110, 253)',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                yAxisID: 'y'
            }, {
                label: 'TSAT (%)',
                data: tsatData,
                borderColor: 'rgb(25, 135, 84)',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Iron Studies'
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
    
    // Calcium/Phosphorus Chart
    const calciumData = patientLabData.map(item => item.calcium).reverse();
    const phosphorusData = patientLabData.map(item => item.phosphorus).reverse();
    new Chart(document.getElementById('calciumChart'), {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Calcium (mmol/L)',
                data: calciumData,
                borderColor: 'rgb(255, 193, 7)',
                backgroundColor: 'rgba(255, 193, 7, 0.1)'
            }, {
                label: 'Phosphorus (mmol/L)',
                data: phosphorusData,
                borderColor: 'rgb(111, 66, 193)',
                backgroundColor: 'rgba(111, 66, 193, 0.1)'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Mineral Bone Disease'
                }
            }
        }
    });
    
    // Dialysis Adequacy Chart
    const urrData = patientLabData.map(item => item.urr).reverse();
    const ktvData = patientLabData.map(item => item.kt_v).reverse();
    new Chart(document.getElementById('adequacyChart'), {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'URR (%)',
                data: urrData,
                borderColor: 'rgb(20, 164, 77)',
                backgroundColor: 'rgba(20, 164, 77, 0.1)',
                yAxisID: 'y'
            }, {
                label: 'Kt/V',
                data: ktvData,
                borderColor: 'rgb(253, 126, 20)',
                backgroundColor: 'rgba(253, 126, 20, 0.1)',
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Dialysis Adequacy'
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
}

// Form submission - wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    const laboratoryForm = document.getElementById('laboratoryForm');
    if (laboratoryForm) {
        let isSubmitting = false;
        
        laboratoryForm.addEventListener('submit', function(e) {
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
            
            fetch('api/save_laboratory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Laboratory data saved successfully!');
                    // Clear unsaved changes flag before redirect
                    if (typeof FormManager !== 'undefined') {
                        FormManager.unsavedChanges = false;
                    }
                    window.onbeforeunload = null;
                    // Redirect to same page with patient ID to refresh data
                    const patientId = '<?php echo $selected_patient_id; ?>';
                    window.location.href = 'laboratory_form.php?patient_id=' + patientId;
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the laboratory data.');
            })
            .finally(() => {
                isSubmitting = false;
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-save me-2"></i>Save Laboratory Data';
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

function editLabData(id) {
    // Implementation for editing lab data
    alert('Edit functionality to be implemented');
}

function deleteLabData(id) {
    if (confirm('Are you sure you want to delete this laboratory record?')) {
        fetch('api/delete_laboratory.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({id: id})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Laboratory record deleted successfully!');
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
</script>

<?php include 'includes/footer.php'; ?>
