<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$config = include('credentials.php');
$servername = $config['servername'];
$username = $config['username'];
$password = $config['password'];
$dbname = $config['dbname'];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = $_POST['id'];
$firstName = $_POST['first_name'];
$lastName = $_POST['last_name'];
$email = $_POST['email'];
$username = $_POST['username'];
$newPassword = $_POST['password'];
$verified = $_POST['verified'];

$sql = "SELECT password FROM movieAccounts WHERE id='$id'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $currentHashedPassword = $row['password'];

    if ($newPassword === $currentHashedPassword) {
        $hashedPassword = $currentHashedPassword;
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    }

    $sql = "UPDATE movieAccounts SET
            first_name='$firstName',
            last_name='$lastName',
            email='$email',
            username='$username',
            password='$hashedPassword',
            verified='$verified'
            WHERE id='$id'";

    if ($conn->query($sql) === TRUE) {
        echo "Record updated successfully";
    } else {
        echo "Error updating record: " . $conn->error;
    }
} else {
    echo "User not found.";
}

$conn->close();
?>
