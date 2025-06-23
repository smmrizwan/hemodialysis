<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dialysis Patient Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row min-vh-100">
            <div class="col-12">
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-heartbeat text-primary" style="font-size: 4rem;"></i>
                    </div>
                    <h1 class="display-4 mb-4">Dialysis Patient Management System</h1>
                    <p class="lead mb-5">Comprehensive patient data management for hemodialysis centers</p>
                    
                    <div class="row justify-content-center">
                        <div class="col-md-8 col-lg-6">
                            <div class="card shadow-lg">
                                <div class="card-body p-5">
                                    <h3 class="card-title mb-4">Get Started</h3>
                                    <p class="card-text mb-4">Access the dashboard to manage patient records, laboratory data, and generate reports.</p>
                                    <a href="dashboard.php" class="btn btn-primary btn-lg">
                                        <i class="fas fa-tachometer-alt me-2"></i>
                                        Go to Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-5">
                        <div class="col-md-4">
                            <div class="feature-card">
                                <i class="fas fa-user-md text-primary mb-3"></i>
                                <h5>Patient Management</h5>
                                <p>Complete patient profiles with medical history and demographics</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-card">
                                <i class="fas fa-chart-line text-success mb-3"></i>
                                <h5>Laboratory Tracking</h5>
                                <p>Monthly lab values with automatic calculations and trend analysis</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-card">
                                <i class="fas fa-file-pdf text-danger mb-3"></i>
                                <h5>Report Generation</h5>
                                <p>Comprehensive PDF reports for patient care documentation</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
