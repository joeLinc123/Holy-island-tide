
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>Welcome - Please enter your details below:</title>
</head>
<body>
<?php

    // php to register a new user. They will enter their details, validate, be set up on database, and redirected to login screen


    require("connectDB.php");
    

    $username = $password = $conf_password = $conf_twitter = $conf_email = $email = $twitter = "";
    $username_err = $password_err = $conf_password_err = $email_err = $conf_email_err = $twitter_er = $conf_twitter_er = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //Validation for username - if the box is empty:
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username. ";
    } else {
        // sql statement - selects IDs from the users table where the username is not there.
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt->$link->stmt_init();
        if ($stmt->prepare($sql)) {
            
            $stmt->bind_param("s", $param_username);
            
            $param_username = trim($_POST["username"]);
            //Attempt to execute the sql query
            if ($stmt->execute()) {
                //Store the result of the statement.
                $stmt->store_result();
                //Error checking - check if the username has not already been used.
                if ($stmt->num_rows == 1) {
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
    // confirm email
    $input_email = trim($_POST['email']);
    $inp_conf_email = trim($POST["confirm_email"]);

    //Check if no email has been entered, and if a confirmation email has
    if (empty($input_email)) {


        if (!empty($inp_conf_email)) {
            $email_err = "Please enter your email";
        }

    } else{

        // Check if there's nothing in the confirm email
        if (empty($inp_conf_email)) {
            $conf_email_err = "Please confirm email";

        } else{

            //Check if the two emails match
            if (empty($email_err) && ($input_email != $inp_conf_email)) {
                $conf_email_err = "The two emails do not match.";
            } else{
                //Now we can confirm each email.
                $email = $input_email;
                //$conf_email = $inp_conf_email;
            
         
            }

        }

    }
        
    //Some validation of the input password - Needs symbols as a requirement.
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
        //Check if passwords match
        if (empty($password_err) && ($password != $conf_password)) {
            $conf_password_err = "Passwords did not match.";
        }
    }
    //Validate twitter handles
    $input_twitter = trim($_POST["twitter"]);
    $conf_input_twitter = trim($POST["confirm_twitter"]);
    if (empty($input_twitter)) {
        if (!empty($conf_input_twitter)) {
            $twitter_er = "Please enter twitter handle";

        }

    } else {
        //If the twitter handle has been entered.
        if (empty($input_twitter)) {
            $conf_twitter_er = "Please confirm twitter handle";
        } else {
            if (empty($twitter_er) && ($input_twitter != $conf_input_twitter)) {
                $conf_twitter_er = "Twitter handle does not match.";
            } else {
                $twitter = $input_twitter;
                //$conf_twitter = $conf_input_twitter;
            }
        }
    }
    //last checks before sql query
    if (empty($username_err) && empty($password_err) && empty($conf_password_err) && empty($twitter_er) && empty($conf_twitter_er) && empty($email_err) && empty($conf_email_err)) {

        $sql = "INSERT INTO users (username, password, twitterHandle, email) VALUES (?,?,?,?)";
        $stmt->$link->stmt_init();
        if ($stmt->prepare($sql)) {
            $stmt->bind_param("ssss", $param_username, $param_password, $param_twitter, $param_email);

            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            $param_twitter = $twitter;
            $param_email = $email;


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
<p>Please fill in this form to create an account</p>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <div class="form-group">
        <label>Username</label>
        <input type = "text" name = "username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
        <span class="invalid-feedback"><?php echo $username_err; ?></span>

    </div>
    <div class="form-group">
        <label>Password</label>
        <input type = "password" name = "password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?> value="<?php echo $password; ?>">
        <span class='invalid-feedback'><?php echo $password_err; ?> </span>

    </div>
    <div class="form-group">
        <label>Confirm Password</label>
        <input type = "password" name = "confirm_password" class="form-control <?php echo (!empty($conf_password_err)) ? 'is-invalid' : ''; ?> value="<?php echo $conf_password; ?>">
        <span class='invalid-feedback'><?php echo $conf_password_err; ?></span>
    </div>
    <div class="form-group">
        <label>Twitter handle</label>
        <input type = "text" name = "twitter" class="form-control <?php echo (!empty($twitter_er)) ? 'is-invalid' : ''; ?> value="<?php echo $twitter; ?>">
        <span class='invalid-feedback'><?php echo $twitter_er; ?></span>
    </div>
    <div class="form-group">
        <label>Confirm Twitter Handle</label>
        <input type = "text" name = "confirm_twitter" class="form-control <?php echo (!empty($conf_twitter_er)) ? 'is-invalid' : ''; ?> value="<?php echo $conf_twitter; ?>">
        <span class='invalid-feedback'><?php echo $conf_twitter_er; ?></span>
    </div>
    <div class="form-group">
        <label>Email</label>
        <input type = "text" name = "email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?> value="<?php echo $email; ?>">
        <span class='invalid-feedback'><?php echo $email_err; ?></span>
    </div>
    <div class="form-group">
        <label>Confirm Email</label>
        <input type = "text" name = "confirm_email" class="form-control <?php echo (!empty($conf_email_err)) ? 'is-invalid' : ''; ?> value="<?php echo $conf_email; ?>">
        <span class='invalid-feedback'><?php echo $conf_email_err; ?></span>_email
    </div>
    <div class="form-group">
        <input type="submit" class="btn btn-primary" value="Submit">
        <input type="reset" class="btn btn-secondary m1-2" value="Reset">
    </div>
    <p>Already have an account? <a href="Login.php">login here</a>.</p>

</form>
</body>
</html>
    
    





