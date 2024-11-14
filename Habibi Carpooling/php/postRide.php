<?php
session_start(); 

// Check if the user is logged in (based on session variable)
if (!isset($_SESSION['username'])) {
    // If not logged in, redirect to the homepage or login page
    header("Location: ../html/homepage.html"); // Replace with your homepage URL if it's not "index.php"
    exit; // Stop further code execution to ensure the redirect works
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect the data from the form
    $departure = htmlspecialchars($_POST['departure']);
    $destination = htmlspecialchars($_POST['destination']);
    $date = htmlspecialchars($_POST['date']);
    $passengersInt = htmlspecialchars($_POST['seats']);
    $passengersListJson = json_encode([]); // Initialize an empty list for passengers

    // Get the username from the session (assuming the user is logged in)
    $username = $_SESSION['username']; // This will hold the logged-in user's username

    // Database connection details
    $host = 'localhost';
    $myUsername = 'root'; // Your MySQL username
    $myPassword = ''; // Your MySQL password (leave empty if not set)
    $dbname = 'profiles'; // The name of your database

    // Create a MySQLi connection
    $conn = new mysqli($host, $myUsername, $myPassword, $dbname);

    // Check the connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare the SQL query to insert the ride details into the database
    $sql = "INSERT INTO rides (driver, origin, destination, rideDate, passengersInt, passengersList) 
            VALUES (?, ?, ?, ?, ?, ?)";

    // Prepare the statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind the parameters to the query
        $stmt->bind_param("ssssis", $username, $departure, $destination, $date, $passengersInt, $passengersListJson);

        // Execute the query
        if ($stmt->execute()) {
            echo "Ride posted successfully!"; // Provide feedback
            // Redirect to the profile page
            header('Location: profile.php');
            exit;
        } else {
            echo "Something went wrong. Please try again."; // Handle failure
        }

        // Close the prepared statement
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error; // Handle SQL errors
    }

    // Close the database connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
    <title>Post a ride</title>

    <head>
        <link rel="stylesheet" href="habibiStyles.css">
        <link href="https://fonts.googleapis.com/css2?family=Sour+Gummy:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    </head>

    <body>
        <div id="postRideForm">
            <form action="postRide.php" method="POST">
                <fieldset>
                    <legend>Enter Ride Details</legend>

                    Source<br>
                    <input type="text" name="departure" required>
                    </br>

                    Destination<br>
                    <input type="text" name="destination" required>
                    </br>

                    Departure Time<br>
                    <input type="datetime-local" name="date" required>
                    </br>

                    Seats Available<br>
                    <input type="number" name="seats" required>
                    </br>

                    <input type="submit">

                </fieldset>
            </form>
        </div>
        <button class="back-button" onclick="window.location.href='profile.php'">Back</button>

    </body>
</html>
