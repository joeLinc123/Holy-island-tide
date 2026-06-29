<?php

// php for the manage journey page. 
// User will have the option to add / edit journeys on this page
// A list of current journey will be provided

require_once("dateClass.php");
require_once("connectDB.php");
//Include the connectDB file, so the database can be queried

// Required Variables

$db_data_found = true; // Whether data has been found
$journeyArray = array();
$verifyFail = "Y";

session_start();
//Start the session

// Access and pick up the current values of each of the trips for the user. It will need to get some data from the crossings table, so join on rangeID made

$sql = "SELECT trips.pcode, trips.warningTime, trips.arrivalDate, trips.currentStatus,
crossings.crossStartDate, crossings.crossEndDate FROM trips, crossings WHERE trips.userID = ? AND crossings.rangeID = trips.assignedRangeID ORDER BY crossings.crossEndDate, trips.arrivalDate";
//Create sql statement - Selects the required data from the trips and crossing tables for the user.
$stmt = $link->stmt_init();

if($stmt->prepare($sql)){

    $stmt->bind_param("i", $param_userid);
    //Set the paramater = to the user's id from the session.
    $param_userid = $_SESSION["id"];

    if($stmt->execute()){

        $stmt->store_result();
        if($stmt->num_rows()>0){
            //Checks the result of the query and binds results and stores the returned results in an array.
            $stmt->bind_result($db_location, $db_warningTime, $db_arrivalDate, $db_currentStatus, $db_crossStartDate, $db_crossEndDate);
            while($stmt->fetch()){
                $tempArray = array($db_location, $db_warningTime, $db_arrivalDate,$db_currentStatus, $db_crossStartDate, $db_crossEndDate);
                array_push($journeyArray, $tempArray);
            }
        }else{ //If nothing is found:
            $db_data_found = false;
        }
    } else{
        echo "Oops! Something went wrong. Please try again later.";
    }

    $stmt->close();
}   //Close statement

// Is the user verified? If not make them verify on trying to add journey. Control access to the "notify me" button if not verified

$emailVerfied = false;
$sql = "SELECT emailVerify FROM users WHERE id = ?";
$stmt = $link->stmt_init();

if($stmt->prepare($sql)){

    $stmt->bind_param("i", $param_userid);


    $param_userid = $_SESSION["id"];


    if($stmt->execute()){

        $stmt->bind_result($db_emailVerify);

        while($stmt->fetch()){
 
            if ($db_emailVerify == "Y") {

                $emailVerfied = true;

            } 
        } 

    } else{
        echo "Oops! Something went wrong. Please try again later.";
    }

    $stmt->close();

}


// Now check to see if journey added button pressed

if (isset($_POST["addJourney"])) {

    // Is the user verified. If not make them verify as can't be certain the notification would come through

    if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
        $uri = 'https://';
    } else {
        $uri = 'http://';
    }
    $uri .= $_SERVER['HTTP_HOST'];

    if ($emailVerify == false) {

        // Redirect to welcome verify email page

        header('Location: '.$uri.'/login/createVerifyCode.php?source=journeyrequest');

    } else {

        // Add a journey

        header('Location: '.$uri.'/login/editJourney.php');

    }
    
} else {

    if (isset($_POST["notifyMe"])) {

        if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
            $uri = 'https://';
        } else {
            $uri = 'http://';
        }
        $uri .= $_SERVER['HTTP_HOST'];

        $warningTime = $_POST["selectedWarning"];

        header('Location: '.$uri.'/login/onIslandJourney.php?time=' . $warningTime);

    }

}


$link->close();




?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Journey</title>
    <link rel="stylesheet" href="../css/siteStyle.css">
</head>
<body>
    <div>
        <a href="dashboard.php"><i class="material-icons">home</i></a>
    </div>
    <div>
        <h2 align="center">Journey list</h2>
        <?php

            if($db_data_found) {
                //Checks if the data has been found
                //If data has been found, show the data in a table.

                echo "<div class='tableWrap'>";
                    echo "<table>";
                    echo "<tr>";
                    echo "<th> source location </th>";
                    echo "<th> warning time </th>";
                    echo "<th> arrival date </th>";
                    echo "<th> status </th>";
                    echo "<th> crossing start </th>";
                    echo "<th> crossing end</th>";
                    echo "</tr>";

                    foreach($journeyArray as $arr) {

                    //Pull back array data
                    // arr[0] = location 
                    // arr[1] = amount of time to be warned before crossing is unsafe
                    // arr[2] = arrival date
                    // arr[3] = journey status
                    // arr[4] = cross Start
                    // arr[5] = cross End
                    
                        $arrival = new dateClass($arr[2]);
                        $start = new dateClass($arr[4]);
                        $end = new dateClass($arr[5]);

                        echo "<tr>";
                        echo "<td>" . $arr[0] . "</td>";
                        echo "<td>" . $arr[1] . "</td>";
                        echo "<td>" . $arrival->getDateOnlyAlt() . "</td>";
                        echo "<td>" . $arr[3] . "</td>";
                        echo "<td>" . $start->getDateOnlyAlt() . " " . $start->getTimeOnlyAlt() . "</td>";
                        echo "<td>" . $end->getDateOnlyAlt() . " " . $end->getTimeOnlyAlt() . "</td>";
                        echo "</tr>";
                    }

                    echo "</table>";

                echo "</div>";

            } else {
                //If not data has been returned, tell the user there are no journeys availiable, allows them to add a journey again.

                echo "no data";
                echo "<h2>There are no Journeys to List</h2>";
                echo "<h3>Why not add journey details using the add journey button below?</h3>";
            }
            
        ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

            <div class="buttonRow"> 
                <div> 
                    <input type="submit" value="Add Journey" name="addJourney">
                </div>
            </div>
            <?php

                // Control showing of the quick access notification button, show if email verified

                if ($emailVerfied == "Y") {

                    echo "<div class='row'>";
                        echo "<div class='col-left'>";
                            echo "<label><span class='small'>Already on Holy Island and just need a notification? Enter the required warning time below and click Notify Me. Please note that the minimum notification time has been increased 15 minutes for safety reasons.</span></label>";
                            echo "<div class='pad'>";
                                echo "<label>Enter Notification Time:</label>";
                            echo "</div>";
                            echo "<div class='pad'>";
                                echo "<select name = 'selectedWarning'>";
                                    echo "<option selected='selTime'>45</option>";
                                    echo "<option value='60'>1hr</option>";
                                    echo "<option value='90'>1hr 30min</option>";
                                    echo "<option value='120'>2hr</option>";
                                    echo "<option value='180'>2hr 30min</option>";
                                    echo "<option value='240'>3hr</option>";
                                echo "</select>";
                            echo "</div>";
                        echo "</div>";
                        echo "<div class='col-right'>";
                            echo "<div class='buttonRow'>";
                                echo "<div>";
                                    echo "<input type='submit' value='Notify Me!' name='notifyMe'>";
                                echo "</div>";
                            echo "</div>";
                        echo "</div>";
                    echo "</div>";

                }

            ?>
            
        </form>

    </div>    
</body>
</html>
