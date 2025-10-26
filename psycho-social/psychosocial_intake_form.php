<?php
session_start();
include "../includes/config.php";

// Get the user_id from the query parameter (if applicable)
$userId = isset($_GET['p_id']) ? $_GET['p_id'] : null;

// Fetch the current settings for the user (if applicable)
$currentSettings = [];
if ($userId) {
    $query = "SELECT * FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
}


// Check if the user is logged in and fetch their user_id
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access this page.");
}
$loggedInUserId = $_SESSION['user_id'];

// Fetch the logged-in user's name from tblusers
$therapists_initials = 'Unknown';
$userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param('i', $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $therapists_initials = $user['first_name'] . ' ' . $user['last_name'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medically Assisted Therapy Psycho-Social Intake Form</title>
    <style>
        /* CSS Styles */
        * {
            box-sizing: border-box;
            font-family: Tahoma, Geneva, sans-serif;
        }

        body {
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }

        .container {
            width: 80%;
            max-width: 90%;
            margin: 0 auto;
            background-color: #FfFfFF;
            padding: 50px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
            font-size: 1.4em;
        }

        .form-section {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .form-header {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            text-align: center;
            margin-bottom: 30px;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 1.1em;
            color: #2c3e50;
            padding-left: 10px;
            border-left: 4px solid #3498db;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        textarea {
            height: 80px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }

        .checkbox-item input {
            margin-right: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .btn-submit {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            margin: 30px auto 0;
            transition: background-color 0.3s;
        }

        .btn-submit:hover {
            background-color: #2980b9;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .form-col {
            flex: 1;
            min-width: 200px;
        }

        .hidden {
            display: none;
        }

        .required::after {
            content: " *";
            color: red;
        }

        .yes-no-group {
            display: flex;
            gap: 20px;
            margin-bottom: 10px;
        }

        .yes-no-item {
            display: flex;
            align-items: center;
        }

        .form-note {
            font-style: italic;
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }

            .form-col {
                width: 100%;
            }

            .container {
                width: 100%;
                padding: 10px;
            }

            h1 {
                font-size: 1.2em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-header">
            <img src="../assets/images/Government of Kenya.png" width="80" height="60" alt="Government Logo">
            <div class="header-center">
                <h2>MEDICALLY ASSISTED THERAPY</h2>
                <h4>PSYCHO-SOCIAL INTAKE FORM</h4>
            </div>
            <p>FORM 2A VER. APR. 2023</p>
        </div>

        <form id="intakeForm" method="POST" action="psychosocial_intake_form_process.php">
            <!-- Visit Type and Date Section -->
            <div class="form-section">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="date" class="required">Date (YYYY/MM/DD)</label>
                            <input type="text" name="visitDate" class="readonly-input" readonly value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label class="required">Visit Type</label>
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="radio" id="initial" name="visit_type" value="initial" checked required>
                                    <label for="initial">Initial</label>
                                </div>
                                <?php for($i = 1; $i <= 8; $i++): ?>
                                <div class="checkbox-item">
                                    <input type="radio" id="followup<?php echo $i; ?>" name="visit_type" value="followup<?php echo $i; ?>" required>
                                    <label for="followup<?php echo $i; ?>">Follow up <?php echo $i; ?></label>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Basic Information Section -->
            <div class="form-section">
                <div class="section-title">1. Client Information</div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="client_name" class="required">Client Name in Full</label>
                            <input type="text" name="clientName" class="readonly-input" readonly value="<?php echo isset($currentSettings['clientName']) ? $currentSettings['clientName'] : ''; ?>">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="mat_id" class="required">MAT ID</label>
                            <input type="text" name="mat_id" class="readonly-input" readonly value="<?php echo isset($currentSettings['mat_id']) ? $currentSettings['mat_id'] : ''; ?>">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label class="required">Sex</label>
                            <input type="text" name="clientName" class="readonly-input" readonly value="<?php echo isset($currentSettings['sex']) ? $currentSettings['sex'] : ''; ?>">
                            
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Clinical Information -->
            <div class="form-section">
                <div class="section-title">2. CLINICAL ASSESSMENT</div>

                <div class="form-group">
                    <label for="pre_complaints">Presenting Complaints</label>
                    <textarea id="pre_complaints" name="pre_complaints" placeholder="Describe current complaints and symptoms"></textarea>
                </div>

                <div class="form-group">
                    <label for="corr_complaints">Corroborative History</label>
                    <textarea id="corr_complaints" name="corr_complaints" placeholder="Information from family or other sources"></textarea>
                </div>

                <div class="form-group">
                    <label for="hx_illness">History of Present Illness</label>
                    <textarea id="hx_illness" name="hx_illness" placeholder="Duration, progression, triggering factors"></textarea>
                </div>

                <div class="form-group">
                    <label for="past_psych_hx">Past Psychiatric History</label>
                    <textarea id="past_psych_hx" name="past_psych_hx" placeholder="Previous psychiatric treatments, hospitalizations"></textarea>
                </div>

                <div class="form-group">
                    <label for="past_med_hx">Past Medical History</label>
                    <textarea id="past_med_hx" name="past_med_hx" placeholder="Medical conditions, surgeries, medications"></textarea>
                </div>
            </div>

            <!-- Substance Use History -->
            <div class="form-section">
                <div class="section-title">3. SUBSTANCE USE HISTORY</div>

                <div class="form-group">
                    <label for="sub_use_hx">Substance Use Details</label>
                    <textarea id="sub_use_hx" name="sub_use_hx" placeholder="Substances used, frequency, duration, route of administration"></textarea>
                </div>

                <div class="form-group">
                    <label for="fam_hx">Family History of Substance Use</label>
                    <textarea id="fam_hx" name="fam_hx" placeholder="Family history of substance use or mental health issues"></textarea>
                </div>
            </div>

            <!-- Social History Section -->
            <div class="form-section">
                <div class="section-title">4. SOCIAL HISTORY</div>

                <div class="form-group">
                    <label for="intake_date" class="required">a. Date of intake interview (dd/mm/yyyy)</label>
                    <input type="date" id="intake_date" name="intake_date" required>
                </div>

                <div class="form-group">
                    <label class="required">b. Marital status?</label>
                    <div class="checkbox-group">
                        <?php
                        $marital_statuses = ["Married", "Remarried", "Widowed", "Separated", "Divorced", "Never Married", "Other"];
                        foreach($marital_statuses as $status):
                        ?>
                        <div class="checkbox-item">
                            <input type="radio" id="marital_<?php echo strtolower(str_replace(' ', '_', $status)); ?>" name="marital_status" value="<?php echo $status; ?>" required>
                            <label for="marital_<?php echo strtolower(str_replace(' ', '_', $status)); ?>"><?php echo $status; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="text" id="marital_other_specify" name="marital_other_specify" placeholder="Specify other" class="hidden">
                </div>

                <div class="form-group">
                    <label class="required">c. How would you describe your usual living arrangements (past 3 months)?</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" id="stable_arrangement" name="living_arrangements" value="stable" required>
                            <label for="stable_arrangement">Stable arrangement</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="no_stable_arrangement" name="living_arrangements" value="no_stable" required>
                            <label for="no_stable_arrangement">No stable arrangement</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>d. Usual living arrangements in the past 3 months? (tick all that apply)</label>
                    <div class="checkbox-group">
                        <?php
                        $living_options = [
                            "Family house", "Friend's house", "Streets", "Abandoned buildings",
                            "Public areas", "Parks", "Bus stations", "Tunnels", "Injecting site", "Other"
                        ];
                        foreach($living_options as $option):
                        ?>
                        <div class="checkbox-item">
                            <input type="checkbox" id="living_<?php echo strtolower(str_replace([' ', "'"], ['_', ''], $option)); ?>" name="living_arrangements_detail[]" value="<?php echo $option; ?>">
                            <label for="living_<?php echo strtolower(str_replace([' ', "'"], ['_', ''], $option)); ?>"><?php echo $option; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="text" id="living_other_specify" name="living_other_specify" placeholder="Specify other" class="hidden">
                </div>

                <div class="form-group">
                    <label class="required">e. Have you ever been treated for Mental illness or substance use disorder before?</label>
                    <div class="yes-no-group">
                        <div class="yes-no-item">
                            <input type="radio" id="treatment_yes" name="previous_treatment" value="yes" required>
                            <label for="treatment_yes">Yes</label>
                        </div>
                        <div class="yes-no-item">
                            <input type="radio" id="treatment_no" name="previous_treatment" value="no" required>
                            <label for="treatment_no">No</label>
                        </div>
                    </div>
                    <input type="text" id="treatment_specify" name="treatment_specify" placeholder="If Yes, Specify" class="hidden">
                </div>
            </div>

            <!-- Sexual History Section -->
            <div class="form-section">
                <div class="section-title">5. SEXUAL HISTORY</div>

                <div class="form-group">
                    <label class="required">a. Have you been sexually active in the last 3 months?</label>
                    <div class="yes-no-group">
                        <div class="yes-no-item">
                            <input type="radio" id="sex_active_yes" name="sexually_active" value="yes" required>
                            <label for="sex_active_yes">Yes</label>
                        </div>
                        <div class="yes-no-item">
                            <input type="radio" id="sex_active_no" name="sexually_active" value="no" required>
                            <label for="sex_active_no">No</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="required">b. Number of sexual partners in the last 3 months?</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" id="single_partner" name="sexual_partners" value="single" required>
                            <label for="single_partner">Single</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="multiple_partners" name="sexual_partners" value="multiple" required>
                            <label for="multiple_partners">Multiple</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="required">c. Have you had unprotected sex in the last 3 months?</label>
                    <div class="yes-no-group">
                        <div class="yes-no-item">
                            <input type="radio" id="unprotected_yes" name="unprotected_sex" value="yes" required>
                            <label for="unprotected_yes">Yes</label>
                        </div>
                        <div class="yes-no-item">
                            <input type="radio" id="unprotected_no" name="unprotected_sex" value="no" required>
                            <label for="unprotected_no">No</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Education and Occupational History Section -->
            <div class="form-section">
                <div class="section-title">6. EDUCATION AND OCCUPATIONAL HISTORY</div>

                <div class="form-group">
                    <label for="edu_hx" class="required">a. What is your highest level of education</label>
                    <div class="checkbox-group">
                        <?php
                        $education_levels = ["None", "Primary", "Secondary", "Post-secondary", "Other"];
                        foreach($education_levels as $level):
                        ?>
                        <div class="checkbox-item">
                            <input type="radio" id="education_<?php echo strtolower($level); ?>" name="education_level" value="<?php echo $level; ?>" required>
                            <label for="education_<?php echo strtolower($level); ?>"><?php echo $level; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="text" id="education_other_specify" name="education_other_specify" placeholder="Specify other" class="hidden">
                </div>

                <div class="form-group">
                    <label class="required">b. Do you have a source of income?</label>
                    <div class="yes-no-group">
                        <div class="yes-no-item">
                            <input type="radio" id="income_yes" name="has_income" value="yes" required>
                            <label for="income_yes">Yes</label>
                        </div>
                        <div class="yes-no-item">
                            <input type="radio" id="income_no" name="has_income" value="no" required>
                            <label for="income_no">No</label>
                        </div>
                    </div>
                    <input type="text" id="income_specify" name="income_specify" placeholder="If Yes, Specify" class="hidden">
                </div>

                <div class="form-group">
                    <label for="occup_hx" class="required">c. What is your employment status in the past 12 months?</label>
                    <div class="checkbox-group">
                        <?php
                        $employment_statuses = [
                            "Employed Full time (40hrs)", "Employment Part time (<40hrs)",
                            "Unemployed (Currently looking for work)", "Unemployed (Currently not looking for work)",
                            "Student", "Retired", "Self-employed"
                        ];
                        foreach($employment_statuses as $status):
                        ?>
                        <div class="checkbox-item">
                            <input type="radio" id="employment_<?php echo strtolower(str_replace([' ', '(', ')', '<'], ['_', '', '', ''], $status)); ?>" name="employment_status" value="<?php echo $status; ?>" required>
                            <label for="employment_<?php echo strtolower(str_replace([' ', '(', ')', '<'], ['_', '', '', ''], $status)); ?>"><?php echo $status; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="required">d. Have you ever Missed work because of your drug use?</label>
                    <div class="yes-no-group">
                        <div class="yes-no-item">
                            <input type="radio" id="missed_work_yes" name="missed_work" value="yes" required>
                            <label for="missed_work_yes">Yes</label>
                        </div>
                        <div class="yes-no-item">
                            <input type="radio" id="missed_work_no" name="missed_work" value="no" required>
                            <label for="missed_work_no">No</label>
                        </div>
                        <div class="yes-no-item">
                            <input type="radio" id="missed_work_na" name="missed_work" value="na" required>
                            <label for="missed_work_na">N/A</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="required">e. Have you ever been fired from your work because of your drug use?</label>
                    <div class="yes-no-group">
                        <div class="yes-no-item">
                            <input type="radio" id="fired_yes" name="fired_work" value="yes" required>
                            <label for="fired_yes">Yes</label>
                        </div>
                        <div class="yes-no-item">
                            <input type="radio" id="fired_no" name="fired_work" value="no" required>
                            <label for="fired_no">No</label>
                        </div>
                        <div class="yes-no-item">
                            <input type="radio" id="fired_na" name="fired_work" value="na" required>
                            <label for="fired_na">N/A</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Family Relationships Section -->
            <div class="form-section">
                <div class="section-title">7. FAMILY RELATIONSHIPS</div>

                <div class="form-group">
                    <label class="required">How is your relationship with your family?</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" id="family_poor" name="family_relationship" value="poor" required>
                            <label for="family_poor">Poor</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="family_fair" name="family_relationship" value="fair" required>
                            <label for="family_fair">Fair</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="family_good" name="family_relationship" value="good" required>
                            <label for="family_good">Good</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Family and Social Support Section -->
            <div class="form-section">
                <div class="section-title">8. FAMILY AND SOCIAL SUPPORT</div>

                <div class="form-group">
                    <label class="required">a. Do you have a person(s) who regularly depends on you for food and shelter?</label>
                    <div class="yes-no-group">
                        <div class="yes-no-item">
                            <input type="radio" id="dependents_yes" name="has_dependents" value="yes" required>
                            <label for="dependents_yes">Yes</label>
                        </div>
                        <div class="yes-no-item">
                            <input type="radio" id="dependents_no" name="has_dependents" value="no" required>
                            <label for="dependents_no">No</label>
                        </div>
                    </div>
                </div>

                <div id="dependents_details" class="hidden">
                    <div class="form-group">
                        <label>b. If yes, who are the persons that depend on you?</label>
                        <div class="checkbox-group">
                            <?php
                            $dependent_options = ["Spouse", "Children", "Parent", "Sibling", "Other"];
                            foreach($dependent_options as $option):
                            ?>
                            <div class="checkbox-item">
                                <input type="checkbox" id="dependent_<?php echo strtolower($option); ?>" name="dependents[]" value="<?php echo $option; ?>">
                                <label for="dependent_<?php echo strtolower($option); ?>"><?php echo $option; ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="text" id="dependent_other_specify" name="dependent_other_specify" placeholder="Specify other" class="hidden">
                    </div>
                </div>

                <div class="form-group">
                    <label class="required">c. Does someone contribute to your support in any way?</label>
                    <div class="yes-no-group">
                        <div class="yes-no-item">
                            <input type="radio" id="support_yes" name="has_support" value="yes" required>
                            <label for="support_yes">Yes</label>
                        </div>
                        <div class="yes-no-item">
                            <input type="radio" id="support_no" name="has_support" value="no" required>
                            <label for="support_no">No</label>
                        </div>
                    </div>
                </div>

                <div id="support_table_container" class="hidden">
                    <div class="form-group">
                        <label>d. If yes, please specify the type of support received:</label>
                        <table>
                            <thead>
                                <tr>
                                    <th>Relationship</th>
                                    <th>Cash</th>
                                    <th>Food</th>
                                    <th>Shelter</th>
                                    <th>Psychological</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $support_relationships = [
                                    "Spouse/Partner", "Mother", "Father", "Brother", "Sister",
                                    "Child", "Peer educator/Outreach worker", "Other"
                                ];
                                foreach($support_relationships as $relationship):
                                ?>
                                <tr>
                                    <td><?php echo $relationship; ?></td>
                                    <td><input type="checkbox" name="support_cash_<?php echo strtolower(str_replace(['/', ' '], ['_', '_'], $relationship)); ?>" value="1"></td>
                                    <td><input type="checkbox" name="support_food_<?php echo strtolower(str_replace(['/', ' '], ['_', '_'], $relationship)); ?>" value="1"></td>
                                    <td><input type="checkbox" name="support_shelter_<?php echo strtolower(str_replace(['/', ' '], ['_', '_'], $relationship)); ?>" value="1"></td>
                                    <td><input type="checkbox" name="support_psychological_<?php echo strtolower(str_replace(['/', ' '], ['_', '_'], $relationship)); ?>" value="1"></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <input type="text" id="support_other_specify" name="support_other_specify" placeholder="Specify other relationship" class="hidden">
                    </div>
                </div>
            </div>

            <!-- Additional History Sections -->
            <div class="form-section">
                <div class="section-title">9. ADDITIONAL HISTORY</div>

                <div class="form-group">
                    <label for="ante_hx">Antenatal and Birth History</label>
                    <textarea id="ante_hx" name="ante_hx" placeholder="If applicable"></textarea>
                </div>

                <div class="form-group">
                    <label for="dev_hx">Developmental History</label>
                    <textarea id="dev_hx" name="dev_hx" placeholder="Developmental milestones, childhood history"></textarea>
                </div>

                <div class="form-group">
                    <label for="child_hx">Childhood History</label>
                    <textarea id="child_hx" name="child_hx" placeholder="Significant childhood events"></textarea>
                </div>
            </div>

            <!-- GBV Section -->
            <div class="form-section">
                <div class="section-title">10. GENDER-BASED VIOLENCE (GBV)</div>

                <div class="form-group">
                    <label class="required">a. Have you experienced any form of gender-based violence?</label>
                    <div class="yes-no-group">
                        <div class="yes-no-item">
                            <input type="radio" id="gbv_yes" name="gbv_experience" value="yes" required>
                            <label for="gbv_yes">Yes</label>
                        </div>
                        <div class="yes-no-item">
                            <input type="radio" id="gbv_no" name="gbv_experience" value="no" required>
                            <label for="gbv_no">No</label>
                        </div>
                    </div>
                </div>

                <div id="gbv_details" class="hidden">
                    <div class="form-group">
                        <label>b. If yes, please describe:</label>
                        <textarea id="gbv_description" name="gbv_description" placeholder="Describe the GBV experience, when it occurred, and any actions taken"></textarea>
                    </div>

                    <div class="form-group">
                        <label>c. Was this reported to authorities?</label>
                        <div class="yes-no-group">
                            <div class="yes-no-item">
                                <input type="radio" id="gbv_reported_yes" name="gbv_reported" value="yes">
                                <label for="gbv_reported_yes">Yes</label>
                            </div>
                            <div class="yes-no-item">
                                <input type="radio" id="gbv_reported_no" name="gbv_reported" value="no">
                                <label for="gbv_reported_no">No</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>d. Was medical attention sought?</label>
                        <div class="yes-no-group">
                            <div class="yes-no-item">
                                <input type="radio" id="gbv_medical_yes" name="gbv_medical" value="yes">
                                <label for="gbv_medical_yes">Yes</label>
                            </div>
                            <div class="yes-no-item">
                                <input type="radio" id="gbv_medical_no" name="gbv_medical" value="no">
                                <label for="gbv_medical_no">No</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-note">
                    <strong>Note:</strong> Use national GBV assessment tools for comprehensive evaluation. Refer to appropriate services if needed.
                </div>
            </div>

            <!-- Legal and Criminal status Section -->
            <div class="form-section">
                <div class="section-title">11. LEGAL/CRIMINAL STATUS</div>

                <div class="form-group">
                    <label class="required">a. Have you committed and/or been arrested for drug-related cases in the last 30 days?</label>
                    <div class="yes-no-group">
                        <div class="yes-no-item">
                            <input type="radio" id="case_yes" name="has_case" value="yes" required>
                            <label for="case_yes">Yes</label>
                        </div>
                        <div class="yes-no-item">
                            <input type="radio" id="case_no" name="has_case" value="no" required>
                            <label for="case_no">No</label>
                        </div>
                    </div>
                </div>

                <div id="case_details" class="hidden">
                    <div class="form-group">
                        <label>b. If yes, what type of case?</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="case_committed" name="case_type[]" value="committed">
                                <label for="case_committed">Committed</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="case_arrested" name="case_type[]" value="arrested">
                                <label for="case_arrested">Arrested</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>c. Specific charges/cases:</label>
                        <table>
                            <thead>
                                <tr>
                                    <th>Type of Offense</th>
                                    <th>Committed</th>
                                    <th>Arrested</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $legal_cases = [
                                    "Shoplifting/vandalism", "Drunk and disorderly", "Drug possession",
                                    "Drug peddling", "Weapons offense", "Burglary", "Robbery",
                                    "Assault", "Rape", "Murder", "Sex work", "Fraud/forgery"
                                ];
                                foreach($legal_cases as $case):
                                $case_id = strtolower(str_replace(['/', ' '], ['_', '_'], $case));
                                ?>
                                <tr>
                                    <td><?php echo $case; ?></td>
                                    <td><input type="checkbox" name="case_committed_<?php echo $case_id; ?>" value="1"></td>
                                    <td><input type="checkbox" name="case_arrested_<?php echo $case_id; ?>" value="1"></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <input type="text" id="case_other_specify" name="case_other_specify" placeholder="Specify other offense" class="hidden">
                    </div>
                </div>
            </div>

            <!-- Clinical Assessment Sections -->
            <div class="form-section">
                <div class="section-title">12. CLINICAL ASSESSMENT FINDINGS</div>

                <div class="form-group">
                    <label for="premord_hx">Premorbid Personality</label>
                    <textarea id="premord_hx" name="premord_hx" placeholder="Personality before illness"></textarea>
                </div>

                <div class="form-group">
                    <label for="forens_hx">Forensic History</label>
                    <textarea id="forens_hx" name="forens_hx" placeholder="Legal history beyond past 30 days"></textarea>
                </div>

                <div class="form-group">
                    <label for="phys_exam">Physical Examination Findings</label>
                    <textarea id="phys_exam" name="phys_exam" placeholder="Physical health assessment"></textarea>
                </div>

                <div class="form-group">
                    <label for="mental_exam">Mental Status Examination</label>
                    <textarea id="mental_exam" name="mental_exam" placeholder="Mental health assessment findings"></textarea>
                </div>

                <div class="form-group">
                    <label for="diagnosis">Diagnosis</label>
                    <textarea id="diagnosis" name="diagnosis" placeholder="Clinical diagnosis"></textarea>
                </div>

                <div class="form-group">
                    <label for="mgt_plan">Management Plan</label>
                    <textarea id="mgt_plan" name="mgt_plan" placeholder="Treatment and management plan"></textarea>
                </div>
            </div>

            <!-- Therapist Information -->
            <div class="form-section">
                <div class="section-title">THERAPIST INFORMATION</div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="therapist_name" class="required">Therapist Name</label>
                            <input type="text" id="therapist_name" name="therapist_name" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="service_date" class="required">Date of Service</label>
                            <input type="date" id="service_date" name="service_date" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="rx_supporter">Treatment Supporter/Contact Person</label>
                    <input type="text" id="rx_supporter" name="rx_supporter" placeholder="Name and contact of treatment supporter">
                </div>

                <div class="form-group">
                    <label for="referral">Referral Information</label>
                    <textarea id="referral" name="referral" placeholder="Referral source and reasons"></textarea>
                </div>
            </div>

            <button type="submit" class="btn-submit">Submit Form</button>
        </form>
    </div>

    <script>
        // JavaScript for dynamic form behavior
        document.addEventListener('DOMContentLoaded', function() {
            // Show/hide "Other" text inputs based on selection
            const otherInputs = [
                { radio: 'other_sex', input: 'other_sex_specify' },
                { radio: 'marital_other', input: 'marital_other_specify' },
                { radio: 'education_other', input: 'education_other_specify' },
                { checkbox: 'dependent_other', input: 'dependent_other_specify' },
                { checkbox: 'living_other', input: 'living_other_specify' },
                { checkbox: 'support_other', input: 'support_other_specify' }
            ];

            otherInputs.forEach(item => {
                const element = document.getElementById(item.radio || item.checkbox);
                const input = document.getElementById(item.input);

                if (element && input) {
                    if (item.radio) {
                        element.addEventListener('change', function() {
                            input.classList.toggle('hidden', !this.checked);
                        });
                    } else {
                        element.addEventListener('change', function() {
                            input.classList.toggle('hidden', !this.checked);
                        });
                    }
                }
            });

            // Show/hide specify fields based on yes/no selections
            const toggleFields = [
                { yes: 'treatment_yes', no: 'treatment_no', target: 'treatment_specify' },
                { yes: 'income_yes', no: 'income_no', target: 'income_specify' },
                { yes: 'dependents_yes', no: 'dependents_no', target: 'dependents_details' },
                { yes: 'support_yes', no: 'support_no', target: 'support_table_container' },
                { yes: 'gbv_yes', no: 'gbv_no', target: 'gbv_details' },
                { yes: 'case_yes', no: 'case_no', target: 'case_details' }
            ];

            toggleFields.forEach(field => {
                const yesRadio = document.getElementById(field.yes);
                const noRadio = document.getElementById(field.no);
                const target = document.getElementById(field.target);

                if (yesRadio && noRadio && target) {
                    yesRadio.addEventListener('change', function() {
                        target.classList.toggle('hidden', !this.checked);
                    });

                    noRadio.addEventListener('change', function() {
                        target.classList.toggle('hidden', this.checked);
                    });

                    // Initialize hidden state
                    target.classList.toggle('hidden', !yesRadio.checked);
                }
            });

            // Additional dynamic behaviors
            const dependentOther = document.getElementById('dependent_other');
            const dependentOtherSpecify = document.getElementById('dependent_other_specify');

            if (dependentOther && dependentOtherSpecify) {
                dependentOther.addEventListener('change', function() {
                    dependentOtherSpecify.classList.toggle('hidden', !this.checked);
                });
            }
        });
    </script>
</body>
</html>