<?php
session_start();
$config = include('credentials.php');

$servername = $config['servername'];
$username = $config['username'];
$password = $config['password'];
$dbname = $config['dbname'];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$loginError = '';
$registerError = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $loginInput = $_POST['login_input'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM movieAccounts WHERE (email = ? OR username = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $loginInput, $loginInput);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            if ($user['verified'] !== 'yes') {
                header('Location: verify_account.php');
            } else {
                header('Location: welcome.php');
            }
            exit();
        } else {
            $loginError = 'Password does not match the account';
        }
    } else {
        $loginError = 'Account is not found';
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    $passwordValid = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&-])[A-Za-z\d@$!%*?&-]{8,}$/';

    if (!preg_match($passwordValid, $password)) {
        $registerError = 'Password is invalid. Valid password must contain:<br>' .
                            'At least one uppercase letter<br>' .
                            'At least one lowercase letter<br>' .
                            'At least one digit<br>' .
                            'At least one special character: @, $, !, %, *, ?, & or -<br>' .
                            'Minimum length of 8 characters<br>' .
                            'No spaces';
    } elseif ($password !== $confirmPassword) {
        $registerError = 'Password and confirm password must match';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sql = "SELECT * FROM movieAccounts WHERE email = ? OR username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $registerError = 'Email or Username already exists';
        } else {
            $sql = "INSERT INTO movieAccounts (first_name, last_name, email, username, password) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssss', $firstName, $lastName, $email, $username, $hashedPassword);

            if ($stmt->execute()) {
                $successMessage = 'Account is created';
            } else {
                $registerError = 'Error occurred during registration';
            }
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Theatre</title>
    <link rel="stylesheet" href="index.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="page-container">
        <div class="header-title">
            <div class="header">Aljoudi Movie Theatre</div>
        </div>
        <div class="container">
            <div class="joinUs">Join Us</div>
            <div class="login-section">
                <h2>Login</h2>
                <form method="post" action="">
                    <p class="error"><?php echo $loginError; ?></p>
                    <input type="text" name="login_input" placeholder="Email or Username" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit" name="login">Login</button>
                    <p><a href="reset_password.php">Forgot your password? Reset</a></p>
                </form>
            </div>
            <div class="register-section" style="display: none;">
                <h2>Register</h2>
                <form method="post" action="" onsubmit="return validatePasswords()">
                    <p class="error"><?php echo $registerError; ?></p>
                    <p class="success" id="successMessage"><?php echo $successMessage; ?></p>
                    <p id="password_error" class="error"></p>
                    <input type="text" name="first_name" placeholder="First Name" required>
                    <input type="text" name="last_name" placeholder="Last Name" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                    <button type="submit" name="register">Register</button>
                </form>
            </div>
            <button id="toggleRegister">Don't have an account? Register</button>
            <button id="toggleLogin" style="display: none;">Already have an account? Login</button>
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
                error.textContent = "Password and confirm password must match";
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

        $('#toggleRegister').on('click', function() {
            $('.login-section').hide();
            $('.register-section').show();
            $('#toggleRegister').hide();
            $('#toggleLogin').show();
        });

        $('#toggleLogin').on('click', function() {
            $('.login-section').show();
            $('.register-section').hide();
            $('#toggleRegister').show();
            $('#toggleLogin').hide();
        });

        <?php if ($registerError): ?>
        $(document).ready(function() {
            $('.login-section').hide();
            $('.register-section').show();
            $('#toggleRegister').hide();
            $('#toggleLogin').show();
        });
        <?php elseif ($successMessage): ?>
        $(document).ready(function() {
            $('.login-section').hide();
            $('.register-section').show();
            $('#successMessage').show();
            setTimeout(function() {
                $('#successMessage').hide();
                $('.register-section').hide();
                $('.login-section').show();
                $('#toggleRegister').show();
                $('#toggleLogin').hide();
            }, 2500);
        });
        <?php endif; ?>
    </script>
</body>
</html>
