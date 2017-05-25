<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Domain Where</title>
    <link href="css/main.css" rel="stylesheet" type="text/css">
    <script src="js/main.js"></script>
</head>
<body>
<div id="wrapper">
<?php
/**
 * Created by PhpStorm.
 * User: allie
 * Date: 17/05/16
 * Time: 6:18 PM
 */
include_once "settings.php";
include_once "functions.php";

$domains[] = "http://z-dspsa.co.za.dedi179.cpt3.host-h.net";

/*$domains[] = "https://ezrails.co.za/";
$domains[] = "https://www.ferreirapartners.co.za";
$domains[] = "http://www.asinteriordesign.co.za";*/

foreach ($domains as $domain) {
    $time_taken = 0;
    $start_time = 0;
    $end_time = 0;

    $start_time = time() + microtime();
    $data = query(["analytics", "cpu", "ram", "disk", "drush", "psi"], $domain);
    $end_time = time() + microtime();


    $time_taken = ($end_time - $start_time);
    print process($data, $time_taken);
}
?>
</div>
</body>
</html>
