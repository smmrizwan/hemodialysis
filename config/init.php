<?php
require_once __DIR__ . '/database_sqlite.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Create tables if they don't exist
$database->createTables();

// Make database connection globally available
$GLOBALS['db'] = $db;

// Utility functions
function sanitize_input($input) {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

function calculate_age($birth_date) {
    $birth = new DateTime($birth_date);
    $today = new DateTime();
    return $today->diff($birth)->y;
}

function calculate_bmi($height_cm, $weight_kg) {
    $height_m = $height_cm / 100;
    return round($weight_kg / ($height_m * $height_m), 2);
}

function calculate_bsa($height_cm, $weight_kg) {
    // BSA using Mosteller formula: 0.007184 * height^0.725 * weight^0.425
    return round(0.007184 * pow($height_cm, 0.725) * pow($weight_kg, 0.425), 3);
}

function calculate_map($systolic, $diastolic) {
    return round(($systolic + 2 * $diastolic) / 3, 2);
}

function calculate_dialysis_duration($start_date) {
    $start = new DateTime($start_date);
    $today = new DateTime();
    $diff = $today->diff($start);
    return [
        'months' => ($diff->y * 12) + $diff->m,
        'years' => $diff->y
    ];
}

function format_date($date, $format = 'd-m-Y') {
    if ($date) {
        return date($format, strtotime($date));
    }
    return '';
}

// Common medication list
$common_medications = [
    'Epoetin alfa', 'Darbepoetin alfa', 'Iron sucrose', 'Ferric gluconate',
    'Calcitriol', 'Paricalcitol', 'Sevelamer', 'Calcium carbonate',
    'Amlodipine', 'Lisinopril', 'Metoprolol', 'Furosemide',
    'Aspirin', 'Clopidogrel', 'Atorvastatin', 'Omeprazole',
    'Insulin', 'Metformin', 'Heparin', 'Warfarin'
];

// Common organisms for catheter infections
$common_organisms = [
    'Staphylococcus aureus', 'Staphylococcus epidermidis',
    'Enterococcus faecalis', 'Enterococcus faecium',
    'Pseudomonas aeruginosa', 'Escherichia coli',
    'Klebsiella pneumoniae', 'Candida albicans',
    'Candida glabrata', 'Streptococcus viridans'
];

// Common chronic/acute problems
$common_problems = [
    'DM - Diabetes Mellitus', 'HTN - Hypertension',
    'CKD-V - Chronic Kidney Disease Stage 5', 'IHD - Ischemic Heart Disease',
    'CHF - Congestive Heart Failure', 'COPD - Chronic Obstructive Pulmonary Disease',
    'CAD - Coronary Artery Disease', 'PVD - Peripheral Vascular Disease',
    'CVA - Cerebrovascular Accident', 'Anemia'
];
?>
