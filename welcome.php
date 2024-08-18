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
$user_email = $_SESSION['user_email'];
$nameParts = explode(' ', $user);
$initials = strtoupper($nameParts[0][0] . $nameParts[1][0]);
$username = '';
$sql = "SELECT username FROM movieAccounts WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $user_email);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $movie_id = $_POST['movie_id'];
    if ($action === 'subscribe') {
        $sql = "INSERT INTO movieSubscriptions (username, movie_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $username, $movie_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'unsubscribe') {
        $sql = "DELETE FROM movieSubscriptions WHERE username = ? AND movie_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $username, $movie_id);
        $stmt->execute();
        $stmt->close();
    }
    echo json_encode(['status' => 'success', 'action' => $action]);
    exit();
}

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="welcome.css">
    <title>Movie Theatre</title>
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

        <form method="get" action="mySubscription.php">
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
                ">My Subscription</button>
        </form>

        <?php if (strpos($user_email, 'feras.aljoudi@gmail.com') !== false): ?>
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

        foreach ($movies as $id => $movie) {
            $isSubscribed = in_array($id, $subscribed_movies);
            $buttonText = $isSubscribed ? "Unsubscribe" : "Subscribe";
            $buttonAction = $isSubscribed ? "unsubscribe" : "subscribe";
            ?>
            <div class="movie-item">
                <img src="<?php echo $movie[1]; ?>" alt="<?php echo $movie[0]; ?>">
                <div class="movie-info">
                    <h3><?php echo $movie[0]; ?></h3>
                    <button class="subscriptionBtn" data-movie-id="<?php echo $id; ?>" data-action="<?php echo $buttonAction; ?>">
                        <?php echo $buttonText; ?>
                    </button>
                </div>
            </div>
        <?php } ?>
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

            $('.subscriptionBtn').on('click', function() {
                var button = $(this);
                var movie_id = button.data('movie-id');
                var action = button.data('action');

                $.ajax({
                    url: '',
                    type: 'POST',
                    data: {
                        movie_id: movie_id,
                        action: action
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            if (response.action === 'subscribe') {
                                button.text('Unsubscribe');
                                button.data('action', 'unsubscribe');
                            } else {
                                button.text('Subscribe');
                                button.data('action', 'subscribe');
                            }
                        } else {
                            alert('An unexpected error occurred.');
                        }
                    },
                    error: function() {
                        alert('There was an error processing your request. Please try again.');
                    }
                });
            });
        });
    </script>
</body>
</html>