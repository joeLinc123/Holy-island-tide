<?php

//create a journey where the user is already on the island and needs a notication of when to leave
//will get the safe crossing details that cover the current time, then create a trip with default data in order to trigger notif.

require_once("connectDB.php");
require_once("dateClass.php");

session_start();

if($_SERVER["REQUEST_METHOD"] == "POST"){

    if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
        $uri = 'https://';
        } else {
        $uri = 'http://';
        }
        $uri .= $_SERVER['HTTP_HOST'];

        header('Location: '.$uri.'/login/journeyIndex.php');

} else {

    $warningTime = $_GET["time"];

    //get todays date and time

    $todaysDate = new DateTime();
    $today = new dateClass($todaysDate->format('Y-m-d H:i:s'));

    //pull back the crossing detail for the current time, start date < now and end date > now

    $sql = 'SELECT rangeID, crossStartDate, crossEndDate FROM crossings WHERE crossType = ? AND ((crossStartDate < ?) AND (crossEndDate > ?))';

    $stmt = $link->stmt_init();
    if($stmt->prepare($sql)) {

        $stmt->bind_param("sss", $param_crossType, $param_todayDate1, $param_todayDate2);

        $param_crossType = "Safe";
        $param_todayDate1 = $today->getFullDateAndTimeAsString();
        $param_todayDate2 = $param_todayDate1;  //same used in check

        if($stmt->execute()) {
          
            $stmt->store_result();
           

            if($stmt->num_rows() == 1) {
            
                //Found the data

                $stmt->bind_result($db_rangeID, $db_crossStart, $db_crossEnd);

                if ($stmt->fetch()) {

                    $sql2 = "INSERT INTO trips (userID, pcode, warningTime, journeyTime, journeyDistance, arrivalDate, assignedRangeID, currentStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
  
                    $stmt2 = $link->stmt_init();

                    if($stmt2->prepare($sql2)){


                        $stmt2->bind_param("isiiisis", $param_userID, $param_pcode, $param_warning, $param_journeyTime, $param_journeyDistance, $param_arrivalDate, $param_rangeID, $param_status);
        
                        // Set parameters, some set up, some will be default (as is triggered from on island)

                        $param_userID = $_SESSION["id"];  
                        $param_pcode = "HolyIsland";
                        $param_warning = $warningTime;
                        $param_journeyTime = "N/A";         
                        $param_journeyDistance = "N/A";  
                        $param_arrivalDate = $today->getFullDateAndTimeAsString();
                        $param_rangeID = $db_rangeID;
                        $param_status = "Confirmed";  // Force a value of confirmed.
 

                        if($stmt2->execute()){

                            echo "Successfully added! You will be notified within your requested time to leave the island. Click the button below to return to welcome page.";

                        } else{
                            echo "Oops! Something went wrong. Please try again later.";
                        }

                        $stmt2->close();
                    }

                }


            } else {
                echo "You are outside of the safe crossing times, so will have to wait until the next safe crossing. Please check tide times...";
            }
        }

        $stmt->close();
   
    }

    // Close connection
    $link->close();
}

?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enter Journey Details</title>
    <link rel="stylesheet" href="../css/siteStyle.css">
</head>
<body>
    <div class="wrapper">
        <h2>On Island Notification Request</h2>
        <!-- The $SERVER(PHP_SELF) holds the current script being executed, Therefore this means to call itself again when the post is done - and how the PHP checking code is called -->
        <!-- Use special characters to ignore any code injection in the call -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div>
                <input type="submit" value="Return">
            </div>
            
        </form>
    </div>    
</body>
</html>