<?php

//Create a verify code to be sent to the user's email
//Set to expire in 2hrs and then stored in the userCodes db. Any current codes will be marked expired
//Redirect to enterVerifyCode, so the user can enter this code and so it can be checked
//Use an HTTP GET request to work out what to do next, as it depends where called from.

session_start();

require_once("connectDB.php");
require_once("dateClass.php");

//Identify the source of the HTTP GET request
$source = $_GET["source"];

//create a code, then redirect so the user can enter the code
$userEmail = "";

//Generate a random six digit code
$code = rand(100000,999999);
$codeString = strVal($code);

//Use dateclass to create today's date, and then today's date + 10 mins for the code expiry

$CurrentDate = new DateTime();
$today = new dateClass($CurrentDate->format('y-m-d H:i:s'));
$CurrentDatePlus2Hour = new dateClass($CurrentDate->format('y-m-d H:i:s'));
$CurrentDatePlus2Hour->dateTimePlus(2,"h");

//Expire all current items as new code is generated
//Get all codes marked current for the user
//for each of these codes, mark them as expired

$sql = "SELECT id from userCodes WHERE status = 'verifyemail' and userID =?";
$sql2 = "UPDATE userCodes SET status = 'expired' WHERE id = ?";

$stmt = $link->stmt_init();

if($stmt->prepare($sql)){
    $stmt->bind_param("i", $param_userID);
    $param_userID = $_SESSION["id"];

    if($stmt->execute()){

        $stmt->store_result();

        if($stmt->num_rows() > 0){
            $stmt->bind_result($db_ID);

            while($stmt->fetch()){

                $userID = $db_ID;
                $stmt2=$link->stmt_init();

                if($stmt2->prepare($sql2)){
                    $stmt2->bind_param("i", $param_recID);

                    $param_recID = $userID;
                    $stmt2->execute();
                    $stmt2->close();
                }
            }
        }
    }
    $stmt->close();
}

//Set up useCodes table in the database, this is for the newly stored codes
//createdDate = Today, expiryDate = Today + 2hrs

$sql3 = "INSERT INTO userCodes(userID, code, createdDate, expiryDate, status) VALUES(?,?,?,?,?)";

$stmt = $link->stmt_init();

if($stmt->prepare($sql3)){
    $stmt->bind_param("iisss", $param_userID, $param_code, $param_createdDate, $param_expiryDate, $param_status);
    $param_userID = $_SESSION["id"];
    $param_code = $code;
    $param_createdDate = $today->getFullDateAndTimeAsString();
    $param_expiryDate = $CurrentDatePlus2Hour->getFullDateAndTimeAsString();
    $param_status = "verifyemail";

    if($stmt->execute()){

    } else{
        echo"Oops, something went wrong, please try again later.";
    }
    $stmt->close();
}

//Now, get ready to send code to the user, so firstly, we will get the email for the user

$sql4 = "SELECT email FROM users WHERE id=?";

$stmt = $link->stmt_init();

if($stmt->prepare($sql4)){
    $stmt->bind_param("i", $param_userID);
    $param_userID = $_SESSION["id"];

    if($stmt->execute()){
        $stmt->bind_result($db_email);

        while($stmt->fetch()){
            $userEmail = $db_email;
            }
        }
        $stmt->close();
    
    
}
$link->close();
//call the python with the email and code
//send through 'register' type so that will reflect 2 hour expiry
$res = shell_exec("python3 notifyEmailCode.py $userEmail $codeString 'register'");


if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
    $uri = 'https://';
} else {
    $uri = 'http://';
}
$uri .= $_SERVER['HTTP_HOST'];
                
header('Location: '.$uri.'/login/enterVerifyCode.php?source='.$source);


?>