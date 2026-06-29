<?php

// Allow the user to update either the twitter handle or email or both, and update the users table

session_start();

require_once "connectDB.php";

$newUsername = $newTwitter = $newEmail = $confNewTwitter = $confNewEmail = "";

$newUsernameErr = $newTwitterErr = $newEmailErr = $confNewTwitterErr = $confNewEmailErr = $newTwitterID = "";

$currentTwitter = $current_email = "";

$SQLOption = 0;  //Decide what has changed


//Get the current twitter and email for the user (so can be checked if changed)
//This will be done on every call

$sql = "SELECT twitterHandle, email FROM users Where id = ?";
$stmt = $link->stmt_init();
//Attempt to execute sql statement

if($stmt->prepare($sql)){

    $stmt->bind_param("i", $paramID);

    $paramID = $_SESSION["id"];

    if($stmt->execute()){
        $stmt->store_result();

        if($stmt->num_rows() == 1 ){

            $stmt->bind_result($db_twitter,$db_email);
            if($stmt->fetch()){
                $currentTwitter = $db_twitter;
                $current_email = $db_email;
                $id = $paramID;
            
            }
        }
    } else{
        echo "Something went wrong. Please try again later";
    }

    $stmt->close();
}

// Process a POST 

if($_SERVER["REQUEST_METHOD"] == "POST"){

    //change Twitter
    $input_twitter = trim($_POST["new_twitter"]);
    $conf_input_twitter = trim($_POST["confirm_new_twitter"]);
    //var_dump($input_twitter);
    //var_dump($conf_input_twitter);
    if (empty($input_twitter)) {
        if (!empty($conf_input_twitter)) {
            $newTwitterErr = "Please enter twitter handle";
        }
    } else {
        //If the twitter handle has been entered.
        if (empty($conf_input_twitter)) {
            $confNewTwitterErr = "Please confirm twitter handle";
        } else {
            if (empty($NewTwitterErr) && ($input_twitter != $conf_input_twitter)) {
                $confNewTwitterErr = "Twitter handle does not match.";
            } else {

                //Check the twitter ID

                $handle = $input_twitter;
                $output = shell_exec("Python3 findUserID.py $handle");

                if (empty($output)) {

                    $newTwitterErr = "Please enter a valid twitter name";

                } else {

                    $newTwitterID = $output;
                    $newTwitter = $input_twitter;
                }

            }
        }
    }
    //change email
    $input_email = trim($_POST['new_email']);
    $inp_conf_email = trim($_POST["confirm_new_email"]);

    //Check if no email has been entered, and if a confirmation email has
    if (empty($input_email)) {


        if (!empty($inp_conf_email)) {
            $newEmailErr = "Please enter your email";
        }

    } else {
        // Check if there's nothing in the confirm email
        if (empty($inp_conf_email)) {
            $confNewEmailErr = "Please confirm email";

        } else {
            //Check if the two emails match
            if (empty($email_err) && ($input_email != $inp_conf_email)) {
                $confNewEmailErr = "The two emails do not match.";
            } else {
                //Now we can confirm each email.
                $newEmail = $input_email;
                
            }
        }
    }   


    //last error checks before entering into database
    if(empty($newTwitterErr) && empty($confNewTwitterErr) && empty($newEmailErr) && empty($confNewEmailErr)){
        //3 different sql statements depending on what the user has entered
        //Either to update twitter and username or just one of them.
        //Updating passwords are done elsewhere
        //SQLOption set to 1 if just Twitter, 2 if just email and 3 if both to control which sql to use

        $sql1 = "UPDATE users set twitterHandle = ?, twitterID = ? WHERE id = ?";
        $sql2 = "UPDATE users set email = ? WHERE id = ?";
        $sql3 = "UPDATE users set email=?, twitterHandle = ?, twitterID = ? WHERE id = ?";
     
        
        if ($newEmail != $current_email && !empty($newEmail)) {

            if ($newTwitter != $currentTwitter && !empty($newTwitter)) {
                echo '3';
                $SQLOption = 3;

            } else {
                echo '2';
                $SQLOption = 2;
            }

        } else {

            if ($newTwitter != $currentTwitter && !empty($newTwitter)) {
                echo '1';
                $SQLOption = 1;

            } 
        }


        
           //Prepare sql statement depending on the user's inputs

        if($SQLOption == 1){
            $stmt = $link->stmt_init();
            if($stmt->prepare($sql1)){
              
                $stmt->bind_param("ssi", $param_twitter, $param_twitterID, $param_ID);

                $param_twitter = $newTwitter;
                $param_twitterID = $newTwitterID;
                $param_ID = $id;
                
                
                if($stmt->execute()){
                    //echo "success";
                    header("location: login.php");
                } else{
                    echo "Something went wrong, please try again later.";
                }
                $stmt->close();

            }
        }
        
        elseif($SQLOption == 2){
            $stmt = $link->stmt_init();
            if($stmt->prepare($sql2)){
               
                $stmt->bind_param("si", $param_email, $param_ID);

                $param_email = $newEmail;
                $param_ID = $id;
                
                
                if($stmt->execute()){
                    $sql4 = "UPDATE users SET emailVerify = 'N' WHERE id=?";
                    if($stmt->prepare($sql4)){

                        $stmt->bind_param("i",$param_id);

                        $param_id = $id;

                        $stmt->execute();
                    }
                    //echo "success";
                    header("location: login.php");
                } else{
                    echo "Something went wrong, please try again later.";
                }
                $stmt->close();
                

            }
        } else{ 
            $stmt = $link->stmt_init();
            if($stmt->prepare($sql3)){
                
                $stmt->bind_param("sssi", $param_email, $param_twitter, $param_twitterID, $param_ID);

                $param_email = $newEmail;
                $param_twitter = $newTwitter;
                $param_twitterID = $newTwitterID;
                $param_ID = $id;
            
               
                if($stmt->execute()){
                    $sql4 = "UPDATE users SET emailVerify = 'N' WHERE id=?";
                    if($stmt->prepare($sql4)){

                        $stmt->bind_param("i",$param_id);

                        $param_id = $id;

                        $stmt->execute();
                    }
                    //echo "success";
                    header("location: login.php");
                } else{
                    echo "Something went wrong, please try again later.";
                }
                $stmt->close();
                

            }
        }
    }
    


}
//Close the link
$link->close();
?>
<!DOCTYPE html>
<html lang = "en">
<head>
    <meta charset="UTF-8">
    <title>Update Profile</title>
    <link rel="stylesheet" href="../css/siteStyle.css">
</head>
<body>
    <div>
        <h2>Change Profile</h2>
        <p>Please complete the form below to verify your new details: </p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div>
            <label>New Email</label>
            <input type = "text" name = "new_email" value="<?php echo $newEmail; ?>">
            <span><?php echo $newEmailErr; ?></span>


        </div>
        <div>
            <label>Confirm New Email</label>
            <input type = "text" name = "confirm_new_email" value="<?php echo $confNewEmail; ?>">
            <span><?php echo $confNewEmailErr; ?></span>


        </div>
        <div>
            <label>New Twitter Handle</label>
            <input type = "text" name = "new_twitter" value="<?php echo $NewTwitter; ?>">
            <span><?php echo $newTwitterErr; ?></span>


        </div>
        <div>
            <label>Confirm New Twitter Handle</label>
            <input type = "text" name = "confirm_new_twitter" class="form-control <?php echo (!empty($confNewTwitterErr)) ? 'is-invalid' : ''; ?>" value="<?php echo $confNewTwitter; ?>">
            <span><?php echo $confNewTwitterErr; ?></span>


        </div>
        <div>
                <input type="submit" value="Submit">
                <input type="reset" value="Reset">
                <a href="dashboard.php">Cancel<a/>
        </div>
        <p>Want to change your password? <a href="createCode.php">Click here</a>.</p>

    </form>
    </div>
</body>
</html>