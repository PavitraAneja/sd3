<?php
// $servername = '199.250.207.194';
// $username = 'boxgra5_sd3';
// $password = 'Real_estate123$';
// $dbname = 'boxgra5_cali';
// $port = 3306;
$servername = '70.22.244.240';
$dbname = 'boxgra6_sd3';
$username = 'boxgra6_yu';
$password = '79._.h*.*yh@_Jd';
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
