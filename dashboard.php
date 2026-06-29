<?php

//php for the dashboard when the user logs in
//Gives options to manage journeys, profile or log out

require_once "connectDB.php";
//Include connectDB.php
session_start();

//Initialise variables
$displayTwitter = $displayEmail = "";
$displayEmailVerify = "Email verified";
$retEmailVal = "";

//Checks if the user is logged in or not
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    //If not logged in, redirects to the login page
    header("location: login.php");
    exit;
}

//Prepare sql statement
//Selects the twitter handle amd the email for the logged in user
$sql = "SELECT twitterHandle, email, emailVerify FROM users WHERE username = ?";
$stmt = $link->stmt_init();
if($stmt->prepare($sql)){
    $stmt->bind_param('s', $param_username);
    //Bind paramaters
    //Set the paramater = to the username of the user in the session.
    $param_username = $_SESSION["username"];
    //Attempt to execute the statement
    if($stmt->execute()){
        $stmt->store_result();

        if ($stmt->num_rows()==1){
            //Bind the results
            $stmt->bind_result($twitter, $email, $emailVerify);
            if($stmt->fetch()){
                //Set the display values equal to the returned values, handle unverified email
                $displayTwitter = $twitter;
                $displayEmail = $email;
                $retEmailVal = $emailVerify;
                if(!empty($displayEmail)){
                    if($retEmailVal == "N"){
                        $displayEmailVerify = "Email not verified. Please verify your details";
                    }
                }

            } else{
                echo "Something went wrong, please logout and try again.";
            }
        } else{
            echo "Something went wrong, please logout and try again.";
        }
    }else{
        echo "Something went wrong, please try again later.";
    }
    //Close the statement.
    mysqli_stmt_close($stmt);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <link rel="stylesheet" href="../css/siteStyle.css">
</head>
<body>
    <div class="row">
        <div class="col-left">
            <table class="dash">
                <tr class="dash">
                    <td class="dash">
                        <a href="updateProfile.php" class="blueLinkbutton">Change Profile</a>
                    </td>
                </tr>
                <tr class="dash">
                    <td class="dash">
                        <a href="createCode.php" class="blueLinkbutton">Reset Password</a>
                    </td>
                </tr>
                <tr class="dash">
                    <td class="dash">
                        <a href="journeyIndex.php" class="blueLinkbutton">Add Journey</a>
                    </td>
                </tr>
                <tr class="dash">
                    <td class="dash">
                        <a href="createVerifyCode.php?source=register" class="blueLinkbutton">Validate Email</a>
                    </td>
                </tr>
                <tr class="dash">
                    <td class="dash">
                        <a href="logout.php" class="redLinkbutton">Sign Out</a>
                    </td>
                </tr>
            </table>
        </div>
        <div class="col-right">
            <div>
                <h1 align="center">Hi, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>. Welcome to your dashboard.</h1>
            </div>
            <div>
                <h2 align="center">Twitter Handle:@ <?php echo $displayTwitter; ?> </h2>
            </div>
            <div>
                <?php

                    //Check the email verified flag, and set the class (css) to either green or red colour to show to user

                    if ($retEmailVal =="N") {
                        $class = "unverified";
                    }  else {
                        $class = "verified";
                    }
                ?>

                <h2 align="center">Email: <?php echo $displayEmail . "<span class=$class> (" . $displayEmailVerify . ")</span>"; ?> </h2>
            </div>
        </div>
    </div>
</body>
</html>