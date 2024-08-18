<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$credentials = include('credentials.php');

$conn = new mysqli(
    $credentials['servername'],
    $credentials['username'],
    $credentials['password'],
    $credentials['dbname'],
    $credentials['port']
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user = $_SESSION['user'];
$email = $_SESSION['user_email'];
$fullName = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    $inputCode = $_POST['verification_code'];
    $sql = "SELECT * FROM movieAccounts WHERE email = ? AND code_to_verify = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $email, $inputCode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $sql = "UPDATE movieAccounts SET verified = 'yes', code_to_verify = NULL WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();

        $_SESSION['user'] = $fullName;
        $success = 'Your account is verified!';
    } else {
        $error = 'Invalid verification code';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_code'])) {
    sendVerificationCode($conn, $email);
} else {
    sendVerificationCode($conn, $email);
}

function sendVerificationCode($conn, $email) {
    $verificationCode = rand(100000, 999999);
    $sql = "UPDATE movieAccounts SET code_to_verify = ? WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $verificationCode, $email);
    $stmt->execute();

    $subject = "Verify Your Account";
    $message = "Your verification code is: $verificationCode";

    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'feras.aljoudi@gmail.com';
        $mail->Password = '***********************';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('no-reply@aljoudimovietheatre.org', 'Aljoudi Movie Theatre');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account</title>
    <link rel="stylesheet" href="verify_account.css">
</head>
<body>
    <div class="page-container">
        <div class="header-title">
            <div class="header">Aljoudi Movie Theatre</div>
        </div>
        <div class="verify-section">
            <h2>Verify Account</h2>
            <p class="error"><?php echo $error ?? ''; ?></p>
            <p class="success"><?php echo $success ?? ''; ?></p>
            
            <?php if (isset($success)): ?>
            <script>
                setTimeout(function() {
                    window.location.href = 'welcome.php';
                }, 2000);
            </script>
            <?php endif; ?>

            <form method="post" action="">
                <input type="text" name="verification_code" placeholder="Enter the 6-digit code" required>
                <button type="submit" name="verify_code">Verify</button>
            </form>
            <form method="post" action="">
                <button type="submit" name="resend_code">Resend Code</button>
            </form>
            <form method="post" action="">
                <button type="submit" name="logout">Logout</button>
            </form>
        </div>
        <div class="footer">
            &copy; <?php echo date("Y"); ?> Feras Aljoudi. All rights reserved.
        </div>
    </div>
</body>
</html>
