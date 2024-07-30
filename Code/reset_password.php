<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Asia/Shanghai');

$config = include('credentials.php');

$servername = $config['servername'];
$username = $config['username'];
$password = $config['password'];
$dbname = $config['dbname'];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("SET time_zone = '+08:00'");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_link'])) {
    $email = $_POST['email'];

    $sql = "SELECT * FROM movieAccounts WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = 'Account is not found';
        } else {
            // Generate a unique token
            $token = bin2hex(random_bytes(50));

            // Store the token and the time in the database with the email
            $sql = "UPDATE movieAccounts SET reset_token = ?, token_created_at = NOW() WHERE email = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param('ss', $token, $email);
                $stmt->execute();

                // Send the reset link via email
                $resetLink = "https://142.3.24.34/update_password.php?token=$token&email=$email";
                $subject = "Reset Your Password";
                $message = "Click the link to reset your password: <a href='$resetLink'>$resetLink</a><br>The link will expire in 5 minutes.";
                
                $mail = new PHPMailer(true);
                try {
                    //Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'feras.aljoudi@gmail.com';
                    $mail->Password = '****************************';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    //Recipients
                    $mail->setFrom('feras.aljoudi@gmail.com', 'Aljoudi Movie Posters');
                    $mail->addAddress($email);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body    = $message;

                    $mail->send();
                    $success = 'Link is sent to your email';
                } catch (Exception $e) {
                    $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
            } else {
                $error = 'Error preparing SQL statement for updating token: ' . $conn->error;
            }
        }

        $stmt->close();
    } else {
        $error = 'Error preparing SQL statement for selecting email: ' . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="reset_password.css">
</head>
<body>
    <div class="page-container">
        <div class="header-title">
            <div class="header">Aljoudi Movie Theatre</div>
        </div>
        <div class="reset-section">
            <h2>Reset Password</h2>
            <?php if (!empty($error)): ?>
            <p class="error"><?php echo $error; ?></p>
            <form method="post" action="">
                <input type="email" name="email" placeholder="Enter your email" required>
                <button type="submit" name="send_link">Send Link</button>
            </form>
            <a href="index.php" class="back-button">Back</a>
            <?php elseif (!empty($success)): ?>
                <p class="success"><?php echo $success; ?></p>
                <a href="index.php" class="back-button">Back</a>
            <?php else: ?>
                <form method="post" action="">
                    <input type="email" name="email" placeholder="Enter your email" required>
                    <button type="submit" name="send_link">Send Link</button>
                </form>
                <a href="index.php" class="back-button">Back</a>
            <?php endif; ?>
        </div>
        <div class="footer">
            &copy; <?php echo date("Y"); ?> Feras Aljoudi. All rights reserved.
        </div>
    </div>
</body>
</html>
