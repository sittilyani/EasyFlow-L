create table other_prescriptions
create table administrationroutes
create table durationunits
counts to change password, loginpage to change password
1. counts/drugstocked.php
2. settings
3. Backup direct on header
4. Root Password: LVCTHealth@20
5. Root Username: LVCTHealth
6. Root database: iorpms
7. Root host: localhost

# LVCTHealth colours: #722182; Purple
# Social links
    ; https://web.facebook.com/LVCTHealth/?_rdc=1&_rdr#
    ; https://www.youtube.com/user/TheLVCT
    ; https://x.com/LVCTKe
    ; https://www.instagram.com/lvct_health/
    ; https://www.linkedin.com/company/lvcthealth/


Tables changes
#tblusers
username and email = unique
# tables added:
; treatment_stage
: psychiatric_rx_problems
; Offences
; psycho_followup_visits
; living_conditions
; employment_status
; psychosocial_interventions
; integration_status
; referral_linkage_services
; psychosocial_outcomes
; nursing_services
; consents
;csos

ALTER TABLE tblusers ADD COLUMN full_name VARCHAR(200) GENERATED ALWAYS AS (CONCAT(first_name, ' ', last_name)) STORED;

<input type="text" name="visitDate" class="readonly-input" readonly value="<?php echo date('Y-m-d'); ?>">

Edit patients table
Add column:


ALTER TABLE patients
ADD COLUMN status_change_notes after current_status DEFAULT NULL,
ADD COLUMN psycho_social_tca DATE DEFAULT NULL,
ADD COLUMN psychiatric_tca DATE DEFAULT NULL,
ADD COLUMN nursing_tca DATE DEFAULT NULL,
ADD COLUMN nutrition_tca DATE DEFAULT NULL,
ADD COLUMN laboratory_tca DATE DEFAULT NULL,
ADD COLUMN records_tca DATE DEFAULT NULL,
ADD COLUMN peer_tca DATE DEFAULT NULL,
ADD COLUMN admin_tca DATE DEFAULT NULL,
ADD COLUMN religion varchar (50) DEFAULT NULL,
ADD COLUMN occupation varchar (50) DEFAULT NULL;

--- add column mflcode to facility_settings

-- update the columns
UPDATE patients
SET psycho_social_tca = next_appointment,
    psychiatric_tca = next_appointment,
    nursing_tca = next_appointment,
    nutrition_tca = next_appointment,
    laboratory_tca = next_appointment,
    records_tca = next_appointment,
    peer_tca = next_appointment,
    admin_tca = next_appointment
WHERE next_appointment IS NOT NULL;

# 24-10-2025
hide next_appointment from Register New client
change from date to text in patients table



UPDATE consents
INNER JOIN patients ON patients.mat_id = consents.mat_id
SET consents.date_of_consent = patients.reg_date
WHERE consents.date_of_consent IS NULL;


# All names:
$service_provider

For future inserts/updates, you'll need to handle this in your PHP code:

// Example for INSERT
$psycho_social_tca = !empty($_POST['psycho_social_tca']) ? $_POST['psycho_social_tca'] : $next_appointment;
$psychiatric_tca = !empty($_POST['psychiatric_tca']) ? $_POST['psychiatric_tca'] : $next_appointment;
// ... and so on for other fields

// In your INSERT query
$sql = "INSERT INTO patients (next_appointment, psycho_social_tca, psychiatric_tca, ...)
        VALUES (?, ?, ?, ...)";


update table userroles
role = unique and add HRIO, Psychiatrist, Data Manager, then id - PK AI



userrole = ('Admin', 'Pharmacist', 'Laboratory Technologist', 'Clinician', 'Psychologist', 'HRIO', 'Peer Educator', Data Manager, Psychiatrist, Receptionist)

access on dashboard.php as follows:

Admin = (administrator, BackUp and Refresh, Patient Management, Pharmacy Management, Clinical Management, Psychosocial Management, Laboratory Management, Referrals Management, Reports Management, Biometrics, Patients Summary, Daily Consumption Summary, Stocks Summary and Monthly Consumption Summary)
Pharmacist = (BackUp and Refresh, Patient Management, Pharmacy Management, Referrals Management, Reports Management, Biometrics, Patients Summary, Daily Consumption Summary, Stocks Summary and Monthly Consumption Summary)
Laboratory Technologist = (BackUp and Refresh, Patient Management, Pharmacy Management, Clinical Management, Psychosocial Management, Laboratory Management, Referrals Management, Reports Management, Biometrics, Patients Summary, Daily Consumption Summary, Stocks Summary and Monthly Consumption Summary)
Clinician = (BackUp and Refresh, Patient Management, Clinical Management, Laboratory Management, Referrals Management, Reports Management, Biometrics, Patients Summary)
Psychologist = (BackUp and Refresh, Patient Management, Psychosocial Management, Referrals Management, Reports Management, Biometrics, Patients Summary)
HRIO = (BackUp and Refresh, Patient Management, Referrals Management, Reports Management, Biometrics, Patients Summary)
Peer Educator = (BackUp and Refresh, Patient Management, Referrals Management, Biometrics)
Data Manager = (BackUp and Refresh, Patient Management, Reports Management, Biometrics, Patients Summary, Daily Consumption Summary)
Psychiatrist = (BackUp and Refresh, Patient Management, Clinical Management, Psychosocial Management, Laboratory Management, Referrals Management, Reports Management, Biometrics, Patients Summary)
Receptionist = (BackUp and Refresh, Patient Management, Referrals Management, Reports Management, Biometrics)

Administrator
#Backup - ../backup/backup.php #no target
#Home - ../dashboard/dashboard.php #no target
#Add User ../public/user_registration.php

Backup and refresh
#Backup - ../backup/backup.php #no target
#Home - ../dashboard/dashboard.php #no target
#Recreate tables 


What is New
# Ease navigation interface
# Auto logout for enhanced security
# Login, logout access log
# 

# add table stocks

# edit table medical_history add comp_date AUTO timestamp

DATE: 21-10-2025

CREATE TABLE clinical_encounters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    facility_name VARCHAR(255),
    mfl_code VARCHAR(50),
    county VARCHAR(100),
    sub_county VARCHAR(100),
    enrolment_date DATE,
    enrolment_time TIME,
    visit_type VARCHAR(255),
    nickname VARCHAR(255),
    presenting_complaints TEXT,
    injecting_history ENUM('yes', 'no'),
    reasons_injecting TEXT,
    reasons_injecting_other TEXT,
    flash_blood ENUM('yes', 'no'),
    shared_needles ENUM('yes', 'no'),
    injecting_complications ENUM('yes', 'no'),
    drug_overdose ENUM('yes', 'no'),
    pulse INT,
    oxygen_saturation INT,
    blood_pressure VARCHAR(50),
    temperature DECIMAL(4,1),
    respiratory_rate INT,
    height DECIMAL(5,1),
    weight DECIMAL(5,1),
    bmi DECIMAL(4,1),
    bmi_interpretation VARCHAR(50),
    cows_provider VARCHAR(255),
    cows_date DATE,
    cows_scores JSON,
    cows_totals JSON,
    cows_interpretations JSON,
    medical_history JSON,
    medical_medication JSON,
    hiv_diagnosis_date DATE,
    hiv_facility_care VARCHAR(255),
    other_medical_problems TEXT,
    allergies TEXT,
    allergies_other TEXT,
    contraception_use ENUM('yes', 'no'),
    contraception_method TEXT,
    last_menstrual_period DATE,
    pregnancy_status VARCHAR(50),
    pregnancy_weeks INT,
    breastfeeding ENUM('yes', 'no'),
    mental_health_diagnosis ENUM('yes', 'no'),
    mental_health_condition TEXT,
    mental_health_other TEXT,
    mental_health_medication ENUM('yes', 'no'),
    mental_health_medication_details TEXT,
    suicidal_thoughts ENUM('yes', 'no'),
    psychiatric_hospitalization ENUM('yes', 'no'),
    family_drug_use ENUM('yes', 'no'),
    family_mental_health ENUM('yes', 'no'),
    family_medical_conditions TEXT,
    family_medical_other TEXT,
    general_appearance TEXT,
    skin_examination TEXT,
    head_examination TEXT,
    eyes_examination TEXT,
    ears_examination TEXT,
    nose_examination TEXT,
    mouth_throat_examination TEXT,
    neck_examination TEXT,
    chest_examination TEXT,
    heart_examination TEXT,
    abdomen_examination TEXT,
    genitalia_examination TEXT,
    extremities_examination TEXT,
    neurological_examination TEXT,
    musculoskeletal_examination TEXT,
    diagnosis_opioid_use VARCHAR(50),
    other_diagnoses TEXT,
    treatment_plan TEXT,
    medication_prescribed TEXT,
    medication_other TEXT,
    initial_dose VARCHAR(255),
    next_appointment DATE,
    clinician_name VARCHAR(255),
    clinician_signature VARCHAR(255),
    patient_consent ENUM('yes', 'no'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE patient_drug_histories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    encounter_id INT NOT NULL,
    drug_type VARCHAR(50) NOT NULL,
    age_first_use INT DEFAULT NULL,
    duration_years INT DEFAULT NULL,
    frequency VARCHAR(50) DEFAULT NULL,
    quantity VARCHAR(255) DEFAULT NULL,
    route VARCHAR(50) DEFAULT NULL,
    last_used DATETIME DEFAULT NULL
);

CREATE TABLE backup_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_date DATE NOT NULL,
    backup_type ENUM('morning', 'midday') NOT NULL,
    backup_file VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


# BACKUP METHODS

Yes, you can definitely implement automated backups based on the session time! Here are a few approaches:

Option 1: Server-Side Cron Job (Recommended)
This is the most reliable approach since it doesn't depend on user sessions.

Create ../backup/auto_backup.php:

php
<?php
// Auto backup script
session_start();
include '../includes/config.php';

// Check if it's backup time (8:30 AM or 11:30 AM)
$current_time = date('H:i');
$backup_times = ['08:30', '11:30'];

if (in_array($current_time, $backup_times)) {
    // Check if backup already ran today for this time
    $today = date('Y-m-d');
    $backup_type = $current_time == '08:30' ? 'morning' : 'midday';
    $check_query = "SELECT * FROM backup_log WHERE backup_date = ? AND backup_type = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('ss', $today, $backup_type);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // Perform backup
        include 'backup_functions.php'; // Your existing backup functions
        performAutoBackup($backup_type);

        // Log the backup
        $log_query = "INSERT INTO backup_log (backup_date, backup_type, created_at) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($log_query);
        $stmt->bind_param('ss', $today, $backup_type);
        $stmt->execute();
    }
    $stmt->close();
}
?>
Then set up a cron job to run every minute:

bash
# Edit crontab
crontab -e

# Add this line to run every minute
* * * * * /usr/bin/php /path/to/your/project/backup/auto_backup.php
Option 2: Client-Side JavaScript with AJAX (Session-based)
Add this to your header.php:

php
<!-- Add this hidden div to store backup times -->
<div id="backup-config"
     data-backup-times='["08:30", "11:30"]'
     data-last-backup-check="<?php echo time(); ?>"
     style="display: none;">
</div>
Then add this JavaScript to your header.php:

javascript
// Automated backup functionality
function setupAutoBackup() {
    const backupTimes = ['08:30', '11:30'];
    let lastBackupCheck = Date.now();

    function checkBackupTime() {
        const now = new Date();
        const currentTime = now.toTimeString().slice(0, 5); // Get HH:MM format

        if (backupTimes.includes(currentTime)) {
            // Check if we already triggered backup for this time today
            const today = now.toDateString();
            const lastBackupDate = localStorage.getItem('lastBackupDate');
            const lastBackupTime = localStorage.getItem('lastBackupTime');

            if (lastBackupDate !== today || lastBackupTime !== currentTime) {
                // Trigger backup
                triggerAutoBackup(currentTime);

                // Update localStorage
                localStorage.setItem('lastBackupDate', today);
                localStorage.setItem('lastBackupTime', currentTime);
            }
        }
    }

    function triggerAutoBackup(time) {
        console.log(`Auto backup triggered at ${time}`);

        // Show notification to user
        showBackupNotification(`Auto backup in progress at ${time}...`);

        // Send AJAX request to perform backup
        fetch('../backup/auto_backup_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `backup_time=${time}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showBackupNotification(`Auto backup completed successfully at ${time}`);
            } else {
                showBackupNotification(`Backup failed: ${data.message}`);
            }
        })
        .catch(error => {
            console.error('Backup error:', error);
            showBackupNotification('Backup failed: Network error');
        });
    }

    function showBackupNotification(message) {
        // Create or show notification element
        let notification = document.getElementById('backup-notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'backup-notification';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #007bff;
                color: white;
                padding: 15px;
                border-radius: 5px;
                z-index: 10000;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            `;
            document.body.appendChild(notification);
        }

        notification.textContent = message;
        notification.style.display = 'block';

        // Auto hide after 5 seconds
        setTimeout(() => {
            notification.style.display = 'none';
        }, 5000);
    }

    // Check every minute
    setInterval(checkBackupTime, 60000);

    // Initial check
    checkBackupTime();
}

// Initialize auto backup when page loads
document.addEventListener('DOMContentLoaded', setupAutoBackup);
Create ../backup/auto_backup_handler.php:

php
<?php
session_start();
include '../includes/config.php';

// Only allow authenticated users
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Check if it's a valid backup time
$backup_times = ['08:30', '11:30'];
$current_time = date('H:i');
$requested_time = $_POST['backup_time'] ?? '';

// Validate the requested backup time
if (!in_array($requested_time, $backup_times) || $requested_time !== $current_time) {
    echo json_encode(['success' => false, 'message' => 'Invalid backup time']);
    exit;
}

// Check if backup already ran for this time today
$today = date('Y-m-d');
$backup_type = $requested_time == '08:30' ? 'morning' : 'midday';

$check_query = "SELECT * FROM backup_log WHERE backup_date = ? AND backup_type = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param('ss', $today, $backup_type);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Backup already performed today']);
    $stmt->close();
    exit;
}
$stmt->close();

// Perform the backup
try {
    include 'backup_functions.php'; // Your existing backup functions

    $backup_file = performAutoBackup($backup_type);

    // Log the backup
    $log_query = "INSERT INTO backup_log (backup_date, backup_type, backup_file, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($log_query);
    $stmt->bind_param('sss', $today, $backup_type, $backup_file);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Backup completed successfully', 'file' => $backup_file]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Backup failed: ' . $e->getMessage()]);
}
?>
Option 3: Enhanced Session Manager with Backup
Update your session_manager.php to include backup functionality:

php
<?php
// Centralized session management with backup features
function updateSessionActivity() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Set timeout duration (10 minutes = 600 seconds)
    $timeout_duration = 600;

    // Check if timeout condition is met
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        // Last request was more than timeout duration ago
        session_unset();
        session_destroy();
        header("Location: ../public/login.php?timeout=1");
        exit();
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();

    // Check for scheduled backups
    checkScheduledBackups();
}

function checkScheduledBackups() {
    $backup_times = ['08:30', '11:30'];
    $current_time = date('H:i');

    if (in_array($current_time, $backup_times)) {
        // Only run once per time slot per day
        $today = date('Y-m-d');
        $backup_type = $current_time == '08:30' ? 'morning' : 'midday';

        if (!isset($_SESSION['last_backup']) || $_SESSION['last_backup'] !== $today . '_' . $backup_type) {
            // Trigger backup (in background)
            triggerBackgroundBackup($backup_type);
            $_SESSION['last_backup'] = $today . '_' . $backup_type;
        }
    }
}

function triggerBackgroundBackup($type) {
    // This runs the backup in background without blocking the user
    $script_path = realpath(dirname(__FILE__) . '/../backup/auto_backup.php');
    $command = "php " . escapeshellarg($script_path) . " " . escapeshellarg($type) . " > /dev/null 2>&1 &";

    // Execute in background (Unix/Linux)
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        shell_exec($command);
    }
}

// Check if user is logged in
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect to login if not logged in
function requireLogin() {
    if (!isUserLoggedIn()) {
        header("Location: ../public/login.php");
        exit();
    }
}
?>
Database Table for Backup Log
Create this table to track backups:

sql
CREATE TABLE backup_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_date DATE NOT NULL,
    backup_type ENUM('morning', 'midday') NOT NULL,
    backup_file VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
Recommendation:
I'd suggest Option 1 (Cron Job) for production use because:

It's reliable and doesn't depend on user sessions

Runs even when no users are logged in

More secure and predictable

Doesn't impact user experience

Use Option 2 if you want user-facing notifications and want the backup to be tied to active sessions.

#22-10-2025
alter table toxicology_results
add column opiates varchar (50) after amitriptyline,
add column phencyclidine varchar (50) after opiates,
add column methadone varchar (50) after phencyclidine,
add column buprenorphine varchar (50)  after methadone,
add column nicotine varchar (50)  after buprenorphine,
add column other_tca varchar (50) after nicotine,
add column tramadol varchar (50) after other_tca,
add COLUMN ketamine varchar (50) after tramadol,
add COLUMN fentanyl varchar (50) after ketamine,
add COLUMN	oxycodone varchar (50) after fentanyl,
add COLUMN	propoxyphene varchar (50) after oxycodone,
add COLUMN	ecstacy varchar (50)  after propoxyphene,
add COLUMN	other_drugs varchar (50)  after ecstacy;


#23-10-2025
Hide dosage and drug in client registration

DELIMITER $$

CREATE TRIGGER update_tca_dates
BEFORE UPDATE ON patients
FOR EACH ROW
BEGIN
    IF NEW.next_appointment IS NOT NULL THEN
        SET NEW.psycho_social_tca = NEW.next_appointment;
        SET NEW.psychiatric_tca = NEW.next_appointment;
        SET NEW.nursing_tca = NEW.next_appointment;
        SET NEW.nutrition_tca = NEW.next_appointment;
        SET NEW.laboratory_tca = NEW.next_appointment;
        SET NEW.records_tca = NEW.next_appointment;
        SET NEW.peer_tca = NEW.next_appointment;
        SET NEW.admin_tca = NEW.next_appointment;
    END IF;
END$$

DELIMITER ;

# 24-10-2025
ALTER TABLE `patients`
drop column next_appointment,
ADD  `next_appointment` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP after results;

#Update next_appointment

update patients
set next_appointment = '2025-12-15';

#scripts

test_pump.php
MasterflexPump.php
uncomment extension = mysql in php.ini

notepad C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.ini
uncomment duplicate extension=mysql
uncomment php_dio.dll
uncoment ;extension=snmp

Check loaded modules in terminal:

php -m

Ensure mysqli appears once, and dio and snmp are absent. correct

#Navigate to Project:
# In Laragon s terminal:

cd C:\laragon\www\iorpms


#Complete composer init:
#If you haven t finished composer init (your earlier output showed it started):

composer init

Try WAMP
# Install all VC ++ redistributable


# Trigger Statements
# For updating full_name in table tblusers

DELIMITER $$

CREATE TRIGGER trg_populate_full_name
BEFORE INSERT ON tblusers
FOR EACH ROW
BEGIN
    SET NEW.full_name = CONCAT(NEW.first_name, ' ', NEW.last_name);
END$$

DELIMITER ;

MySQL Trigger:
sql

DELIMITER $$

CREATE TRIGGER trg_populate_full_name
BEFORE INSERT ON tblusers
FOR EACH ROW
BEGIN
    SET NEW.full_name = CONCAT(NEW.first_name, ' ', NEW.last_name);
END$$

DELIMITER ;

For UPDATE operations as well:
sqlDELIMITER $$

CREATE TRIGGER trg_populate_full_name_insert
BEFORE INSERT ON tblusers
FOR EACH ROW
BEGIN
    SET NEW.full_name = CONCAT(NEW.first_name, ' ', NEW.last_name);
END$$

CREATE TRIGGER trg_populate_full_name_update
BEFORE UPDATE ON tblusers
FOR EACH ROW
BEGIN
    SET NEW.full_name = CONCAT(NEW.first_name, ' ', NEW.last_name);
END$$

DELIMITER ;

-- PostgreSQL Trigger:
-- sql


CREATE OR REPLACE FUNCTION populate_full_name()
RETURNS TRIGGER AS $$
BEGIN
    NEW.full_name := NEW.first_name || ' ' || NEW.last_name;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_populate_full_name
BEFORE INSERT OR UPDATE ON tblusers
FOR EACH ROW
EXECUTE FUNCTION populate_full_name();


-- SQL Server Trigger:
-- sql

CREATE TRIGGER trg_populate_full_name
ON tblusers
INSTEAD OF INSERT
AS
BEGIN
    INSERT INTO tblusers (first_name, last_name, full_name)
    SELECT first_name, last_name, CONCAT(first_name, ' ', last_name)
    FROM inserted;
END;




