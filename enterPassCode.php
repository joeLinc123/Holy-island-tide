<?php

// Allow user to enter the code that was generated in create-pass-code for a password reset
// If the code matches that on the database, then it is marked as complete on database, then redirect to the reset password screen

session_start();
require_once("connectDB.php");


$code=$code_err=$retrivedCode="";
//code = code that the user inputs
//$code_err will handle errors to do with that input from the user
//$retrivedCode is the code from the database

//Process a POST request
//Get code input
//Access the database to retrive the code, and then validate.


if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Validate if a code is input

    $inp_code = trim($_POST["inputCode"]);

    if(empty($inp_code)){
        $code_err = "Please enter the code.";     
    } else {
        $code = $inp_code;
    }

    // Get any codes with 'current' status from the userCodes table for the user ID
    // There will only be one value because the create code routine sets any current records to 'expired' as part of code generation process

    $sql = "SELECT code FROM userCodes WHERE userID = ? AND status = ?";
    $stmt = $link->stmt_init();
        
    if($stmt->prepare($sql)){
        
        $stmt->bind_param("is", $param_id, $param_status);

        $param_id = $_SESSION["id"];
        $param_status = 'current';
            
        if($stmt->execute()){

            $stmt->store_result();
           
            if($stmt->num_rows() == 1){

                $stmt->bind_result($db_retCode);
                while($stmt->fetch()){
                    $retrievedCode = $db_retCode;
                    if ($retrievedCode != $code) {
                        $code_err = "Sorry, that code does not match the one that was sent..";
                    }
                } 
            } else {
                echo "Something went wrong";  // No Data Returned
            }
        }

    } else{
                echo "Oops! Something went wrong. Please try again later.";
    }

    $stmt->close();

    
    // Check input errors before updating the database

    if(empty($code_err)){
        
        // Code is correct. Update the database to set the code to status 'complete' so doesn't get picked up anymore

        $sql = "UPDATE userCodes SET status = ? WHERE userID = ?";
        $stmt = $link->stmt_init();
        
        if($stmt->prepare($sql)){

            $stmt->bind_param("si", $param_status, $param_id);

            $param_status = "complete";
            $param_id = $_SESSION["id"];

            if($stmt->execute()){

                //Once complete, redirect to the reset password page so that user can change

                if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
                    $uri = 'https://';
                } else {
                    $uri = 'http://';
                }
                $uri .= $_SERVER['HTTP_HOST'];
                
                header('Location: '.$uri.'/login/ResetPassword.php');
           

            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            
        }

        $stmt->close();
    }
    
    $link->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../css/siteStyle.css">
</style>
</head>
<body>
    <div>
        <h2>Enter Code</h2>
        <p>A Code has been sent to your registered email. Please check it and enter the code. If you cannot find it, check your junk folder:</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post"> 
            <div>
                <label>Enter Code</label>
                <input type="input" name="inputCode" value="<?php echo $inp_code; ?>">
                <span><?php echo $code_err; ?></span>
            </div>
            <div>
                <input type="submit" value="Submit">
            </div>
        </form>
    </div>    
</body>
</html>