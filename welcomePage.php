<?php

// php for the welcome page
// this will pull back crossing times for the next 30 days, to be shown on the page
// this allows the user to see this data manually, and use it as a guide

require_once("connectDB.php");
require_once("dateClass.php");
//Include the connectDB file, so the database can be queried, and dateClass to calculate today + 30 days

// Required Variables

$db_data_found = true; // Whether data has been found
$crossingArray = array();
$verifyFail = "Y";

// get todays date, and set it to midnight
// add 30 days and set it to 23:59:59 to create the data range

$todaysDate = new DateTime(); 
$today = new dateClass($todaysDate->format('Y-m-d H:i:s'));
$todayPlus30 = new dateClass($today->getFullDateAndTimeAsString());
$todayPlus30->dateTimePlus(30, "d");

$startOfRange = $today->getDateOnly(). " 00:00:00";
$endOfRange = $todayPlus30->getDateOnly(). " 23:59:59";

session_start();
//Start the session

// Get the crossings in the range startOfRange - endOfRange

$sql = "SELECT crossStartDate, crossType, crossEndDate FROM crossings WHERE crossStartDate BETWEEN ? AND ?";

$stmt = $link->stmt_init();

if($stmt->prepare($sql)){

    $stmt->bind_param("ss", $param_SOR, $param_EOR);
    
    $param_SOR = $startOfRange;
    $param_EOR = $endOfRange;

    if($stmt->execute()){

        $stmt->store_result();
        if($stmt->num_rows()>0){
            //Checks the result of the query and binds results and stores the returned results in an array.
            $stmt->bind_result($db_startDate, $db_type, $db_endDate);
            while($stmt->fetch()){
                $tempArray = array($db_startDate, $db_type, $db_endDate);
                array_push($crossingArray, $tempArray);
            }
        }else{ //If nothing is found:
            $db_data_found = false;
        }
    } else{
        echo "Oops! Something went wrong. Please try again later.";
    }

    $stmt->close();
}   //Close statement

$link->close();

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to Holy Island Crossings!</title>
    <link rel="stylesheet" href="../css/siteStyle.css">
</head>
<body>
    <div class="header">
        <h1>Welcome to Holy Island Crossings!</h1>
    </div>
    <div class="topnavbar">
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    </div>
    <div class="row">
        <div class="column">
        <h1> What we do:</h1>
        <p> We believe there is an issue with the current system in place to travel to Holy Island.
        This website makes the process of planning a trip to Holy Island seamless and automatic, so you can travel stress free.</p>

        </div>
        <div class="column">
        <h2> How does it work?</h2>
        <p> All you have to do is input your current location, and the system will do the rest!
        A Twitter account is also required to send you notifications of how long you have left before the causeway becomes unsafe.</p>
        </div>
    </div>
    <div>
        <h2>Crossing Details for next 30 days</h2>
        <?php

            if($db_data_found) {
                //Checks if the data has been found
                //If data has been found, show the data in a table.

                echo "<table>";
                echo "<tr>";
                echo "<th> Crossing Start Day </th>";
                echo "<th> Crossing Start Date </th>";
                echo "<th> Crossing Start Time </th>";
                echo "<th> Type </th>";
                echo "<th> Crossing End Day </th>";
                echo "<th> Crossing End Date </th>";
                echo "<th> Crossing End Time </th>";
                echo "</tr>";

                foreach($crossingArray as $arr) {

                    //Pull back array data
                    // arr[0] = crossing start date
                    // arr[1] = crossing type
                    // arr[2] = crossing end date

                    //Create date and time dateClass objects so that can show separate date and time in the table

                    $SDate = new dateClass($arr[0]);
                    $EDate = new dateClass($arr[2]);

                    echo "<tr>";

                    //show safe and unsafe differently in table

                    if ($arr[1] == "Safe") {
                        $class = "safe";
                    } else {
                        $class = "unsafe";
                    }
                    echo "<td class=$class>" . $SDate->getDayOnly() . "</td>";
                    echo "<td class=$class>" . $SDate->getDateOnlyAlt() . "</td>";
                    echo "<td class=$class>" . $SDate->getTimeOnlyAlt() . "</td>";
                    echo "<td class=$class>" . $arr[1] . "</td>";
                    echo "<td class=$class>" . $EDate->getDayOnly() . "</td>";
                    echo "<td class=$class>" . $EDate->getDateOnlyAlt() . "</td>";
                    echo "<td class=$class>" . $EDate->getTimeOnlyAlt() . "</td>";
                    echo "</tr>";
                }

                echo "</table>";

            } else {
                //If not data has been returned, tell the user there are no journeys availiable, allows them to add a journey again.

                echo "no data";
                echo "<h2>No Data Available</h2>";
                echo "<h3>We've gone beyond the end of June 2023, which was the last of data provided</h3>";
            }
            
        ?>
    </div>
   
    
        
</body>
</html>
