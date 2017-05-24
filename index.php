<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Domain Where</title>
    <style>
        body {
            font-family: sans-serif;
            color: white;
            background-color: rgba(0,0,0,.8);
            font-size: 14px;
        }

        h2 {
            font-size: 0.8em;
            text-align: center;
            color: #eae9ff;
            border: solid white;
            margin: 0 5px;
            border-radius: 2px 2px 0 0;
            border-width: 1px 1px 0px 1px;
            padding: 1em 0;
            background: rgba(62, 117, 20, 0.92);
        }

        h3 {
            margin: 1em auto 0.1em;
            text-transform: capitalize;
        }

        #wrapper {
            display: flex;
            flex-flow: row wrap;
            justify-content: space-around;
            margin: 1em;
            background-color: rgba(0,0,0,.8);
            border-radius: 4px;
            filter: drop-shadow(0 0 3px black);
            padding: 1em;
        }

        .reports {
            flex: 0 1 260px;
        }

        .report {
            padding: 0.6em 0.8em 1.5em;
            background-color: rgba(255, 255, 255, 0.22);
            border-radius: 3px;
            margin-bottom: 1px;
        }

        .time-taken {
            text-align: center;
            padding: 1em;
        }

        dt {
            text-transform: uppercase;
        }

        .v {
            color: green;
        }

        .e {
            color: red;
        }
        .reporter {
            position: relative;
        }

        .update-status {
            font-size: 0.8em;
            border: 1px solid lightskyblue;
            padding: 0.2em;
            text-align: center;
            border-radius: 2px;
            background-color: rgba(173, 216, 230, 0.34);
            position: absolute;
            top: -2em;
            right: -0.8em;
            opacity: 0.2;
        }

        .update-status:hover {
            opacity: 1;
        }

    </style>
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
/*
$domains[] = "https://ezrails.co.za/";
$domains[] = "https://www.ferreirapartners.co.za";
$domains[] = "http://www.asinteriordesign.co.za";*/
//$domains[] = "http://aucor.taylor";

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
