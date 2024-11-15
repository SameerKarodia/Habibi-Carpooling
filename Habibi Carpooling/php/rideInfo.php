<?php
    session_start();

    // Check if the user is logged in
    if (!isset($_SESSION['username'])) {
        // If not logged in, redirect to the homepage
        header("Location: ../html/index.html");
        exit;
    }

    // Get the username
    $username = $_SESSION['username'];

    // Get the ride ID from the URL query string
    if (!isset($_GET['rideID'])) {
        echo "Ride ID is missing.";
        exit;
    }
    $rideID = $_GET['rideID'];

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

    // Get the ride details using the rideID
    $sql = "SELECT * FROM rides WHERE rideID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $rideID); // Bind the rideID as an integer
    $stmt->execute();
    $result = $stmt->get_result();

    // If no ride is found, show an error
    if ($result->num_rows == 0) {
        echo "Ride not found.";
        exit;
    }

    // Fetch the ride details
    $ride = $result->fetch_assoc();

    // Check if the logged-in user is the driver or a passenger
    $isDriver = ($ride['driver'] == $username);
    $passengers = json_decode($ride['passengersList'], true);
    $isPassenger = ($ride['driver'] != $username);

    // Fetch contact info for the driver and passengers
    $driverContact = null;
    $passengerContacts = [];

    if ($isDriver) {
        // Driver: Show the passengers' contact info
        foreach ($passengers as $passenger) {
            $sqlContact = "SELECT email, telephone FROM users WHERE username = ?";
            $stmtContact = $conn->prepare($sqlContact);
            $stmtContact->bind_param("s", $passenger);
            $stmtContact->execute();
            $resultContact = $stmtContact->get_result();
            if ($resultContact->num_rows > 0) {
                $passengerInfo = $resultContact->fetch_assoc();
                $passengerContacts[] = [
                    'email' => $passengerInfo['email'],
                    'telephone' => $passengerInfo['telephone']
                ];
            }
            $stmtContact->close();
        }
    } else {
        // Passenger: Show the driver's contact info
        $sqlContact = "SELECT email, telephone FROM users WHERE username = ?";
        $stmtContact = $conn->prepare($sqlContact);
        $stmtContact->bind_param("s", $ride['driver']);
        $stmtContact->execute();
        $resultContact = $stmtContact->get_result();
        if ($resultContact->num_rows > 0) {
            $driverContact = $resultContact->fetch_assoc();
        }
    }

    $stmt->close();

    // Handle ride deletion or passenger removal if the form is submitted
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['deleteRide']) && $isDriver) {
            // Delete the ride from the database
            $sqlDelete = "DELETE FROM rides WHERE rideID = ?";
            $stmtDelete = $conn->prepare($sqlDelete);
            $stmtDelete->bind_param("i", $rideID);
            $stmtDelete->execute();
            $stmtDelete->close();
            $conn->close();

            header("Location: profile.php"); // Redirect after deletion
            exit;
        }

        if (isset($_POST['removePassenger']) && $isPassenger) {
            // Remove the user from the passengers list
            $passengers = array_diff($passengers, [$username]);  // Remove the user from the list
            $newPassengersList = json_encode(array_values($passengers));  // Update the indexes in the array

            // Calculate the new number of passengers
            $newPassengersCount = count($passengers);

            // Update the passengers list and number of passengers in the database
            $sqlUpdate = "UPDATE rides SET passengersList = ?, passengersInt = ? WHERE rideID = ?";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bind_param("sii", $newPassengersList, $newPassengersCount, $rideID); // Bind 3 parameters
            $stmtUpdate->execute();
            $stmtUpdate->close();

            // Redirect back to their profile page after removal
            $conn->close();
            header("Location: profile.php");
            exit;
            }
        }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ride Info</title>
    <link rel="stylesheet" href="habibiStyles.css">
</head>
<body>

<div id="rideInfo">
    <h1>Ride Information</h1>

    <h2>Ride ID: <?php echo htmlspecialchars($ride['rideID']); ?></h2>
    <p><strong>Driver:</strong> <?php echo htmlspecialchars($ride['driver']); ?></p>
    <p><strong>Origin:</strong> <?php echo htmlspecialchars($ride['origin']); ?></p>
    <p><strong>Destination:</strong> <?php echo htmlspecialchars($ride['destination']); ?></p>
    <p><strong>Date:</strong> <?php 
        $rideDate = new DateTime($ride['rideDate']);
        echo $rideDate->format('l, F j, Y g:i A');
    ?></p>
    <p><strong>Seats:</strong> <?php echo htmlspecialchars($ride['passengersInt'] + 1); ?></p>
    <p><strong>Passengers:</strong> <?php 
        echo count($passengers) > 0 ? implode(', ', $passengers) : "No passengers yet.";
    ?></p>

    <!-- Display contact info based on user role -->
    <?php if ($isDriver): ?>
        <h3>Passenger Contact Info:</h3>
        <ul>
            <?php foreach ($passengerContacts as $contact): ?>
                <li>
                    <strong>Email:</strong> <?php echo htmlspecialchars($contact['email']); ?><br>
                    <strong>Telephone:</strong> <?php echo htmlspecialchars($contact['telephone']); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php elseif ($isPassenger): ?>
        <h3>Driver's Contact Info:</h3>
        <p>
            <strong>Email:</strong> <?php echo htmlspecialchars($driverContact['email']); ?><br>
            <strong>Telephone:</strong> <?php echo htmlspecialchars($driverContact['telephone']); ?>
        </p>
    <?php endif; ?>

    <!-- Allow the driver to delete the ride -->
    <?php if ($isDriver): ?>
        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this ride?');">
            <button type="submit" name="deleteRide">Delete Ride</button>
        </form>
    <?php endif; ?>

    <!-- Allow passengers to remove themselves -->
    <?php if ($isPassenger): ?>
        <form method="POST" onsubmit="return confirm('Are you sure you want to remove yourself from this ride?');">
            <button type="submit" name="removePassenger">Remove Me</button>
        </form>
    <?php endif; ?>

</div>

<button onclick="window.location.href='profile.php'">Back to Profile</button>

</body>
</html>
