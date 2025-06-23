<?php
require_once 'config/init.php';

// Security check - only allow this in development
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_clear'])) {
    try {
        $db = $GLOBALS['db'];
        
        // Start transaction
        $db->beginTransaction();
        
        // Get list of existing tables
        $existing_tables = [];
        $tables_stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        while ($row = $tables_stmt->fetch(PDO::FETCH_ASSOC)) {
            $existing_tables[] = $row['name'];
        }
        
        // List of tables to clear (in order to handle foreign key constraints)
        $tables_to_clear = [
            'quarterly_lab_data',
            'laboratory_data',
            'catheter_infections',
            'dialysis_complications',
            'medical_background',
            'hd_prescription',
            'medications',
            'vaccinations',
            'patients'
        ];
        
        $cleared_count = [];
        
        // Clear each table that exists
        foreach ($tables_to_clear as $table) {
            if (in_array($table, $existing_tables)) {
                // Get count before clearing
                $count_stmt = $db->query("SELECT COUNT(*) FROM $table");
                $count = $count_stmt->fetchColumn();
                $cleared_count[$table] = $count;
                
                // Clear the table
                $db->exec("DELETE FROM $table");
                
                // Reset auto-increment counter
                $db->exec("DELETE FROM sqlite_sequence WHERE name='$table'");
            } else {
                $cleared_count[$table] = 0; // Table doesn't exist
            }
        }
        
        // Commit transaction
        $db->commit();
        
        $success = true;
        $message = "Database cleared successfully!";
        
    } catch (Exception $e) {
        $db->rollback();
        $success = false;
        $message = "Error clearing database: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clear Database - Dialysis Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Clear Database</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <h5><i class="fas fa-check-circle me-2"></i><?php echo $message; ?></h5>
                                    <h6>Records cleared:</h6>
                                    <ul>
                                        <?php foreach ($cleared_count as $table => $count): ?>
                                            <li><?php echo ucfirst(str_replace('_', ' ', $table)); ?>: <?php echo $count; ?> records</li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <a href="dashboard.php" class="btn btn-primary mt-3">
                                        <i class="fas fa-home me-2"></i>Go to Dashboard
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    <h5><i class="fas fa-exclamation-circle me-2"></i><?php echo $message; ?></h5>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <h5><i class="fas fa-exclamation-triangle me-2"></i>Warning!</h5>
                                <p>This action will permanently delete ALL data from the following tables:</p>
                                <ul>
                                    <li><strong>Patients</strong> - All patient records and personal information</li>
                                    <li><strong>Laboratory Data</strong> - All lab test results</li>
                                    <li><strong>Quarterly Lab Data</strong> - All quarterly laboratory records</li>
                                    <li><strong>Catheter Infections</strong> - All infection records</li>
                                    <li><strong>Complications</strong> - All complication records</li>
                                    <li><strong>Medical Background</strong> - All medical history data</li>
                                    <li><strong>HD Prescriptions</strong> - All dialysis prescriptions</li>
                                    <li><strong>Medications</strong> - All medication records</li>
                                    <li><strong>Vaccinations</strong> - All vaccination records</li>
                                </ul>
                                <p class="text-danger"><strong>This action cannot be undone!</strong></p>
                            </div>
                            
                            <form method="POST" onsubmit="return confirmClear()">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="understand" required>
                                    <label class="form-check-label" for="understand">
                                        I understand that this will permanently delete all data
                                    </label>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="dashboard.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Cancel
                                    </a>
                                    <button type="submit" name="confirm_clear" class="btn btn-danger">
                                        <i class="fas fa-trash me-2"></i>Clear All Data
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmClear() {
            return confirm('Are you absolutely sure you want to delete ALL data? This action cannot be undone!');
        }
    </script>
</body>
</html>