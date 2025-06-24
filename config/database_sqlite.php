<?php
class Database {
    private $db_file = "database/dialysis_management.db";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // Create database directory if it doesn't exist
            $db_dir = dirname($this->db_file);
            if (!is_dir($db_dir)) {
                mkdir($db_dir, 0755, true);
            }
            
            $this->conn = new PDO("sqlite:" . $this->db_file);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("PRAGMA foreign_keys = ON");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
    
    public function createTables() {
        $queries = [
            // Patients table
            "CREATE TABLE IF NOT EXISTS patients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                file_number TEXT UNIQUE NOT NULL,
                name_english TEXT NOT NULL,
                date_of_birth DATE NOT NULL,
                age INTEGER NOT NULL,
                gender TEXT NOT NULL CHECK (gender IN ('male', 'female')),
                contact_number TEXT NOT NULL,
                room_number INTEGER CHECK (room_number BETWEEN 1 AND 23),
                group_type TEXT NOT NULL CHECK (group_type IN ('SAT', 'SUN')),
                shift_type TEXT NOT NULL CHECK (shift_type IN ('1st', '2nd', '3rd')),
                height_cm REAL,
                weight_kg REAL,
                bmi REAL,
                bsa REAL,
                blood_group TEXT CHECK (blood_group IN ('O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-')),
                dialysis_initiation_date DATE,
                dialysis_months INTEGER,
                dialysis_years INTEGER,
                chronic_problems TEXT,
                acute_problems TEXT,
                pre_systolic_bp INTEGER,
                pre_diastolic_bp INTEGER,
                pre_map REAL,
                post_systolic_bp INTEGER,
                post_diastolic_bp INTEGER,
                post_map REAL,
                pulse_rate INTEGER,
                temperature REAL,
                av_fistula INTEGER DEFAULT 0,
                catheter INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            // Laboratory data table
            "CREATE TABLE IF NOT EXISTS laboratory_data (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_id INTEGER NOT NULL,
                test_date DATE NOT NULL,
                hb REAL,
                mcv REAL,
                iron REAL,
                tibc REAL,
                tsat REAL,
                ferritin REAL,
                hb_change_percent REAL,
                vitamin_d REAL,
                calcium REAL,
                phosphorus REAL,
                albumin REAL,
                pth REAL,
                corrected_calcium REAL,
                ca_phos_product REAL,
                hbsag TEXT CHECK (hbsag IN ('+ve', '-ve')),
                anti_hcv TEXT CHECK (anti_hcv IN ('+ve', '-ve')),
                hiv TEXT CHECK (hiv IN ('+ve', '-ve')),
                hbsab REAL,
                pre_dialysis_bun REAL,
                post_dialysis_bun REAL,
                dialysis_duration REAL,
                ultrafiltrate_volume REAL,
                post_dialysis_weight REAL,
                urr REAL,
                kt_v REAL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
            )",
            
            // Catheter infections table
            "CREATE TABLE IF NOT EXISTS catheter_infections (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_id INTEGER NOT NULL,
                infection_date DATE NOT NULL,
                organism TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
            )",
            
            // Dialysis complications table
            "CREATE TABLE IF NOT EXISTS dialysis_complications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_id INTEGER NOT NULL,
                hypotension INTEGER DEFAULT 0,
                hypertension INTEGER DEFAULT 0,
                muscle_cramps INTEGER DEFAULT 0,
                nausea_vomiting INTEGER DEFAULT 0,
                headache INTEGER DEFAULT 0,
                chest_pain INTEGER DEFAULT 0,
                pruritus INTEGER DEFAULT 0,
                fever_chills INTEGER DEFAULT 0,
                dyspnea INTEGER DEFAULT 0,
                seizures INTEGER DEFAULT 0,
                arrhythmias INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
            )",
            
            // Medical background table
            "CREATE TABLE IF NOT EXISTS medical_background (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_id INTEGER NOT NULL,
                history_pd INTEGER DEFAULT 0,
                pd_start_date DATE,
                pd_end_date DATE,
                history_transplant INTEGER DEFAULT 0,
                transplant_start_date DATE,
                transplant_end_date DATE,
                history_dm INTEGER DEFAULT 0,
                dm_duration_years INTEGER,
                history_htn INTEGER DEFAULT 0,
                htn_duration_years INTEGER,
                history_cardiac INTEGER DEFAULT 0,
                cardiac_duration_years INTEGER,
                residual_renal_function INTEGER DEFAULT 0,
                residual_urine_ml INTEGER,
                other_history TEXT,
                primary_ckd_cause TEXT,
                last_us_date DATE,
                last_us_findings TEXT,
                last_echo_date DATE,
                last_echo_findings TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
            )",
            
            // Hemodialysis prescription table
            "CREATE TABLE IF NOT EXISTS hd_prescription (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_id INTEGER NOT NULL,
                dialysis_modality TEXT DEFAULT 'Hemodialysis',
                dialyzer TEXT DEFAULT 'low flux' CHECK (dialyzer IN ('high flux', 'low flux')),
                frequency TEXT DEFAULT 'thrice weekly' CHECK (frequency IN ('once weekly', 'twice weekly', 'thrice weekly')),
                duration REAL DEFAULT 4.0,
                vascular_access TEXT CHECK (vascular_access IN ('permcath', 'temporary HD catheter', 'AV fistula', 'AV graft')),
                heparin_initial REAL,
                heparin_maintenance REAL,
                blood_flow_rate INTEGER,
                dialysate_flow_rate INTEGER,
                dry_body_weight REAL,
                ultrafiltration REAL DEFAULT 13.0,
                sodium REAL DEFAULT 135.0,
                potassium REAL DEFAULT 2.0,
                calcium REAL DEFAULT 1.5,
                bicarbonate REAL DEFAULT 35.0,
                catheter_lock TEXT CHECK (catheter_lock IN ('heparin', 'alteplase', 'normal saline', 'antibiotic lock')),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
            )",
            
            // Vaccinations table
            "CREATE TABLE IF NOT EXISTS vaccinations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_id INTEGER NOT NULL,
                hepatitis_b_completed INTEGER DEFAULT 0,
                hepatitis_b_date DATE,
                hepatitis_b_series TEXT CHECK (hepatitis_b_series IN ('Three dose Recombivax HB', 'Four dose Engerix-B')),
                flu_vaccine_completed INTEGER DEFAULT 0,
                flu_vaccine_date DATE,
                ppv23_completed INTEGER DEFAULT 0,
                ppv23_date DATE,
                rsv_completed INTEGER DEFAULT 0,
                rsv_date DATE,
                rsv_recommendation TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
            )",
            
            // Medications table
            "CREATE TABLE IF NOT EXISTS medications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_id INTEGER NOT NULL,
                medication_name TEXT NOT NULL,
                dosage TEXT,
                frequency TEXT CHECK (frequency IN ('Once daily', 'Twice daily', 'Three times daily', 'Four times daily', 'Every other day', 'Weekly', 'Monthly', 'As needed', 'With dialysis')),
                route TEXT CHECK (route IN ('PO', 'IV', 's/c', 'IM')),
                row_order INTEGER DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
            )"
        ];

        foreach ($queries as $query) {
            try {
                $this->conn->exec($query);
            } catch (PDOException $e) {
                echo "Error creating table: " . $e->getMessage() . "\n";
            }
        }
    }
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

if ($db) {
    $database->createTables();
}

// Helper functions are now defined in init.php
?>