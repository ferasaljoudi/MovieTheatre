<?php
session_start();
if (!isset($_SESSION['user'])) {
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

$movie_id = $_GET['movie_id'] ?? null;

if (!$movie_id) {
    echo "Invalid movie ID.";
    exit();
}

$api_key = '***************************************';
$api_url = "https://api.themoviedb.org/3/movie/$movie_id?api_key=$api_key";

$movie_details = file_get_contents($api_url);
if ($movie_details === FALSE) {
    die('Error fetching movie details.');
}

$movie_details = json_decode($movie_details, true);

$spoken_languages = array_map(function($lang) {
    return $lang['name'];
}, $movie_details['spoken_languages']);
$title = $movie_details['title'];
$overview = $movie_details['overview'];

$api_url_trailer = "https://api.themoviedb.org/3/movie/$movie_id/videos?api_key=$api_key";
$trailer_details = file_get_contents($api_url_trailer);
if ($trailer_details === FALSE) {
    die('Error fetching trailer details.');
}

$trailer_details = json_decode($trailer_details, true);
$youtube_key = null;

foreach ($trailer_details['results'] as $video) {
    if ($video['site'] === 'YouTube' && $video['type'] === 'Trailer') {
        $youtube_key = $video['key'];
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unsubscribe'])) {
    $sql = "DELETE FROM movieSubscriptions WHERE username = ? AND movie_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $username, $movie_id);
    if ($stmt->execute()) {
        header('Location: mySubscription.php');
        exit();
    } else {
        echo "Error unsubscribing from the movie.";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="movieDetails.css">
    <title><?php echo htmlspecialchars($title); ?></title>
</head>
<body>
    <div class="header">
        <div class="titleContainer">
            <div class="title">Aljoudi Movie Theatre</div>
        </div>
    </div>

    <div class="movieDetails">
        <h2><?php echo htmlspecialchars($title); ?></h2>
        <p><?php echo htmlspecialchars($overview); ?></p>
        <p>Spoken Languages: <?php echo htmlspecialchars(implode(', ', $spoken_languages)); ?></p>

        <?php if ($youtube_key): ?>
            <div class="trailer">
                <h3>Trailer</h3>
                <iframe width="660" height="415" src="https://www.youtube.com/embed/<?php echo htmlspecialchars($youtube_key); ?>" frameborder="0" allowfullscreen></iframe>
            </div>
        <?php else: ?>
            <p>No trailer available.</p>
        <?php endif; ?>

        <form method="post" action="">
            <button type="submit" name="unsubscribe">Unsubscribe</button>
        </form>

        <a href="mySubscription.php">Back to My Subscriptions</a>
    </div>

    <div class="footer">
        &copy; <?php echo date("Y"); ?> Feras Aljoudi. All rights reserved.
    </div>
</body>
</html>
