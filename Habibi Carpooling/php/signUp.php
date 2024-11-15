<!DOCTYPE html>
<html>
    <head>
        <title>Sign Up</title>
        <link rel="stylesheet" href="habibiStyles.css">
        <link href="https://fonts.googleapis.com/css2?family=Sour+Gummy:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    </head>

    <body class="blurredBackground">
        <div id="signup">
            

            <!-- Form submission will be handled by PHP below -->
            <form id="signupForm" action="signUp.php" method="POST">
                <fieldset>
                    <legend>Create Your Account!</legend>
                    <br>

                    <input id="text" type="text" name="user" placeholder="Enter your username" required>
                    <br><br>
                

                    <input id="text" type="password" name="password" placeholder="Enter your password"required>
                    <br><br>

                    
                    <input id="text2" type="email" name="email" placeholder="Email" required>
                    <br><br>
                   
                    <input id="text2" type="tel" name="telephone" placeholder="Phone Number " required>
                    <br><br>

                    <input id="signupButton" type="submit" value="Sign Up">
                </fieldset>
            </form>
        </div>
    </body>
</html>

<?php
    // Check if the form is submitted
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Sanitize user input 
        $user = htmlspecialchars($_POST['user']);
        $password = htmlspecialchars($_POST['password']);
        $email = htmlspecialchars($_POST['email']);
        $telephone = htmlspecialchars($_POST['telephone']);


        // Database connection details
        $host = 'localhost'; //Database host
        $myUsername = 'root'; //Database username
        $myPassword = ''; //Database password
        $dbname = 'profiles'; // Database name

        // Create a MySQLi connection
        $conn = new mysqli($host, $myUsername, $myPassword, $dbname);

        // Check for connection errors
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        //make sure they don't already exist in the db with their username, email or telephone, and give them error messages
        // Check if username already exists
        $sql_check_username = "SELECT COUNT(*) FROM users WHERE username = ?";
        $stmt_check_username = $conn->prepare($sql_check_username);
        $stmt_check_username->bind_param("s", $user);
        $stmt_check_username->execute();
        $stmt_check_username->bind_result($username_exists);
        $stmt_check_username->fetch();
        $stmt_check_username->close();

        // Check if email already exists
        $sql_check_email = "SELECT COUNT(*) FROM users WHERE email = ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param("s", $email);
        $stmt_check_email->execute();
        $stmt_check_email->bind_result($email_exists);
        $stmt_check_email->fetch();
        $stmt_check_email->close();

        // Check if telephone already exists
        $sql_check_telephone = "SELECT COUNT(*) FROM users WHERE telephone = ?";
        $stmt_check_telephone = $conn->prepare($sql_check_telephone);
        $stmt_check_telephone->bind_param("s", $telephone);
        $stmt_check_telephone->execute();
        $stmt_check_telephone->bind_result($telephone_exists);
        $stmt_check_telephone->fetch();
        $stmt_check_telephone->close();

        // If any of the fields already exist, show an error message
        if ($username_exists > 0) {
            echo "Username already exists. Please choose a different one.";
        } elseif ($email_exists > 0) {
            echo "Email already exists. Please choose a different one.";
        } elseif ($telephone_exists > 0) {
            echo "Telephone number already exists. Please choose a different one.";
        }else{
            // SQL query to insert user data
            $sql = "INSERT INTO users (username, password, email, telephone) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            // Bind parameters to the query
            $stmt->bind_param("ssss", $user, $password, $email, $telephone);

            // Execute the query
            if ($stmt->execute()) {
                echo "Sign up successful!";
                header("Location: profile.php");
                exit;
            } else {
                echo "Something went wrong. Please try again.";
            }

            // Close the statement and connection
            $stmt->close();
            $conn->close();
    }
        
    }
?>
