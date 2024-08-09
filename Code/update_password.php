<?php
session_start();
$config = include('credentials.php');

date_default_timezone_set('Asia/Shanghai');

$servername = $config['servername'];
$username = $config['username'];
$password = $config['password'];
$dbname = $config['dbname'];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set the MySQL session time zone to China Standard Time
$conn->query("SET time_zone = '+08:00'");

$error = '';
$success = '';

// Validate token and email parameters
if (!isset($_GET['token']) || !isset($_GET['email'])) {
    header('Location: index.php'); // Redirect to an error page
    exit();
}

$token = $_GET['token'];
$email = $_GET['email'];

$sql = "SELECT * FROM movieAccounts WHERE email = ? AND reset_token = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('ss', $email, $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $error = 'Invalid token or email';
    } else {
        $user = $result->fetch_assoc();
        $token_creation_time = strtotime($user['token_created_at']);
        $current_time = time();

        if (($current_time - $token_creation_time) > 300) { // 300 seconds = 5 minutes
            $error = 'Link expired';
        }
    }

    $stmt->close();
} else {
    $error = 'Error preparing SQL statement for validating token: ' . $conn->error;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    if (empty($error)) {
        $token = $_POST['token'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        $passwordValid = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&-])[A-Za-z\d@$!%*?&-]{8,}$/';

        if (!preg_match($passwordValid, $password)) {
            $error = 'Password is invalid. Valid password must contain:<br>' .
                        'At least one uppercase letter<br>' .
                        'At least one lowercase letter<br>' .
                        'At least one digit<br>' .
                        'At least one special character: @, $, !, %, *, ?, & or -<br>' .
                        'Minimum length of 8 characters<br>' .
                        'No spaces';
        } elseif ($password !== $confirm_password) {
            $error = 'Password and confirm password must match';
        } else {
            $new_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "UPDATE movieAccounts SET password = ?, reset_token = NULL, token_created_at = NULL WHERE email = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param('ss', $new_password, $email);
                if ($stmt->execute()) {
                    $success = 'Password updated successfully';
                } else {
                    $error = 'Error updating password';
                }
            } else {
                $error = 'Error preparing SQL statement for updating password: ' . $conn->error;
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
    <link rel="stylesheet" href="update_password.css">
</head>
<body>
    <div class="page-container">
        <div class="header-title">
            <div class="header">Aljoudi Movie Posters</div>
        </div>
        <div class="update-section">
            <h2>Update Password</h2>
            <?php if (!empty($error)): ?>
                <p class="error"><?php echo $error; ?></p>
                <a href="index.php" class="back-button">Back</a>
            <?php elseif (!empty($success)): ?>
                <p class="success"><?php echo $success; ?></p>
                <a href="index.php" class="back-button">Back</a>
            <?php else: ?>
                <form method="post" action="" onsubmit="return validatePasswords()">
                    <p id="password_error" class="error"></p>
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <input type="password" id="password" name="password" placeholder="Enter new password" required>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                    <button type="submit" name="update_password">Update Password</button>
                </form>
                <a href="index.php" class="back-button">Back</a>
            <?php endif; ?>
        </div>
        <div class="footer">
            &copy; <?php echo date("Y"); ?> Feras Aljoudi. All rights reserved.
        </div>
    </div>

    <script>
        function validatePasswords() {
            var password = document.getElementById("password").value;
            var confirmPassword = document.getElementById("confirm_password").value;
            var error = document.getElementById("password_error");

            var passwordValid = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&-])[A-Za-z\d@$!%*?&-]{8,}$/;

            if (password !== confirmPassword) {
                error.innerHTML = "Password and confirm password must match";
                return false;
            }

            if (!passwordValid.test(password)) {
                error.innerHTML = "Password is invalid. Valid password must contain:<br>" +
                                    "At least one uppercase letter<br>" +
                                    "At least one lowercase letter<br>" +
                                    "At least one digit<br>" +
                                    "At least one special character: @, $, !, %, *, ?, & or -<br>" +
                                    "Minimum length of 8 characters<br>" +
                                    "No spaces";
                return false;
            }

            error.innerHTML = "";
            return true;
        }
    </script>
</body>
</html>
