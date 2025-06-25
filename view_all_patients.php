<?php
require_once 'config/init.php';
include 'includes/header.php';

// Get database connection from global
$db = $GLOBALS['db'];

// Get all patients
try {
    $stmt = $db->prepare("SELECT * FROM patients ORDER BY name_english ASC");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Add count for troubleshooting
    $debug_count = count($patients);
    error_log("View All Patients: Found $debug_count patients");
    
} catch(PDOException $e) {
    $patients = [];
    $error_message = "Error fetching patients: " . $e->getMessage();
    error_log("View All Patients Error: " . $e->getMessage());
}
?>

<style>
/* Custom styles for better visibility */
.action-buttons .btn {
    margin: 0 2px;
}
.action-buttons .btn-group {
    margin: 0 2px;
}
/* Make dropdown menu appear on hover for better accessibility */
.action-buttons .dropdown:hover .dropdown-menu {
    display: block;
    margin-top: 0;
}
/* Larger click area for dropdown items */
.action-buttons .dropdown-item {
    padding: 0.5rem 1rem;
}
/* Better visual separation */
.action-buttons .dropdown-item:hover {
    background-color: #f8f9fa;
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>
                        <i class="fas fa-users me-2"></i>All Patients
                    </h4>
                    <div class="float-end">
                        <a href="patient_form.php" class="btn btn-primary me-2">
                            <i class="fas fa-plus me-2"></i>Add New Patient
                        </a>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($patients)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>File Number</th>
                                        <th>Name</th>
                                        <th>Age</th>
                                        <th>Gender</th>
                                        <th>Room</th>
                                        <th>Group</th>
                                        <th>Shift</th>
                                        <th>Blood Group</th>
                                        <th>Contact</th>
                                        <th>Created</th>
                                        <th style="width: 280px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($patients as $patient): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($patient['file_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($patient['name_english']); ?></td>
                                            <td><?php echo $patient['age']; ?> years</td>
                                            <td>
                                                <span class="badge bg-<?php echo $patient['gender'] === 'male' ? 'primary' : 'pink'; ?>">
                                                    <?php echo ucfirst($patient['gender']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $patient['room_number'] ?? 'N/A'; ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $patient['group_type']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo $patient['shift_type']; ?></span>
                                            </td>
                                            <td><?php echo $patient['blood_group'] ?? 'N/A'; ?></td>
                                            <td><?php echo htmlspecialchars($patient['contact_number']); ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($patient['created_at'])); ?></td>
                                            <td class="action-buttons">
                                                <!-- Option 1: All buttons visible (recommended for clarity) -->
                                                <div class="d-flex flex-wrap gap-1">
                                                    <a href="patient_report.php?id=<?php echo $patient['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" title="View Report">
                                                        <i class="fas fa-eye"></i> Report
                                                    </a>
                                                    <a href="patient_report.php?id=<?php echo $patient['id']; ?>&format=text" 
                                                       class="btn btn-sm btn-outline-info" title="Text Report" target="_blank">
                                                        <i class="fas fa-file-alt"></i> Text
                                                    </a>
                                                    <a href="patient_form.php?edit=<?php echo $patient['id']; ?>" 
                                                       class="btn btn-sm btn-outline-secondary" title="Edit Patient">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a href="laboratory_form.php?patient_id=<?php echo $patient['id']; ?>" 
                                                       class="btn btn-sm btn-outline-success" title="Lab Data">
                                                        <i class="fas fa-flask"></i> Lab
                                                    </a>
                                                </div>
                                                
                                                <!-- Option 2: Split button group (if you prefer a more compact approach) -->
                                                <!-- Uncomment below and comment out Option 1 if you prefer this style -->
                                                <!--
                                                <div class="btn-group" role="group">
                                                    <a href="patient_report.php?id=<?php echo $patient['id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye me-1"></i>View Report
                                                    </a>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split" 
                                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                            <span class="visually-hidden">Toggle Dropdown</span>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><a class="dropdown-item" href="patient_report.php?id=<?php echo $patient['id']; ?>&format=text" target="_blank">
                                                                <i class="fas fa-file-alt me-2"></i>Text Format
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="patient_report.php?id=<?php echo $patient['id']; ?>&format=pdf" target="_blank">
                                                                <i class="fas fa-file-pdf me-2"></i>PDF Format
                                                            </a></li>
                                                        </ul>
                                                    </div>
                                                    <a href="patient_form.php?edit=<?php echo $patient['id']; ?>" 
                                                       class="btn btn-sm btn-secondary ms-2" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="laboratory_form.php?patient_id=<?php echo $patient['id']; ?>" 
                                                       class="btn btn-sm btn-success" title="Lab Data">
                                                        <i class="fas fa-flask"></i>
                                                    </a>
                                                </div>
                                                -->
                                                
                                                <!-- Option 3: Icon buttons with tooltip (most compact) -->
                                                <!-- Uncomment below and comment out other options if you prefer this style -->
                                                <!--
                                                <div class="btn-toolbar" role="toolbar">
                                                    <div class="btn-group me-1" role="group">
                                                        <a href="patient_report.php?id=<?php echo $patient['id']; ?>" 
                                                           class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="View Report">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="patient_report.php?id=<?php echo $patient['id']; ?>&format=text" 
                                                           class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Text Report" target="_blank">
                                                            <i class="fas fa-file-alt"></i>
                                                        </a>
                                                    </div>
                                                    <div class="btn-group" role="group">
                                                        <a href="patient_form.php?edit=<?php echo $patient['id']; ?>" 
                                                           class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="Edit Patient">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="laboratory_form.php?patient_id=<?php echo $patient['id']; ?>" 
                                                           class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="Lab Data">
                                                            <i class="fas fa-flask"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                                -->
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            <p class="text-muted">
                                <i class="fas fa-info-circle me-2"></i>
                                Total: <?php echo count($patients); ?> patient<?php echo count($patients) !== 1 ? 's' : ''; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5>No patients found</h5>
                            <p class="text-muted">Start by adding your first patient to the system.</p>
                            <a href="patient_form.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add First Patient
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize tooltips if using Option 3
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
</script>

<?php include 'includes/footer.php'; ?>