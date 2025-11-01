
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Quick Screening Tool (Form 2B)</title>
    <link rel="stylesheet" href="style.css"> </head>
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
<body>
    <div class="container">
        <div class="form-header">
            <img src="../assets/images/Government of Kenya.png" width="80" height="60" alt="">
            <div><h4>Community Quick Screening Tool for Mental Disorders (FORM 2B)</h4></div>
                <p>FORM 2B VER. APR. 2022</p>

        </div>

        <form action="process_qst.php" method="POST">
            <div class="info-group">
                <label for="client_id">Client ID / Name:</label>
                <input type="text" id="client_id" name="client_id" required>
            </div>
            <hr>

            <h2>Assessment Questions</h2>

            <div class="question-group">
                <p class="instruction"><strong>1.</strong> Over the past 2 weeks have you been bothered by these problems?</p>
                <table class="phq9-table" style="font-size: 0.9em;">
                    <thead>
                        <tr>
                            <th>Problem</th>
                            <th>Not at all (0)</th>
                            <th>0-4 days (1)</th>
                            <th>5-9 days (2)</th>
                            <th>10-14 days (3)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1.a. Feeling nervous, anxious, or on edge</td>
                            <td><input type="radio" name="q1a" value="0" required></td>
                            <td><input type="radio" name="q1a" value="1"></td>
                            <td><input type="radio" name="q1a" value="2"></td>
                            <td><input type="radio" name="q1a" value="3"></td>
                        </tr>
                        <tr>
                            <td>1.b. Not being able to control worrying</td>
                            <td><input type="radio" name="q1b" value="0" required></td>
                            <td><input type="radio" name="q1b" value="1"></td>
                            <td><input type="radio" name="q1b" value="2"></td>
                            <td><input type="radio" name="q1b" value="3"></td>
                        </tr>
                        <tr>
                            <td>1.c. Feeling down, depressed, or hopeless</td>
                            <td><input type="radio" name="q1c" value="0" required></td>
                            <td><input type="radio" name="q1c" value="1"></td>
                            <td><input type="radio" name="q1c" value="2"></td>
                            <td><input type="radio" name="q1c" value="3"></td>
                        </tr>
                        <tr>
                            <td>1.d. Little interest or pleasure in doing things</td>
                            <td><input type="radio" name="q1d" value="0" required></td>
                            <td><input type="radio" name="q1d" value="1"></td>
                            <td><input type="radio" name="q1d" value="2"></td>
                            <td><input type="radio" name="q1d" value="3"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="question-group">
                <p><strong>2.</strong> Over the past 2 weeks, the thought of harming myself has occurred to you?</p>
                <label><input type="radio" name="q2" value="0" required> No (0)</label>
                <label><input type="radio" name="q2" value="2"> Yes (2)</label>
            </div>
            <div class="question-group">
                <p><strong>3.</strong> In the past, did you ever attempt suicide?</p>
                <label><input type="radio" name="q3" value="0" required> No (0)</label>
                <label><input type="radio" name="q3" value="2"> Yes (2)</label>
            </div>

            <div class="question-group">
                <p><strong>4.</strong> How often do you have drugs/ a drink containing alcohol?</p>
                <label><input type="radio" name="q4" value="0" required> Not at all (0)</label>
                <label><input type="radio" name="q4" value="1"> Monthly or less (1)</label>
                <label><input type="radio" name="q4" value="2"> 2-4 times a month (2)</label>
                <label><input type="radio" name="q4" value="3"> 2-3 times a month (3)</label>
                <label><input type="radio" name="q4" value="4"> 4 or more times a week (4)</label>
            </div>
            <div class="question-group">
                <p><strong>5.</strong> How many drugs/ drinks containing alcohol do you have on a typical day when you take or drink?</p>
                <label><input type="radio" name="q5" value="0" required> 1 or 2 days (0)</label>
                <label><input type="radio" name="q5" value="1"> 3 or 4 days (1)</label>
                <label><input type="radio" name="q5" value="2"> 5 or 6 days (2)</label>
                <label><input type="radio" name="q5" value="3"> 7 or 9 days (3)</label>
                <label><input type="radio" name="q5" value="4"> 10 or more days (4)</label>
            </div>

            <div class="question-group">
                <p><strong>6.</strong> Have you been exposed (experienced, witnessed) to a potentially traumatic event? This includes acts of violence, an accident or injury to yourself/others</p>
                <label><input type="radio" name="q6" value="0" required> No (0)</label>
                <label><input type="radio" name="q6" value="2"> Yes (2)</label>
            </div>
            <div class="question-group">
                <p><strong>7.</strong> How much time has passed since the traumatic event? (Only score if Q6 is Yes)</p>
                <label><input type="radio" name="q7" value="0" required> 0-3 days (0)</label>
                <label><input type="radio" name="q7" value="2"> 3 days-1 month (2)</label>
                <label><input type="radio" name="q7" value="4"> More than 1 month (4)</label>
            </div>

            <div class="question-group">
                <p><strong>8.</strong> Do you feel your gender identity is at odds with your biological sex?</p>
                <label><input type="radio" name="q8" value="0" required> No (0)</label>
                <label><input type="radio" name="q8" value="2"> Yes (2)</label>
            </div>
            <div class="question-group">
                <p><strong>9.</strong> Do you wish that the people in your life would treat you the same Way they treat males (if your assigned gender is female) or female (if your assigned gender is male)?</p>
                <label><input type="radio" name="q9" value="0" required> No (0)</label>
                <label><input type="radio" name="q9" value="2"> Yes (2)</label>
            </div>


            <button type="submit">Calculate Global Score and Screen</button>
        </form>
    </div>
</body>
</html>