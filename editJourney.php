<?php

// php to allow the user to enter the relevant details to calculate the journey time and distance to Holy Island
// This will take the post code (validated against an ONS list), and chosen time to be warned before cross becomes unsafe
// Date of arrival will be presented as a list of 30 dates from today, so no validation needed.

// Include config file for DB and dateClass

require_once("connectDB.php");
require_once("dateClass.php");

session_start();


// Create a string array of dates from today for 30 days to go in selection

$datesArray = array();
$todaysDate = new DateTime(); // use DateTime object to create a 'now' date position
$today = new dateClass($todaysDate->format('Y-m-d H:i:s'));

for ($x = 0; $x <= 30; $x++) {
    array_push($datesArray, $today->getFullDateAndTimeAsString());
    $today->dateTimePlus(1, 'd');
  } 

// Initialise variables. These will be used to reference validated input data and also handle errors.

$location = $arrival_date;
$warning = 30;
$latitude = $longtitude = "";
$location_err = $warning_err = $date_err = "";

// Processing form data when form is submitted

if($_SERVER["REQUEST_METHOD"] == "POST"){
 

    // validate location
    $input_location = trim($_POST["location"]);

    // Now try to validate from the postcodes table

    $sql = "SELECT latitude, longtitude FROM pcodes WHERE pcode1 = ? OR pcode2 = ? OR pcode3 = ?"; // Look for all 3 aternate formats provided in the ONS feed

    $stmt = $link->stmt_init();
    
    if($stmt->prepare($sql)) {

        $stmt->bind_param("sss", $param_location1, $param_location2, $param_location3);

        // Each parameter can be bound to the SAME user input e.g. the location value

        $param_location1 = $input_location;
        $param_location2 = $input_location;
        $param_location3 = $input_location;

        if($stmt->execute()) {

            $stmt->store_result();

            if($stmt->num_rows() == 1) {
                
                //Found input postcode so valid

                $location = $input_location;
                $stmt->bind_result($param_lat, $param_long);

                if ($stmt->fetch()) {

                    //Save the longtitude and latitude details for the directions call.

                    $latitude = $param_lat;
                    $longtitude = $param_long;

                }


            } else {
                $location_err = "Please enter a valid postcode";
            }
        }

        $stmt->close();
    }

    $input_date = trim($_POST["selectedDate"]);

    if (isset($input_date)) {

        if ($input_date != "-- Select Date --") {
            $arrival_date = $input_date;
        } else {
            $date_err = "Please Select a date";
        }

    } else {
        $date_err = "Please Select a date";
    }

    

    // validate warning
    $warning = trim($_POST["selectedWarning"]);
    

    // Check input errors before inserting in database
    if(empty($location_err) && empty($warning_err) && empty($date_err)){

        // Everything is ok, so....

        // Set up session variables for what will go into the database
        // Call the route calculator for latitude and logtitude and store in session
        // redirect to the safe crossing display page

        $_SESSION["pcode"] = $location;
        $_SESSION["latitude"] = $latitude;
        $_SESSION["longtitude"] = $longtitude;
        $_SESSION["arrivalDate"] = $arrival_date;
        $_SESSION["warning"] = $warning;

        // Don't need the ID because is already in $SESSION["id"]

        // Call the calcJourneyRoute.py with the saved latitude/longtitude as params. Outputs will come to the $res var as a string stream output where the print command used in .py code

        $res = shell_exec("python3 calcJourneyRoute.py $latitude $longtitude");
        $res_array = explode(",", $res);  // Use the explode command to parse the comma separate stream. Creates an array of data

        // res_array:
        // [0] = Response
        // [1] = Response Description
        // [2] = Route Distance (m)
        // [3] = Route Time (s)

        $_SESSION["distance"] = trim($res_array[2]);
        $_SESSION["time"] = trim($res_array[3]);


        if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
            $uri = 'https://';
        } else {
            $uri = 'http://';
        }
        $uri .= $_SERVER['HTTP_HOST'];

        // Redirect to process page, allowing the user to select a safe crossing

        header('Location:ProcessJourney.php');

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
        <h2>Enter Journey Details</h2>
        <!-- The $SERVER(PHP_SELF) holds the current script being executed, Therefore this means to call itself again when the post is done - and how the PHP checking code is called -->
        <!-- Use special characters to ignore any code injection in the call -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div>
                <label>Enter Your Departure Location (Postcode)</label>
                <input type="text" name="location" class="formItem <?php echo (!empty($location_err)) ? '-invalid' : ''; ?>" value="<?php echo $location; ?>">
                <span class="invalid"><?php echo $location_err; ?></span>
            </div>  
            <div>
                <select name = "selectedDate">
                    <option selected="selDate">-- Select Date --</option>
                    <?php
                        // Iterating through the product array
                        foreach($datesArray as $date){
                            $tempDate = new dateClass($date);
                            $val = $tempDate->getDateOnly();
                            $opt = $tempDate->getDayOnly() . "," . $tempDate->getDateOnlyAlt();
                            echo "<option value='$val'>$opt</option>";
                        }
                    ?>
                </select>
            </div>             
            <div>
                <label>Enter Preferred Warning Time</label>
                <select name = 'selectedWarning'>
                    <option selected='selTime'>30</option>
                    <option value='60'>1hr</option>
                    <option value='90'>1hr 30min</option>
                    <option value='120'>2hr</option>
                    <option value='180'>2hr 30min</option>
                    <option value='240'>3hr</option>
                </select>
            </div>  
            <div>

                <input type="submit" value="Get Safe Crossings">
                <input type="reset" value="Reset">
                <p>Source: Office for National Statistics licensed under the Open Government Licence v.3.0
                Contains OS data © Crown copyright and database right [2022]</p>
            </div>
            
        </form>
    </div>    
</body>
</html>