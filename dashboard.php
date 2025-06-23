<?php
require_once 'config/init.php';
include 'includes/header.php';

// Get database connection from global
$db = $GLOBALS['db'];

// Get patient statistics
try {
    $stmt = $db->prepare("SELECT COUNT(*) as total_patients FROM patients");
    $stmt->execute();
    $total_patients = $stmt->fetch(PDO::FETCH_ASSOC)['total_patients'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as recent_labs FROM laboratory_data WHERE test_date >= date('now', '-30 days')");
    $stmt->execute();
    $recent_labs = $stmt->fetch(PDO::FETCH_ASSOC)['recent_labs'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as infections FROM catheter_infections WHERE infection_date >= date('now', '-90 days')");
    $stmt->execute();
    $recent_infections = $stmt->fetch(PDO::FETCH_ASSOC)['infections'];
} catch(PDOException $e) {
    $total_patients = 0;
    $recent_labs = 0;
    $recent_infections = 0;
}

// Get recent patients
try {
    $stmt = $db->prepare("SELECT id, file_number, name_english, created_at FROM patients ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recent_patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $recent_patients = [];
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Statistics Cards -->
        <div class="col-12">
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="card-title"><?php echo $total_patients; ?></h3>
                                    <p class="card-text">Total Patients</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="card-title"><?php echo $recent_labs; ?></h3>
                                    <p class="card-text">Recent Lab Tests</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-flask fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="card-title"><?php echo $recent_infections; ?></h3>
                                    <p class="card-text">Recent Infections</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="card-title">100%</h3>
                                    <p class="card-text">System Uptime</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-heartbeat fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Navigation -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-th-large me-2"></i>Patient Management</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="patient_form.php" class="btn btn-outline-primary btn-lg w-100 h-100 d-flex align-items-center justify-content-center text-decoration-none">
                                <div class="text-center">
                                    <i class="fas fa-user-plus fa-2x mb-2 d-block"></i>
                                    <span>Patient Registration</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="laboratory_form.php" class="btn btn-outline-success btn-lg w-100 h-100 d-flex align-items-center justify-content-center text-decoration-none">
                                <div class="text-center">
                                    <i class="fas fa-flask fa-2x mb-2 d-block"></i>
                                    <span>Laboratory Data</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="quarterly_lab_form.php" class="btn btn-outline-success btn-lg w-100 h-100 d-flex align-items-center justify-content-center text-decoration-none">
                                <div class="text-center">
                                    <i class="fas fa-chart-line fa-2x mb-2 d-block"></i>
                                    <span>Quarterly Lab Data</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="catheter_infection_form.php" class="btn btn-outline-warning btn-lg w-100 h-100 d-flex align-items-center justify-content-center text-decoration-none">
                                <div class="text-center">
                                    <i class="fas fa-virus fa-2x mb-2 d-block"></i>
                                    <span>Catheter Infections</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="complications_form.php" class="btn btn-outline-danger btn-lg w-100 h-100 d-flex align-items-center justify-content-center text-decoration-none">
                                <div class="text-center">
                                    <i class="fas fa-exclamation-circle fa-2x mb-2 d-block"></i>
                                    <span>Dialysis Complications</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="medical_background_form.php" class="btn btn-outline-info btn-lg w-100 h-100 d-flex align-items-center justify-content-center text-decoration-none">
                                <div class="text-center">
                                    <i class="fas fa-history fa-2x mb-2 d-block"></i>
                                    <span>Medical Background</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="prescription_form.php" class="btn btn-outline-secondary btn-lg w-100 h-100 d-flex align-items-center justify-content-center text-decoration-none">
                                <div class="text-center">
                                    <i class="fas fa-prescription fa-2x mb-2 d-block"></i>
                                    <span>HD Prescription</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="vaccination_form.php" class="btn btn-outline-primary btn-lg w-100 h-100 d-flex align-items-center justify-content-center text-decoration-none">
                                <div class="text-center">
                                    <i class="fas fa-syringe fa-2x mb-2 d-block"></i>
                                    <span>Vaccinations</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="medications_form.php" class="btn btn-outline-success btn-lg w-100 h-100 d-flex align-items-center justify-content-center text-decoration-none">
                                <div class="text-center">
                                    <i class="fas fa-pills fa-2x mb-2 d-block"></i>
                                    <span>Medications</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Patients -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-clock me-2"></i>Recent Patients</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_patients)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_patients as $patient): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($patient['name_english']); ?></h6>
                                        <small class="text-muted">File: <?php echo htmlspecialchars($patient['file_number']); ?></small>
                                    </div>
                                    <div class="btn-group" role="group">
                                        <a href="patient_report.php?id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Report">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="patient_form.php?edit=<?php echo $patient['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">No patients registered yet.</p>
                        <div class="text-center">
                            <a href="patient_form.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add First Patient
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h4><i class="fas fa-bolt me-2"></i>Quick Actions</h4>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="searchPatient()">
                            <i class="fas fa-search me-2"></i>Search Patient
                        </button>
                        <button class="btn btn-outline-success" onclick="generateReports()">
                            <i class="fas fa-chart-bar me-2"></i>Generate Reports
                        </button>
                        <button class="btn btn-outline-info" onclick="exportData()">
                            <i class="fas fa-download me-2"></i>Export Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search Modal -->
<div class="modal fade" id="searchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Search Patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Search by File Number or Name</label>
                    <input type="text" class="form-control" id="searchInput" placeholder="Enter file number or patient name">
                </div>
                <div id="searchResults"></div>
            </div>
        </div>
    </div>
</div>

<script>
function searchPatient() {
    document.getElementById('searchModal').classList.add('show');
    document.getElementById('searchModal').style.display = 'block';
    document.body.classList.add('modal-open');
    
    // Add backdrop
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    backdrop.id = 'searchBackdrop';
    document.body.appendChild(backdrop);
}

function generateReports() {
    // Redirect to patient list where users can access individual patient reports
    window.location.href = 'view_all_patients.php';
}

function exportData() {
    // Create export modal
    const modal = document.createElement('div');
    modal.className = 'modal fade show';
    modal.style.display = 'block';
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Data</h5>
                    <button type="button" class="btn-close" onclick="closeExportModal()"></button>
                </div>
                <div class="modal-body">
                    <p>Choose export format:</p>
                    <div class="d-grid gap-2">
                        <a href="export_data.php?type=excel" class="btn btn-success">
                            <i class="fas fa-file-excel me-2"></i>Export to Excel (CSV)
                        </a>
                        <a href="export_data.php?type=mysql" class="btn btn-primary">
                            <i class="fas fa-database me-2"></i>Export MySQL Dump
                        </a>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <strong>Excel:</strong> Downloads CSV file compatible with Excel/Google Sheets<br>
                            <strong>MySQL:</strong> Downloads SQL file for database import
                        </small>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    document.body.classList.add('modal-open');
    
    // Add backdrop
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    backdrop.id = 'exportBackdrop';
    backdrop.onclick = closeExportModal;
    document.body.appendChild(backdrop);
}

function closeExportModal() {
    const modal = document.querySelector('.modal.show');
    const backdrop = document.getElementById('exportBackdrop');
    
    if (modal) modal.remove();
    if (backdrop) backdrop.remove();
    document.body.classList.remove('modal-open');
}

// Close modal functionality
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-close') || e.target.classList.contains('modal-backdrop')) {
        const modal = document.getElementById('searchModal');
        const backdrop = document.getElementById('searchBackdrop');
        
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        
        if (backdrop) {
            backdrop.remove();
        }
    }
});

// Search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const query = this.value;
    if (query.length > 2) {
        // Implement search API call here
        fetch(`api/search_patients.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                const resultsDiv = document.getElementById('searchResults');
                if (data.length > 0) {
                    resultsDiv.innerHTML = data.map(patient => `
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6>${patient.name_english}</h6>
                                    <small>File: ${patient.file_number}</small>
                                </div>
                                <a href="patient_report.php?id=${patient.id}" class="btn btn-sm btn-primary">View</a>
                            </div>
                        </div>
                    `).join('');
                } else {
                    resultsDiv.innerHTML = '<p class="text-muted">No patients found</p>';
                }
            })
            .catch(error => {
                console.error('Search error:', error);
            });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
