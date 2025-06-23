<?php
require_once 'config/init.php';
require_once 'vendor/autoload.php'; // For TCPDF if using Composer, otherwise include TCPDF manually

// Manual TCPDF include if not using Composer
if (!class_exists('TCPDF')) {
    // Download TCPDF and include it
    // For now, we'll create a simple HTML to PDF solution
}

if (!isset($_GET['patient_id']) || empty($_GET['patient_id'])) {
    die("Patient ID required");
}

$patient_id = (int)$_GET['patient_id'];

// Get patient data
try {
    $stmt = $db->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        die("Patient not found");
    }
} catch(PDOException $e) {
    die("Error fetching patient data: " . $e->getMessage());
}

// Get all related data
$laboratory_data = [];
$catheter_infections = [];
$complications = [];
$medical_background = [];
$prescription = [];
$vaccinations = [];
$medications = [];

try {
    // Laboratory data
    $stmt = $db->prepare("SELECT * FROM laboratory_data WHERE patient_id = ? ORDER BY test_date DESC LIMIT 10");
    $stmt->execute([$patient_id]);
    $laboratory_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Catheter infections
    $stmt = $db->prepare("SELECT * FROM catheter_infections WHERE patient_id = ? ORDER BY infection_date DESC");
    $stmt->execute([$patient_id]);
    $catheter_infections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Complications
    $stmt = $db->prepare("SELECT * FROM dialysis_complications WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $complications = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Medical background
    $stmt = $db->prepare("SELECT * FROM medical_background WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $medical_background = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Prescription
    $stmt = $db->prepare("SELECT * FROM hd_prescription WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $prescription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Vaccinations
    $stmt = $db->prepare("SELECT * FROM vaccinations WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $vaccinations = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Medications
    $stmt = $db->prepare("SELECT * FROM medications WHERE patient_id = ? AND medication_name IS NOT NULL AND medication_name != '' ORDER BY row_order ASC");
    $stmt->execute([$patient_id]);
    $medications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    // Continue with empty data sets
}

// Set headers for PDF download
header('Content-Type: text/html; charset=utf-8');

// Since TCPDF might not be available, we'll create an HTML version that can be printed as PDF
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Medical Report - <?php echo htmlspecialchars($patient['name_english']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: #2c5f5d;
            font-size: 24px;
        }
        .header h2 {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 16px;
        }
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .section-title {
            background-color: #2c5f5d;
            color: white;
            padding: 8px 12px;
            margin-bottom: 15px;
            font-weight: bold;
            font-size: 14px;
        }
        .patient-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .patient-info-left, .patient-info-right {
            width: 48%;
        }
        .info-row {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 11px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 6px;
        }
        .medications-table {
            font-size: 10px;
        }
        .medications-table td {
            padding: 4px;
        }
        .print-date {
            text-align: right;
            font-style: italic;
            color: #666;
            margin-bottom: 20px;
        }
        @media print {
            body {
                margin: 0;
                font-size: 11px;
            }
            .section {
                page-break-inside: avoid;
            }
            .no-print {
                display: none;
            }
        }
        .two-column {
            display: flex;
            justify-content: space-between;
        }
        .column {
            width: 48%;
        }
        .complications-list {
            list-style: none;
            padding: 0;
        }
        .complications-list li {
            margin-bottom: 5px;
        }
        .yes {
            color: #d32f2f;
            font-weight: bold;
        }
        .no {
            color: #388e3c;
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; background: #2c5f5d; color: white; border: none; border-radius: 5px; cursor: pointer;">Print Report</button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px; background: #666; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">Close</button>
    </div>

    <div class="header">
        <h1>DIALYSIS PATIENT MANAGEMENT SYSTEM</h1>
        <h2>COMPREHENSIVE MEDICAL REPORT</h2>
    </div>

    <div class="print-date">
        Report Generated: <?php echo date('d-m-Y H:i:s'); ?>
    </div>

    <!-- PATIENT IDENTIFICATION -->
    <div class="section">
        <div class="section-title"># PATIENT IDENTIFICATION:</div>
        <div class="patient-info">
            <div class="patient-info-left">
                <div class="info-row">
                    <span class="info-label">File Number:</span>
                    <?php echo htmlspecialchars($patient['file_number']); ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Name (English):</span>
                    <?php echo htmlspecialchars($patient['name_english']); ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Date of Birth:</span>
                    <?php echo format_date($patient['date_of_birth']); ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Age:</span>
                    <?php echo $patient['age']; ?> years
                </div>
                <div class="info-row">
                    <span class="info-label">Gender:</span>
                    <?php echo ucfirst($patient['gender']); ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Contact Number:</span>
                    <?php echo htmlspecialchars($patient['contact_number']); ?>
                </div>
            </div>
            <div class="patient-info-right">
                <div class="info-row">
                    <span class="info-label">Room Number:</span>
                    <?php echo $patient['room_number']; ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Group:</span>
                    <?php echo $patient['group_type']; ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Shift:</span>
                    <?php echo $patient['shift_type']; ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Height:</span>
                    <?php echo $patient['height_cm'] ? $patient['height_cm'] . ' cm' : 'Not recorded'; ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Weight:</span>
                    <?php echo $patient['weight_kg'] ? $patient['weight_kg'] . ' kg' : 'Not recorded'; ?>
                </div>
                <div class="info-row">
                    <span class="info-label">BMI:</span>
                    <?php echo $patient['bmi'] ? $patient['bmi'] : 'Not calculated'; ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Blood Group:</span>
                    <?php echo $patient['blood_group'] ?: 'Not recorded'; ?>
                </div>
            </div>
        </div>
        
        <?php if ($patient['dialysis_initiation_date']): ?>
        <div style="margin-top: 15px;">
            <div class="info-row">
                <span class="info-label">HD Initiation:</span>
                <?php echo format_date($patient['dialysis_initiation_date']); ?>
            </div>
            <div class="info-row">
                <span class="info-label">HD Duration:</span>
                <?php echo $patient['dialysis_years']; ?> years, <?php echo $patient['dialysis_months']; ?> months total
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($medical_background): ?>
    <!-- MEDICAL BACKGROUND -->
    <div class="section">
        <div class="section-title"># MEDICAL BACKGROUND:</div>
        <div class="two-column">
            <div class="column">
                <strong>Previous Renal Replacement Therapy:</strong>
                <ul>
                    <li><strong>Peritoneal Dialysis:</strong> <?php echo $medical_background['history_pd'] ? 'Yes' : 'No'; ?>
                        <?php if ($medical_background['history_pd'] && ($medical_background['pd_start_date'] || $medical_background['pd_end_date'])): ?>
                            (<?php echo format_date($medical_background['pd_start_date']); ?> to <?php echo format_date($medical_background['pd_end_date']); ?>)
                        <?php endif; ?>
                    </li>
                    <li><strong>Renal Transplant:</strong> <?php echo $medical_background['history_transplant'] ? 'Yes' : 'No'; ?>
                        <?php if ($medical_background['history_transplant'] && ($medical_background['transplant_start_date'] || $medical_background['transplant_end_date'])): ?>
                            (<?php echo format_date($medical_background['transplant_start_date']); ?> to <?php echo format_date($medical_background['transplant_end_date']); ?>)
                        <?php endif; ?>
                    </li>
                </ul>
                
                <strong>Medical Conditions:</strong>
                <ul>
                    <li><strong>Diabetes Mellitus:</strong> <?php echo $medical_background['history_dm'] ? 'Yes' : 'No'; ?>
                        <?php if ($medical_background['history_dm'] && $medical_background['dm_duration_years']): ?>
                            (<?php echo $medical_background['dm_duration_years']; ?> years)
                        <?php endif; ?>
                    </li>
                    <li><strong>Hypertension:</strong> <?php echo $medical_background['history_htn'] ? 'Yes' : 'No'; ?>
                        <?php if ($medical_background['history_htn'] && $medical_background['htn_duration_years']): ?>
                            (<?php echo $medical_background['htn_duration_years']; ?> years)
                        <?php endif; ?>
                    </li>
                    <li><strong>Cardiac Problems:</strong> <?php echo $medical_background['history_cardiac'] ? 'Yes' : 'No'; ?>
                        <?php if ($medical_background['history_cardiac'] && $medical_background['cardiac_duration_years']): ?>
                            (<?php echo $medical_background['cardiac_duration_years']; ?> years)
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
            <div class="column">
                <strong>Primary Cause of CKD-V:</strong>
                <p><?php echo $medical_background['primary_ckd_cause'] ? htmlspecialchars($medical_background['primary_ckd_cause']) : 'Not specified'; ?></p>
                
                <strong>Residual Renal Function:</strong>
                <p><?php echo $medical_background['residual_renal_function'] ? 'Yes' : 'No'; ?>
                    <?php if ($medical_background['residual_renal_function'] && $medical_background['residual_urine_ml']): ?>
                        (<?php echo $medical_background['residual_urine_ml']; ?> ml/day)
                    <?php endif; ?>
                </p>
                
                <?php if ($medical_background['last_us_date']): ?>
                <strong>Last Abdominal US:</strong>
                <p><?php echo format_date($medical_background['last_us_date']); ?></p>
                <?php if ($medical_background['last_us_findings']): ?>
                    <p><em><?php echo htmlspecialchars($medical_background['last_us_findings']); ?></em></p>
                <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($medical_background['last_echo_date']): ?>
                <strong>Last Echocardiography:</strong>
                <p><?php echo format_date($medical_background['last_echo_date']); ?></p>
                <?php if ($medical_background['last_echo_findings']): ?>
                    <p><em><?php echo htmlspecialchars($medical_background['last_echo_findings']); ?></em></p>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($catheter_infections)): ?>
    <!-- CATHETER INFECTIONS -->
    <div class="section">
        <div class="section-title"># HISTORY OF CATHETER RELATED INFECTION:</div>
        <?php foreach ($catheter_infections as $index => $infection): ?>
            <p><strong>Episode <?php echo $index + 1; ?>:</strong> 
               Type: Catheter-related infection | 
               Diagnosis date: <?php echo format_date($infection['infection_date']); ?> | 
               Organism: <?php echo $infection['organism'] ? htmlspecialchars($infection['organism']) : 'Not specified'; ?>
            </p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($complications): ?>
    <!-- DIALYSIS COMPLICATIONS -->
    <div class="section">
        <div class="section-title"># DIALYSIS COMPLICATIONS:</div>
        <div class="two-column">
            <div class="column">
                <ul class="complications-list">
                    <li><strong>Hypotension:</strong> <span class="<?php echo $complications['hypotension'] ? 'yes' : 'no'; ?>"><?php echo $complications['hypotension'] ? 'Yes' : 'No'; ?></span></li>
                    <li><strong>Hypertension:</strong> <span class="<?php echo $complications['hypertension'] ? 'yes' : 'no'; ?>"><?php echo $complications['hypertension'] ? 'Yes' : 'No'; ?></span></li>
                    <li><strong>Muscle cramps:</strong> <span class="<?php echo $complications['muscle_cramps'] ? 'yes' : 'no'; ?>"><?php echo $complications['muscle_cramps'] ? 'Yes' : 'No'; ?></span></li>
                    <li><strong>Nausea and vomiting:</strong> <span class="<?php echo $complications['nausea_vomiting'] ? 'yes' : 'no'; ?>"><?php echo $complications['nausea_vomiting'] ? 'Yes' : 'No'; ?></span></li>
                    <li><strong>Headache:</strong> <span class="<?php echo $complications['headache'] ? 'yes' : 'no'; ?>"><?php echo $complications['headache'] ? 'Yes' : 'No'; ?></span></li>
                    <li><strong>Chest pain (cardiac):</strong> <span class="<?php echo $complications['chest_pain'] ? 'yes' : 'no'; ?>"><?php echo $complications['chest_pain'] ? 'Yes' : 'No'; ?></span></li>
                </ul>
            </div>
            <div class="column">
                <ul class="complications-list">
                    <li><strong>Pruritus:</strong> <span class="<?php echo $complications['pruritus'] ? 'yes' : 'no'; ?>"><?php echo $complications['pruritus'] ? 'Yes' : 'No'; ?></span></li>
                    <li><strong>Fever and chills:</strong> <span class="<?php echo $complications['fever_chills'] ? 'yes' : 'no'; ?>"><?php echo $complications['fever_chills'] ? 'Yes' : 'No'; ?></span></li>
                    <li><strong>Dyspnea:</strong> <span class="<?php echo $complications['dyspnea'] ? 'yes' : 'no'; ?>"><?php echo $complications['dyspnea'] ? 'Yes' : 'No'; ?></span></li>
                    <li><strong>Seizures:</strong> <span class="<?php echo $complications['seizures'] ? 'yes' : 'no'; ?>"><?php echo $complications['seizures'] ? 'Yes' : 'No'; ?></span></li>
                    <li><strong>Arrhythmias (palpitations):</strong> <span class="<?php echo $complications['arrhythmias'] ? 'yes' : 'no'; ?>"><?php echo $complications['arrhythmias'] ? 'Yes' : 'No'; ?></span></li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- AVERAGE BLOOD PRESSURE -->
    <div class="section">
        <div class="section-title"># AVERAGE BLOOD PRESSURE:</div>
        <div class="two-column">
            <div class="column">
                <strong>Pre-dialysis:</strong>
                <div class="info-row">Systolic: <?php echo $patient['pre_systolic_bp'] ?: 'Not recorded'; ?> mmHg</div>
                <div class="info-row">Diastolic: <?php echo $patient['pre_diastolic_bp'] ?: 'Not recorded'; ?> mmHg</div>
                <div class="info-row">MAP: <?php echo $patient['pre_map'] ?: 'Not calculated'; ?> mmHg</div>
            </div>
            <div class="column">
                <strong>Post-dialysis:</strong>
                <div class="info-row">Systolic: <?php echo $patient['post_systolic_bp'] ?: 'Not recorded'; ?> mmHg</div>
                <div class="info-row">Diastolic: <?php echo $patient['post_diastolic_bp'] ?: 'Not recorded'; ?> mmHg</div>
                <div class="info-row">MAP: <?php echo $patient['post_map'] ?: 'Not calculated'; ?> mmHg</div>
            </div>
        </div>
        <div class="two-column" style="margin-top: 10px;">
            <div class="column">
                <div class="info-row"><strong>Pulse:</strong> <?php echo $patient['pulse_rate'] ? $patient['pulse_rate'] . ' /min' : 'Not recorded'; ?></div>
            </div>
            <div class="column">
                <div class="info-row"><strong>Temperature:</strong> <?php echo $patient['temperature'] ? $patient['temperature'] . '°C' : 'Not recorded'; ?></div>
            </div>
        </div>
        <div style="margin-top: 10px;">
            <strong>Vascular Access:</strong>
            <?php if ($patient['av_fistula']): ?>AV Fistula <?php endif; ?>
            <?php if ($patient['catheter']): ?>Catheter <?php endif; ?>
            <?php if (!$patient['av_fistula'] && !$patient['catheter']): ?>Not specified<?php endif; ?>
        </div>
    </div>

    <?php if (!empty($laboratory_data)): ?>
    <!-- ANEMIA PROFILE -->
    <div class="section">
        <div class="section-title"># ANEMIA PROFILE:</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Hb (g/L)</th>
                    <th>MCV</th>
                    <th>Iron</th>
                    <th>TIBC</th>
                    <th>TSAT (%)</th>
                    <th>Ferritin</th>
                    <th>Hb Change (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($laboratory_data, 0, 5) as $lab): ?>
                <tr>
                    <td><?php echo format_date($lab['test_date']); ?></td>
                    <td><?php echo $lab['hb'] ? number_format($lab['hb'], 1) : '-'; ?></td>
                    <td><?php echo $lab['mcv'] ? number_format($lab['mcv'], 1) : '-'; ?></td>
                    <td><?php echo $lab['iron'] ? number_format($lab['iron'], 1) : '-'; ?></td>
                    <td><?php echo $lab['tibc'] ? number_format($lab['tibc'], 1) : '-'; ?></td>
                    <td><?php echo $lab['tsat'] ? number_format($lab['tsat'], 1) : '-'; ?></td>
                    <td><?php echo $lab['ferritin'] ? number_format($lab['ferritin'], 1) : '-'; ?></td>
                    <td><?php echo $lab['hb_change_percent'] ? number_format($lab['hb_change_percent'], 1) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- MINERAL BONE DISEASE -->
    <div class="section">
        <div class="section-title"># MINERAL BONE DISEASE (MBD):</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>25-OH-D</th>
                    <th>Calcium (mmol/L)</th>
                    <th>Phosphorus (mmol/L)</th>
                    <th>Albumin (g/L)</th>
                    <th>PTH</th>
                    <th>Corrected Ca</th>
                    <th>Ca×Phos Product</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($laboratory_data, 0, 5) as $lab): ?>
                <tr>
                    <td><?php echo format_date($lab['test_date']); ?></td>
                    <td><?php echo $lab['vitamin_d'] ? number_format($lab['vitamin_d'], 1) : '-'; ?></td>
                    <td><?php echo $lab['calcium'] ? number_format($lab['calcium'], 3) : '-'; ?></td>
                    <td><?php echo $lab['phosphorus'] ? number_format($lab['phosphorus'], 3) : '-'; ?></td>
                    <td><?php echo $lab['albumin'] ? number_format($lab['albumin'], 1) : '-'; ?></td>
                    <td><?php echo $lab['pth'] ? number_format($lab['pth'], 1) : '-'; ?></td>
                    <td><?php echo $lab['corrected_calcium'] ? number_format($lab['corrected_calcium'], 3) : '-'; ?></td>
                    <td><?php echo $lab['ca_phos_product'] ? number_format($lab['ca_phos_product'], 1) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- VIRAL SEROLOGY -->
    <div class="section">
        <div class="section-title"># VIRAL SEROLOGY:</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>HBsAg</th>
                    <th>Anti HCV</th>
                    <th>HIV</th>
                    <th>HBsAb</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($laboratory_data, 0, 5) as $lab): ?>
                <tr>
                    <td><?php echo format_date($lab['test_date']); ?></td>
                    <td><?php echo $lab['hbsag'] ?: '-'; ?></td>
                    <td><?php echo $lab['anti_hcv'] ?: '-'; ?></td>
                    <td><?php echo $lab['hiv'] ?: '-'; ?></td>
                    <td><?php echo $lab['hbsab'] ? number_format($lab['hbsab'], 1) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($prescription): ?>
    <!-- CURRENT HEMODIALYSIS PRESCRIPTION -->
    <div class="section">
        <div class="section-title"># CURRENT HEMODIALYSIS PRESCRIPTION:</div>
        <div class="two-column">
            <div class="column">
                <strong>Basic Settings:</strong>
                <div class="info-row">Dialysis modality: <?php echo htmlspecialchars($prescription['dialysis_modality']); ?></div>
                <div class="info-row">Dialyzer: <?php echo htmlspecialchars($prescription['dialyzer']); ?></div>
                <div class="info-row">Frequency: <?php echo htmlspecialchars($prescription['frequency']); ?></div>
                <div class="info-row">Duration: <?php echo $prescription['duration']; ?> hours</div>
                <div class="info-row">Current vascular access: <?php echo htmlspecialchars($prescription['vascular_access']); ?></div>
                
                <strong style="margin-top: 15px; display: block;">Anticoagulation:</strong>
                <div class="info-row">Heparin dose (initial): <?php echo $prescription['heparin_initial'] ? $prescription['heparin_initial'] . ' units' : 'Not specified'; ?></div>
                <div class="info-row">Heparin dose (maintenance): <?php echo $prescription['heparin_maintenance'] ? $prescription['heparin_maintenance'] . ' units/hr' : 'Not specified'; ?></div>
            </div>
            <div class="column">
                <strong>Flow Rates & Fluid Management:</strong>
                <div class="info-row">Blood Flow Rate: <?php echo $prescription['blood_flow_rate'] ? $prescription['blood_flow_rate'] . ' ml/min' : 'Not specified'; ?></div>
                <div class="info-row">Dialysate Flow Rate: <?php echo $prescription['dialysate_flow_rate'] ? $prescription['dialysate_flow_rate'] . ' ml/min' : 'Not specified'; ?></div>
                <div class="info-row">Dry Body Weight: <?php echo $prescription['dry_body_weight'] ? $prescription['dry_body_weight'] . ' kg' : 'Not specified'; ?></div>
                <div class="info-row">Ultrafiltration: <?php echo $prescription['ultrafiltration'] ? $prescription['ultrafiltration'] . ' ml/kg/hr' : 'Not specified'; ?></div>
                
                <strong style="margin-top: 15px; display: block;">Dialysate Composition:</strong>
                <div class="info-row">Na: <?php echo $prescription['sodium'] ? $prescription['sodium'] . ' mEq/L' : 'Not specified'; ?></div>
                <div class="info-row">K: <?php echo $prescription['potassium'] ? $prescription['potassium'] . ' mmol/L' : 'Not specified'; ?></div>
                <div class="info-row">Ca: <?php echo $prescription['calcium'] ? $prescription['calcium'] . ' mmol/L' : 'Not specified'; ?></div>
                <div class="info-row">HCO3: <?php echo $prescription['bicarbonate'] ? $prescription['bicarbonate'] . ' mmol/L' : 'Not specified'; ?></div>
                <div class="info-row">Catheter lock: <?php echo htmlspecialchars($prescription['catheter_lock'] ?: 'Not specified'); ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($medications)): ?>
    <!-- MEDICATIONS -->
    <div class="section">
        <div class="section-title"># MEDICATIONS:</div>
        <table class="medications-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 40%;">Medication Name</th>
                    <th style="width: 20%;">Dosage</th>
                    <th style="width: 20%;">Frequency</th>
                    <th style="width: 15%;">Route</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($medications as $index => $med): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($med['medication_name']); ?></td>
                    <td><?php echo htmlspecialchars($med['dosage']); ?></td>
                    <td><?php echo htmlspecialchars($med['frequency']); ?></td>
                    <td><?php echo htmlspecialchars($med['route']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($vaccinations): ?>
    <!-- VACCINATIONS -->
    <div class="section">
        <div class="section-title"># VACCINATIONS:</div>
        <div class="two-column">
            <div class="column">
                <strong>Hepatitis B vaccination:</strong>
                <div class="info-row">Completed: <?php echo $vaccinations['hepatitis_b_completed'] ? 'Yes' : 'No'; ?></div>
                <?php if ($vaccinations['hepatitis_b_completed'] && $vaccinations['hepatitis_b_date']): ?>
                    <div class="info-row">Date completed: <?php echo format_date($vaccinations['hepatitis_b_date']); ?></div>
                <?php endif; ?>
                <?php if (!$vaccinations['hepatitis_b_completed'] && $vaccinations['hepatitis_b_series']): ?>
                    <div class="info-row">Recommended series: <?php echo htmlspecialchars($vaccinations['hepatitis_b_series']); ?></div>
                <?php endif; ?>
                
                <strong style="margin-top: 15px; display: block;">Annual flu vaccine (Influenza):</strong>
                <div class="info-row">Completed: <?php echo $vaccinations['flu_vaccine_completed'] ? 'Yes' : 'No'; ?></div>
                <?php if ($vaccinations['flu_vaccine_completed'] && $vaccinations['flu_vaccine_date']): ?>
                    <div class="info-row">Date: <?php echo format_date($vaccinations['flu_vaccine_date']); ?></div>
                <?php endif; ?>
            </div>
            <div class="column">
                <strong>PPV23 vaccine (Pneumococcal conjugate 20-valent):</strong>
                <div class="info-row">Completed: <?php echo $vaccinations['ppv23_completed'] ? 'Yes' : 'No'; ?></div>
                <?php if ($vaccinations['ppv23_completed'] && $vaccinations['ppv23_date']): ?>
                    <div class="info-row">Date: <?php echo format_date($vaccinations['ppv23_date']); ?></div>
                <?php endif; ?>
                
                <strong style="margin-top: 15px; display: block;">Respiratory Syncytial virus vaccine:</strong>
                <div class="info-row">Completed: <?php echo $vaccinations['rsv_completed'] ? 'Yes' : 'No'; ?></div>
                <?php if ($vaccinations['rsv_completed'] && $vaccinations['rsv_date']): ?>
                    <div class="info-row">Date: <?php echo format_date($vaccinations['rsv_date']); ?></div>
                <?php endif; ?>
                <?php if (!$vaccinations['rsv_completed'] && $vaccinations['rsv_recommendation']): ?>
                    <div class="info-row">Recommendation: <?php echo htmlspecialchars($vaccinations['rsv_recommendation']); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div style="margin-top: 40px; text-align: center; font-style: italic; color: #666;">
        <p>--- End of Report ---</p>
        <p>Generated by Dialysis Patient Management System</p>
    </div>

    <script>
        // Auto-print when page loads if requested
        if (window.location.search.includes('autoprint=1')) {
            window.onload = function() {
                window.print();
            };
        }
    </script>
</body>
</html>
