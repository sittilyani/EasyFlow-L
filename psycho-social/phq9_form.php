<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Health Questionnaire-9 (PHQ-9)</title>
    <link rel="stylesheet" href="style.css">
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f9;
                color: #333;
                padding: 20px;
            }

            .container {
                max-width:70%;
                margin: 0 auto;
                background: #fff;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
            .form-header {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                text-align: center;
                margin-bottom: 30px;
            }

            h1, h2 {
                text-align: center;
                color: #0056b3;
            }

            .info-group {
                margin-bottom: 15px;
            }

            .info-group label {
                display: block;
                font-weight: bold;
                margin-bottom: 5px;
            }

            .info-group input[type="text"],
            .info-group input[type="number"] {
                width: 100%;
                padding: 8px;
                border: 1px solid #ccc;
                border-radius: 4px;
                box-sizing: border-box; /* Ensures padding doesn't affect total width */
            }

            hr {
                border: 0;
                height: 1px;
                background: #ccc;
                margin: 20px 0;
            }

            .instruction {
                font-style: italic;
                margin-bottom: 15px;
            }

            .gad7-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }

            .gad7-table th, .gad7-table td {
                border: 1px solid #ddd;
                padding: 10px;
                text-align: center;
            }

            .gad7-table th:first-child, .gad7-table td:first-child {
                text-align: left;
                width: 40%;
            }

            .gad7-table thead th {
                background-color: #e9e9e9;
                font-weight: bold;
            }

            .gad7-table td input[type="radio"] {
                transform: scale(1.5); /* Make radio buttons easier to click */
            }

            button[type="submit"] {
                display: block;
                width: 100%;
                padding: 10px;
                background-color: #007bff;
                color: white;
                border: none;
                border-radius: 4px;
                font-size: 1.1em;
                cursor: pointer;
                transition: background-color 0.3s ease;
            }

            button[type="submit"]:hover {
                background-color: #0056b3;
            }

        </style>
</head>
<body>
    <div class="container">
        <div class="form-header">
            <img src="../assets/images/Government of Kenya.png" width="80" height="60" alt="">
            <div><h4>Patient Health Questionnaire-9 (PHQ-9) for Depression Screening</h4></div>
                <p>FORM VER. APR. 2022</p>

        </div>

        <h2>General Information</h2>
        <form action="process_phq9.php" method="POST">
            <div class="social-demographic-grid">
                    <div class="section-column" style="grid-column: 1 / span 4;">
                        <h4>PERSONAL DETAILS</h4>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                            <div class="form-group">
                                <label for="visitDate">Date of consultation</label>
                                <input type="date" name="visitDate" class="readonly-input" readonly value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="clientName">Client Name</label>
                                <input type="text" name="clientName" class="readonly-input" readonly value="<?php echo isset($currentSettings['clientName']) ? htmlspecialchars($currentSettings['clientName']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="mat_id">MAT ID</label>
                                <input type="text" name="mat_id" class="readonly-input" readonly value="<?php echo isset($currentSettings['mat_id']) ? htmlspecialchars($currentSettings['mat_id']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="dob">Date of Birth</label>
                                <input type="text" name="dob" class="readonly-input" readonly value="<?php echo isset($currentSettings['dob']) ? htmlspecialchars($currentSettings['dob']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="age">Age</label>
                                <input type="text" name="age" class="readonly-input" readonly value="<?php echo isset($currentSettings['age']) ? htmlspecialchars($currentSettings['age']) : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label for="occupation">Occupation</label>
                                <input type="text" name="occupation">
                            </div>
                            <div class="form-group">
                                <label for="sex">Gender</label>
                                <input type="text" name="sex" class="readonly-input" readonly value="<?php echo isset($currentSettings['sex']) ? htmlspecialchars($currentSettings['sex']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="kp_type">KP Type</label>
                                <select id="kp_type" name="kp_type">
                                    <option value="FSW">FSW</option>
                                    <option value="MSM">MSM</option>
                                    <option value="MSW">MSW</option>
                                    <option value="PWID">PWID</option>
                                    <option value="TG Man">TG man</option>
                                    <option value="TG woman">TG woman</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="vp_type">KP Type</label>
                                <select id="vp_type" name="vp_type">
                                    <option value="Fisherfolk">Fisherfolk</option>
                                    <option value="Truckers">Truckers</option>
                                    <option value="Truckers">Discordant couples</option>
                                    <option value="Persons in prison">Persons in prison</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="kp_hotspot">KP Hotspot</label>
                                <input type="text" name="kp_hotspot">
                            </div>
                            <div class="form-group">
                                <label for="contact_phone">Contact</label>
                                <input type="text" name="contact_phone">
                            </div>

                        </div>
                    </div>
            <hr>

            <h2>PHQ-9 Assessment</h2>
            <p>
                Ask the patient the questions below for each of the nine (9) symptoms and select the responses
                for each question. After asking all questions, add the points for each column at the bottom.
                The total score is the sum of the column totals. Interpretation and management recommendations are
                provided at the bottom of the table.
            </p>
            <p class="instruction">Over the last **2 weeks**, how often have you been bothered by any of the following problems?</p>

            <table class="phq9-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Problem</th>
                        <th>Not at all (0)</th>
                        <th>Several days (1)</th>
                        <th>More than half the days (2)</th>
                        <th>Nearly every day (3)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Little interest or pleasure in doing things</td>
                        <td><input type="radio" name="q1" value="0" required></td>
                        <td><input type="radio" name="q1" value="1"></td>
                        <td><input type="radio" name="q1" value="2"></td>
                        <td><input type="radio" name="q1" value="3"></td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Feeling down, depressed, or hopeless</td>
                        <td><input type="radio" name="q2" value="0" required></td>
                        <td><input type="radio" name="q2" value="1"></td>
                        <td><input type="radio" name="q2" value="2"></td>
                        <td><input type="radio" name="q2" value="3"></td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>Trouble falling or staying asleep, or sleeping too much</td>
                        <td><input type="radio" name="q3" value="0" required></td>
                        <td><input type="radio" name="q3" value="1"></td>
                        <td><input type="radio" name="q3" value="2"></td>
                        <td><input type="radio" name="q3" value="3"></td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>Feeling tired or having little energy</td>
                        <td><input type="radio" name="q4" value="0" required></td>
                        <td><input type="radio" name="q4" value="1"></td>
                        <td><input type="radio" name="q4" value="2"></td>
                        <td><input type="radio" name="q4" value="3"></td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>Poor appetite or overeating</td>
                        <td><input type="radio" name="q5" value="0" required></td>
                        <td><input type="radio" name="q5" value="1"></td>
                        <td><input type="radio" name="q5" value="2"></td>
                        <td><input type="radio" name="q5" value="3"></td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td>Feeling bad about yourself, or that you are a failure, or that you have let yourself or your family down</td>
                        <td><input type="radio" name="q6" value="0" required></td>
                        <td><input type="radio" name="q6" value="1"></td>
                        <td><input type="radio" name="q6" value="2"></td>
                        <td><input type="radio" name="q6" value="3"></td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td>Trouble concentrating on things (e.g., reading the newspaper or listening to a radio programme)</td>
                        <td><input type="radio" name="q7" value="0" required></td>
                        <td><input type="radio" name="q7" value="1"></td>
                        <td><input type="radio" name="q7" value="2"></td>
                        <td><input type="radio" name="q7" value="3"></td>
                    </tr>
                    <tr>
                        <td>8</td>
                        <td>Moving or speaking so slowly that other people could have noticed. Or the opposite, being so fidgety or restless that you have been moving around a lot more than usual</td>
                        <td><input type="radio" name="q8" value="0" required></td>
                        <td><input type="radio" name="q8" value="1"></td>
                        <td><input type="radio" name="q8" value="2"></td>
                        <td><input type="radio" name="q8" value="3"></td>
                    </tr>
                    <tr>
                        <td>9</td>
                        <td>Thoughts that you would be better off dead or of hurting yourself in some way</td>
                        <td><input type="radio" name="q9" value="0" required></td>
                        <td><input type="radio" name="q9" value="1"></td>
                        <td><input type="radio" name="q9" value="2"></td>
                        <td><input type="radio" name="q9" value="3"></td>
                    </tr>
                </tbody>
            </table>

            <button type="submit">Calculate Score and Interpret</button>
        </form>
    </div>
</body>
</html>