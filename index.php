<?php
/**
 * Created by PhpStorm.
 * User: allie
 * Date: 17/05/16
 * Time: 6:18 PM
 */
namespace reporter;

include_once "reporter/convenience.php";
include_once "reporter/reporter.php";
include_once "reporter/reporterFrontend.php";
spl_autoload_register();
?>

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

  $domains["http://allie.local"] = FALSE;
  $domains["http://aucor.com"] = FALSE;
  $domains["http://dsp"] = FALSE;
  $domains["https://ezrails.co.za/"] = FALSE;
  $domains["http://dtm"] = TRUE;
  $domains["https://www.ferreirapartners.co.za"] = TRUE;
  $domains["http://www.asinteriordesign.co.za"] = FALSE;

  foreach ($domains as $domain => $active) {
    if ($active) {
      $start_time = time() + microtime();
      $report = new _reporterFrontend($domain);
      $report
        ->query(["loadaverage", "disk", "drush", "psi"])
        ->process($start_time);
      unset($report);
    }
  }
  ?>
</div>
</body>
</html>
