<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /index.php');
    exit();
}

if ($_SESSION['user_email'] !== 'feras.aljoudi@gmail.com') {
    header('Location: /welcome.php');
    exit();
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: /index.php');
    exit();
}

$user = $_SESSION['user'];
$nameParts = explode(' ', $user);
$initials = strtoupper($nameParts[0][0] . $nameParts[1][0]);

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

$sql = "SELECT id, first_name, last_name, email, username, password, verified FROM movieAccounts";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="administration.css">
    <title>Administration</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="header">
        <div class="titleContainer">
            <div class="title">Aljoudi Movie Theatre</div>
        </div>
        <div class="burgerMenu" id="burgerMenu"><?php echo $initials; ?></div>
    </div>

    <div class="burgerMenuContent" id="burgerMenuContent">
        <p><?php echo htmlspecialchars($user); ?></p>

        <form method="get" action="/welcome.php">
            <button type="submit" class="menuBtns">Movie Page</button>
        </form>
        <form method="get" action="/mySubscription.php">
            <button type="submit" class="menuBtns">My Subscription</button>
        </form>
        <form method="post" action="">
            <button type="submit" name="logout" class="logoutBtn">Logout</button>
        </form>
    </div>

    <div class="container">
        <table class="tableContainer">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Verified</th>
                    <th>Modify</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr id="row-<?php echo $row['id']; ?>">
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td>
                                ******
                                <input type="hidden" class="password-hidden" value="<?php echo htmlspecialchars($row['password']); ?>">
                            </td>
                            <td><?php echo htmlspecialchars($row['verified']); ?></td>
                            <td>
                                <button class="edit-btn" data-id="<?php echo $row['id']; ?>">Edit</button>
                                <button class="delete-btn" data-id="<?php echo $row['id']; ?>">Delete</button>
                                <button class="update-btn" data-id="<?php echo $row['id']; ?>" style="display:none;">Update</button>
                                <button class="cancel-btn" data-id="<?php echo $row['id']; ?>" style="display:none;">Cancel</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">No records found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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
            
            $('.edit-btn').on('click', function() {
                var id = $(this).data('id');
                var row = $('#row-' + id);
                row.find('.delete-btn').hide();

                row.find('td').each(function(index) {
                    if (index === 0) {
                        return;
                    }
                    if (index < 7) {
                        var cellValue = $(this).text();
                        if (index === 5) {
                            var passwordValue = $(this).find('.password-hidden').val();
                            var inputField = '<input type="password" placeholder="******" class="editable" data-original-value="' + passwordValue + '">';
                        } else {
                            var inputField = '<input type="text" value="' + cellValue + '" class="editable">';
                        }
                        $(this).html(inputField);
                    }
                });

                row.find('.update-btn, .cancel-btn').show();
                $(this).hide();
            });

            $('.cancel-btn').on('click', function() {
                var id = $(this).data('id');
                location.reload();
            });

            $('.update-btn').on('click', function() {
                var id = $(this).data('id');
                var row = $('#row-' + id);
                var updatedData = {};

                row.find('td').each(function(index) {
                    if (index === 0) {
                        updatedData['id'] = $(this).text();
                        return;
                    }
                    if (index < 7) {
                        var inputValue = $(this).find('input').val();
                        if (index === 5) {
                            var originalPassword = $(this).find('input').data('original-value');
                            if (inputValue === '' || inputValue === '******') {
                                updatedData['password'] = originalPassword;
                            } else {
                                updatedData['password'] = inputValue;
                            }
                        } else {
                            var key;
                            switch (index) {
                                case 1: key = 'first_name'; break;
                                case 2: key = 'last_name'; break;
                                case 3: key = 'email'; break;
                                case 4: key = 'username'; break;
                                case 6: key = 'verified'; break;
                            }
                            updatedData[key] = inputValue;
                        }
                    }
                });
                $.ajax({
                    url: 'update.php',
                    method: 'POST',
                    data: updatedData,
                    success: function(response) {
                        location.reload();
                    },
                    error: function() {
                        alert('Update failed. Please try again.');
                    }
                });
            });

            let deleteId;

            $('.delete-btn').on('click', function() {
                deleteId = $(this).data('id');
                $('#deleteModal').fadeIn();
            });

            $('.closeBtn, #cancelDelete').on('click', function() {
                $('#deleteModal').fadeOut();
            });

            $('#confirmDelete').on('click', function() {
                $.ajax({
                    url: 'deleteAccount.php',
                    method: 'POST',
                    data: { id: deleteId },
                    success: function(response) {
                        $('#deleteModal').fadeOut();
                        location.reload();
                    },
                    error: function() {
                        alert('Delete failed. Please try again.');
                    }
                });
            });
        });
    </script>
    <div id="deleteModal" class="modal">
        <div class="modalContent">
            <span class="closeBtn">&times;</span>
            <h2>Confirm Deletion</h2>
            <p>Are you sure you want to delete this account?</p>
            <button id="confirmDelete" class="btn-delete">Delete</button>
            <button id="cancelDelete" class="btn-cancel">Cancel</button>
        </div>
    </div>
</body>
</html>
