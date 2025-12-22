<?php
ob_start();
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

include('../includes/config.php');

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = "User Registration";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Invalid CSRF token";
        header("Location: user_registration.php");
        exit;
    }

    // Sanitize and validate input
    $username = $_POST['username'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $userrole = $_POST['userrole'] ?? '';
    $photo = '';

    // Validate required fields
    if (empty($username) || empty($first_name) || empty($last_name) || empty($email) || empty($gender) || empty($mobile) || empty($userrole)) {
        $_SESSION['error_message'] = "All fields except photo are required";
        header("Location: user_registration.php");
        exit;
    }

    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../photos/users/';
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        $file_tmp = $_FILES['photo']['tmp_name'];
        $file_type = $_FILES['photo']['type'];
        $file_size = $_FILES['photo']['size'];
        $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

        // Validate file type and size
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error_message'] = "Invalid file type. Only JPEG, PNG, and GIF are allowed.";
            header("Location: user_registration.php");
            exit;
        }

        if ($file_size > $max_size) {
            $_SESSION['error_message'] = "File size exceeds 5MB limit.";
            header("Location: user_registration.php");
            exit;
        }

        // Generate unique filename: full_name_user_id_current_date
        $full_name = str_replace(' ', '_', trim($first_name . '_' . $last_name));
        $current_date = date('Ymd');
        $user_id_temp = uniqid(); // Temporary user ID for filename (will update later)
        $photo = "{$full_name}_{$user_id_temp}_{$current_date}.{$file_ext}";

        // Move uploaded file to destination
        if (!move_uploaded_file($file_tmp, $upload_dir . $photo)) {
            $_SESSION['error_message'] = "Failed to upload photo.";
            header("Location: user_registration.php");
            exit;
        }
    } elseif (isset($_POST['webcam_photo']) && !empty($_POST['webcam_photo'])) {
        // Handle webcam photo
        $upload_dir = '../photos/users/';
        $full_name = str_replace(' ', '_', trim($first_name . '_' . $last_name));
        $current_date = date('Ymd');
        $user_id_temp = uniqid();
        $photo = "{$full_name}_{$user_id_temp}_{$current_date}.jpg";

        // Decode base64 image from webcam
        $webcam_data = $_POST['webcam_photo'];
        $webcam_data = str_replace('data:image/jpeg;base64,', '', $webcam_data);
        $webcam_data = str_replace(' ', '+', $webcam_data);
        $image_data = base64_decode($webcam_data);

        if ($image_data === false) {
            $_SESSION['error_message'] = "Failed to process webcam photo.";
            header("Location: user_registration.php");
            exit;
        }

        // Save webcam photo
        if (!file_put_contents($upload_dir . $photo, $image_data)) {
            $_SESSION['error_message'] = "Failed to save webcam photo.";
            header("Location: user_registration.php");
            exit;
        }
    }

    // Set default password and hash it securely
    $default_password = '123456';
    $hashed_password = password_hash($default_password, PASSWORD_BCRYPT);

    // Prepare and execute SQL statement
    $sql = "INSERT INTO tblusers (username, first_name, last_name, email, password, gender, mobile, photo, userrole)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $_SESSION['error_message'] = "Database error: " . $conn->error;
        header("Location: user_registration.php");
        exit;
    }

    $stmt->bind_param("sssssssss", $username, $first_name, $last_name, $email, $hashed_password, $gender, $mobile, $photo, $userrole);

    if ($stmt->execute()) {
        // Get the inserted user ID
        $user_id = $conn->insert_id;

        // Update photo filename with actual user ID
        if (!empty($photo)) {
            $new_photo = str_replace("_{$user_id_temp}_", "_{$user_id}_", $photo);
            if (rename($upload_dir . $photo, $upload_dir . $new_photo)) {
                // Update database with new filename
                $update_sql = "UPDATE tblusers SET photo = ? WHERE user_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("si", $new_photo, $user_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }

        $_SESSION['success_message'] = "User added successfully. Default password is 123456 - please change it after login.";
        header("Location: ../public/userslist.php");
        exit;
    } else {
        $_SESSION['error_message'] = "Registration failed: " . $stmt->error;
        header("Location: user_registration.php");
        exit;
    }

    $stmt->close();
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/forms.css" type="text/css">
    <style>
        :root {
            --primary-color: #0056b3;
            --secondary-color: #6c757d;
            --background-light: #f8f9fa;
            --card-background: #ffffff;
            --border-color: #dee2e6;
            --success-color: #28a745;
            --success-bg-color: #d4edda;
            --text-color: #343a40;
            --input-border: #ced4da;
            --input-focus-border: #80bdff;
            --shadow-light: rgba(0, 0, 0, 0.1);
            --font-family: 'Arial', sans-serif;
        }

        form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            padding: 20px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 2px 5px var(--shadow-light);
        }

        .main-content {
            padding: 20px;
            max-width: 700px;
            margin: 20px auto;
            background-color: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-light);
        }

        h2 {
            color: linear-gradient(135deg, #1a2a6c, #2b5876);
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8em;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }

        #success-message {
            background-color: var(--success-bg-color);
            color: var(--success-color);
            padding: 15px 20px;
            margin-bottom: 20px;
            border: 1px solid var(--success-color);
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        #success-message .fas {
            font-size: 1.2em;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--text-color);
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        input[type="file"],
        select,
        video,
        canvas {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--input-border);
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="date"]:focus,
        input[type="file"]:focus,
        select:focus {
            border-color: var(--input-focus-border);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .readonly-input {
            background-color: #e9ecef;
            cursor: not-allowed;
        }

        .custom-submit-btn {
            grid-column: 1 / -1;
            padding: 15px 25px;
            background: linear-gradient(135deg, #1a2a6c, #2b5876);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .custom-submit-btn:hover {
            background: #FF9966;
            transform: translateY(-2px);
        }

        .custom-submit-btn:active {
            transform: translateY(0);
        }

        .webcam-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        #capture-btn {
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        #capture-btn:hover {
            background-color: #004085;
        }

        @media (max-width: 992px) {
            form {
                grid-template-columns: repeat(2, 1fr);
            }
            .custom-submit-btn {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 768px) {
            form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
        </div>
        <h2><?php echo htmlspecialchars($page_title); ?></h2>
        <form method="post" action="user_registration.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="password" name="password" value="123456" hidden>
            <input type="hidden" name="webcam_photo" id="webcam_photo">

            <div class="form-group">
                <label for="username">User Name</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" class="form-control" id="first_name" name="first_name" required>
            </div>

            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" class="form-control" id="last_name" name="last_name" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="gender">Sex</label>
                <select class="form-control" id="gender" name="gender" required>
                    <?php
                    $result = $conn->query("SELECT gender_id, gender_name FROM tblgender");
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='".htmlspecialchars($row['gender_name'])."'>".htmlspecialchars($row['gender_name'])."</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="mobile">Mobile</label>
                <input type="text" class="form-control" id="mobile" name="mobile" required>
            </div>

            <div class="form-group">
                <label for="userrole">User Role</label>
                <select class="form-control" id="userrole" name="userrole" required>
                    <?php
                    $result = $conn->query("SELECT id, role FROM userroles");
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='".htmlspecialchars($row['role'])."'>".htmlspecialchars($row['role'])."</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="photo">Upload Photo</label>
                <input type="file" class="form-control" id="photo" name="photo" accept="image/jpeg,image/png,image/gif">
            </div>

            <div class="form-group webcam-container">
                <label>Or Take a Photo</label>
                <video id="video" width="100%" height="auto" autoplay style="display: none;"></video>
                <canvas id="canvas" style="display: none;"></canvas>
                <img id="preview" style="display: none; width: 100%; border-radius: 5px;">
                <button type="button" id="start-webcam" class="btn btn-secondary">Start Webcam</button>
                <button type="button" id="capture-btn" style="display: none;">Capture Photo</button>
            </div>

            <div class="form-group">
                <button type="submit" class="custom-submit-btn">Register New User</button>
            </div>
        </form>
    </div>

    <script src="../assets/js/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Webcam capture functionality
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const preview = document.getElementById('preview');
        const startWebcamBtn = document.getElementById('start-webcam');
        const captureBtn = document.getElementById('capture-btn');
        const webcamPhotoInput = document.getElementById('webcam_photo');
        const photoInput = document.getElementById('photo');

        startWebcamBtn.addEventListener('click', async () => {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
                video.style.display = 'block';
                captureBtn.style.display = 'block';
                startWebcamBtn.style.display = 'none';
                preview.style.display = 'none';
                photoInput.value = ''; // Clear file input if webcam is used
            } catch (err) {
                alert('Error accessing webcam: ' + err.message);
            }
        });

        captureBtn.addEventListener('click', () => {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            const dataUrl = canvas.toDataURL('image/jpeg');
            preview.src = dataUrl;
            preview.style.display = 'block';
            webcamPhotoInput.value = dataUrl;
            video.style.display = 'none';
            captureBtn.style.display = 'none';
            startWebcamBtn.style.display = 'block';
            photoInput.value = ''; // Clear file input if webcam is used

            // Stop webcam stream
            video.srcObject.getTracks().forEach(track => track.stop());
        });

        // Clear webcam photo if file input is used
        photoInput.addEventListener('change', () => {
            if (photoInput.files.length > 0) {
                webcamPhotoInput.value = '';
                preview.style.display = 'none';
            }
        });
    </script>
</body>
</html>