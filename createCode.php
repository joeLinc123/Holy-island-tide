<?php


//Aims to create a code to send to the user's email, for validation that their email actually works

session_start();

require_once("connectDB.php");
require_once("dateClass.php");

//create a code, then redirect so the user can enter the code
$userEmail = $email_verify = "";

//Generate a random six digit code
$code = rand(100000,999999);
$codeString = strVal($code);

//Use dateclass to create today's date, and then today's date + 10 mins for the code expiry

$CurrentDate = new DateTime();
$today = new dateClass($CurrentDate->format('y-m-d H:i:s'));
$CurrentDatePlusTen = new dateClass($CurrentDate->format('y-m-d H:i:s'));
$CurrentDatePlusTen->dateTimePlus(10,"m");

$sql = "SELECT emailVerify FROM users WHERE id = ?";
$stmt = $link->stmt_init();

if($stmt->prepare($sql)){
    $stmt->bind_param("i",$p_userID);
    $p_userID = $_SESSION["id"];
    if($stmt->execute()){
        $stmt->store_result();
        if($stmt->num_rows()==1){
            $stmt->bind_result($db_email_verify);
            while($stmt->fetch()){
                $email_verify = $db_email_verify;
            }
            
            
        }
    }
    $stmt->close();
}

if ($email_verify == "Y"){
    
    $sql2 = "SELECT id from userCodes WHERE status = 'current' and userID =?";
    $sql3 = "UPDATE userCodes SET status = 'expired' WHERE id = ?";
    
    $stmt = $link->stmt_init();
    
    if($stmt->prepare($sql2)){
        $stmt->bind_param("i", $param_userID);
        $param_userID = $_SESSION["id"];
    
        if($stmt->execute()){
    
            $stmt->store_result();
    
            if($stmt->num_rows() > 0){
                $stmt->bind_result($db_ID);
    
                while($stmt->fetch()){
    
                    $userID = $db_ID;
                    $stmt2=$link->stmt_init();
    
                    if($stmt2->prepare($sql3)){
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
    //createdDate = Today, expiryDate = Today + 10 mins
    
    $sql4 = "INSERT INTO userCodes(userID, code, createdDate, expiryDate, status) VALUES(?,?,?,?,?)";
    
    $stmt = $link->stmt_init();
    
    if($stmt->prepare($sql4)){
        $stmt->bind_param("iisss", $param_userID, $param_code, $param_createdDate, $param_expiryDate, $param_status);
        $param_userID = $_SESSION["id"];
        $param_code = $code;
        $param_createdDate = $today->getFullDateAndTimeAsString();
        $param_expiryDate = $CurrentDatePlusTen->getFullDateAndTimeAsString();
        $param_status = "current";
    
        if($stmt->execute()){
    
        } else{
            echo"Oops, something went wrong, please try again later.";
        }
        $stmt->close();
    }
    
    //Now, get ready to send code to the user, so firstly, we will get the email for the user
    
    $sql5 = "SELECT email FROM users WHERE id=?";
    
    $stmt = $link->stmt_init();
    
    if($stmt->prepare($sql5)){
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
    //call the python with the email and code
    //send through 'password' type so that will reflect 10 min expiry
    $res = shell_exec("python3 notifyEmailCode.py $userEmail $codeString 'password'");
    
    
    if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
        $uri = 'https://';
    } else {
        $uri = 'http://';
    }
    $uri .= $_SERVER['HTTP_HOST'];
                    
    header('Location: '.$uri.'/login/enterPassCode.php');
} else{
    echo"Email is not verified, so we cannot send you a code";
    echo"Please verify your email";
    header('location: Dashboard.php');
}
//Expire all current items as new code is generated
//Get all codes marked current for the user
//for each of these codes, mark them as expired



$link->close();

?>