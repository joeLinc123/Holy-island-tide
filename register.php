<?php

    // php to register a new user. They will enter their details, validate, be set up on database, and redirected to login screen

    require("connectDB.php");
    

    $username = $password = $conf_password = $conf_twitter = $conf_email = $email = $twitter = $twitterID = "";
    $username_err = $password_err = $conf_password_err = $email_err = $conf_email_err = $twitter_err = $conf_twitter_err = "";
    
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username

    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username. ";
    } else {
        // sql statement - selects IDs from the users table where the username is not there.
        $sql = "SELECT id FROM users WHERE username = ?";
        
        $stmt=$link->stmt_init();

        if ($stmt->prepare($sql)) {
            
            $stmt->bind_param("s", $param_username);
            
            $param_username = trim($_POST["username"]);
        
            if ($stmt->execute()) {
                
                $stmt->store_result();
                //Error checking - check if the username has not already been used.
                if ($stmt->num_rows() == 1) {
                    $username_err = "This username is not availiable";


                } else {
                    $username = trim($_POST["username"]);
                }

            } else {
                echo "Something went wrong - D'oh! Please try again later.";
            }
            //close statement.
            $stmt->close();
        }

    }
        
    //Some validation of the email

    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email.";
    
    } else{
        $email = trim($_POST["email"]);
    }
    
    if(empty(trim($_POST["confirm_email"]))){
        $conf_email_err = "Please confirm email";
        
    } else {

        $conf_email = trim($_POST["confirm_email"]);

        if(empty($email_err) && ($email!= $conf_email)){
            $conf_email_err = "The two emails do not match";
        }
    }
        
    //Some validation of the input password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password";
    } elseif (strlen(trim($_POST['password'])) < 6) {
        $password_err = "Password must have more than 6 characters";
    } else{
        $password = trim($_POST["password"]);
    }
    //Validation of the confirmed password
    if (empty(trim($_POST["confirm_password"]))) {
        $conf_password_err = "Please confirm password";
        
    } else {

        $conf_password = trim($_POST["confirm_password"]);

        //Check if passwords match
        if (empty($password_err) && ($password != $conf_password)) {
            $conf_password_err = "Passwords did not match.";
        }

    }

    //Validate twitter handle (optional)
   if(!empty(trim($_POST["twitter"]))){

        //Validate the twitter handle
        $handle = trim($_POST["twitter"]);
        $output = shell_exec("Python3 findUserID.py $handle");

        if (empty($output)) {

            $twitter_err = "Please enter a valid Twitter Name";

        } else {

            $twitterID = $output;
            //Check the confirm 

            if(empty(trim($_POST["confirm_twitter"]))){
                $conf_twitter_err = "Please confirm twitter";
            } else {  //confirm twitter not empty
                if(empty($twitter_err) && ($twitter != $conf_twitter)){
                    $conf_twitter_err = "twitter handles do not match";
                } else {
                    $twitter = trim($_POST["twitter"]);
                }
            }

        }  
   } else {  //Twitter is empty

        if(!empty(trim($_POST["confirm_twitter"]))){
            $twitter_err = "twitter handles do not match";
        } 
    }


    //last checks before sql query
    if (empty($username_err) && empty($password_err) && empty($conf_password_err) && empty($twitter_err) && empty($conf_twitter_err) && empty($email_err) && empty($conf_email_err)) {

        $sql = "INSERT INTO users (username, password, twitterHandle, twitterID, email, emailVerify) VALUES (?,?,?,?,?,?)";
        $stmt = $link->stmt_init();
        if ($stmt->prepare($sql)) {
            $stmt->bind_param("ssssss", $param_username, $param_password, $param_twitter, $param_twitterID, $param_email, $param_emailVerify);

            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            $param_twitter = $twitter;
            $param_twitterID = $twitterID;
            $param_email = $email;
            $param_emailVerify = "N";


            if ($stmt->execute()) {

                header("location: Login.php");

            } else {
                echo "There was a problem!";
            }

            $stmt->close();
        }
    }

    $link->close();

    


}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <link rel="stylesheet" href="../css/siteStyle.css">
</head>
<body>
    <div>
        <h2>Sign Up</h2>
        <p>Please fill this form to create an account.</p>
        <!-- The $SERVER(PHP_SELF) holds the current script being executed, Therefore this means to call itself again when the post is done - and how the PHP checking code is called -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div>
                <label>Username</label>
                <?php 
                if (!empty($username_err)){
                    echo "<input class = 'formItem-invalid' type='text' name='username' value='" . $username . "'>";
                } else{
                    echo "<input class = 'formItem' type='text' name='username' value='" . $username . "'>";
                }
                ?>
                
                
                <span class="formItem"><?php echo $username_err; ?></span>
            </div>    
            <div>
                <label>Password</label>
                <?php 
                if (!empty($password_err)){
                    echo "<input class = 'formItem-invalid' type='password' name='password' value='" . $password . "'>";
                } else{
                    echo "<input class = 'formItem' type='password' name='password' value='" . $password . "'>";
                }
                ?>
                
                <span class="formItem"><?php echo $password_err; ?></span>
            </div>
            <div>
                <label>Confirm Password</label>
                <?php 
                if (!empty($conf_password_err)){
                    echo "<input class = 'formItem-invalid' type='password' name='confirm_password' value='" . $conf_password . "'>";
                } else{
                    echo "<input class = 'formItem' type='password' name='confirm_password' value='" . $conf_password . "'>";
                }
                ?>
                <span class="formItem"><?php echo $conf_password_err; ?></span>
            </div>
            <div>
                <label>Twitter Handle (Optional)</label>
                <?php 
                if (!empty($twitter_err)){
                    echo "<input class = 'formItem-invalid' type='text' name='twitter' value='" . $twitter . "'>";
                } else{
                    echo "<input class = 'formItem' type='text' name='twitter' value='" . $twitter . "'>";
                }
                ?>
                
                <span class="formItem"><?php echo $twitter_err; ?></span>
            </div>  
            <div>
                <label>Confirm Twitter Handle</label>
                <?php 
                if (!empty($conf_twitter_err)){
                    echo "<input class = 'formItem-invalid' type='text' name='confirm_twitter' value='" . $conf_twitter . "'>";
                } else{
                    echo "<input class = 'formItem' type='text' name='confirm_twitter' value='" . $conf_twitter . "'>";
                }
                ?>
            
                <span class="formItem"><?php echo $conf_twitter_err; ?></span>
            </div>  
            <div>
                <label>Email</label>
                <?php 
                if (!empty($email_err)){
                    echo "<input class = 'formItem-invalid' type='text' name='email' value='" . $email . "'>";
                } else{
                    echo "<input class = 'formItem' type='text' name='email' value='" . $email . "'>";
                }
                ?>
                
                <span class="formItem"><?php echo $email_err; ?></span>
            </div>  
            <div>
                <label>Confirm Email</label>
                <?php 
                if (!empty($conf_email_err)){
                    echo "<input class = 'formItem-invalid' type='text' name='confirm_email' value='" . $conf_email . "'>";
                } else{
                    echo "<input class = 'formItem' type='text' name='confirm_email' value='" . $conf_email . "'>";
                }
                ?>
                
                <span class="formItem"><?php echo $conf_email_err; ?></span>
            </div>  
            <div>
                <input type="submit" value="Submit">
                <input type="reset" value="Reset">
            </div>
            <p>Already have an account? <a href="login.php">Login here</a>.</p>
        </form>
    </div>    
</body>
</html>
