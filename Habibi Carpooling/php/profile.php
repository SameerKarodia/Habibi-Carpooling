<?php
    session_start();
    // Check if the user is logged in
    if (!isset($_SESSION['username'])) {

        // If not logged in, redirect to the homepage
        header("Location: ../html/index.html"); 
        exit; 
    }

    // Get the logged-in username
    $username = $_SESSION['username'];

    // Database connection details
    $host = 'localhost';
    $myUsername = 'root';
    $myPassword = ''; 
    $dbname = 'profiles';

    // Create a MySQLi connection
    $conn = new mysqli($host, $myUsername, $myPassword, $dbname);

    // Check for connection errors
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Initialize $ridesTaken and $ridesPosted as empty arrays before the queries
    $ridesTaken = [];
    $ridesPosted = [];

    // Get rides posted by the user
    $sqlPosted = "SELECT * FROM rides WHERE driver = ?";
    $stmtPosted = $conn->prepare($sqlPosted);
    $stmtPosted->bind_param("s", $username);
    $stmtPosted->execute();
    $resultPosted = $stmtPosted->get_result();
    $ridesPosted = $resultPosted->fetch_all(MYSQLI_ASSOC);

    // Get rides taken by the user using JSON_CONTAINS
    $sqlTaken = "SELECT * FROM rides WHERE JSON_CONTAINS(passengersList, ?)";
    $stmtTaken = $conn->prepare($sqlTaken);
    $usernameJson = json_encode([$username]); // Ensure the username is JSON-encoded
    $stmtTaken->bind_param("s", $usernameJson);
    $stmtTaken->execute();
    $resultTaken = $stmtTaken->get_result();
    $ridesTaken = $resultTaken->fetch_all(MYSQLI_ASSOC);

    // Close statements
    $stmtPosted->close();
    $stmtTaken->close();

    // Close the database connection
    $conn->close();
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Profile</title>
        <link rel="stylesheet" href="habibiStyles.css">
        <link href="https://fonts.googleapis.com/css2?family=Sour+Gummy:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
        
    </head>
    <body>

        <div id="profile">
            <!-- Welcome Message -->
            <h1>Welcome, <span style="color:#1fb4c1"> <?php echo htmlspecialchars($username); ?> ! </span></h1>
        
            <!-- Container for the 4 Divs -->
            <div class="profile-children-container">
                <!-- Search Bar -->
                <div class="profilechild" id="search">
                    <h2>Search for a Ride</h2>
                    <form action="searchRide.php" method="GET">
                        <input type="text" id="searchText" name="search" placeholder="Search by: origin, destination, etc." required>
                        <input id="submitButton" type="submit" value="Search">
                    </form>
                </div>
        
                <!-- Post a New Ride -->
                <div class="profilechild" id="post">
                    <h2>Post a Ride</h2>
                    <form action="postRide.php" method="GET">
                        <input id="submitButton" type="submit" value="Post a New Ride">
                    </form>
                </div>
        
                <!-- Display Rides Posted by the User -->
                <div class="profilechild" id="display">
                    <h2>Rides Posted</h2>
                    <?php if (count($ridesPosted) > 0): ?>
                        <ul>
                            <?php foreach ($ridesPosted as $ride): ?>
                                <li>
                                    <strong><a href="rideInfo.php?rideID=<?php echo $ride['rideID']; ?>">Ride ID: <?php echo $ride['rideID']; ?></a></strong><br>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>You have not posted any rides yet.</p>
                    <?php endif; ?>
                </div>
        
                <!-- Display Rides Taken by the User -->
                <div class="profilechild" id="taken">
                    <h2>Rides Taken</h2>
                    <?php if (count($ridesTaken) > 0): ?>
                        <ul>
                            <?php foreach ($ridesTaken as $ride): ?>
                                <li>
                                    <strong><a href="rideInfo.php?rideID=<?php echo $ride['rideID']; ?>">Ride ID: <?php echo $ride['rideID']; ?></a></strong><br>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>You have not taken any rides yet.</p>
                    <?php endif; ?>
                </div>
            </div>

        <button id="logoutButton" onclick="window.location.href='../html/index.html'">Logout</button>
            
        </div>
        


    </body>
</html>
