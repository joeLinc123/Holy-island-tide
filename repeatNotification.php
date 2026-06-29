<?php
// php script to be repeatedly ran in the background every minute.
// It will go through the journeys table for todays arrivals. Then it will check the crossing end date - user warning time to work out if a notification needs sending
// If it does then the notifyEmail process will be called, and the database status updated


//include the connectDB and dateClass files
require_once("connectDB.php");
require_once("dateClass.php");

//initialise the dataselection as an array
$dataSelection = array();

// Get the date information so that can construct an SQL statement
// This will get todays date, and calculate the start of day as <date> 00:00:00 and end of day as <date> 23:59:59
$today = new DateTime();
$today_class = new dateClass($today->format('Y-m-d H:i:s'));
$arrival_date = $today_class->getDateOnly();
$start_of_day = $arrival_date . " 00:00:00";
$end_of_day = $arrival_date . " 23:59:59";

//Create sql statement
$sql = "SELECT trips.tripID, trips.userID, trips.warningTime, trips.arrivalDate, crossings.crossEndDate, users.email, users.twitterHandle, users.twitterID FROM trips, crossings, users WHERE (trips.arrivalDate between ? AND ?) AND (crossings.rangeID = trips.assignedRangeID) AND (trips.userID = users.ID)";
$stmt = $link->stmt_init();

//Prepare statement for execution
if($stmt->prepare($sql)){
    //Bind params
    $stmt->bind_param("ss", $param_arrivalSOD, $param_arrivalEOD);
    $param_arrivalSOD = $start_of_day;
    $param_arrivalEOD = $end_of_day;
    //Attempt to execute the statement
    if($stmt->execute()){
        $stmt->store_result();
        //gathers the returned data and then pushes this into an array.
        if($stmt->num_rows() > 0){

            $stmt->bind_result($db_tripid, $db_userid, $db_warning_time, $db_arrivalDate,  $db_crossEndDate, $db_email, $db_twitterHandle, $db_twitterID);
            while($stmt->fetch()){
                $temp_array = array($db_tripid, $db_userid, $db_warning_time, $db_arrivalDate, $db_crossEndDate, $db_email, $db_twitterHandle, $db_twitterID);
                array_push($dataSelection, $temp_array);
            }
        }
    }

    //Close the statement
    $stmt->close();
    //Traverse the array to see if in warning time area
    foreach($dataSelection as $arr) {

        // $arr:
        // [0] = trip ID
        // [1] = user ID
        // [2] = warning time
        // [3] = arrival Date
        // [4] = crossing End Date
        // [5] = user email address
        // [6] = user twitter handle
        // [7] = user twitter ID

        // Form date Class with the end date
        // Subtract the warning time and see if the value is greater than now (i.e. is within the warning period and notification should be sent)

        $warningTime = $arr[2];

        $endDate = new dateClass($arr[4]);
        $endDate->dateTimeMinus(intVal($warningTime));
        //Is the end date - warning time >= now
        $result = $endDate->compareDate($today);
        //If so, send notifications
        if($result == "LT" or $result == "EQ"){

            $email = $arr[5];
            $twitterHandle = $arr[6];
            $twitterID = $arr[7];

            $res = shell_exec("python3 SendEmail.py $email $warningTime");

            if (!empty($twitterHandle) && !empty($twitterID)) {

                //Send Twitter DM
                $res = shell_exec("python3 sendDM.py $twitterID");

            }
            

            //Prepare sql statement to update the trips table

            $sql2 = "UPDATE trips SET currentStatus = 'warningSent' WHERE tripID = ?";
            $stmt2 = $link->stmt_init();
            //Prepare the statement and bind params
            if($stmt2->prepare($sql2)){

                $stmt2->bind_param("i", $param_tripID);
                $param_tripID = $arr[0];
                //execute
                $stmt2->execute();

            }
            //Close the statement.
            $stmt2->close();
            

        }


    }

}

// Housekeep journey Database. Set any database entry with an arrival date yesterday or before to a status of 'archived'

$sql3 = "SELECT tripID, arrivalDate, curremtStatus FROM trips";
$sql4 = "UPDATE trips SET currentStatus = 'archived' WHERE tripID = ?";
$sql5 = "DELETE FROM trips WHERE tripID = ?";

// Get all of the arrival Date and trip ID for each trip 
// For each one, get yesterdays date (using the 'yesterday' value to DateTime class) and use the dateClass to set to end of day yesterday (add 23:59:59)
// Then check the arrival date against End of yesterday. If it is less than or equal to it, set to archived
// Finally if the data is archived, delete if 30 days have now passed


$stmt = $link->stmt_init();

if($stmt->prepare($sql3)){

    if ($stmt->execute()){

        $stmt->bind_param($db_tripID, $db_arrivalDate, $db_currentStatus);

        while ($stmt->fetch()) {  

            $checkDate = new dateClass($db_arrivalDate);

            if ($db_currentStatus <> 'archived') {

                $yesterday = new DateTime('yesterday');
                $yesterdayDate = new dateClass($yesterday->format('Y-m-d H:i:s'));
                $yesterdayCompare = $yesterdayDate->getDateOnly() . "23:59:59";
                $EO_yesterday = DateTime :: createFromFormat('Y-m-d H:i:s', $yesterdayCompare);
                
                $checkRes = $checkDate->compareDate($EO_yesterday);

                if ($checkRes == "LT" OR $checkRes == 'EQ') {

                    $stmt2 = $link->stmt_init();

                    if($stmt2->prepare($sql4)){
               
                        $stmt2->bind_param("i", $param_tripID);
                        $param_tripID = $db_tripID;

                        $stmt2->execute();  
                
                    }
            
                    $stmt2->close();

                }

            } else {

                $checkDate->dateTimePlus(30,'d');

                $checkRes = $checkDate->compareDate(new DateTime($checkDate->getFullDateAndTimeAsString()));
                
                if ($checkRes == "LT" OR $checkRes == 'EQ') {

                    $stmt2 = $link->stmt_init();

                    if($stmt2->prepare($sql5)){
               
                        $stmt2->bind_param("i", $param_tripID);
                        $param_tripID = $db_tripID;

                        $stmt2->execute();  
                
                    }
            
                    $stmt2->close();

                }

                

            }
            
            


        }

    }  
                
}

            
$stmt->close();



// Housekeep userCodes Database

$sql6 = "DELETE FROM userCodes WHERE status = 'complete' OR status ='expired'";
$stmt = $link->stmt_init();

if($stmt->prepare($sql6)){

    //No variables to bind, just execute

    $stmt->execute();  //Just need to execute the update statement
                
}
            
$stmt->close();

// And finally expire any codes that have gone past expiry date

$sql7 = "SELECT id, expiryDate from userCodes WHERE status = 'current' or status = 'verifyemail'";
$sql8 = "UPDATE userCodes SET status = 'expired' WHERE id = ?";

$stmt=$link->stmt_init();

if($stmt->prepare($sql7)){
    
    // No variables to bind
      
    // Attempt to execute the prepared statement
    if($stmt->execute()){
        /* store result */
        $stmt->store_result();
           
        if($stmt->num_rows() > 0){

            $stmt->bind_result($db_ID, $db_expDate);

            while($stmt->fetch()){

                $useID = $db_ID;
                $useExpDate = new dateClass($db_expDate);

                $checkResult = $useExpDate->compareDate(new DateTime());

                if ($checkResult == "LT" or $checkResult == "EQ") {

                    $stmt2=$link->stmt_init();

                    if ($stmt2->prepare($sql8)) {

                        $stmt2->bind_param("i", $param_recID);
                        $param_recID = $useID;

                        $stmt2->execute();

                        $stmt2->close();

                    }


                }

            }
 
        } 
    }

    $stmt->close();

}
    
//End the link.
$link->close();

?>
<script>

    setTimeout(function(){window.location.reload();}, 1*60*1000);
    //Show current time.
    document.write(new Date());
</script>