<?php
// Use centralized session management
include "../includes/session_manager.php";
updateSessionActivity();
requireLogin(); // This will redirect to login if not logged in

// Include configuration
include "../includes/config.php";

// Get user role from session
$userrole = $_SESSION['userrole'] ?? '';
$user_id = $_SESSION['user_id'] ?? '';
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Patient Management System</title>
<link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css" type="text/css">
<link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.min.css" type="text/css">
<link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css" type="text/css">
<link rel="apple-touch-icon" sizes="180x180" href="../assets/favicons/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="../assets/favicons/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16x16.png">
<link rel="manifest" href="../assets/favicons/site.webmanifest">
<link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
<link rel="stylesheet" href="../assets/css/sidenav.css" type="text/css">
<style>
.timeout-warning {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    z-index: 1000;
    text-align: center;
}

.timeout-warning h4 {
    color: #856404;
    margin-bottom: 10px;
}

.timeout-warning p {
    color: #856404;
    margin-bottom: 15px;
}

.timeout-warning button {
    background: #007bff;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.timeout-warning button:hover {
    background: #0056b3;
}

.content-header {
    background: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.current-time {
    color: #6c757d;
    font-size: 0.9em;
}

.logout-btn {
    color: #ffffff;
    text-decoration: none;
    padding: 5px 10px;
    border: 1px solid #dc3545;
    border-radius: 4px;
    transition: all 0.3s;
}

.logout-btn:hover {
    background: #dc3545;
    color: white;
    text-decoration: none;
}

.content-area {
    padding: 20px;
}

.welcome-message {
    text-align: center;
    padding: 40px 20px;
}

.welcome-message img {
    margin-bottom: 20px;
}

.welcome-message h3 {
    color: #2c3e50;
    margin-bottom: 15px;
}

.welcome-message p {
    color: #7f8c8d;
    margin-bottom: 10px;
}

.nav-link.active {
    background-color: #007bff !important;
    color: white !important;
}

.sidenav {
    background-color: #2c3e50;
    color: white;
}

.sidenav h2 {
    color: white;
    text-align: center;
    padding: 20px 0;
    border-bottom: 1px solid #34495e;
}

.nav-link {
    color: #bdc3c7 !important;
    padding: 12px 20px;
    border-bottom: 1px solid #34495e;
    transition: all 0.3s;
}

.nav-link:hover {
    background-color: #34495e;
    color: white !important;
}

.nav-link.home-link {
    background-color: #27ae60;
    color: white !important;
}

.nav-link.home-link:hover {
    background-color: #219a52;
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
        <i class="fa fa-forward"></i><br>
        Patient referrals
    </h2>

    <?php if (in_array($userrole, ['clinician', 'Pharmacist', 'Psychologist', 'Laboratory Scientist', 'Peer Educator', 'HRIO', 'Psychiatrist', 'Data manager', 'Guest', 'Nursing'])) : ?>
    <!-- Home link - will navigate away from this page -->
        <a href="../dashboard/dashboard.php" class="nav-link home-link">
            <i class="fa fa-home"></i>Home
        </a>

        <a href="../referrals/dashboard_completed.php" target="contentFrame" class="nav-link">
            <i class="fa fa-check"></i>Show Completed Referrals</a>

        <a href="../referrals/referral_dashboard.php" target="contentFrame" class="nav-link">
            <i class="fa fa-bug"></i>Show pending referrals</a>

    <?php elseif (in_array($userrole, ['Admin', 'Super Admin'])) : ?>
        <!-- Home link - will navigate away from this page -->
        <a href="../dashboard/dashboard.php" class="nav-link home-link">
            <i class="fa fa-home"></i>Home
        </a>


        <a href="../referrals/dashboard_completed.php" target="contentFrame" class="nav-link">
            <i class="fa fa-check"></i>Show Completed Referrals</a>

        <a href="../referrals/referral_dashboard.php" target="contentFrame" class="nav-link">
            <i class="fa fa-bug"></i>Show pending referrals</a>
        <!--<a href="../referrals/view_referral_pending.php" target="contentFrame" class="nav-link">
            <i class="fa fa-bug"></i>View pending referrals</a>-->
        <a href="../referrals/view_referral_pending.php" target="contentFrame" class="nav-link">
            <i class="fa fa-bug"></i>Redirect pending referrals</a>

    <?php else : ?>
        <!-- For other roles (Doctor, Nurse, etc.) - Limited access -->
        <a href="../dashboard/dashboard.php" class="nav-link home-link">
            <i class="fa fa-home"></i>Home
        </a>

        <a href="../referrals/referral_dashboard.php" target="contentFrame" class="nav-link">
            <i class="fa fa-bug"></i>View My Referrals</a>
    <?php endif; ?>

</div>

<div class="main">
    <div class="content-header">
        <h2>Referrals Management</h2>
        <div class="user-info">
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
            <h3>Welcome to the integrated patient management system</h3>
            <p>Select an option from the side navigation to access services. Your access level is: <strong><?php echo ucfirst($userrole); ?></strong></p>

            <?php if (in_array($userrole, ['Admin', 'Super Admin', 'Clinician'])) : ?>
                <p>As an <strong><?php echo ucfirst($userrole); ?></strong>, <span style="color: red;">always backup your database on external hard-drive and cloud e.g Google Drive</span></p>
            <?php else : ?>
                <p>You have access to view referrals assigned to you. Contact your administrator for additional permissions.</p>
            <?php endif; ?>
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

</body>
</html>