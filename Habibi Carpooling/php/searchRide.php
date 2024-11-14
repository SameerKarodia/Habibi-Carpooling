<?php
session_start(); // Ensure session is started to get the logged-in user

// Check if the user is logged in (based on session variable)
if (!isset($_SESSION['username'])) {
    // If not logged in, redirect to the homepage or login page
    header("Location: ../html/homepage.html"); // Replace with your homepage URL if it's not "index.php"
    exit; // Stop further code execution to ensure the redirect works
}

$userName = $_SESSION['username']; // Get the logged-in username

// Database connection details
$host = 'localhost';
$dbUsername = 'root'; // Your MySQL username
$dbPassword = ''; // Add your database password if required
$dbname = 'profiles'; // The name of your database

// Check if the form is submitted to join a ride
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ride_id'])) {
    $rideID = $_POST['ride_id']; // Ride ID of the ride the user wants to join

    try {
        // Create a PDO connection
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbUsername, $dbPassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get the current passenger list and passengers count for this ride
        $checkRide = $pdo->prepare("SELECT passengersList, passengersInt FROM rides WHERE rideID = :rideID");
        $checkRide->bindParam(':rideID', $rideID);
        $checkRide->execute();
        $ride = $checkRide->fetch(PDO::FETCH_ASSOC);

        if ($ride) {
            $passengersList = json_decode($ride['passengersList'], true); // Decode the JSON list
            $passengersInt = $ride['passengersInt']; // Maximum allowed passengers

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
                           $sql = "UPDATE rides SET passengersList = :passengersList WHERE rideID = :rideID";
                           $stmt = $pdo->prepare($sql);
                           $stmt->bindParam(':passengersList', $updatedPassengersListJson);
                           $stmt->bindParam(':rideID', $rideID);

                           if ($stmt->execute()) {
                               echo "You have successfully joined the ride!";
                           } else {
                               echo "Something went wrong. Please try again.";
                           }
                       }
                   } else {
                       echo "Ride not found.";
                   }
               } catch (PDOException $e) {
                   echo "Error: " . $e->getMessage();
               }
           }

           // Get all rides (without filtering out the driver's own rides)
           try {
               $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbUsername, $dbPassword);
               $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

               $stmt = $pdo->prepare("SELECT rideID, origin, destination, rideDate, passengersList, passengersInt, driver FROM rides WHERE passengersList IS NULL OR JSON_LENGTH(passengersList) < passengersInt");
               $stmt->execute();
               $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

               // Filter out the rides where the logged-in user is the driver
               $rides = array_filter($rides, function($ride) use ($userName) {
                   return $ride['driver'] !== $userName; // Exclude the ride if the driver is the logged-in user
               });
           } catch (PDOException $e) {
               echo "Error: " . $e->getMessage();
           }
           ?>

<!DOCTYPE html>
<html>
<head>
    <title>Search for Rides</title>
    <link rel="stylesheet" href="habibiStyles.css"> 
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

