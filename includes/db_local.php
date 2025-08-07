<?php
// $host = 'localhost';
// $dbname = 'boxgra5_cali';
// $dbuser = 'boxgra5_sd3';
// $dbpass = 'Real_estate123$';
// $table1 = 'rets_property';

$host = 'localhost';
$dbname = 'boxgra6_sd3';
$dbuser = 'boxgra6_yu';
$dbpass = '79._.h*.*yh@_Jd';
$table1 = 'rets_property_yu';

try {
    // Create a PHP data object instance to connect to mysql
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully to table: <strong>$table1</strong><br><br>";
    
    // Display the frist 10 rows from the table
    $sql = "SELECT * FROM $table1 LIMIT 10";
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<pre>";
    print_r($results);
    echo "</pre>";

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>