<?php

//Date class
//A Helper class for a DateTime object in the form yyyy-mm-dd hh:mm:ss and perform actions on it


class dateClass {

    public DateTime $fullDateAndTime;

    // Constructor is $var = new dateClass(String)

    public function __construct(string $dateAndTime) {

        //Will construct an object from the string in this format
        $this->fullDateAndTime = new DateTime($dateAndTime);
    }

    //methods to return date, and reformat into just date and time

    //Return as a DateTime object (used for convenience in calculations that need a DateTime object as parameter)

    public function getFullDateAndTime() : DateTime{
        return $this->fullDateAndTime;
    }

     // Return yyyy-mm-dd hh:mm:ss format of the object. Used to create further date objects in this format, or to display where both are needed
    public function getFullDateAndTimeAsString() : string{
        return $this->fullDateAndTime->format('Y-m-d H:i:s');
    }

    // Return yyyy-mm-dd format of the object. Used where makes sense to show the date separately, or if creating a new date / time but just need the date to start with
    public function getDateOnly() : string{
        return $this->fullDateAndTime->format('Y-m-d');
    }

    // Return dd-mm-yyyy format of the object. Used where makes sense to show the date separately, or if creating a new date / time but just need the date to start with
    public function getDateOnlyAlt() : string{
        return $this->fullDateAndTime->format('d-m-Y');
    }

    // Return hh:mm:ss format of the object. Used where makes sense to show the time as part of output in tables
    public function getTimeOnly() : string{
        return $this->fullDateAndTime->format('H:i:s');
    }

    // Return hh:mm format of the object. Used where makes sense to show the time as part of output in tables
    public function getTimeOnlyAlt() : string{
        return $this->fullDateAndTime->format('H:i');
    }

    // Return hh format of the object. This is used to grab the hour when displaying crossing journeys to the user, so it can add :00:00 to make the time "on the hour"
    public function getHourOnly() : string{
        return $this->fullDateAndTime->format('H');
    }

    // Return day name of the object. This is used to show the day in the list of available dates
    public function getDayOnly() : string{
        return $this->fullDateAndTime->format('D');
    }

    // Add either a value of days, months or hours to the object. Uses DataInterval to do this
    public function dateTimePlus(int $value, string $type){
        
        if($type == "d"){
            $this->fullDateAndTime->add(new DateInterval('P'. $value . 'D'));
        } elseif($type == "m"){
            $this->fullDateAndTime->add(new DateInterval('PT' . $value . 'M'));

        } elseif($type == 'h'){
            $this->fullDateAndTime->add(new DateInterval('PT' . $value . 'H'));
        }
    }

    //subtract a number of day from the object. Uses DataInterval to do this
    public function dateTimeMinus(int $minutes){
        $interval = new DateInterval('PT' . $minutes . 'M');
        $interval->invert = 1;
        $this->fullDateAndTime->add($interval);

    }

    // Compare this date with another DateTime object. Return EQ if equal, GT if greater than and LT if less than. Used to check data in tables to determine if row should be processed
    public function compareDate(DateTime $withDate) : String{

        if($this->fullDateAndTime == $withDate){
            return "EQ";
        } else{
            if($this->fullDateAndTime > $withDate) {
                return "GT";

            } else {
                return "LT";
            }
        }
    }

    // Work out the difference in minutes between this object and another DateTime. Used to work out user time on the island. The difference is split into separate days, hours and minutes, so convert to minutes only
    public function difference(DateTime $withDate) : Int{
        $diff = $this->fullDateAndTime->diff($withDate);
        $total_minutes = ($diff->days * 24 * 60);
        $total_minutes += ($diff->h * 60);
        $total_minutes += $diff->i;
        return $total_minutes;
    }

    // As above but works out the difference in user readable format e.g. 120 mins = 2 hr, 140 mins = 2 hr 20 min
    public function differenceAsString(DateTime $withDate) : String {
        $return_value = "";
        $diff = $this->fullDateAndTime->diff($withDate);
        if ($diff->days > 0) {
            $return_value = $return_value.strval($diff->days) . "d ";
        }
        if ($diff->h > 0) {
            $return_value = $return_value.strval($diff->h) . "hr ";
        }
        if ($diff->i > 0) {
            $return_value = $return_value.strval($diff->i) . "min";
        }
        return $return_value;
    }



}
