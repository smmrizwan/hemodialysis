<?php
require_once 'config/init.php';

// Helper functions - defined first
if (!function_exists('safe_value')) {
    function safe_value($value, $default = '-') {
        return !empty($value) ? htmlspecialchars($value) : $default;
    }
}

if (!function_exists('format_number')) {
    function format_number($value, $decimals = 1) {
        return !empty($value) ? number_format($value, $decimals) : '-';
    }
}

// Override format_date for report specific formatting
if (!function_exists('format_date')) {
    function format_date($date) {
        if (!$date) return '-';
        return date('d-m-Y', strtotime($date));
    }
}

// Get direct database connection
$db = new PDO("sqlite:" . __DIR__ . "/database/dialysis_management.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("PRAGMA foreign_keys = ON");

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$patient_id = (int)$_GET['id'];
$format = isset($_GET['format']) ? $_GET['format'] : 'html';

// Handle PDF generation
if ($format === 'pdf') {
    require_once 'vendor/autoload.php';
    
    // Get patient data first
    $stmt = $db->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        header("Location: dashboard.php");
        exit();
    }
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Dialysis Management System');
    $pdf->SetAuthor('Healthcare Provider');
    $pdf->SetTitle('Patient Report - ' . $patient['name_english']);
    $pdf->SetSubject('Comprehensive Patient Report');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'PATIENT COMPREHENSIVE REPORT', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Patient Identification
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Patient Identification:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    
    $pdf->Cell(90, 6, 'File Number: ' . safe_value($patient['file_number']), 0, 0, 'L');
    $pdf->Cell(90, 6, 'Name: ' . safe_value($patient['name_english']), 0, 1, 'L');
    $pdf->Cell(90, 6, 'Date of Birth: ' . format_date($patient['date_of_birth']), 0, 0, 'L');
    $pdf->Cell(90, 6, 'Age: ' . safe_value($patient['age']) . ' years', 0, 1, 'L');
    $pdf->Cell(90, 6, 'Gender: ' . safe_value($patient['gender']), 0, 0, 'L');
    $pdf->Cell(90, 6, 'Contact: ' . safe_value($patient['contact_number']), 0, 1, 'L');
    $pdf->Cell(90, 6, 'Room: ' . safe_value($patient['room_number']), 0, 0, 'L');
    $pdf->Cell(90, 6, 'Group: ' . safe_value($patient['group_type']), 0, 1, 'L');
    $pdf->Cell(90, 6, 'Shift: ' . safe_value($patient['shift_type']), 0, 0, 'L');
    $pdf->Cell(90, 6, 'Blood Group: ' . safe_value($patient['blood_group']), 0, 1, 'L');
    
    if ($patient['height_cm'] || $patient['weight_kg']) {
        $pdf->Cell(90, 6, 'Height: ' . safe_value($patient['height_cm']) . ' cm', 0, 0, 'L');
        $pdf->Cell(90, 6, 'Weight: ' . safe_value($patient['weight_kg']) . ' kg', 0, 1, 'L');
        if ($patient['bmi']) {
            $pdf->Cell(90, 6, 'BMI: ' . safe_value($patient['bmi']), 0, 0, 'L');
        }
        if ($patient['bsa']) {
            $pdf->Cell(90, 6, 'BSA: ' . safe_value($patient['bsa']) . ' m²', 0, 1, 'L');
        } else {
            $pdf->Ln(6);
        }
    }
    
    if ($patient['dialysis_initiation_date']) {
        $pdf->Cell(0, 6, 'Dialysis Initiation: ' . format_date($patient['dialysis_initiation_date']), 0, 1, 'L');
    }
    
    if ($patient['chronic_problems']) {
        $pdf->Ln(3);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'Chronic Problems:', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(0, 5, safe_value($patient['chronic_problems']), 0, 'L');
    }
    
    $pdf->Ln(5);
    
    // Get medical background
    $stmt = $db->prepare("SELECT * FROM medical_background WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$patient_id]);
    $medical_bg = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($medical_bg) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Medical Background:', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        
        if ($medical_bg['previous_dialysis']) {
            $pdf->Cell(0, 6, 'Previous Dialysis: Yes', 0, 1, 'L');
            if ($medical_bg['dialysis_duration']) {
                $pdf->Cell(0, 6, 'Duration: ' . safe_value($medical_bg['dialysis_duration']), 0, 1, 'L');
            }
        }
        
        if ($medical_bg['kidney_transplant']) {
            $pdf->Cell(0, 6, 'Kidney Transplant: Yes', 0, 1, 'L');
        }
        
        $pdf->Ln(3);
    }
    
    // Get laboratory data
    $stmt = $db->prepare("SELECT * FROM laboratory_data WHERE patient_id = ? ORDER BY test_date DESC LIMIT 3");
    $stmt->execute([$patient_id]);
    $lab_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($lab_data) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Recent Laboratory Results:', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        
        foreach ($lab_data as $lab) {
            $pdf->Cell(0, 6, 'Date: ' . format_date($lab['test_date']), 0, 1, 'L');
            if ($lab['hemoglobin']) $pdf->Cell(0, 5, 'Hemoglobin: ' . $lab['hemoglobin'] . ' g/dL', 0, 1, 'L');
            if ($lab['calcium']) $pdf->Cell(0, 5, 'Calcium: ' . $lab['calcium'] . ' mmol/L', 0, 1, 'L');
            if ($lab['phosphorus']) $pdf->Cell(0, 5, 'Phosphorus: ' . $lab['phosphorus'] . ' mmol/L', 0, 1, 'L');
            if ($lab['ferritin']) $pdf->Cell(0, 5, 'Ferritin: ' . $lab['ferritin'] . ' ng/mL', 0, 1, 'L');
            $pdf->Ln(2);
        }
    }
    
    // Get quarterly laboratory data
    $stmt = $db->prepare("SELECT * FROM quarterly_lab_data WHERE patient_id = ? ORDER BY quarter_year DESC LIMIT 2");
    $stmt->execute([$patient_id]);
    $quarterly_lab_pdf = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($quarterly_lab_pdf) {
        $pdf->Ln(3);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Quarterly Laboratory Data:', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        
        foreach ($quarterly_lab_pdf as $qlab) {
            $pdf->Cell(0, 6, 'Quarter/Year: ' . safe_value($qlab['quarter_year']), 0, 1, 'L');
            if ($qlab['hb_1'] || $qlab['hb_2'] || $qlab['hb_3']) {
                $pdf->Cell(0, 5, 'Hemoglobin (g/L): ' . safe_value($qlab['hb_1']) . ' / ' . safe_value($qlab['hb_2']) . ' / ' . safe_value($qlab['hb_3']), 0, 1, 'L');
            }
            if ($qlab['hb_diff_1_2']) {
                $pdf->Cell(0, 5, 'Hb Difference (%): ' . safe_value($qlab['hb_diff_1_2']), 0, 1, 'L');
            }
            if ($qlab['mcv_1'] || $qlab['mcv_2'] || $qlab['mcv_3']) {
                $pdf->Cell(0, 5, 'MCV (fL): ' . safe_value($qlab['mcv_1']) . ' / ' . safe_value($qlab['mcv_2']) . ' / ' . safe_value($qlab['mcv_3']), 0, 1, 'L');
            }
            if ($qlab['iron_1'] || $qlab['iron_2'] || $qlab['iron_3']) {
                $pdf->Cell(0, 5, 'Iron (µg/dL): ' . safe_value($qlab['iron_1']) . ' / ' . safe_value($qlab['iron_2']) . ' / ' . safe_value($qlab['iron_3']), 0, 1, 'L');
            }
            if ($qlab['tibc_1'] || $qlab['tibc_2'] || $qlab['tibc_3']) {
                $pdf->Cell(0, 5, 'TIBC (µg/dL): ' . safe_value($qlab['tibc_1']) . ' / ' . safe_value($qlab['tibc_2']) . ' / ' . safe_value($qlab['tibc_3']), 0, 1, 'L');
            }
            if ($qlab['tsat_1'] || $qlab['tsat_2'] || $qlab['tsat_3']) {
                $pdf->Cell(0, 5, 'TSAT (%): ' . safe_value($qlab['tsat_1']) . ' / ' . safe_value($qlab['tsat_2']) . ' / ' . safe_value($qlab['tsat_3']), 0, 1, 'L');
            }
            if ($qlab['ferritin_1'] || $qlab['ferritin_2'] || $qlab['ferritin_3']) {
                $pdf->Cell(0, 5, 'Ferritin (ng/mL): ' . safe_value($qlab['ferritin_1']) . ' / ' . safe_value($qlab['ferritin_2']) . ' / ' . safe_value($qlab['ferritin_3']), 0, 1, 'L');
            }
            if ($qlab['wbc_1'] || $qlab['wbc_2'] || $qlab['wbc_3']) {
                $pdf->Cell(0, 5, 'WBC (×10³/µL): ' . safe_value($qlab['wbc_1']) . ' / ' . safe_value($qlab['wbc_2']) . ' / ' . safe_value($qlab['wbc_3']), 0, 1, 'L');
            }
            if ($qlab['platelets_1'] || $qlab['platelets_2'] || $qlab['platelets_3']) {
                $pdf->Cell(0, 5, 'Platelets (×10³/µL): ' . safe_value($qlab['platelets_1']) . ' / ' . safe_value($qlab['platelets_2']) . ' / ' . safe_value($qlab['platelets_3']), 0, 1, 'L');
            }
            if ($qlab['calcium_1'] || $qlab['calcium_2'] || $qlab['calcium_3']) {
                $pdf->Cell(0, 5, 'Calcium (mmol/L): ' . safe_value($qlab['calcium_1']) . ' / ' . safe_value($qlab['calcium_2']) . ' / ' . safe_value($qlab['calcium_3']), 0, 1, 'L');
            }
            if ($qlab['phosphorus_1'] || $qlab['phosphorus_2'] || $qlab['phosphorus_3']) {
                $pdf->Cell(0, 5, 'Phosphorus (mmol/L): ' . safe_value($qlab['phosphorus_1']) . ' / ' . safe_value($qlab['phosphorus_2']) . ' / ' . safe_value($qlab['phosphorus_3']), 0, 1, 'L');
            }
            if ($qlab['albumin_1'] || $qlab['albumin_2'] || $qlab['albumin_3']) {
                $pdf->Cell(0, 5, 'Albumin (g/L): ' . safe_value($qlab['albumin_1']) . ' / ' . safe_value($qlab['albumin_2']) . ' / ' . safe_value($qlab['albumin_3']), 0, 1, 'L');
            }
            if ($qlab['corrected_calcium_1'] || $qlab['corrected_calcium_2'] || $qlab['corrected_calcium_3']) {
                $pdf->Cell(0, 5, 'Corrected Ca (mmol/L): ' . safe_value($qlab['corrected_calcium_1']) . ' / ' . safe_value($qlab['corrected_calcium_2']) . ' / ' . safe_value($qlab['corrected_calcium_3']), 0, 1, 'L');
            }
            if ($qlab['ca_phos_product_1'] || $qlab['ca_phos_product_2'] || $qlab['ca_phos_product_3']) {
                $pdf->Cell(0, 5, 'Ca×Phos Product (mg²/dL²): ' . safe_value($qlab['ca_phos_product_1']) . ' / ' . safe_value($qlab['ca_phos_product_2']) . ' / ' . safe_value($qlab['ca_phos_product_3']), 0, 1, 'L');
            }
            if ($qlab['pth_pmol_1'] || $qlab['pth_pmol_2'] || $qlab['pth_pmol_3']) {
                $pdf->Cell(0, 5, 'PTH (Pmol/L): ' . safe_value($qlab['pth_pmol_1']) . ' / ' . safe_value($qlab['pth_pmol_2']) . ' / ' . safe_value($qlab['pth_pmol_3']), 0, 1, 'L');
            }
            if ($qlab['vitamin_d_1'] || $qlab['vitamin_d_2'] || $qlab['vitamin_d_3']) {
                $pdf->Cell(0, 5, 'Vitamin D (nmol/L): ' . safe_value($qlab['vitamin_d_1']) . ' / ' . safe_value($qlab['vitamin_d_2']) . ' / ' . safe_value($qlab['vitamin_d_3']), 0, 1, 'L');
            }
            if ($qlab['sodium_1'] || $qlab['sodium_2'] || $qlab['sodium_3']) {
                $pdf->Cell(0, 5, 'Sodium (mmol/L): ' . safe_value($qlab['sodium_1']) . ' / ' . safe_value($qlab['sodium_2']) . ' / ' . safe_value($qlab['sodium_3']), 0, 1, 'L');
            }
            if ($qlab['potassium_1'] || $qlab['potassium_2'] || $qlab['potassium_3']) {
                $pdf->Cell(0, 5, 'Potassium (mmol/L): ' . safe_value($qlab['potassium_1']) . ' / ' . safe_value($qlab['potassium_2']) . ' / ' . safe_value($qlab['potassium_3']), 0, 1, 'L');
            }
            if ($qlab['uric_acid_1'] || $qlab['uric_acid_2'] || $qlab['uric_acid_3']) {
                $pdf->Cell(0, 5, 'Uric Acid (µmol/L): ' . safe_value($qlab['uric_acid_1']) . ' / ' . safe_value($qlab['uric_acid_2']) . ' / ' . safe_value($qlab['uric_acid_3']), 0, 1, 'L');
            }
            if ($qlab['creatinine_1'] || $qlab['creatinine_2'] || $qlab['creatinine_3']) {
                $pdf->Cell(0, 5, 'Creatinine (µmol/L): ' . safe_value($qlab['creatinine_1']) . ' / ' . safe_value($qlab['creatinine_2']) . ' / ' . safe_value($qlab['creatinine_3']), 0, 1, 'L');
            }
            if ($qlab['pre_dialysis_bun_1'] || $qlab['pre_dialysis_bun_2'] || $qlab['pre_dialysis_bun_3']) {
                $pdf->Cell(0, 5, 'Pre-dialysis BUN (mmol/L): ' . safe_value($qlab['pre_dialysis_bun_1']) . ' / ' . safe_value($qlab['pre_dialysis_bun_2']) . ' / ' . safe_value($qlab['pre_dialysis_bun_3']), 0, 1, 'L');
            }
            if ($qlab['post_dialysis_bun_1'] || $qlab['post_dialysis_bun_2'] || $qlab['post_dialysis_bun_3']) {
                $pdf->Cell(0, 5, 'Post-dialysis BUN (mmol/L): ' . safe_value($qlab['post_dialysis_bun_1']) . ' / ' . safe_value($qlab['post_dialysis_bun_2']) . ' / ' . safe_value($qlab['post_dialysis_bun_3']), 0, 1, 'L');
            }
            if ($qlab['dialysis_duration_1'] || $qlab['dialysis_duration_2'] || $qlab['dialysis_duration_3']) {
                $pdf->Cell(0, 5, 'Dialysis Duration (hours): ' . safe_value($qlab['dialysis_duration_1']) . ' / ' . safe_value($qlab['dialysis_duration_2']) . ' / ' . safe_value($qlab['dialysis_duration_3']), 0, 1, 'L');
            }
            if ($qlab['ultrafiltrate_volume_1'] || $qlab['ultrafiltrate_volume_2'] || $qlab['ultrafiltrate_volume_3']) {
                $pdf->Cell(0, 5, 'Ultrafiltrate Volume (L): ' . safe_value($qlab['ultrafiltrate_volume_1']) . ' / ' . safe_value($qlab['ultrafiltrate_volume_2']) . ' / ' . safe_value($qlab['ultrafiltrate_volume_3']), 0, 1, 'L');
            }
            if ($qlab['post_dialysis_weight_1'] || $qlab['post_dialysis_weight_2'] || $qlab['post_dialysis_weight_3']) {
                $pdf->Cell(0, 5, 'Post-dialysis Weight (kg): ' . safe_value($qlab['post_dialysis_weight_1']) . ' / ' . safe_value($qlab['post_dialysis_weight_2']) . ' / ' . safe_value($qlab['post_dialysis_weight_3']), 0, 1, 'L');
            }
            if ($qlab['urr_1'] || $qlab['urr_2'] || $qlab['urr_3']) {
                $pdf->Cell(0, 5, 'URR (%): ' . safe_value($qlab['urr_1']) . ' / ' . safe_value($qlab['urr_2']) . ' / ' . safe_value($qlab['urr_3']), 0, 1, 'L');
            }
            if ($qlab['kt_v_1'] || $qlab['kt_v_2'] || $qlab['kt_v_3']) {
                $pdf->Cell(0, 5, 'Kt/V: ' . safe_value($qlab['kt_v_1']) . ' / ' . safe_value($qlab['kt_v_2']) . ' / ' . safe_value($qlab['kt_v_3']), 0, 1, 'L');
            }
            if ($qlab['hbsag_1'] || $qlab['hbsag_2'] || $qlab['hbsag_3']) {
                $pdf->Cell(0, 5, 'HBsAg: ' . safe_value($qlab['hbsag_1']) . ' / ' . safe_value($qlab['hbsag_2']) . ' / ' . safe_value($qlab['hbsag_3']), 0, 1, 'L');
            }
            if ($qlab['anti_hcv_1'] || $qlab['anti_hcv_2'] || $qlab['anti_hcv_3']) {
                $pdf->Cell(0, 5, 'Anti-HCV: ' . safe_value($qlab['anti_hcv_1']) . ' / ' . safe_value($qlab['anti_hcv_2']) . ' / ' . safe_value($qlab['anti_hcv_3']), 0, 1, 'L');
            }
            if ($qlab['hiv_1'] || $qlab['hiv_2'] || $qlab['hiv_3']) {
                $pdf->Cell(0, 5, 'HIV: ' . safe_value($qlab['hiv_1']) . ' / ' . safe_value($qlab['hiv_2']) . ' / ' . safe_value($qlab['hiv_3']), 0, 1, 'L');
            }
            if ($qlab['hbsab_1'] || $qlab['hbsab_2'] || $qlab['hbsab_3']) {
                $pdf->Cell(0, 5, 'HBsAb (IU/L): ' . safe_value($qlab['hbsab_1']) . ' / ' . safe_value($qlab['hbsab_2']) . ' / ' . safe_value($qlab['hbsab_3']), 0, 1, 'L');
            }
            $pdf->Ln(2);
        }
    }
    
    // Get HD prescription
    $stmt = $db->prepare("SELECT * FROM hd_prescription WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$patient_id]);
    $prescription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($prescription) {
        $pdf->Ln(3);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'HD Prescription:', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        
        $pdf->Cell(90, 6, 'Duration: ' . safe_value($prescription['duration_hours']) . ' hours', 0, 0, 'L');
        $pdf->Cell(90, 6, 'Frequency: ' . safe_value($prescription['frequency_per_week']) . ' per week', 0, 1, 'L');
        $pdf->Cell(90, 6, 'Blood Flow: ' . safe_value($prescription['blood_flow_rate']) . ' mL/min', 0, 0, 'L');
        $pdf->Cell(90, 6, 'Dialysate Flow: ' . safe_value($prescription['dialysate_flow_rate']) . ' mL/min', 0, 1, 'L');
    }
    
    // Output PDF
    $filename = 'Patient_Report_' . $patient['file_number'] . '_' . date('Y-m-d') . '.pdf';
    $pdf->Output($filename, 'D');
    exit;
}

// Get patient data
try {
    $stmt = $db->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        header("Location: dashboard.php");
        exit();
    }
} catch(PDOException $e) {
    die("Error fetching patient data: " . $e->getMessage());
}

// Get all related data
$laboratory_data = [];
$quarterly_lab_data = [];
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
    
    // Quarterly laboratory data
    $stmt = $db->prepare("SELECT * FROM quarterly_lab_data WHERE patient_id = ? ORDER BY quarter_year DESC LIMIT 5");
    $stmt->execute([$patient_id]);
    $quarterly_lab_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Catheter infections
    $stmt = $db->prepare("SELECT * FROM catheter_infections WHERE patient_id = ? ORDER BY infection_date DESC");
    $stmt->execute([$patient_id]);
    $catheter_infections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Complications
    $stmt = $db->prepare("SELECT * FROM dialysis_complications WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$patient_id]);
    $complications = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Medical background
    $stmt = $db->prepare("SELECT * FROM medical_background WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$patient_id]);
    $medical_background = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // HD Prescription
    $stmt = $db->prepare("SELECT * FROM hd_prescription WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$patient_id]);
    $prescription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Vaccinations
    $stmt = $db->prepare("SELECT * FROM vaccinations WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$patient_id]);
    $vaccination_record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Medications
    $stmt = $db->prepare("SELECT * FROM medications WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$patient_id]);
    $medications = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Error fetching patient report data: " . $e->getMessage());
}

// If PDF format requested
if ($format === 'pdf') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="patient_report_' . $patient['file_number'] . '_' . date('Y-m-d') . '.pdf"');
    // For now, we'll output plain text. In production, you'd use a PDF library
    echo "PDF generation would be implemented here with a library like TCPDF or mPDF";
    exit();
}

// Set page title
$page_title = 'Patient Report - ' . htmlspecialchars($patient['name_english']);

// Check if text format is requested
$text_format = isset($_GET['format']) && $_GET['format'] === 'text';

if ($text_format) {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="patient_report_' . $patient['file_number'] . '_' . date('Y-m-d') . '.txt"');
    
    echo "===================================================================\n";
    echo "                    PATIENT REPORT\n";
    echo "===================================================================\n\n";
    
    // Patient Identification
    echo "PATIENT IDENTIFICATION:\n";
    echo "------------------------\n";
    echo "File Number: " . safe_value($patient['file_number']) . "\n";
    echo "Name: " . safe_value($patient['name_english']) . "\n";
    echo "Date of Birth: " . format_date($patient['date_of_birth']) . "\n";
    echo "Age: " . safe_value($patient['age']) . " years\n";
    echo "Gender: " . safe_value($patient['gender']) . "\n";
    echo "Contact Number: " . safe_value($patient['contact_number']) . "\n";
    echo "Room Number: " . safe_value($patient['room_number']) . "\n";
    echo "Group: " . safe_value($patient['group_type']) . "\n";
    echo "Shift: " . safe_value($patient['shift_type']) . "\n";
    echo "Height: " . safe_value($patient['height_cm']) . " cm\n";
    echo "Weight: " . safe_value($patient['weight_kg']) . " kg\n";
    if (isset($patient['bsa']) && $patient['bsa']) {
        echo "BSA: " . safe_value($patient['bsa']) . " m²\n";
    }
    echo "Registration Date: " . format_date($patient['created_at']) . "\n\n";
    
    // Medical Background
    if ($medical_background) {
        echo "MEDICAL BACKGROUND:\n";
        echo "-------------------\n";
        echo "Previous Dialysis: " . safe_value($medical_background['previous_dialysis'] ?? '') . "\n";
        echo "Previous Transplant: " . safe_value($medical_background['previous_transplant'] ?? '') . "\n";
        echo "Diabetes: " . safe_value($medical_background['diabetes'] ?? '') . "\n";
        echo "Hypertension: " . safe_value($medical_background['hypertension'] ?? '') . "\n";
        echo "Heart Disease: " . safe_value($medical_background['heart_disease'] ?? '') . "\n";
        echo "Other Conditions: " . safe_value($medical_background['other_conditions'] ?? '') . "\n\n";
    }
    
    // Catheter Infections
    if (!empty($catheter_infections)) {
        echo "CATHETER INFECTIONS:\n";
        echo "--------------------\n";
        foreach (array_slice($catheter_infections, 0, 5) as $infection) {
            echo "Date: " . format_date($infection['infection_date']) . "\n";
            echo "Organism: " . safe_value($infection['organism']) . "\n";
            echo "Antibiotic: " . safe_value($infection['antibiotic_used']) . "\n";
            echo "Resolution Date: " . format_date($infection['resolution_date']) . "\n";
            echo "---\n";
        }
        echo "\n";
    }
    
    // Complications
    if ($complications) {
        echo "DIALYSIS COMPLICATIONS:\n";
        echo "-----------------------\n";
        echo "Hypotension Episodes: " . safe_value($complications['hypotension_episodes'] ?? '') . "\n";
        echo "Cramping: " . safe_value($complications['cramping'] ?? '') . "\n";
        echo "Clotting: " . safe_value($complications['clotting'] ?? '') . "\n";
        echo "Access Issues: " . safe_value($complications['access_issues'] ?? '') . "\n";
        echo "Other: " . safe_value($complications['other_complications'] ?? '') . "\n\n";
    }
    
    // Recent Laboratory Data
    if (!empty($laboratory_data)) {
        echo "RECENT LABORATORY DATA:\n";
        echo "-----------------------\n";
        foreach (array_slice($laboratory_data, 0, 5) as $lab) {
            echo "Date: " . format_date($lab['test_date']) . "\n";
            echo "Hemoglobin: " . safe_value($lab['hemoglobin']) . " g/dL\n";
            echo "Calcium: " . safe_value($lab['calcium']) . " mmol/L\n";
            echo "Phosphorus: " . safe_value($lab['phosphorus']) . " mmol/L\n";
            echo "Ferritin: " . safe_value($lab['ferritin']) . " ng/mL\n";
            echo "---\n";
        }
        echo "\n";
    }
    
    // Quarterly Laboratory Data
    if (!empty($quarterly_lab_data)) {
        echo "QUARTERLY LABORATORY DATA:\n";
        echo "--------------------------\n";
        foreach (array_slice($quarterly_lab_data, 0, 3) as $qlab) {
            echo "Quarter/Year: " . safe_value($qlab['quarter_year']) . "\n";
            
            // Hematology with new format
            if ($qlab['hb_1'] || $qlab['hb_2'] || $qlab['hb_3']) {
                echo "Hb: ";
                if ($qlab['date_1'] && $qlab['hb_1']) echo format_date($qlab['date_1']) . " (" . $qlab['hb_1'] . " g/L)";
                if ($qlab['date_2'] && $qlab['hb_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['hb_2'] . " g/L)";
                if ($qlab['date_3'] && $qlab['hb_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['hb_3'] . " g/L)";
                echo "\n";
            }
            
            // Iron studies
            if ($qlab['iron_1'] || $qlab['iron_2'] || $qlab['iron_3']) {
                echo "Iron: ";
                if ($qlab['date_1'] && $qlab['iron_1']) echo format_date($qlab['date_1']) . " (" . $qlab['iron_1'] . ")";
                if ($qlab['date_2'] && $qlab['iron_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['iron_2'] . ")";
                if ($qlab['date_3'] && $qlab['iron_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['iron_3'] . ")";
                echo "\n";
            }
            
            // Calcium
            if ($qlab['calcium_1'] || $qlab['calcium_2'] || $qlab['calcium_3']) {
                echo "Calcium: ";
                if ($qlab['date_1'] && $qlab['calcium_1']) echo format_date($qlab['date_1']) . " (" . $qlab['calcium_1'] . " mmol/L)";
                if ($qlab['date_2'] && $qlab['calcium_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['calcium_2'] . " mmol/L)";
                if ($qlab['date_3'] && $qlab['calcium_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['calcium_3'] . " mmol/L)";
                echo "\n";
            }
            
            // Phosphorus
            if ($qlab['phosphorus_1'] || $qlab['phosphorus_2'] || $qlab['phosphorus_3']) {
                echo "Phosphorus: ";
                if ($qlab['date_1'] && $qlab['phosphorus_1']) echo format_date($qlab['date_1']) . " (" . $qlab['phosphorus_1'] . " mmol/L)";
                if ($qlab['date_2'] && $qlab['phosphorus_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['phosphorus_2'] . " mmol/L)";
                if ($qlab['date_3'] && $qlab['phosphorus_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['phosphorus_3'] . " mmol/L)";
                echo "\n";
            }
            
            // PTH
            if ($qlab['pth_pmol_1'] || $qlab['pth_pmol_2'] || $qlab['pth_pmol_3']) {
                echo "PTH: ";
                if ($qlab['date_1'] && $qlab['pth_pmol_1']) echo format_date($qlab['date_1']) . " (" . $qlab['pth_pmol_1'] . " Pmol/L)";
                if ($qlab['date_2'] && $qlab['pth_pmol_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['pth_pmol_2'] . " Pmol/L)";
                if ($qlab['date_3'] && $qlab['pth_pmol_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['pth_pmol_3'] . " Pmol/L)";
                echo "\n";
            }
            
            // MCV
            if ($qlab['mcv_1'] || $qlab['mcv_2'] || $qlab['mcv_3']) {
                echo "MCV: ";
                if ($qlab['date_1'] && $qlab['mcv_1']) echo format_date($qlab['date_1']) . " (" . $qlab['mcv_1'] . " fL)";
                if ($qlab['date_2'] && $qlab['mcv_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['mcv_2'] . " fL)";
                if ($qlab['date_3'] && $qlab['mcv_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['mcv_3'] . " fL)";
                echo "\n";
            }
            
            // TIBC
            if ($qlab['tibc_1'] || $qlab['tibc_2'] || $qlab['tibc_3']) {
                echo "TIBC: ";
                if ($qlab['date_1'] && $qlab['tibc_1']) echo format_date($qlab['date_1']) . " (" . $qlab['tibc_1'] . " µg/dL)";
                if ($qlab['date_2'] && $qlab['tibc_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['tibc_2'] . " µg/dL)";
                if ($qlab['date_3'] && $qlab['tibc_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['tibc_3'] . " µg/dL)";
                echo "\n";
            }
            
            // TSAT
            if ($qlab['tsat_1'] || $qlab['tsat_2'] || $qlab['tsat_3']) {
                echo "TSAT: ";
                if ($qlab['date_1'] && $qlab['tsat_1']) echo format_date($qlab['date_1']) . " (" . $qlab['tsat_1'] . "%)";
                if ($qlab['date_2'] && $qlab['tsat_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['tsat_2'] . "%)";
                if ($qlab['date_3'] && $qlab['tsat_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['tsat_3'] . "%)";
                echo "\n";
            }
            
            // Ferritin
            if ($qlab['ferritin_1'] || $qlab['ferritin_2'] || $qlab['ferritin_3']) {
                echo "Ferritin: ";
                if ($qlab['date_1'] && $qlab['ferritin_1']) echo format_date($qlab['date_1']) . " (" . $qlab['ferritin_1'] . " ng/mL)";
                if ($qlab['date_2'] && $qlab['ferritin_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['ferritin_2'] . " ng/mL)";
                if ($qlab['date_3'] && $qlab['ferritin_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['ferritin_3'] . " ng/mL)";
                echo "\n";
            }
            
            // WBC
            if ($qlab['wbc_1'] || $qlab['wbc_2'] || $qlab['wbc_3']) {
                echo "WBC: ";
                if ($qlab['date_1'] && $qlab['wbc_1']) echo format_date($qlab['date_1']) . " (" . $qlab['wbc_1'] . " ×10³/µL)";
                if ($qlab['date_2'] && $qlab['wbc_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['wbc_2'] . " ×10³/µL)";
                if ($qlab['date_3'] && $qlab['wbc_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['wbc_3'] . " ×10³/µL)";
                echo "\n";
            }
            
            // Platelets
            if ($qlab['platelets_1'] || $qlab['platelets_2'] || $qlab['platelets_3']) {
                echo "Platelets: ";
                if ($qlab['date_1'] && $qlab['platelets_1']) echo format_date($qlab['date_1']) . " (" . $qlab['platelets_1'] . " ×10³/µL)";
                if ($qlab['date_2'] && $qlab['platelets_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['platelets_2'] . " ×10³/µL)";
                if ($qlab['date_3'] && $qlab['platelets_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['platelets_3'] . " ×10³/µL)";
                echo "\n";
            }
            
            // Albumin
            if ($qlab['albumin_1'] || $qlab['albumin_2'] || $qlab['albumin_3']) {
                echo "Albumin: ";
                if ($qlab['date_1'] && $qlab['albumin_1']) echo format_date($qlab['date_1']) . " (" . $qlab['albumin_1'] . " g/L)";
                if ($qlab['date_2'] && $qlab['albumin_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['albumin_2'] . " g/L)";
                if ($qlab['date_3'] && $qlab['albumin_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['albumin_3'] . " g/L)";
                echo "\n";
            }
            
            // Corrected Calcium
            if ($qlab['corrected_calcium_1'] || $qlab['corrected_calcium_2'] || $qlab['corrected_calcium_3']) {
                echo "Corrected Calcium: ";
                if ($qlab['date_1'] && $qlab['corrected_calcium_1']) echo format_date($qlab['date_1']) . " (" . $qlab['corrected_calcium_1'] . " mmol/L)";
                if ($qlab['date_2'] && $qlab['corrected_calcium_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['corrected_calcium_2'] . " mmol/L)";
                if ($qlab['date_3'] && $qlab['corrected_calcium_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['corrected_calcium_3'] . " mmol/L)";
                echo "\n";
            }
            
            // Ca×Phosphorus Product
            if ($qlab['ca_phos_product_1'] || $qlab['ca_phos_product_2'] || $qlab['ca_phos_product_3']) {
                echo "Ca×Phos Product: ";
                if ($qlab['date_1'] && $qlab['ca_phos_product_1']) echo format_date($qlab['date_1']) . " (" . $qlab['ca_phos_product_1'] . " mg²/dL²)";
                if ($qlab['date_2'] && $qlab['ca_phos_product_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['ca_phos_product_2'] . " mg²/dL²)";
                if ($qlab['date_3'] && $qlab['ca_phos_product_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['ca_phos_product_3'] . " mg²/dL²)";
                echo "\n";
            }
            
            // Vitamin D
            if ($qlab['vitamin_d_1'] || $qlab['vitamin_d_2'] || $qlab['vitamin_d_3']) {
                echo "Vitamin D: ";
                if ($qlab['date_1'] && $qlab['vitamin_d_1']) echo format_date($qlab['date_1']) . " (" . $qlab['vitamin_d_1'] . " nmol/L)";
                if ($qlab['date_2'] && $qlab['vitamin_d_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['vitamin_d_2'] . " nmol/L)";
                if ($qlab['date_3'] && $qlab['vitamin_d_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['vitamin_d_3'] . " nmol/L)";
                echo "\n";
            }
            
            // Sodium
            if ($qlab['sodium_1'] || $qlab['sodium_2'] || $qlab['sodium_3']) {
                echo "Sodium: ";
                if ($qlab['date_1'] && $qlab['sodium_1']) echo format_date($qlab['date_1']) . " (" . $qlab['sodium_1'] . " mmol/L)";
                if ($qlab['date_2'] && $qlab['sodium_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['sodium_2'] . " mmol/L)";
                if ($qlab['date_3'] && $qlab['sodium_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['sodium_3'] . " mmol/L)";
                echo "\n";
            }
            
            // Potassium
            if ($qlab['potassium_1'] || $qlab['potassium_2'] || $qlab['potassium_3']) {
                echo "Potassium: ";
                if ($qlab['date_1'] && $qlab['potassium_1']) echo format_date($qlab['date_1']) . " (" . $qlab['potassium_1'] . " mmol/L)";
                if ($qlab['date_2'] && $qlab['potassium_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['potassium_2'] . " mmol/L)";
                if ($qlab['date_3'] && $qlab['potassium_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['potassium_3'] . " mmol/L)";
                echo "\n";
            }
            
            // Uric Acid
            if ($qlab['uric_acid_1'] || $qlab['uric_acid_2'] || $qlab['uric_acid_3']) {
                echo "Uric Acid: ";
                if ($qlab['date_1'] && $qlab['uric_acid_1']) echo format_date($qlab['date_1']) . " (" . $qlab['uric_acid_1'] . " µmol/L)";
                if ($qlab['date_2'] && $qlab['uric_acid_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['uric_acid_2'] . " µmol/L)";
                if ($qlab['date_3'] && $qlab['uric_acid_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['uric_acid_3'] . " µmol/L)";
                echo "\n";
            }
            
            // Creatinine
            if ($qlab['creatinine_1'] || $qlab['creatinine_2'] || $qlab['creatinine_3']) {
                echo "Creatinine: ";
                if ($qlab['date_1'] && $qlab['creatinine_1']) echo format_date($qlab['date_1']) . " (" . $qlab['creatinine_1'] . " µmol/L)";
                if ($qlab['date_2'] && $qlab['creatinine_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['creatinine_2'] . " µmol/L)";
                if ($qlab['date_3'] && $qlab['creatinine_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['creatinine_3'] . " µmol/L)";
                echo "\n";
            }
            
            // Pre-dialysis BUN
            if ($qlab['pre_dialysis_bun_1'] || $qlab['pre_dialysis_bun_2'] || $qlab['pre_dialysis_bun_3']) {
                echo "Pre-dialysis BUN: ";
                if ($qlab['date_1'] && $qlab['pre_dialysis_bun_1']) echo format_date($qlab['date_1']) . " (" . $qlab['pre_dialysis_bun_1'] . " mmol/L)";
                if ($qlab['date_2'] && $qlab['pre_dialysis_bun_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['pre_dialysis_bun_2'] . " mmol/L)";
                if ($qlab['date_3'] && $qlab['pre_dialysis_bun_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['pre_dialysis_bun_3'] . " mmol/L)";
                echo "\n";
            }
            
            // Post-dialysis BUN
            if ($qlab['post_dialysis_bun_1'] || $qlab['post_dialysis_bun_2'] || $qlab['post_dialysis_bun_3']) {
                echo "Post-dialysis BUN: ";
                if ($qlab['date_1'] && $qlab['post_dialysis_bun_1']) echo format_date($qlab['date_1']) . " (" . $qlab['post_dialysis_bun_1'] . " mmol/L)";
                if ($qlab['date_2'] && $qlab['post_dialysis_bun_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['post_dialysis_bun_2'] . " mmol/L)";
                if ($qlab['date_3'] && $qlab['post_dialysis_bun_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['post_dialysis_bun_3'] . " mmol/L)";
                echo "\n";
            }
            
            // Dialysis Duration
            if ($qlab['dialysis_duration_1'] || $qlab['dialysis_duration_2'] || $qlab['dialysis_duration_3']) {
                echo "Dialysis Duration: ";
                if ($qlab['date_1'] && $qlab['dialysis_duration_1']) echo format_date($qlab['date_1']) . " (" . $qlab['dialysis_duration_1'] . " hours)";
                if ($qlab['date_2'] && $qlab['dialysis_duration_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['dialysis_duration_2'] . " hours)";
                if ($qlab['date_3'] && $qlab['dialysis_duration_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['dialysis_duration_3'] . " hours)";
                echo "\n";
            }
            
            // Ultrafiltrate Volume
            if ($qlab['ultrafiltrate_volume_1'] || $qlab['ultrafiltrate_volume_2'] || $qlab['ultrafiltrate_volume_3']) {
                echo "Ultrafiltrate Volume: ";
                if ($qlab['date_1'] && $qlab['ultrafiltrate_volume_1']) echo format_date($qlab['date_1']) . " (" . $qlab['ultrafiltrate_volume_1'] . " L)";
                if ($qlab['date_2'] && $qlab['ultrafiltrate_volume_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['ultrafiltrate_volume_2'] . " L)";
                if ($qlab['date_3'] && $qlab['ultrafiltrate_volume_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['ultrafiltrate_volume_3'] . " L)";
                echo "\n";
            }
            
            // Post-dialysis Weight
            if ($qlab['post_dialysis_weight_1'] || $qlab['post_dialysis_weight_2'] || $qlab['post_dialysis_weight_3']) {
                echo "Post-dialysis Weight: ";
                if ($qlab['date_1'] && $qlab['post_dialysis_weight_1']) echo format_date($qlab['date_1']) . " (" . $qlab['post_dialysis_weight_1'] . " kg)";
                if ($qlab['date_2'] && $qlab['post_dialysis_weight_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['post_dialysis_weight_2'] . " kg)";
                if ($qlab['date_3'] && $qlab['post_dialysis_weight_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['post_dialysis_weight_3'] . " kg)";
                echo "\n";
            }
            
            // Dialysis adequacy parameters
            if ($qlab['urr_1'] || $qlab['urr_2'] || $qlab['urr_3']) {
                echo "URR: ";
                if ($qlab['date_1'] && $qlab['urr_1']) echo format_date($qlab['date_1']) . " (" . $qlab['urr_1'] . "%)";
                if ($qlab['date_2'] && $qlab['urr_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['urr_2'] . "%)";
                if ($qlab['date_3'] && $qlab['urr_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['urr_3'] . "%)";
                echo "\n";
            }
            
            if ($qlab['kt_v_1'] || $qlab['kt_v_2'] || $qlab['kt_v_3']) {
                echo "Kt/V: ";
                if ($qlab['date_1'] && $qlab['kt_v_1']) echo format_date($qlab['date_1']) . " (" . $qlab['kt_v_1'] . ")";
                if ($qlab['date_2'] && $qlab['kt_v_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['kt_v_2'] . ")";
                if ($qlab['date_3'] && $qlab['kt_v_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['kt_v_3'] . ")";
                echo "\n";
            }
            
            // Serology
            if ($qlab['hbsag_1'] || $qlab['hbsag_2'] || $qlab['hbsag_3']) {
                echo "HBsAg: ";
                if ($qlab['date_1'] && $qlab['hbsag_1']) echo format_date($qlab['date_1']) . " (" . $qlab['hbsag_1'] . ")";
                if ($qlab['date_2'] && $qlab['hbsag_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['hbsag_2'] . ")";
                if ($qlab['date_3'] && $qlab['hbsag_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['hbsag_3'] . ")";
                echo "\n";
            }
            
            if ($qlab['anti_hcv_1'] || $qlab['anti_hcv_2'] || $qlab['anti_hcv_3']) {
                echo "Anti-HCV: ";
                if ($qlab['date_1'] && $qlab['anti_hcv_1']) echo format_date($qlab['date_1']) . " (" . $qlab['anti_hcv_1'] . ")";
                if ($qlab['date_2'] && $qlab['anti_hcv_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['anti_hcv_2'] . ")";
                if ($qlab['date_3'] && $qlab['anti_hcv_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['anti_hcv_3'] . ")";
                echo "\n";
            }
            
            if ($qlab['hiv_1'] || $qlab['hiv_2'] || $qlab['hiv_3']) {
                echo "HIV: ";
                if ($qlab['date_1'] && $qlab['hiv_1']) echo format_date($qlab['date_1']) . " (" . $qlab['hiv_1'] . ")";
                if ($qlab['date_2'] && $qlab['hiv_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['hiv_2'] . ")";
                if ($qlab['date_3'] && $qlab['hiv_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['hiv_3'] . ")";
                echo "\n";
            }
            
            if ($qlab['hbsab_1'] || $qlab['hbsab_2'] || $qlab['hbsab_3']) {
                echo "HBsAb: ";
                if ($qlab['date_1'] && $qlab['hbsab_1']) echo format_date($qlab['date_1']) . " (" . $qlab['hbsab_1'] . " IU/L)";
                if ($qlab['date_2'] && $qlab['hbsab_2']) echo " <-- " . format_date($qlab['date_2']) . " (" . $qlab['hbsab_2'] . " IU/L)";
                if ($qlab['date_3'] && $qlab['hbsab_3']) echo " <-- " . format_date($qlab['date_3']) . " (" . $qlab['hbsab_3'] . " IU/L)";
                echo "\n";
            }
            
            echo "---\n";
        }
        echo "\n";
    }
    
    // HD Prescription
    if ($prescription) {
        echo "HD PRESCRIPTION:\n";
        echo "----------------\n";
        echo "Access Type: " . safe_value($prescription['access_type'] ?? '') . "\n";
        echo "Dialyzer: " . safe_value($prescription['dialyzer'] ?? '') . "\n";
        echo "Blood Flow: " . safe_value($prescription['blood_flow'] ?? '') . " ml/min\n";
        echo "Dialysate Flow: " . safe_value($prescription['dialysate_flow'] ?? '') . " ml/min\n";
        echo "Duration: " . safe_value($prescription['duration'] ?? '') . " hours\n";
        echo "Frequency: " . safe_value($prescription['frequency'] ?? '') . " per week\n\n";
    }
    
    // Medications
    if ($medications) {
        echo "CURRENT MEDICATIONS:\n";
        echo "--------------------\n";
        echo "EPO: " . safe_value($medications['epo'] ?? '') . "\n";
        echo "Iron: " . safe_value($medications['iron'] ?? '') . "\n";
        echo "Calcium: " . safe_value($medications['calcium'] ?? '') . "\n";
        echo "Phosphate Binder: " . safe_value($medications['phosphate_binder'] ?? '') . "\n";
        echo "Vitamin D: " . safe_value($medications['vitamin_d'] ?? '') . "\n";
        echo "Other: " . safe_value($medications['other_medications'] ?? '') . "\n\n";
    }
    
    // Vaccination
    if ($vaccination_record) {
        echo "VACCINATION RECORD:\n";
        echo "-------------------\n";
        echo "Hepatitis B: " . format_date($vaccination_record['hepatitis_b_date'] ?? '') . "\n";
        echo "Flu Vaccine: " . format_date($vaccination_record['flu_vaccine_date'] ?? '') . "\n";
        echo "COVID-19: " . format_date($vaccination_record['covid_vaccine_date'] ?? '') . "\n";
        echo "Other: " . safe_value($vaccination_record['other_vaccines'] ?? '') . "\n\n";
    }
    
    echo "===================================================================\n";
    echo "Report generated on: " . date('d-m-Y H:i:s') . "\n";
    echo "Patient File: " . safe_value($patient['file_number']) . "\n";
    echo "===================================================================\n";
    
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            .container-fluid { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .card { border: none !important; box-shadow: none !important; }
            body { font-size: 12px; }
            .report-header { border-bottom: 2px solid #000; margin-bottom: 20px; }
        }
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #dee2e6;
        }
        .report-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .section-title {
            font-weight: bold;
            font-size: 14px;
            color: #2c3e50;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .data-row {
            margin-bottom: 5px;
        }
        .lab-table {
            font-size: 11px;
        }
        .medication-item {
            border-left: 3px solid #007bff;
            padding-left: 10px;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-3">
        <!-- Action Buttons -->
        <div class="row no-print mb-3">
            <div class="col-12">
                <a href="dashboard.php" class="btn btn-secondary me-2">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
                <button onclick="window.print()" class="btn btn-primary me-2">
                    <i class="fas fa-print me-2"></i>Print Report
                </button>
                <a href="patient_report.php?id=<?php echo $patient_id; ?>&format=text" class="btn btn-success me-2" target="_blank">
                    <i class="fas fa-file-alt me-2"></i>Download Text Format
                </a>
                <a href="patient_report.php?id=<?php echo $patient_id; ?>&format=pdf" class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf me-2"></i>Download PDF
                </a>
            </div>
        </div>

        <!-- Report Content -->
        <div class="card">
            <div class="card-body">
                <!-- Report Header -->
                <div class="report-header">
                    <p style="margin: 0; font-size: 16px; font-weight: bold;">DATE: <?php echo date('d-m-Y'); ?></p>
                </div>

                <!-- Patient Identification -->
                <div class="report-section">
                    <div class="section-title"># Patient Identification:</div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="data-row"><strong>File Number:</strong> <?php echo safe_value($patient['file_number'] ?? ''); ?></div>
                            <div class="data-row"><strong>Name in English:</strong> <?php echo safe_value($patient['name_english'] ?? ''); ?></div>
                            <div class="data-row"><strong>Date of Birth:</strong> <?php echo format_date($patient['date_of_birth'] ?? ''); ?></div>
                            <div class="data-row"><strong>Age:</strong> <?php echo safe_value($patient['age'] ?? ''); ?> years</div>
                            <div class="data-row"><strong>Gender:</strong> <?php echo safe_value($patient['gender'] ?? ''); ?></div>
                            <div class="data-row"><strong>Contact Number:</strong> <?php echo safe_value($patient['contact_number'] ?? ''); ?></div>
                            <div class="data-row"><strong>Room Number:</strong> <?php echo safe_value($patient['room_number'] ?? ''); ?></div>
                            <div class="data-row"><strong>Group:</strong> <?php echo safe_value($patient['group_type'] ?? ''); ?></div>
                            <div class="data-row"><strong>Shift:</strong> <?php echo safe_value($patient['shift_type'] ?? ''); ?></div>
                            <div class="data-row"><strong>Height:</strong> <?php echo safe_value($patient['height_cm'] ?? ''); ?> cm</div>
                            <div class="data-row"><strong>Weight:</strong> <?php echo safe_value($patient['weight_kg'] ?? ''); ?> kg</div>
                            <div class="data-row"><strong>BMI:</strong> <?php echo safe_value($patient['bmi'] ?? ''); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="data-row"><strong>Blood Group:</strong> <?php echo safe_value($patient['blood_group'] ?? ''); ?></div>
                            <div class="data-row"><strong>Dialysis Initiation Date:</strong> <?php echo format_date($patient['dialysis_initiation_date'] ?? ''); ?></div>
                            <div class="data-row"><strong>Months on Hemodialysis:</strong> <?php echo safe_value($patient['dialysis_months'] ?? ''); ?></div>
                            <div class="data-row"><strong>Years on Hemodialysis:</strong> <?php echo safe_value($patient['dialysis_years'] ?? ''); ?></div>
                            <div class="data-row"><strong>Chronic Problems:</strong> <?php echo safe_value($patient['chronic_problems'] ?? ''); ?></div>
                            <div class="data-row"><strong>Acute Problems:</strong> <?php echo safe_value($patient['acute_problems'] ?? ''); ?></div>
                            <div class="data-row"><strong>Pre-dialysis BP:</strong> <?php echo safe_value($patient['pre_systolic_bp'] ?? ''); ?>/<?php echo safe_value($patient['pre_diastolic_bp'] ?? ''); ?> (MAP: <?php echo safe_value($patient['pre_map'] ?? ''); ?>)</div>
                            <div class="data-row"><strong>Post-dialysis BP:</strong> <?php echo safe_value($patient['post_systolic_bp'] ?? ''); ?>/<?php echo safe_value($patient['post_diastolic_bp'] ?? ''); ?> (MAP: <?php echo safe_value($patient['post_map'] ?? ''); ?>)</div>
                            <div class="data-row"><strong>Pulse Rate:</strong> <?php echo safe_value($patient['pulse_rate'] ?? ''); ?> /min</div>
                            <div class="data-row"><strong>Temperature:</strong> <?php echo safe_value($patient['temperature'] ?? ''); ?> °C</div>
                            <div class="data-row"><strong>Vascular Access:</strong> 
                                <?php 
                                $access = [];
                                if ($patient['av_fistula'] ?? false) $access[] = 'AV Fistula';
                                if ($patient['catheter'] ?? false) $access[] = 'Catheter';
                                echo implode(', ', $access) ?: 'Not specified';
                                ?>
                            </div>
                            <div class="data-row"><strong>Registration Date:</strong> <?php echo format_date($patient['created_at'] ?? ''); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Medical Background -->
                <div class="report-section">
                    <div class="section-title"># Medical Background:</div>
                    <?php if ($medical_background): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="data-row"><strong>Previous PD:</strong> <?php echo ($medical_background['history_pd'] ?? 0) ? 'Yes' : 'No'; ?></div>
                            <?php if ($medical_background['history_pd'] ?? 0): ?>
                                <div class="data-row">&nbsp;&nbsp;• Start Date: <?php echo format_date($medical_background['pd_start_date'] ?? ''); ?></div>
                                <div class="data-row">&nbsp;&nbsp;• End Date: <?php echo format_date($medical_background['pd_end_date'] ?? ''); ?></div>
                            <?php endif; ?>
                            <div class="data-row"><strong>Transplant History:</strong> <?php echo ($medical_background['history_transplant'] ?? 0) ? 'Yes' : 'No'; ?></div>
                            <?php if ($medical_background['history_transplant'] ?? 0): ?>
                                <div class="data-row">&nbsp;&nbsp;• Date: <?php echo format_date($medical_background['transplant_date'] ?? ''); ?></div>
                                <div class="data-row">&nbsp;&nbsp;• Donor: <?php echo safe_value($medical_background['transplant_donor'] ?? ''); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <div class="data-row"><strong>Diabetes:</strong> <?php echo ($medical_background['diabetes'] ?? 0) ? 'Yes' : 'No'; ?></div>
                            <?php if ($medical_background['diabetes'] ?? 0): ?>
                                <div class="data-row">&nbsp;&nbsp;• Duration: <?php echo safe_value($medical_background['dm_duration'] ?? ''); ?></div>
                            <?php endif; ?>
                            <div class="data-row"><strong>Hypertension:</strong> <?php echo ($medical_background['hypertension'] ?? 0) ? 'Yes' : 'No'; ?></div>
                            <?php if ($medical_background['hypertension'] ?? 0): ?>
                                <div class="data-row">&nbsp;&nbsp;• Duration: <?php echo safe_value($medical_background['htn_duration'] ?? ''); ?></div>
                            <?php endif; ?>
                            <div class="data-row"><strong>Cardiac Disease:</strong> <?php echo ($medical_background['cardiac_disease'] ?? 0) ? 'Yes' : 'No'; ?></div>
                            <?php if ($medical_background['cardiac_disease'] ?? 0): ?>
                                <div class="data-row">&nbsp;&nbsp;• Duration: <?php echo safe_value($medical_background['cardiac_duration'] ?? ''); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <p>No medical background data recorded.</p>
                    <?php endif; ?>
                </div>

                <!-- Catheter Infections -->
                <div class="report-section">
                    <div class="section-title"># Catheter Infections:</div>
                    <?php if (!empty($catheter_infections)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Site</th>
                                    <th>Organism</th>
                                    <th>Treatment</th>
                                    <th>Outcome</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($catheter_infections as $infection): ?>
                                <tr>
                                    <td><?php echo format_date($infection['infection_date'] ?? ''); ?></td>
                                    <td><?php echo safe_value($infection['site'] ?? ''); ?></td>
                                    <td><?php echo safe_value($infection['organism'] ?? ''); ?></td>
                                    <td><?php echo safe_value($infection['treatment'] ?? ''); ?></td>
                                    <td><?php echo safe_value($infection['outcome'] ?? ''); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p>No catheter infections recorded.</p>
                    <?php endif; ?>
                </div>

                <!-- Complications -->
                <div class="report-section">
                    <div class="section-title"># Complications:</div>
                    <?php if ($complications): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="data-row"><strong>Hypotension:</strong> <?php echo ($complications['hypotension'] ?? 0) ? 'Yes' : 'No'; ?></div>
                            <div class="data-row"><strong>Muscle Cramps:</strong> <?php echo ($complications['muscle_cramps'] ?? 0) ? 'Yes' : 'No'; ?></div>
                            <div class="data-row"><strong>Nausea/Vomiting:</strong> <?php echo ($complications['nausea_vomiting'] ?? 0) ? 'Yes' : 'No'; ?></div>
                            <div class="data-row"><strong>Headache:</strong> <?php echo ($complications['headache'] ?? 0) ? 'Yes' : 'No'; ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="data-row"><strong>Chest Pain:</strong> <?php echo ($complications['chest_pain'] ?? 0) ? 'Yes' : 'No'; ?></div>
                            <div class="data-row"><strong>Back Pain:</strong> <?php echo ($complications['back_pain'] ?? 0) ? 'Yes' : 'No'; ?></div>
                            <div class="data-row"><strong>Itching:</strong> <?php echo ($complications['itching'] ?? 0) ? 'Yes' : 'No'; ?></div>
                            <div class="data-row"><strong>Others:</strong> <?php echo safe_value($complications['other_complications'] ?? ''); ?></div>
                        </div>
                    </div>
                    <?php else: ?>
                    <p>No complications recorded.</p>
                    <?php endif; ?>
                </div>

                <!-- Laboratory Data -->
                <div class="report-section">
                    <div class="section-title"># Laboratory Data:</div>
                    <?php if (!empty($laboratory_data)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered lab-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Hb</th>
                                    <th>Iron</th>
                                    <th>TIBC</th>
                                    <th>TSAT%</th>
                                    <th>Calcium</th>
                                    <th>Phosphorus</th>
                                    <th>PTH</th>
                                    <th>URR%</th>
                                    <th>Kt/V</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($laboratory_data, 0, 5) as $lab): ?>
                                <tr>
                                    <td><?php echo format_date($lab['test_date']); ?></td>
                                    <td><?php echo format_number($lab['hb']); ?></td>
                                    <td><?php echo format_number($lab['iron']); ?></td>
                                    <td><?php echo format_number($lab['tibc']); ?></td>
                                    <td><?php echo format_number($lab['tsat']); ?></td>
                                    <td><?php echo format_number($lab['calcium'], 3); ?></td>
                                    <td><?php echo format_number($lab['phosphorus'], 3); ?></td>
                                    <td><?php echo format_number($lab['pth']); ?></td>
                                    <td><?php echo format_number($lab['urr']); ?></td>
                                    <td><?php echo format_number($lab['kt_v'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p>No laboratory data recorded.</p>
                    <?php endif; ?>
                </div>

                <!-- Quarterly Laboratory Data -->
                <div class="report-section">
                    <div class="section-title"># Quarterly Laboratory Data:</div>
                    <?php if (!empty($quarterly_lab_data)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered lab-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Quarter/Year</th>
                                    <th>Hb (g/L)</th>
                                    <th>Hb Diff %</th>
                                    <th>Iron</th>
                                    <th>TSAT%</th>
                                    <th>Calcium</th>
                                    <th>Phosphorus</th>
                                    <th>Corrected Ca</th>
                                    <th>Ca×Phos</th>
                                    <th>PTH (Pmol/L)</th>
                                    <th>PTH (pg/mL)</th>
                                    <th>Sodium</th>
                                    <th>Potassium</th>
                                    <th>Uric Acid</th>
                                    <th>Creatinine</th>
                                    <th>Pre-BUN</th>
                                    <th>Post-BUN</th>
                                    <th>Duration</th>
                                    <th>UF Volume</th>
                                    <th>Post-Weight</th>
                                    <th>URR %</th>
                                    <th>Kt/V</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($quarterly_lab_data, 0, 3) as $qlab): ?>
                                <tr>
                                    <td><?php echo safe_value($qlab['quarter_year']); ?></td>
                                    <td><?php echo format_number($qlab['hb_1']); ?> / <?php echo format_number($qlab['hb_2']); ?> / <?php echo format_number($qlab['hb_3']); ?></td>
                                    <td><?php echo format_number($qlab['hb_diff_1_2']); ?></td>
                                    <td><?php echo format_number($qlab['iron_1']); ?> / <?php echo format_number($qlab['iron_2']); ?> / <?php echo format_number($qlab['iron_3']); ?></td>
                                    <td><?php echo format_number($qlab['tsat_1']); ?> / <?php echo format_number($qlab['tsat_2']); ?> / <?php echo format_number($qlab['tsat_3']); ?></td>
                                    <td><?php echo format_number($qlab['calcium_1'], 2); ?> / <?php echo format_number($qlab['calcium_2'], 2); ?> / <?php echo format_number($qlab['calcium_3'], 2); ?></td>
                                    <td><?php echo format_number($qlab['phosphorus_1'], 2); ?> / <?php echo format_number($qlab['phosphorus_2'], 2); ?> / <?php echo format_number($qlab['phosphorus_3'], 2); ?></td>
                                    <td><?php echo format_number($qlab['corrected_calcium_1'], 2); ?> / <?php echo format_number($qlab['corrected_calcium_2'], 2); ?> / <?php echo format_number($qlab['corrected_calcium_3'], 2); ?></td>
                                    <td><?php echo format_number($qlab['ca_phos_product_1'], 2); ?> / <?php echo format_number($qlab['ca_phos_product_2'], 2); ?> / <?php echo format_number($qlab['ca_phos_product_3'], 2); ?></td>
                                    <td><?php echo format_number($qlab['pth_pmol_1'], 1); ?> / <?php echo format_number($qlab['pth_pmol_2'], 1); ?> / <?php echo format_number($qlab['pth_pmol_3'], 1); ?></td>
                                    <td><?php echo format_number($qlab['pth_pgml_1'], 1); ?> / <?php echo format_number($qlab['pth_pgml_2'], 1); ?> / <?php echo format_number($qlab['pth_pgml_3'], 1); ?></td>
                                    <td><?php echo format_number($qlab['sodium_1'], 1); ?> / <?php echo format_number($qlab['sodium_2'], 1); ?> / <?php echo format_number($qlab['sodium_3'], 1); ?></td>
                                    <td><?php echo format_number($qlab['potassium_1'], 1); ?> / <?php echo format_number($qlab['potassium_2'], 1); ?> / <?php echo format_number($qlab['potassium_3'], 1); ?></td>
                                    <td><?php echo format_number($qlab['uric_acid_1'], 1); ?> / <?php echo format_number($qlab['uric_acid_2'], 1); ?> / <?php echo format_number($qlab['uric_acid_3'], 1); ?></td>
                                    <td><?php echo format_number($qlab['creatinine_1'], 1); ?> / <?php echo format_number($qlab['creatinine_2'], 1); ?> / <?php echo format_number($qlab['creatinine_3'], 1); ?></td>
                                    <td><?php echo format_number($qlab['pre_dialysis_bun_1'], 1); ?> / <?php echo format_number($qlab['pre_dialysis_bun_2'], 1); ?> / <?php echo format_number($qlab['pre_dialysis_bun_3'], 1); ?></td>
                                    <td><?php echo format_number($qlab['post_dialysis_bun_1'], 1); ?> / <?php echo format_number($qlab['post_dialysis_bun_2'], 1); ?> / <?php echo format_number($qlab['post_dialysis_bun_3'], 1); ?></td>
                                    <td><?php echo format_number($qlab['dialysis_duration_1'], 1); ?> / <?php echo format_number($qlab['dialysis_duration_2'], 1); ?> / <?php echo format_number($qlab['dialysis_duration_3'], 1); ?></td>
                                    <td><?php echo format_number($qlab['ultrafiltrate_volume_1'], 1); ?> / <?php echo format_number($qlab['ultrafiltrate_volume_2'], 1); ?> / <?php echo format_number($qlab['ultrafiltrate_volume_3'], 1); ?></td>
                                    <td><?php echo format_number($qlab['post_dialysis_weight_1'], 1); ?> / <?php echo format_number($qlab['post_dialysis_weight_2'], 1); ?> / <?php echo format_number($qlab['post_dialysis_weight_3'], 1); ?></td>
                                    <td><?php echo format_number($qlab['urr_1'], 1); ?> / <?php echo format_number($qlab['urr_2'], 1); ?> / <?php echo format_number($qlab['urr_3'], 1); ?></td>
                                    <td><?php echo format_number($qlab['kt_v_1'], 2); ?> / <?php echo format_number($qlab['kt_v_2'], 2); ?> / <?php echo format_number($qlab['kt_v_3'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p>No quarterly laboratory data recorded.</p>
                    <?php endif; ?>
                </div>

                <!-- HD Prescription -->
                <div class="report-section">
                    <div class="section-title"># HD Prescription:</div>
                    <?php if ($prescription): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="data-row"><strong>Access Type:</strong> <?php echo safe_value($prescription['access_type'] ?? ''); ?></div>
                            <div class="data-row"><strong>Dialyzer:</strong> <?php echo safe_value($prescription['dialyzer'] ?? ''); ?></div>
                            <div class="data-row"><strong>Blood Flow:</strong> <?php echo safe_value($prescription['blood_flow'] ?? ''); ?> ml/min</div>
                            <div class="data-row"><strong>Dialysate Flow:</strong> <?php echo safe_value($prescription['dialysate_flow'] ?? ''); ?> ml/min</div>
                            <div class="data-row"><strong>Duration:</strong> <?php echo safe_value($prescription['duration'] ?? ''); ?> hours</div>
                        </div>
                        <div class="col-md-6">
                            <div class="data-row"><strong>UF Goal:</strong> <?php echo safe_value($prescription['uf_goal'] ?? ''); ?> L</div>
                            <div class="data-row"><strong>Heparin Initial:</strong> <?php echo safe_value($prescription['heparin_initial'] ?? ''); ?> units</div>
                            <div class="data-row"><strong>Heparin Maintenance:</strong> <?php echo safe_value($prescription['heparin_maintenance'] ?? ''); ?> units/hr</div>
                            <div class="data-row"><strong>Frequency:</strong> <?php echo safe_value($prescription['frequency'] ?? ''); ?></div>
                            <div class="data-row"><strong>Dry Weight:</strong> <?php echo safe_value($prescription['dry_weight'] ?? ''); ?> kg</div>
                        </div>
                    </div>
                    <?php else: ?>
                    <p>No HD prescription recorded.</p>
                    <?php endif; ?>
                </div>

                <!-- Medications -->
                <div class="report-section">
                    <div class="section-title"># Medications:</div>
                    <?php if ($medications): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <?php if ($medications['erythropoietin'] ?? ''): ?>
                            <div class="medication-item">
                                <strong>Erythropoietin:</strong> <?php echo safe_value($medications['erythropoietin'] ?? ''); ?><br>
                                <small>Dose: <?php echo safe_value($medications['epo_dose'] ?? ''); ?> | Frequency: <?php echo safe_value($medications['epo_frequency'] ?? ''); ?></small>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($medications['iron_supplement'] ?? ''): ?>
                            <div class="medication-item">
                                <strong>Iron Supplement:</strong> <?php echo safe_value($medications['iron_supplement'] ?? ''); ?><br>
                                <small>Dose: <?php echo safe_value($medications['iron_dose'] ?? ''); ?> | Frequency: <?php echo safe_value($medications['iron_frequency'] ?? ''); ?></small>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($medications['calcium_supplement'] ?? ''): ?>
                            <div class="medication-item">
                                <strong>Calcium Supplement:</strong> <?php echo safe_value($medications['calcium_supplement'] ?? ''); ?><br>
                                <small>Dose: <?php echo safe_value($medications['calcium_dose'] ?? ''); ?> | Frequency: <?php echo safe_value($medications['calcium_frequency'] ?? ''); ?></small>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <?php if ($medications['phosphate_binder'] ?? ''): ?>
                            <div class="medication-item">
                                <strong>Phosphate Binder:</strong> <?php echo safe_value($medications['phosphate_binder'] ?? ''); ?><br>
                                <small>Dose: <?php echo safe_value($medications['phosphate_dose'] ?? ''); ?> | Frequency: <?php echo safe_value($medications['phosphate_frequency'] ?? ''); ?></small>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($medications['vitamin_d'] ?? ''): ?>
                            <div class="medication-item">
                                <strong>Vitamin D:</strong> <?php echo safe_value($medications['vitamin_d'] ?? ''); ?><br>
                                <small>Dose: <?php echo safe_value($medications['vitamin_d_dose'] ?? ''); ?> | Frequency: <?php echo safe_value($medications['vitamin_d_frequency'] ?? ''); ?></small>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($medications['other_medications'] ?? ''): ?>
                            <div class="medication-item">
                                <strong>Other Medications:</strong><br>
                                <small><?php echo nl2br(safe_value($medications['other_medications'] ?? '')); ?></small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <p>No medications recorded.</p>
                    <?php endif; ?>
                </div>

                <!-- Vaccination -->
                <div class="report-section">
                    <div class="section-title"># Vaccination:</div>
                    <?php if ($vaccination_record): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="data-row"><strong>Hepatitis B:</strong> 
                                <?php echo ($vaccination_record['hepatitis_b_completed'] ?? 0) ? 'Completed' : 'Not Completed'; ?>
                            </div>
                            <?php if ($vaccination_record['hepatitis_b_completed'] ?? 0): ?>
                                <div class="data-row">&nbsp;&nbsp;• Date: <?php echo format_date($vaccination_record['hepatitis_b_date'] ?? ''); ?></div>
                                <div class="data-row">&nbsp;&nbsp;• Series: <?php echo safe_value($vaccination_record['hepatitis_b_series'] ?? ''); ?></div>
                            <?php endif; ?>
                            
                            <div class="data-row"><strong>Flu Vaccine:</strong> 
                                <?php echo ($vaccination_record['flu_vaccine_completed'] ?? 0) ? 'Completed' : 'Not Completed'; ?>
                            </div>
                            <?php if ($vaccination_record['flu_vaccine_completed'] ?? 0): ?>
                                <div class="data-row">&nbsp;&nbsp;• Date: <?php echo format_date($vaccination_record['flu_vaccine_date'] ?? ''); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <div class="data-row"><strong>PPV23:</strong> 
                                <?php echo ($vaccination_record['ppv23_completed'] ?? 0) ? 'Completed' : 'Not Completed'; ?>
                            </div>
                            <?php if ($vaccination_record['ppv23_completed'] ?? 0): ?>
                                <div class="data-row">&nbsp;&nbsp;• Date: <?php echo format_date($vaccination_record['ppv23_date'] ?? ''); ?></div>
                            <?php endif; ?>
                            
                            <div class="data-row"><strong>RSV:</strong> 
                                <?php echo ($vaccination_record['rsv_completed'] ?? 0) ? 'Completed' : 'Not Completed'; ?>
                            </div>
                            <?php if ($vaccination_record['rsv_completed'] ?? 0): ?>
                                <div class="data-row">&nbsp;&nbsp;• Date: <?php echo format_date($vaccination_record['rsv_date'] ?? ''); ?></div>
                                <div class="data-row">&nbsp;&nbsp;• Recommendation: <?php echo safe_value($vaccination_record['rsv_recommendation'] ?? ''); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <p>No vaccinations recorded.</p>
                    <?php endif; ?>
                </div>

                <!-- Report Footer -->
                <div class="text-center mt-4" style="border-top: 1px solid #dee2e6; padding-top: 15px;">
                    <small class="text-muted">
                        Report generated on <?php echo date('d-m-Y H:i:s'); ?> | 
                        Patient File: <?php echo safe_value($patient['file_number']); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>