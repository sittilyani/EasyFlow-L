<?php
session_start();
include "../includes/config.php";

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access this page.");
}

// Get logged-in user details
$loggedInUserId = $_SESSION['user_id'];
$therapists_name = $_SESSION['full_name'] ?? 'Unknown';

// Get patient ID
$p_id = isset($_GET['p_id']) ? (int)$_GET['p_id'] : null;

// Fetch patient data
$currentSettings = [];
if ($p_id) {
    $query = "SELECT * FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $p_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
    $stmt->close();
}

// Fetch clinic visits
$clinicVisits = [];
$query = "SELECT clinic_id, visit_name FROM clinic_visits ORDER BY clinic_id";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $clinicVisits[] = $row;
}

// Fetch marital status
$maritalStatuses = [];
$query = "SELECT mar_id, marital_status_name FROM marital_status ORDER BY mar_id";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $maritalStatuses[] = $row;
}

// Fetch living conditions
$livingConditions = [];
$query = "SELECT cond_id, condition_name FROM living_conditions ORDER BY cond_id";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $livingConditions[] = $row;
}

// Fetch employment status
$employmentStatuses = [];
$query = "SELECT emp_id, emp_status_name FROM employment_status ORDER BY emp_id";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $employmentStatuses[] = $row;
}

// Fetch treatment stages
$treatmentStages = [];
$query = "SELECT stage_id, stage_of_rx_name FROM treatment_stage ORDER BY stage_id";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $treatmentStages[] = $row;
}

// Fetch psychosocial interventions
$interventions = [];
$query = "SELECT intervention_id, intervention_name FROM psychosocial_interventions ORDER BY intervention_id";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $interventions[] = $row;
}

// Fetch reintegration status
$reintegrationStatuses = [];
$query = "SELECT reint_id, reint_name FROM reintegration_status ORDER BY reint_id";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $reintegrationStatuses[] = $row;
}

// Fetch referral linkage services
$referralServices = [];
$query = "SELECT ref_id, ref_name FROM referral_linkage_services ORDER BY ref_id";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $referralServices[] = $row;
}

// Calculate age from DOB
$age = '';
if (isset($currentSettings['dob']) && !empty($currentSettings['dob'])) {
    $dob = new DateTime($currentSettings['dob']);
    $today = new DateTime();
    $age = $today->diff($dob)->y;
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PsychoDAR - Psycho-social Daily Activity Register</title>
    <link rel="stylesheet" href="../assests/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--primary:#2C3162;--secondary:#99FFBB;--accent:#4CAF50;--text:#333;--border:#ddd;--bg:#f8f9fa}
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:var(--bg);padding:20px}
        .container{max-width:1400px;margin:0 auto;background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.1);overflow:hidden}
        .form-header{background:linear-gradient(135deg,var(--primary),#1a1f3f);color:#fff;padding:30px;text-align:center}
        .form-header h2{margin:0;font-size:28px;font-weight:600}
        .form-header p{margin:10px 0 0;opacity:.9;font-size:14px}
        .form-body{padding:40px}
        .section-title{background:var(--secondary);color:var(--primary);padding:12px 20px;margin:30px 0 20px;border-left:5px solid var(--primary);font-weight:700;font-size:16px;border-radius:4px}
        .section-title:first-of-type{margin-top:0}
        .form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:25px;margin-bottom:30px}
        .form-group{display:flex;flex-direction:column}
        .form-group label{font-weight:600;color:var(--text);margin-bottom:8px;font-size:14px}
        .form-group label i{margin-right:5px;color:var(--primary)}
        .form-control,input[type="text"],input[type="date"],input[type="number"],select,textarea{width:100%;padding:10px 12px;border:2px solid var(--border);border-radius:6px;font-size:14px;transition:all .3s;background:#fff}
        .form-control:focus,input:focus,select:focus,textarea:focus{border-color:var(--primary);outline:0;box-shadow:0 0 0 3px rgba(44,49,98,.1)}
        .readonly-input{background:#f0f0f0!important;cursor:not-allowed;color:#666}
        textarea{resize:vertical;min-height:80px;font-family:inherit}
        .full-width{grid-column:1/-1}
        .btn-submit{background:var(--accent);color:#fff;padding:14px 40px;border:none;border-radius:6px;font-size:16px;font-weight:600;cursor:pointer;transition:all .3s;width:100%;margin-top:20px}
        .btn-submit:hover{background:#45a049;transform:translateY(-2px);box-shadow:0 4px 12px rgba(76,175,80,.3)}
        .required::after{content:' *';color:red}
        select[multiple]{min-height:120px;padding:8px}
        .badge{display:inline-block;padding:4px 10px;background:var(--primary);color:#fff;border-radius:4px;font-size:12px;margin-left:8px}
        @media (max-width:768px){.form-grid{grid-template-columns:1fr}.form-body{padding:20px}}
    </style>
</head>
<body>
    <div class="container">
        <div class="form-header">
            <h2><i class="fas fa-clipboard-list"></i> Psycho-social Daily Activity Register</h2>
            <p>Comprehensive Patient Assessment and Treatment Documentation</p>
        </div>

        <div class="form-body">
            <form action="psychodar_process.php" method="POST">
                <input type="hidden" name="p_id" value="<?= $p_id ?>">
                <input type="hidden" name="therapist_id" value="<?= $loggedInUserId ?>">

                <!-- CLIENT BIODATA -->
                <div class="section-title"><i class="fas fa-user-circle"></i> CLIENT BIODATA</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label><i class="far fa-calendar-alt"></i> Visit Date</label>
                        <input type="date" name="visit_date" class="readonly-input" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-stethoscope"></i> Visit Type</label>
                        <select name="visit_name" class="form-control" required>
                            <option value="">-- Select Visit Type --</option>
                            <?php foreach ($clinicVisits as $visit): ?>
                                <option value="<?= $visit['clinic_id'] ?>"><?= htmlspecialchars($visit['visit_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> MAT ID</label>
                        <input type="text" name="mat_id" class="readonly-input" readonly value="<?= htmlspecialchars($currentSettings['mat_id'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Client Name</label>
                        <input type="text" name="clientName" class="readonly-input" readonly value="<?= htmlspecialchars($currentSettings['clientName'] ?? '') ?>">
                        <input type="hidden" name="dob" value="<?= $currentSettings['dob'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-venus-mars"></i> Gender</label>
                        <input type="text" name="sex" class="readonly-input" readonly value="<?= htmlspecialchars($currentSettings['sex'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-birthday-cake"></i> Age</label>
                        <input type="number" name="age" class="readonly-input" readonly value="<?= $age ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-pills"></i> Drug Name</label>
                        <input type="text" name="drugname" class="readonly-input" readonly value="<?= htmlspecialchars($currentSettings['drugname'] ?? 'Methadone') ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-prescription-bottle"></i> Dosage</label>
                        <input type="text" name="dosage" class="readonly-input" readonly value="<?= htmlspecialchars($currentSettings['dosage'] ?? '') ?>">
                    </div>
                </div>

                <!-- SOCIAL HISTORY -->
                <div class="section-title"><i class="fas fa-users"></i> SOCIAL HISTORY</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label><i class="far fa-calendar-check"></i> Date of Intake Interview</label>
                        <input type="date" name="date_of_intake" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-heart"></i> Marital Status</label>
                        <select name="marital_status" class="form-control" required>
                            <option value="">-- Select Status --</option>
                            <?php foreach ($maritalStatuses as $status): ?>
                                <option value="<?= $status['mar_id'] ?>"><?= htmlspecialchars($status['marital_status_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-edit"></i> Other Marital Status (Specify)</label>
                        <input type="text" name="other_marital_status" placeholder="If other, please specify">
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-home"></i> Living Arrangements (Past 3 Months)</label>
                        <select name="living_arrangements" class="form-control" required>
                            <option value="">-- Select Arrangement --</option>
                            <option value="stable">Stable Arrangement</option>
                            <option value="non-stable">Non-stable Arrangement</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label><i class="fas fa-check-square"></i> Usual Living Conditions (Select all that apply)</label>
                        <select name="living_conditions[]" class="form-control" multiple>
                            <?php foreach ($livingConditions as $condition): ?>
                                <option value="<?= $condition['cond_id'] ?>"><?= htmlspecialchars($condition['condition_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color:#666;margin-top:5px">Hold Ctrl (Windows) or Cmd (Mac) to select multiple</small>
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-building"></i> Accommodation/Residence</label>
                        <select name="accommodation" class="form-control" required>
                            <option value="">-- Select --</option>
                            <option value="stable">Stable</option>
                            <option value="unstable">Unstable</option>
                            <option value="not_applicable">Not Applicable</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-briefcase"></i> Employment Status</label>
                        <select name="employment_status" class="form-control" required>
                            <option value="">-- Select Status --</option>
                            <?php foreach ($employmentStatuses as $emp): ?>
                                <option value="<?= $emp['emp_id'] ?>"><?= htmlspecialchars($emp['emp_status_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-procedures"></i> Treatment Stage</label>
                        <select name="treatment_stage" class="form-control" required>
                            <option value="">-- Select Stage --</option>
                            <?php foreach ($treatmentStages as $stage): ?>
                                <option value="<?= $stage['stage_id'] ?>"><?= htmlspecialchars($stage['stage_of_rx_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- PSYCHOSOCIAL ASSESSMENT -->
                <div class="section-title"><i class="fas fa-brain"></i> PSYCHOSOCIAL ASSESSMENT & INTERVENTION</div>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="required"><i class="fas fa-comment-medical"></i> Psycho-social Issues</label>
                        <textarea name="psycho_issues" rows="4" required placeholder="Describe psycho-social issues identified..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-hands-helping"></i> Psycho-social Interventions</label>
                        <select name="psycho_interventions" class="form-control" required>
                            <option value="">-- Select Intervention --</option>
                            <?php foreach ($interventions as $intervention): ?>
                                <option value="<?= $intervention['intervention_id'] ?>"><?= htmlspecialchars($intervention['intervention_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-chart-line"></i> Reintegration Status</label>
                        <select name="reintegration_status" class="form-control" required>
                            <option value="">-- Select Status --</option>
                            <?php foreach ($reintegrationStatuses as $reint): ?>
                                <option value="<?= $reint['reint_id'] ?>"><?= htmlspecialchars($reint['reint_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label><i class="fas fa-gavel"></i> Legal/Criminal/Court Issues</label>
                        <textarea name="legal_issues" rows="3" placeholder="Describe any legal issues..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-shield-alt"></i> Screened for GBV?</label>
                        <select name="gbv_screen" class="form-control" required>
                            <option value="">-- Select --</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                            <option value="not_applicable">Not Applicable</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-life-ring"></i> Given GBV Support?</label>
                        <select name="gbv_support" class="form-control" required>
                            <option value="">-- Select --</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                            <option value="not_applicable">Not Applicable</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-link"></i> Referral & Linkage Services</label>
                        <select name="linkage" class="form-control">
                            <option value="">-- Select Service --</option>
                            <?php foreach ($referralServices as $service): ?>
                                <option value="<?= $service['ref_id'] ?>"><?= htmlspecialchars($service['ref_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- THERAPIST NOTES -->
                <div class="section-title"><i class="fas fa-user-md"></i> THERAPIST INFORMATION</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label><i class="fas fa-user-nurse"></i> Therapist's Name</label>
                        <input type="text" name="therapists_name" value="<?= htmlspecialchars($therapists_name) ?>" class="readonly-input" readonly>
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="far fa-calendar-plus"></i> Next Appointment Date</label>
                        <input type="date" name="next_appointment" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group full-width">
                        <label class="required"><i class="fas fa-notes-medical"></i> Therapist's Notes</label>
                        <textarea name="therapists_notes" rows="5" required placeholder="Enter detailed session notes..."></textarea>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Submit PsychoDAR Form
                </button>
            </form>
        </div>
    </div>

    <script src="../assests/js/bootstrap.bundle.min.js"></script>
</body>
</html>