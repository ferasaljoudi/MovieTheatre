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

$user = $_SESSION['user'];
$nameParts = explode(' ', $user);
$initials = strtoupper($nameParts[0][0] . $nameParts[1][0]);
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
        <div class="title-container">
            <div class="title">Aljoudi Movie Theatre</div>
        </div>
        <div class="burger-menu" id="burgerMenu"><?php echo $initials; ?></div>
    </div>

    <div class="burger-menu-content" id="burgerMenuContent">
        <p><?php echo htmlspecialchars($user); ?></p>
        <form method="post" action="">
            <button type="submit" name="logout">Logout</button>
        </form>
    </div>
    <div class="movie-grid">
        <div class="movie-item">
            <img src="moviesImages/Ottman.jpg" alt="Ottman">
            <div class="movie-info">
                <h3>Ottman</h3>
                <p>An epic story of conquest and strategy.</p>
            </div>
        </div>
        <div class="movie-item">
            <img src="moviesImages/Alhayba.jpg" alt="Alhayba">
            <div class="movie-info">
                <h3>Alhayba</h3>
                <p>A tale of love and conflict in the mountains.</p>
            </div>
        </div>
        <div class="movie-item">
            <img src="moviesImages/KurtlarVadisi.jpg" alt="Kurtlar Vadisi">
            <div class="movie-info">
                <h3>Kurtlar Vadisi</h3>
                <p>A gripping story of power and loyalty.</p>
            </div>
        </div>
        <div class="movie-item">
            <img src="moviesImages/Aladdin.jpg" alt="Aladdin">
            <div class="movie-info">
                <h3>Aladdin</h3>
                <p>A magical adventure in the streets of Agrabah.</p>
            </div>
        </div>
        <div class="movie-item">
            <img src="moviesImages/FastAndFurious.jpg" alt="Fast and Furious">
            <div class="movie-info">
                <h3>Fast and Furious</h3>
                <p>High-speed action and thrilling car chases.</p>
            </div>
        </div>
        <div class="movie-item">
            <img src="moviesImages/SpiderMan.jpg" alt="SpiderMan">
            <div class="movie-info">
                <h3>SpiderMan</h3>
                <p>The adventures of your friendly neighborhood hero.</p>
            </div>
        </div>
    </div>

    <div class="footer">
        &copy; <?php echo date("Y"); ?> Feras Aljoudi. All rights reserved.
    </div>

    <script>
        $(document).ready(function() {
            $('#burgerMenu').on('click', function(event) {
                event.stopPropagation();
                $('#burgerMenuContent').toggle();
            });

            $(document).on('click', function() {
                $('#burgerMenuContent').hide();
            });

            $('#burgerMenuContent').on('click', function(event) {
                event.stopPropagation();
            });
        });
    </script>
</body>
</html>
