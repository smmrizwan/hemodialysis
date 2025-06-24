<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Dialysis Patient Management System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-heartbeat me-2"></i>
                Dialysis Management System
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="patientsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users me-1"></i>Patients
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="patient_form.php">
                                <i class="fas fa-user-plus me-2"></i>Add Patient
                            </a></li>
                            <li><a class="dropdown-item" href="view_all_patients.php">
                                <i class="fas fa-list me-2"></i>View All Patients
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="formsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-clipboard me-1"></i>Forms
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="laboratory_form.php">
                                <i class="fas fa-flask me-2"></i>Laboratory Data
                            </a></li>
                            <li><a class="dropdown-item" href="quarterly_lab_form.php">
                                <i class="fas fa-chart-line me-2"></i>Quarterly Lab Data
                            </a></li>
                            <li><a class="dropdown-item" href="catheter_infection_form.php">
                                <i class="fas fa-virus me-2"></i>Catheter Infections
                            </a></li>
                            <li><a class="dropdown-item" href="complications_form.php">
                                <i class="fas fa-exclamation-circle me-2"></i>Complications
                            </a></li>
                            <li><a class="dropdown-item" href="medical_background_form.php">
                                <i class="fas fa-history me-2"></i>Medical Background
                            </a></li>
                            <li><a class="dropdown-item" href="prescription_form.php">
                                <i class="fas fa-prescription me-2"></i>HD Prescription
                            </a></li>
                            <li><a class="dropdown-item" href="vaccination_form.php">
                                <i class="fas fa-syringe me-2"></i>Vaccinations
                            </a></li>
                            <li><a class="dropdown-item" href="medications_form.php">
                                <i class="fas fa-pills me-2"></i>Medications
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="searchPatients()">
                            <i class="fas fa-search me-1"></i>Search
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="navbar-text">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo date('d M Y'); ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main class="container-fluid mt-4">
