<?php

// php page to enable the user to confirm the journey they have chosen. If confirmed, the journey will be committed to the database
// user will be presented with different times the user could leave their location and how long they'd have on the island as a result
// This uses $_SESSION variables from the previous pages in order to provide necessary data.


require_once("connectDB.php");
require_once("dateClass.php");
//include the dateclass and connectDB files
session_start();
//Establish all variables
$table_rangeID = $table_crossStartDate = $table_crossStartDate = $table_crossEndDay = $table_crossEndDate = "";

$timesArray = array(); //Store data here
$stage = 0;

$range_ID = $_SESSION["rangeID"]; //Get rangeID from the session - refrence to the crossings table ID so the chosen crossing is used - chosen in processJourney.php

$time_to_dest = intVal($_SESSION["time"]) / 60; //Time of journey in secs, so change to mins.
$dist_to_dest = intVal($_SESSION["distance"]) / 1000;//convert distance to km from m
$warning_value = intVal($_SESSION["warning"]);//Warning value chosen by the user

//Process form data when submitted
//When the user confirms their journey
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // User POST
    // Check Cancel button pressed (redirect), otherwise process as confirm
    
    if (isset($_POST["Cancel"])) {


        // Redirect to index

        if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
            $uri = 'https://';
        } else {
            $uri = 'http://';
        }
        $uri .= $_SERVER['HTTP_HOST'];


        header('Location: '.$uri.'/login/journeyIndex.php');

    } else {

        // Prepare an insert statement for the journey into the trips table

        $sql = "INSERT INTO trips (userID, pcode, warningTime, journeyTime, journeyDistance, arrivalDate, assignedRangeID, currentStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
  
        $stmt = $link->stmt_init();

        if($stmt->prepare($sql)){


            $stmt->bind_param("isiiisis", $param_userID, $param_pcode, $param_warning, $param_journeyTime, $param_journeyDistance, $param_arrivalDate, $param_rangeID, $param_status);
        
            // Set parameters, mostly from data already collected through the journey set up process

            $param_userID = $_SESSION["id"];  
            $param_pcode = $_SESSION["pcode"];
            $param_warning = $_SESSION["warning"];
            $param_journeyTime = $_SESSION["time"];         
            $param_journeyDistance = $_SESSION["distance"];  //distance (and time) will be stored in their base format
            $param_arrivalDate = $_SESSION["arrivalDate"];
            $param_rangeID = $_SESSION["rangeID"];
            $param_status = "Confirmed";  // Force a value of confirmed.
 

            if($stmt->execute()){
                // Has worked ok, relocate to the journeyIndex page

                if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
                    $uri = 'https://';
                } else {
                    $uri = 'http://';
                }
                $uri .= $_SERVER['HTTP_HOST'];

                header('Location: '.$uri.'/login/journeyIndex.php');

            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            $stmt->close();
        }

    }
   
} else {

    //Do when not posting, so will do this when page first loaded

    //Prepare statement to get the start and end date/time from the crossings table

    $sql = 'SELECT crossStartDate, crossEndDate FROM crossings WHERE rangeID = ?';


    $stmt = $link->stmt_init();
    
    if($stmt->prepare($sql)) {

        $stmt->bind_param("i", $param_ID);   

        $param_ID = $range_ID;  // Will be the range ID that was selected in processJourney, taken from the $_SESSION

        if($stmt->execute()) {

            $stmt->store_result();

            if($stmt->num_rows() == 1) {
                $stmt->bind_result($db_crossStartDate, $db_crossEndDate);
                if($stmt->fetch()){
                    // Store the cross start and end date for further use
                    $table_crossStartDate = $db_crossStartDate;
                    $table_crossEndDate = $db_crossEndDate;
                } 
                
            } 
        }

        $stmt->close();
    }

    $link->close();

    // Now that have the database data, can create the journey data to show the user.
    // This will be a set of times the user could leave and how long they will get on the island as a result
    // An hourly interval will be used, and the dateclass methods will assist this process.

    // Build 1st set of data

    //leave_time = the time user would start journey
    //end_time_less_warning = the safe crossing end time, minus the requested warning time. This will be used to calc time on island (obviously not using the warning time!)


    $last_arrival = 10000000;  //Set to large value so will pass 1st check below
    $leave_time = new dateClass($table_crossStartDate);
    $leave_time->dateTimeMinus($time_to_dest);
    $end_time_less_warning = new dateClass($table_crossEndDate);
    $end_time_less_warning->dateTimeMinus($warning_value);
    $compare_value = "EQ";

    while ($compare_value <> "GT") {  //Do this until the time that the user would arrive is after the crossing end/date time.

        //reformat the journey start time on the hour e.g. 14:23:21 would become 14:00:00

        $leave_hh = $leave_time->getHourOnly();
        $leave_value = $leave_time->getDateOnly() . ' ' . $leave_hh . ":00:00";
        $arrive_time = new dateClass($leave_value); // Create new dateClass item for the arrival date/time, initialise with the leave time.
        $leave_time->dateTimePlus(1,"h"); // Add 1 to the hour for the journey start time (for next pass)
        $arrive_time->dateTimePlus($time_to_dest, "m"); //arrival time = start time + journey time
        $time_on_island = $arrive_time->differenceAsString($end_time_less_warning->getFullDateAndTime()); // time on island = difference between arrival time and cross end time - warning
        $time_on_island_mins = $arrive_time->difference($end_time_less_warning->getFullDateAndTime()); // as above but with minute value (for use in table output)


        //Push this data into an array
        if ($time_on_island_mins < $last_arrival) {
            $temp_array = array($leave_value, $arrive_time->getFullDateAndTimeAsString(), $time_on_island, $time_on_island_mins);
            array_push($timesArray, $temp_array);
        }

        $compare_value = $arrive_time->compareDate($end_time_less_warning->getFullDateAndTime()); // Compare arrival time with crossing end time - warning for next pass.

        $last_arrival = $time_on_island_mins;


    }

}


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Journey</title>
    <link rel="stylesheet" href="../css/siteStyle.css">
</head>
<body>
    <div class="wrapper">
        <h2>Confirm Journey</h2>
        <!-- The $SERVER(PHP_SELF) holds the current script being executed, Therefore this means to call itself again when the post is done - and how the PHP checking code is called -->
        <!-- Use special characters to ignore any code injection in the call -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

        <?php

            // Split time into hrs and mins. Only work out separate minutes if the hr > 0; otherwise just show mins

            echo "<p>";
            $hr = floor($time_to_dest/60);
            if($hr>0){
                $min = $time_to_dest%60;
                echo "Your journey to Holy Island will take approx " . $hr . "hr, " . $min . "mins ( ".round($time_to_dest) . "mins) and be approx" . $dist_to_dest . " km";
            } else{
                echo "Your journey to Holy Island will take " . round($time_to_dest) . " mins and be approx " . $dist_to_dest . " km";
            }
            echo "</p>";

            echo "<table>";
            echo "<tr>";
            echo "<th>Leave Time</th>";
            echo "<th>Arrival Time</th>";
            echo "<th>Time Spent on Island</th>";
            echo "</tr>";

            foreach($timesArray as $arr) {

                // Present each item from the array stored by php
                // Item 0 = Leave Date/Time
                // Item 1 = Arrive Date/Time
                // Item 2 = Time on Island
                // Item 3 = Time on Island (mins)

                $leave = new dateClass($arr[0]);
                $arrive = new dateClass($arr[1]);

                if ($arr[3] > 120) {
                    $class = "safe";
                } else {
                    $class = "unsafe";
                }


                echo "<tr>";
                echo "<td>" . $leave->getTimeOnlyAlt() . "</td>";
                echo "<td>" . $arrive->getTimeOnlyAlt() . "</td>";
                echo "<td class=$class>" . $arr[2] . "</td>";
                echo "</tr>";
            }

            echo "</table>";
        ?>


       
        <input type="submit" value="Confirm" name="Confirm">
        <input type="submit" value="Cancel" name="Cancel">

        </form>
    </div>    
</body>
</html>


