<?php
     
     $servername = "localhost";
     $username = "root";
     $dbpassword = "918273645";
     $dbname = "elitefit";

    //  mysqli_connect is a function in php and it takes these parameters
     $conn = mysqli_connect($servername, $username, $dbpassword, $dbname);

    //  if connection does not work, tell us what the error is
     if(!$conn){
        die("Connection failed: " . mysqli_connect_error());
     }


?>