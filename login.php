<?php

// php to handle the login.
// If the user is already logged in via $_SESSION variable then redirect to the dashboard
// Otherwise either process the login, or let the user register

// Initialize the session
session_start();
 
// Check if the user is already logged in, if yes then redirect user to the dashboard
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: dashboard.php");
    exit;
}
 
// Include database config file
require "connectDB.php";
 
// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if username is empty, set the username to the posted value if not
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty, set the password to posted value if not
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Pull in id, username and password from the users table for the input username. If 1 record is returned, username exists
        $sql = "SELECT id, username, password FROM users WHERE username = ?";
        $stmt = $link->stmt_init();
        if($stmt->prepare($sql)){
           
            $stmt->bind_param("s", $param_username);
            
            $param_username = $username;
            
           
            if($stmt->execute()){
               
                $stmt->store_result();
                
               
                if($stmt->num_rows() == 1){                    
                    // Bind result variables. The password is stored hashed.
                    $stmt->bind_result($id, $username, $hashed_password);
                    if($stmt->fetch()){
                        // use the password_verify function to check the hashed password against that input
                        if(password_verify($password, $hashed_password)){
                            // Password ok
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;                            
                            
                            // Redirect user to dashboard page
                            header("location: dashboard.php");
                        } else{
                            // Password is not valid, error message is displayed
                            $login_err = "Invalid password.";
                        }
                    }
                } else{
                    // Username doesn't exist, display error message
                    $login_err = "Invalid username.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";  //trap failed DB call
            }

            // Close statement
            $stmt->close();
        }
    }
    
    // Close connection
    $link->close();
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="../css/siteStyle.css">
    <title>Login</title>
</head>
<body>
    <H2>Welcome to the Holy Island Crossing</H2>
    <div>
        
        <a href="welcomePage.php"><i class="material-icons">home</i></a>
        
    </div>
    <div class="wrapper">
        <h2>Login</h2>
        <p>Please fill in your credentials to login.</p>

        <?php 
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div>
                <label>Username</label>
                <input type="text" name="username" class = "formItem <?php echo (!empty($username_err)) ? '-invalid' : ''; ?> value="<?php echo $username; ?>">
                <span><?php echo $username_err; ?></span>
            </div>    
            <div ">
                <label>Password</label>
                <input type="password" name="password" >
                <span><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" value="Login">
            </div>
            <p>Don't have an account? <a href="register.php">Sign up now</a>.</p>
            <p>We care about your privacy.<a href="PrivacyPolicy.HTML">Click here for privacy policy</a>.</p>
        </form>
    </div>
</body>
</html>