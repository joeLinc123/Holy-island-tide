<?php

//Log out. Forget any session variables for this session, and return to login page

//init session
session_start();
//unset session variables
$_SESSION = array();
//End the session
session_destroy();
//redirect user to the home page of the website
header("location: welcomePage.php");
exit;
?>