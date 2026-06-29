<?php

// php to process journey
// user has input where is starting from (pcode), warning time and date of arrival on editJourney.php before this is called
// How long this will take has already been calculated
// User will be presented with a table of safe crossings on the arrival date. 
// These will be where the crossing start date or end date is on the day of arrival
// Once row has been selected, this will be passed to the confirmJourney page


// Include config file
require_once("connectDB.php");
require_once("dateClass.php");

session_start();

$tableArray = array(); //Array for the display table
$confirm_err = "";  // Looks for an error with checkboxes
$rangeValue = ""; //The chosen Range ID
$data_found = true; // if data found in table


// Get the arrival date information so that can construct a SQL statement


$arrival_date = $_SESSION["arrivalDate"];


//create strings for the start and end of day
$start_of_day = $arrival_date . " 00:00:00";
$end_of_day = $arrival_date . " 23:59:59";

$arrival_display_date = new dateClass($arrival_date);


// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(!empty($_POST['pickRange'])) {
        // Counting number of checked checkboxes, hence use of array as the name
        $checked_count = count($_POST['pickRange']);

        if ($checked_count > 1) {
            $confirm_err = "Please only choose 1 range";
        } else {
            $rangeValue = $_POST['pickRange'][0]; //Get the only value
        }

    } else {

            $confirm_err = "Please select a range";
    }

    if (empty($confirm_err)) {

        // Everything is ok, can now go to the final confirmation page
        $_SESSION["rangeID"] = $rangeValue; // Get the crossing rangeID key for use on confirm page

        if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
            $uri = 'https://';
        } else {
            $uri = 'http://';
        }
        $uri .= $_SERVER['HTTP_HOST'];
        header('Location: '.$uri.'/login/confirmJourney.php'); // redirect to confirm page

    }
 
    
} 

// Get the data from the crossings table where crossing is safe and cross start or end date is between the start of day and end of day

$sql = 'SELECT rangeId, crossStartDate, crossEndDate FROM crossings WHERE crossType = ? AND ((crossStartDate between ? AND ?) OR (crossEndDate between ? AND ?))';
   
$stmt = $link->stmt_init();
    
if($stmt->prepare($sql)) {

    $stmt->bind_param("sssss", $param_type, $param_crossStartSOD, $param_crossStartEOD, $param_crossEndSOD, $param_crossEndEOD);
    $param_type = "safe";

    //Each SOD param bound to start_of_day, each EOD to end_of_day
    $param_crossStartSOD = $start_of_day;
    $param_crossStartEOD = $end_of_day;
    $param_crossEndSOD = $start_of_day;
    $param_crossEndEOD = $end_of_day;

    if($stmt->execute()) {

        $stmt->store_result();

        if($stmt->num_rows() > 0) {
            $stmt->bind_result($db_rangeID, $db_crossStart,$db_crossEnd);
            while($stmt->fetch()){
                //Push table returned data to the array
                $tempArray = array($db_rangeID,$db_crossStart,$db_crossEnd);
                array_push($tableArray,$tempArray);
            } 
        } else {
            // No data

            $data_found = false;
        }
        
    }

    $stmt->close();
 
    // Close connection
    $link->close();

}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Safe Crossing</title>
    <link rel="stylesheet" href="../css/siteStyle.css">
</head>
<body>
    <div>
        <?php
            echo "<h2>Safe Crossing Time(s) for " . $arrival_display_date->getDayOnly() . "," . $arrival_display_date->getDateOnlyAlt() . "</h2>";
        ?>
        <!-- The $SERVER(PHP_SELF) holds the current script being executed, Therefore this means to call itself again when the post is done - and how the PHP checking code is called -->
        <!-- Use special characters to ignore any code injection in the call -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <?php

        if ($data_found) {
            echo "<table>";
            echo "<tr>";
            echo "<th>Choose</th>";
            echo "<th>Crossing Start Day</th>";
            echo "<th>Crossing Start Date</th>";
            echo "<th>Crossing Start Time</th>";
            echo "<th>Crossing End Day</th>";
            echo "<th>Crossing End Date</th>";
            echo "<th>Crossing End Time</th>";
            echo "</tr>";

            foreach($tableArray as $arr) {

                // Array data:
                    // arr[0] = range ID
                    // arr[1] = Crossing Start Date/Time
                    // arr[2] = Crossing End Date/Time

                    //Create date and time dateClass objects so that can show separate date and time in the table

                $SDate = new dateClass($arr[1]);
                $EDate = new dateClass($arr[2]);

                echo "<tr>";
                echo "<td>";
                // Create a selection, with a value of the crossing range ID for later use. Note the name is an array, so can trap multiple / zero selections
                echo "<input type = 'checkbox' name = 'pickRange[]', value = '$arr[0]'>"; 
                echo "</td>";
                echo "<td>" . $SDate->getDayOnly() . "</td>";
                echo "<td>" . $SDate->getDateOnlyAlt() . "</td>"; // Split out Date
                echo "<td>" . $SDate->getTimeOnlyAlt() . "</td>"; // Split out Time
                echo "<td>" . $EDate->getDayOnly() . "</td>";
                echo "<td>" . $EDate->getDateOnlyAlt() . "</td>";
                echo "<td>" . $EDate->getTimeOnlyAlt() . "</td>";
                echo "</tr>";
            }

            echo "</table>";

        } else {

            echo "No crossing data found, please press back button on your browser and choose another date";

        }

        ?>

            <div>
                <p>
                    <?php 
                        echo $confirm_err; 
                    ?>
                </p>
            </div>   
            <div>
                <input type="submit" value="Confirm">
                <input type="reset" value="Reset">
            </div>
        </form>
    </div>    
</body>
</html>