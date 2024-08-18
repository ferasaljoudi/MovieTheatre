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
$nameParts = explode(' ', $user);
$initials = strtoupper($nameParts[0][0] . $nameParts[1][0]);

$username = '';
$sql = "SELECT username FROM movieAccounts WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();

$subscribed_movies = [];
$sql = "SELECT movie_id FROM movieSubscriptions WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $subscribed_movies[] = $row['movie_id'];
}
$stmt->close();
$conn->close();

$movies = [
    1248795 => ["Art of Love", "moviesImages/ArtOfLove.jpg"],
    420817 => ["Aladdin", "moviesImages/Aladdin.jpg"],
    762441 => ["A Quiet Place Day One", "moviesImages/QuietPlace.jpg"],
    1129598 => ["Prey", "moviesImages/Prey.jpg"],
    117251 => ["White House Down", "moviesImages/WhiteHouseDown.jpg"],
    573435 => ["Bad Boys", "moviesImages/BadBoys.jpg"],
    384018 => ["Fast And Furious", "moviesImages/FastAndFurious.jpg"],
    24428 => ["The Avengers", "moviesImages/TheAvengers.jpg"],
    634649 => ["Spider-Man", "moviesImages/Spiderman.jpg"],
    298618 => ["The Flash", "moviesImages/Flash.jpg"]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="mySubscription.css">
    <title>My Subscriptions</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="header">
        <div class="titleContainer">
            <div class="title">Aljoudi Movie Theatre</div>
        </div>
        <div class="burgerMenu" id="burgerMenuID"><?php echo $initials; ?></div>
    </div>

    <div class="burgerMenuContent" id="burgerMenuContentID">
        <p><?php echo htmlspecialchars($user); ?></p>

        <form method="get" action="welcome.php">
            <button type="submit"
                style="
                    padding: 10px;
                    background-color: rgb(94, 165, 191);
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    width: 100%;
                    font-size: 16px;
                    margin-bottom: 10px;
                ">Movie Page</button>
        </form>

        <?php if (strpos($email, 'feras.aljoudi@gmail.com') !== false): ?>
            <form method="get" action="administration.php">
                <button type="submit"
                    style="
                        padding: 10px;
                        background-color: rgb(94, 165, 191);
                        color: white;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        width: 100%;
                        font-size: 16px;
                        margin-bottom: 10px;
                    ">Administration</button>
            </form>
        <?php endif; ?>

        <form method="post" action="">
            <button type="submit" name="logout"
                style="
                    padding: 10px;
                    background-color: #dc3545;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    width: 100%;
                    font-size: 16px;
                    margin-bottom: 10px;
                ">Logout</button>
        </form>
    </div>

    <div class="movie-grid">
        <?php
            if (empty($subscribed_movies)) {
                echo "<p style='text-align: center; font-weight: bold; display: flex; justify-content: center; align-items: center;'>You have not subscribed to any movies yet.</p>";
            } else {
                foreach ($subscribed_movies as $movie_id) {
                    if (isset($movies[$movie_id])) {
                        $movie = $movies[$movie_id];
                        ?>
                        <div class="movie-item">
                        <a href="movieDetails.php?movie_id=<?php echo $movie_id; ?>">
                            <img src="<?php echo $movie[1]; ?>" alt="<?php echo $movie[0]; ?>">
                            <div class="movie-info">
                                <h3><?php echo $movie[0]; ?></h3>
                            </div>
                        </a>
                        </div>
                    <?php }
                }
            }
        ?>
    </div>

    <div class="footer">
        &copy; <?php echo date("Y"); ?> Feras Aljoudi. All rights reserved.
    </div>

    <script>
        $(document).ready(function() {
            $('#burgerMenuID').on('click', function(event) {
                event.stopPropagation();
                $('#burgerMenuContentID').toggle();
            });

            $(document).on('click', function() {
                $('#burgerMenuContentID').hide();
            });

            $('#burgerMenuContentID').on('click', function(event) {
                event.stopPropagation();
            });
        });
    </script>
</body>
</html>
