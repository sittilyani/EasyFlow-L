<?php
// Use centralized session manager
include "../includes/session_manager.php";
requireLogin();

// Include configuration
include "../includes/config.php";

// Get user info from session manager functions
$userrole = getUserRole();
$user_id = getUserId();
$full_name = getUserFullName();
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>EasyFlow-L</title>
<link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.min.css" type="text/css">
<link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css" type="text/css">
<link rel="apple-touch-icon" sizes="180x180" href="../assets/favicons/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="../assets/favicons/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16x16.png">
<link rel="manifest" href="../assets/favicons/site.webmanifest">
<link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
<!--<link rel="stylesheet" href="../assets/css/sidenav.css" type="text/css">-->
<style>
       * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        display: flex;
        min-height: 100vh;
        background-color: #f5f7fa;
    }

    .sidenav {
        height: 100vh;
        width: 300px;
        position: fixed;
        z-index: 1;
        top: 0;
        left: 0;
        background: linear-gradient(135deg, #1a2a6c, #2b5876);
        overflow-x: hidden;
        padding-top: 70px;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    }

    .sidenav h2 {
        color: white;
        text-align: center;
        margin-bottom: 30px;
        padding: 0 15px;
        font-size: 22px;
    }

    .sidenav a {
        padding: 15px 25px;
        text-decoration: none;
        font-size: 16px;
        color: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        transition: 0.3s;
        border-left: 4px solid transparent;
    }

    .sidenav a i {
        margin-right: 12px;
        font-size: 18px;
        width: 25px;
        text-align: center;
    }

    .sidenav a:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
        border-left: 4px solid #4facfe;
    }

    .sidenav a.active {
        background-color: rgba(255, 255, 255, 0.15);
        color: white;
        border-left: 4px solid #4facfe;
    }

    .main {
        margin-left: 300px;
        padding: 30px;
        width: calc(100% - 250px);
        min-height: 100vh;
    }

    .content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e0e0e0;
    }

    .content-header h2 {
        color: #2c3e50;
        font-weight: 600;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .user-info span {
        font-size: 14px;
    }

    .current-time {
        background: #f8f9fa;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 14px;
    }

    .logout-btn {
        background: #dc3545;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 14px;
        transition: background 0.3s;
    }

    .logout-btn:hover {
        background: #c82333;
        color: white;
        text-decoration: none;
    }

    .content-area {
        background: white;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        min-height: 400px;
    }

    .welcome-message .sops{
                    background: white;
                    text-align: left;
                    padding: 30px;
                    font-size: 22px;
                    font-weight: bold;
                    line-height: 2;
            }

    @media screen and (max-width: 768px) {
        .sidenav {
            width: 100%;
            height: auto;
            position: relative;
            padding-top: 20px;
        }

        .sidenav a {
            float: left;
            padding: 15px;
        }

        .main {
            margin-left: 0;
            width: 100%;
        }

        .content-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .user-info {
            flex-wrap: wrap;
        }
        /* Timeout warning styling */
    .timeout-warning {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 5px;
        padding: 20px;
        z-index: 1000;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        text-align: center;
    }

    .timeout-warning h4 {
        margin-top: 0;
        color: #856404;
    }

    .timeout-warning p {
        margin-bottom: 15px;
    }

    .timeout-warning button {
        background-color: #f0ad4e;
        border: none;
        padding: 8px 15px;
        border-radius: 4px;
        color: #fff;
        cursor: pointer;
    }

}
/* Gear icon styling */
    .settings-icon {
        float: center;
        margin-top: 5px;
        font-size: 26px;
        color: #FFFFFF;
    }

    /* Home link styling to differentiate it */
    .home-link {
        background-color: #CC0000;
        font-weight: bold;
    }




</style>
</head>
<body>

<!-- Timeout Warning Modal -->
<div id="timeout-warning" class="timeout-warning">
    <h4><i class="fa fa-exclamation-triangle"></i> Session Timeout Warning</h4>
    <p>Your session will expire in <span id="countdown">60</span> seconds due to inactivity.</p>
    <button onclick="continueSession()">Continue Session</button>
</div>

<div class="sidenav">
    <h2>
        <i class="fa fa-pills"></i><br>
        Pharmacy processes
    </h2>

    <!-- Home link - will navigate away from this page -->
                <a href="../dashboard/dashboard.php" class="home-link">
                    <i class="fa fa-home"></i>Home </a>
                <a href="../pharmacy/dispensing_pump.php" target="contentFrame" class="nav-link" style="background: yellow; color: #000000; margin-top: 10px;">
                    <i class="fa fa-pump-medical"></i>Dispense with Pump</a>
                <a href="../pharmacy/pump_reservoir.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-hourglass-half"></i>Pump reservoir</a>
                <a href="../pharmacy/calibration_table.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-hourglass-half"></i>Calibration History</a>
                <a href="../clinician/other_prescriptions.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-adjust"></i>Prescribe other drugs</a>
                <a href="../pharmacy/prisons_module.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-anchor"></i>Prisons Dispensing</a>
                <a href="../pharmacy/retro_dispensing_module.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-anchor"></i>Retro Dispensing</a>
                <a href="../pharmacy/dispensing.php" target="contentFrame" class="nav-link" style="background: #66ccff; color: #000000; margin-top: 10px;">
                    <i class="fa fa-ban"></i>Dispense without pump</a>
                <a href="../pharmacy/edit_dispensed_dose.php" target="contentFrame" class="nav-link" style="background: #ccccff; color: #000000; margin-top: 10px;">
                    <i class="fa fa-anchor"></i>Edit dispensed doses</a>
                <a href="../pharmacy/inventory_form.php" target="contentFrame" class="nav-link" style="background: #b1f0c2; color: #000000; margin-top: 10px;">
                    <i class="fa fa-anchor"></i>Daily Stores movements</a>
                <a href="../pharmacy/add_stocks.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-balance-scale"></i>Add stocks</a>
                <a href="../pharmacy/add_other_drugs.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-bell"></i>Add new drug or item</a>
                <a href="../pharmacy/view_other_drugs.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-binoculars"></i>View items/drugs list</a>
                <a href="../pharmacy/dispensed_drugs.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-briefcase"></i>View drugs dispensed</a>
                <a href="../pharmacy/view_deleted_prescriptions.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-calendar-check-o"></i>View Deleted prescriptions</a>
                <a href="../pharmacy/stock_taking.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-calculator"></i>Stock taking</a>
                <a href="../pharmacy/view_transactions.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-cc"></i>Stock Cards</a>
                <a href="../referrals/referral_dashboard.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-stethoscope"></i>View referrals</a>
                <a href="../pharmacy/view_prescriptions.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-check-circle"></i>View Prescriptions</a>
                <a href="../pharmacy/view_completed_prescriptions.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-check-square-o"></i>View Closed Prescriptions</a>
        </div>

<div class="main">
    <div class="content-header">
        <h2>Pharmacotherapeutic patient management</h2>
        <div class="user-info">
            <div class="user-details">
                    <?php
                        if (isset($_SESSION['current_facility_name'])) {
                            echo htmlspecialchars($_SESSION['current_facility_name']);
                        } else {
                            echo "No Facility Set";
                        }
                    ?>
                </div>
            <span>Welcome, <strong><?php echo $_SESSION['full_name'] ?? 'User'; ?></strong> (<?php echo $userrole; ?>)</span>
            <span class="current-time">
                <i class="far fa-clock"></i> <span id="current-time"><?php echo date('H:i:s'); ?></span>
            </span>
            <a href="../public/login.php" class="logout-btn">
                <i class="fa fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="content-area">
        <iframe name="contentFrame" src="about:blank" style="width: 100%; height: 90vh; border: none; display: none;" id="contentFrame"></iframe>

        <div class="welcome-message" id="welcomeMessage">
            <img src="../assets/images/pt-doctor-removebg-preview.png" width="172" height="116" alt="">
            <h3>Remember these steps and Repeat <span style="color: red;">PRIME/REVERSE PRIME AND CALIBRATION</span> every after serving <span style="color: red;">20</span> Clients </h3>
            <p>This feature is critical as it ensures that the pump dispenses the correct volumes of drugs     even with wear and tear of the pump tube.</p>
            <div class='sops'>
                <p>1. Fill the jug with Methadone</p>
                <p>2. Put the container in receiving chamber</p>
                <p>3. Click on Dispensing Pharmacy</p>
                <p>4. Click on Pump Reservoir on the left</p>
                <p>5. Click <span style='color:red'>Prime</span> button to fill pump tube with dispensing drug </p>
                <p>NB. <span style='color:red'>Repeat priming</span> until drug dispenses to the target container. If pump unresponsive, use </p>
                <p>6. <span style='color:red'>Reverse Prime</span> button to initiate the pump or empty drug from the tube</p>
                <p>7. If drug completely fills the tube, prime once on an empty tumbler/container, measure the dispensed volume and measure to determine its quantity in millimetres.</p>
                <p>8. Click on <span style='color:red'>Calibrate</span> button and enter the dispensed volume in the prompt</p>
                <p>9. Now you can proceed to normal dispensing procedure after calibration</p>
                <p>10. <span style='color:red'>NOTE:</span> Its highly advisable that priming and calibration be done everyday before first client is served</p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.querySelectorAll('.nav-link[target="contentFrame"]');
        const contentFrame = document.getElementById('contentFrame');
        const welcomeMessage = document.getElementById('welcomeMessage');

        // Function to update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            document.getElementById('current-time').textContent = timeString;
        }

        // Update time immediately and then every second
        updateTime();
        setInterval(updateTime, 1000);

        // Function to load content into iframe
        function loadContent(url) {
            if (url) {
                contentFrame.style.display = 'block';
                welcomeMessage.style.display = 'none';
                contentFrame.src = url;
            }
        }

        // Add click event listeners to navigation links (excluding Home)
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.getAttribute('href');

                // Remove active class from all links
                navLinks.forEach(l => l.classList.remove('active'));

                // Add active class to clicked link
                this.classList.add('active');

                // Load content
                loadContent(url);

                // Reset timeout timer on activity
                resetTimer();
            });
        });

        // Handle iframe load event
        contentFrame.addEventListener('load', function() {
            try {
                // Adjust iframe height to content
                const iframeDoc = this.contentDocument || this.contentWindow.document;
                const iframeBody = iframeDoc.body;
                const iframeHtml = iframeDoc.documentElement;

                const height = Math.max(
                    iframeBody.scrollHeight,
                    iframeBody.offsetHeight,
                    iframeHtml.clientHeight,
                    iframeHtml.scrollHeight,
                    iframeHtml.offsetHeight
                );

                this.style.height = height + 'px';
            } catch (e) {
                // Cross-origin frame, can't access contents
                console.log('Cannot adjust iframe height due to cross-origin restrictions');
            }
        });

        // Show welcome message initially
        contentFrame.style.display = 'none';
        welcomeMessage.style.display = 'block';
    });

    // Auto logout after inactivity
    let timeout;
    const warningTime = 60; // Show warning 60 seconds before logout
    const logoutTime = 600; // Logout after 600 seconds (5 minutes)

    function resetTimer() {
        clearTimeout(timeout);

        // Hide warning if visible
        const warningElement = document.getElementById('timeout-warning');
        if (warningElement) {
            warningElement.style.display = 'none';
        }

        // Set new timeout
        timeout = setTimeout(showWarning, (logoutTime - warningTime) * 1000);
    }

    function showWarning() {
        // Show warning
        const warningElement = document.getElementById('timeout-warning');
        if (warningElement) {
            warningElement.style.display = 'block';

            // Start countdown
            let seconds = warningTime;
            const countdownElement = document.getElementById('countdown');
            if (countdownElement) {
                countdownElement.textContent = seconds;

                const countdownInterval = setInterval(() => {
                    seconds--;
                    countdownElement.textContent = seconds;

                    if (seconds <= 0) {
                        clearInterval(countdownInterval);
                        window.location.href = '../public/login.php?timeout=1';
                    }
                }, 1000);
            }

            // Set final logout timeout
            timeout = setTimeout(() => {
                window.location.href = '../public/login.php?timeout=1';
            }, warningTime * 1000);
        }
    }

    function continueSession() {
        // Hide warning
        const warningElement = document.getElementById('timeout-warning');
        if (warningElement) {
            warningElement.style.display = 'none';
        }

        // Reset timer
        resetTimer();

        // Send a request to the server to keep the session alive
        fetch('../includes/keepalive.php')
            .then(response => response.text())
            .then(data => {
                console.log('Session extended');
            })
            .catch(error => {
                console.error('Error extending session:', error);
            });
    }

    // Reset timer on any user activity
    document.addEventListener('mousemove', resetTimer);
    document.addEventListener('keypress', resetTimer);
    document.addEventListener('click', resetTimer);
    document.addEventListener('scroll', resetTimer);

    // Initialize timer
    resetTimer();
</script>
<script>
            // Keep session alive across page navigation
            let sessionKeepAlive = null;

            function startSessionKeepAlive() {
                // Send keep-alive request every 5 minutes
                sessionKeepAlive = setInterval(() => {
                    fetch('../includes/keepalive.php')
                        .then(response => response.text())
                        .then(data => {
                            console.log('Session kept alive');
                        })
                        .catch(error => {
                            console.error('Error keeping session alive:', error);
                        });
                }, 300000); // 5 minutes
            }

            // Stop keep-alive when leaving page
            function stopSessionKeepAlive() {
                if (sessionKeepAlive) {
                    clearInterval(sessionKeepAlive);
                    sessionKeepAlive = null;
                }
            }

            // Start when page loads
            document.addEventListener('DOMContentLoaded', function() {
                startSessionKeepAlive();
            });

            // Clean up before page unload
            window.addEventListener('beforeunload', stopSessionKeepAlive);

            // Reset timeout on user activity
            ['click', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
                document.addEventListener(event, function() {
                    // Send activity ping
                    fetch('../includes/keepalive.php?activity=1')
                        .then(response => response.text())
                        .then(data => {
                            console.log('Activity recorded');
                        });
                });
            });
    </script>

</body>
</html>