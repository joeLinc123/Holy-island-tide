<?php
//Allow the user to connect to the database

$sname = "localhost";
$uname = "root";
$password = "root";

$db_name = "users";

$link = mysqli_connect($sname, $uname, $password, $db_name);

if(!$link) {
    echo "connection failed!";
}
