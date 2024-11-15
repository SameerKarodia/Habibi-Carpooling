<?php
session_start(); // Ensure session is started to get the logged-in user

// Check if the user is logged in (based on session variable)
if (!isset($_SESSION['username'])) {
    // If not logged in, redirect to the homepage or login page
    header("Location: ../../index.html"); // Replace with your homepage URL if it's not "index.php"
    exit; // Stop further code execution to ensure the redirect works
}

$userName = $_SESSION['username']; // Get the logged-in username

// Database connection details
$host = 'sql207.infinityfree.com'; // Database host
$dbname = 'if0_37721054_profiles'; // Database name
$myUsername = 'if0_37721054'; // Database username
$myPassword = 'XBy6Pc3xIhSzC'; // Database password

// Create a MySQLi connection
$mysqli = new mysqli($host, $myUsername, $myPassword, $dbname);

// Check the connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Check if the form is submitted to join a ride
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ride_id'])) {
    $rideID = $_POST['ride_id']; // Ride ID of the ride the user wants to join

    // Get the current passenger list and passengers count for this ride
    $sql = "SELECT passengersList, passengersInt FROM rides WHERE rideID = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('i', $rideID); // 'i' means integer
        $stmt->execute();
        $stmt->bind_result($passengersListJson, $passengersInt);
        $stmt->fetch();
        $stmt->close();

        if ($passengersListJson !== null) {
            $passengersList = json_decode($passengersListJson, true); // Decode the JSON list

            // Check if the user is already in the passenger list
            if (in_array($userName, $passengersList)) {
                echo "You have already joined this ride.";
            } elseif (count($passengersList) >= $passengersInt) {
                echo "Sorry, this ride has reached its maximum number of passengers.";
            } else {
                // Add the current user to the passenger list
                $passengersList[] = $userName;

                // Encode the updated passenger list back to JSON
                $updatedPassengersListJson = json_encode($passengersList);

                // Update the ride with the new passenger list
                $sql = "UPDATE rides SET passengersList = ? WHERE rideID = ?";
                if ($stmt = $mysqli->prepare($sql)) {
                    $stmt->bind_param('si', $updatedPassengersListJson, $rideID); // 's' for string, 'i' for integer
                    if ($stmt->execute()) {
                        echo "You have successfully joined the ride!";
                    } else {
                        echo "Something went wrong. Please try again.";
                    }
                    $stmt->close();
                }
            }
        } else {
            echo "Ride not found.";
        }
    } else {
        echo "Error preparing query.";
    }
}

// Get all rides (without filtering out the driver's own rides)
$sql = "SELECT rideID, origin, destination, rideDate, passengersList, passengersInt, driver FROM rides WHERE passengersList IS NULL OR JSON_LENGTH(passengersList) < passengersInt";
$result = $mysqli->query($sql);

// Filter out the rides where the logged-in user is the driver
$rides = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['driver'] !== $userName) {
            $rides[] = $row;
        }
    }
}

$mysqli->close(); // Close the connection to the database

?>

<!DOCTYPE html>
<html>
<head>
    <title>Search for Rides</title>
    <link rel="stylesheet" href="../css/habibiStyles.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Sour+Gummy:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
</head>
<body>
    <div class="rides-container">
        <h1>Available Rides</h1>

        <?php if ($rides): ?>
            <table class="rides-table">
                <tr>
                    <th>From</th>
                    <th>To</th>
                    <th>Departure Time</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($rides as $availableRide): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($availableRide['origin']); ?></td>
                        <td><?php echo htmlspecialchars($availableRide['destination']); ?></td>
                        <td><?php echo htmlspecialchars($availableRide['rideDate']); ?></td>
                        <td>
                            <?php
                            $passengersList = json_decode($availableRide['passengersList'], true);
                            if (count($passengersList) < $availableRide['passengersInt']):
                            ?>
                                <form action="searchRide.php" method="POST">
                                    <input type="hidden" name="ride_id" value="<?php echo $availableRide['rideID']; ?>">
                                    <input type="submit" class="join-ride-btn" value="Join Ride">
                                </form>
                            <?php else: ?>
                                <span class="ride-full">Ride Full</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No rides available.</p>
        <?php endif; ?>

        <button class="back-button" onclick="window.location.href='profile.php'">Back</button>
    </div>
</body>
</html>
