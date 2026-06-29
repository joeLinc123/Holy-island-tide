<?php
// Enable the user to reset their password, update and redirect to the login page

require_once "connectDB.php";
//Include the connectDB file so can query the database.
//Start the session
session_start();

//Checks if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

//Create variables
$newPassword = $confirmPassword = $newPassword_err = $confirmPassword_err =  "";

//Checks whether the user has entered the form
if($_SERVER["REQUEST_METHOD"] == "POST"){

    //Checks if the new password input box is empty
    //If it is, display error message
    if(empty(trim($_POST["newPassword"]))){
        $newPassword_err = "Please enter the new password: ";
        //Checks the length of the new password
    } elseif(strlen(trim($_POST["newPassword"])) <6 ){
        $newPassword_err = "Password must have at least 6 characters.";
    } else{ 
        $newPassword = trim($_POST["newPassword"]); //If all ok, store the new password as a variable
    }

    if(empty(trim($_POST["confirmPassword"]))){ 
        $confirmPassword_err = "Please confirm the password: "; //Validate the confirm password, if the box is empty, display error

    } 
     else{
        $confirmPassword = trim($_POST["confirmPassword"]); //Then make sure the passwords match.
        if(empty($newPassword_err) && ($newPassword != $confirmPassword)){
            $confirmPassword_err = "passwords do not match";
        }
    }
    //Last checks before sql query
    if(empty($newPassword_err) && empty($confirmPassword_err)){
        //Update the user's password
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $link->stmt_init();
        if($stmt->prepare($sql)){
            
            $stmt->bind_param("si", $paramPassword, $paramID);
            //Make sure that the password is hashed before entering into the databse
            $paramPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $paramID = $_SESSION["id"];

            if($stmt->execute()){
                //If the statement is successful, redirect to login page
                session_destroy();
                header("location: login.php");
                exit();

            } else {
                //If not, display error message
                echo "Something went wrong, please try again later";
            }

            $stmt->close();
        }

    }
    $link->close();

    //Close statement and link.
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../css/siteStyle.css">

</head>
<body>
    <div class = "wrapper">
        <h2>Reset Password</h2>
        <P>Fill in these boxes to reset your password: </P>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div>
                <label>New Password</label>
                <input type = "password" name="newPassword"
                value="<?php echo $newPassword; ?>">
                <span><?php echo $newPassword_err; ?></span>

            </div>
            <div>
                <label>Confirm New Password</label>
                <input type = "password" name="confirmPassword"
                value="<?php echo $confirmPassword; ?>">
                <span><?php echo $confirmPassword_err; ?></span>

            </div>
            <div>
                <input type="submit" value="submit">
                <a href="dashboard.php">Cancel</a>
            </div>
    </form>
    </div>
</body>
</html>