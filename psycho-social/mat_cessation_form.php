<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAT Cessation Assessment Checklist (Form 2E)</title>
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
        /* Add specific styling for Form 2E if needed, e.g., wider question column */
        .cessation-table th:nth-child(2), .cessation-table td:nth-child(2) {
            width: 70%;
            text-align: left;
        }
        .cessation-table th:nth-child(3), .cessation-table th:nth-child(4) {
            width: 15%;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-header">
            <img src="../assets/images/Government of Kenya.png" width="80" height="60" alt="">
            <div><h4>MEDICALLY ASSISTED THERAPY ASSESSMENT CHECKLIST FOR CESSATION (FORM 2E)</h4></div>
                <p>FORM 2E VER. APR. 2022</p>

        </div>

        <h2>General Information</h2>
        <form action="process_form2e.php" method="POST">
            <div class="info-grid">
                <div class="info-group"><label for="name">Name:</label><input type="text" id="name" name="name" required></div>
                <div class="info-group"><label for="age">Age:</label><input type="number" id="age" name="age" min="0" required></div>
                <div class="info-group"><label for="mat_id">MAT ID NO.:</label><input type="text" id="mat_id" name="mat_id"></div>
                <div class="info-group"><label for="sex">Sex:</label>
                    <select id="sex" name="sex">
                        <option value="">Select</option>
                        <option value="1">Male</option>
                        <option value="2">Female</option>
                    </select>
                </div>
                <div class="info-group"><label for="dob">Date of Birth:</label><input type="date" id="dob" name="dob"></div>
                <div class="info-group"><label for="enroll_date">MAT Enrollment Date:</label><input type="date" id="enroll_date" name="enroll_date"></div>
                <div class="info-group"><label for="supporter_name">Treatment Supporter's Name:</label><input type="text" id="supporter_name" name="supporter_name"></div>
                <div class="info-group"><label for="supporter_tel">Telephone No.:</label><input type="tel" id="supporter_tel" name="supporter_tel"></div>
                <div class="info-group"><label for="current_dose">Current MAT Dose (mg):</label><input type="number" id="current_dose" name="current_dose" step="any"></div>
            </div>

            <hr>

            <h2>Cessation Readiness Questions</h2>
            <p class="instruction">Select the response for each question. **Yes = 1, No = 0**</p>

            <table class="phq9-table cessation-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Question</th>
                        <th>Yes (1)</th>
                        <th>No (0)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Have you been abstaining from drugs of addiction, such as heroin, cannabis, benzodiazepines, etc.?<br>
                            If yes, how long? <input type="number" name="q1_months" style="width: 80px;" min="0"> months (Confirm with Lab tracking form)
                        </td>
                        <td><input type="radio" name="q1" value="1" required></td>
                        <td><input type="radio" name="q1" value="0"></td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Do you have a supportive family or non-drug using friends that you spend time with?</td>
                        <td><input type="radio" name="q2" value="1" required></td>
                        <td><input type="radio" name="q2" value="0"></td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>Do you have a stable living arrangement?</td>
                        <td><input type="radio" name="q3" value="1" required></td>
                        <td><input type="radio" name="q3" value="0"></td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>Is the client in good mental and physical health?</td>
                        <td><input type="radio" name="q4" value="1" required></td>
                        <td><input type="radio" name="q4" value="0"></td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>Has the client been on methadone at least for the past 12 months without interruption?</td>
                        <td><input type="radio" name="q5" value="1" required></td>
                        <td><input type="radio" name="q5" value="0"></td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td>Do you really want to get off methadone, buprenorphine or naltrexone?</td>
                        <td><input type="radio" name="q6" value="1" required></td>
                        <td><input type="radio" name="q6" value="0"></td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td>Do you have a compelling reason to get off methadone, buprenorphine or naltrexone? (logistical constraints/Travel/Job/Ramadhan/Dissatisfaction/other)</td>
                        <td><input type="radio" name="q7" value="1" required></td>
                        <td><input type="radio" name="q7" value="0"></td>
                    </tr>
                    <tr>
                        <td>8</td>
                        <td>Do you have a main motivation or reason for wanting to get off methadone, buprenorphine or naltrexone?<br>
                            If yes, what is it? <input type="text" name="q8_motivation" style="width: 70%;"> (Feels recovered/logistical constraints/Travel/Job/ Ramadhan/Dissatisfaction/other)
                        </td>
                        <td><input type="radio" name="q8" value="1" required></td>
                        <td><input type="radio" name="q8" value="0"></td>
                    </tr>
                    <tr>
                        <td>9</td>
                        <td>Do you think you are able to cope with stressful situations without using drugs?</td>
                        <td><input type="radio" name="q9" value="1" required></td>
                        <td><input type="radio" name="q9" value="0"></td>
                    </tr>
                    <tr>
                        <td>10</td>
                        <td>Are you staying away from former drug using friends?</td>
                        <td><input type="radio" name="q10" value="1" required></td>
                        <td><input type="radio" name="q10" value="0"></td>
                    </tr>
                    <tr>
                        <td>11</td>
                        <td>Do you live in a neighborhood that is not close to drug using sites?</td>
                        <td><input type="radio" name="q11" value="1" required></td>
                        <td><input type="radio" name="q11" value="0"></td>
                    </tr>
                    <tr>
                        <td>12</td>
                        <td>Have you stopped engaging in criminal behavior?</td>
                        <td><input type="radio" name="q12" value="1" required></td>
                        <td><input type="radio" name="q12" value="0"></td>
                    </tr>
                    <tr>
                        <td>13</td>
                        <td>Do you have a stable source of income?</td>
                        <td><input type="radio" name="q13" value="1" required></td>
                        <td><input type="radio" name="q13" value="0"></td>
                    </tr>
                    <tr>
                        <td>14</td>
                        <td>Have you been on the same methadone, buprenorphine or naltrexone dose for the past 3 months?</td>
                        <td><input type="radio" name="q14" value="1" required></td>
                        <td><input type="radio" name="q14" value="0"></td>
                    </tr>
                    <tr>
                        <td>15</td>
                        <td>Have you been receiving psychosocial counseling regularly at a MAT clinic or DIC?</td>
                        <td><input type="radio" name="q15" value="1" required></td>
                        <td><input type="radio" name="q15" value="0"></td>
                    </tr>
                    <tr>
                        <td>16</td>
                        <td>Does your counselor think you are ready to taper off methadone, buprenorphine or naltrexone?</td>
                        <td><input type="radio" name="q16" value="1" required></td>
                        <td><input type="radio" name="q16" value="0"></td>
                    </tr>
                    <tr>
                        <td>17</td>
                        <td>Have your urine drug screening results over the past 6 months been negative for heroin and other drugs? (Confirm with Lab tracking form)</td>
                        <td><input type="radio" name="q17" value="1" required></td>
                        <td><input type="radio" name="q17" value="0"></td>
                    </tr>
                    <tr>
                        <td>18</td>
                        <td>Do you have friends or family who would be helpful and supportive during weaning?</td>
                        <td><input type="radio" name="q18" value="1" required></td>
                        <td><input type="radio" name="q18" value="0"></td>
                    </tr>
                    <tr>
                        <td>19</td>
                        <td>Would you ask for help if you were unable to cope with the weaning process?<br>
                            If yes, whom would you first go to for help? <input type="text" name="q19_contact" style="width: 70%;"> (Clinician/counselor/spouse/sibling/friend/peer MAT client/ORW/other)
                        </td>
                        <td><input type="radio" name="q19" value="1" required></td>
                        <td><input type="radio" name="q19" value="0"></td>
                    </tr>
                </tbody>
            </table>

            <button type="submit">Calculate Overall Score</button>
        </form>
    </div>
</body>
</html>

