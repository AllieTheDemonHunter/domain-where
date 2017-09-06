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
  $domains["http://z-starbright.co.za.dedi25.cpt4.host-h.net"] = 1;
  $domains["http://z-dtm.co.za.dedi1255.jnb1.host-h.net"] = 0;
  $domains["http://z-dspsa.co.za.dedi179.cpt3.host-h.net"] = 0;
  $domains["https://www.ferreirapartners.co.za"] = 0;
  $domains["http://sph"] = 0;

  $requests = ["analytics", "loadaverage", "disk", "drush", "psi"];

  $requests = ["analytics", "loadaverage", "disk", "drush"];

  foreach ($domains as $domain => $active) {
    if ($active) {
      $start_time = time() + microtime();
      $report = new _reporterFrontend($domain);
      $report
        ->query($requests)
        ->process($start_time);
      unset($report);
    }
  }
  ?>
</div>
</body>
</html>