<?php

namespace reporter;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once "convenience.php";
include_once "reporter.php";
spl_autoload_register();

new _reporterRemote($_SERVER['SERVER_NAME']);

class _reporterRemote extends reporter {

  public $user;

  public $web_root = "/usr/home/%s/public_html";

  public $drush_root = "/usr/home/%s/vendor/bin/drush.php";

  public function __construct($domain) {
    parent::__construct($domain);
    $this->user = trim(`whoami`);
    // Replace in the user name into string.
    // Note that if a webroot argument is passed, it's assumed that the user part is already present.
    $this->web_root = sprintf($this->web_root, $this->user);
    $this->drush_root = sprintf($this->drush_root, $this->user);

    $this->report();
  }

  public function report() {
    $infoSource = $_SERVER['REQUEST_METHOD'] == "POST" ? $this->getInputFromRequestBody() :
      ($_SERVER['REQUEST_METHOD'] == "GET" ? $_GET :
        []);

    if (!array_key_exists("t", $infoSource)) {
      $infoSource["t"] = "cpu";
    }

    $report_type = $infoSource["t"];

    switch ($report_type) {
      case ("version"):
        break;
      case ("loadaverage"): {
        $topData = sys_getloadavg();
        if (is_null($topData)) {
          $this->response[$report_type]["e"]
            = "<code>sys_getloadavg()</code> is denied on server.";
        }
        else {
            $this->response[$report_type]["v"] = implode(" | ", $topData);
        }
        break;
      }

      case ("disk"): {
        $here = getcwd();
        $free_space = disk_free_space($here);
        $total_space = disk_total_space($here);
        $disk_free_space_percentage = ($total_space - $free_space) / $total_space;
        if (is_null($disk_free_space_percentage)) {
          $this->response[$report_type]["e"] = "<code>disk_free_space()</code> is denied on server.";
        } elseif ($disk_free_space_percentage > 0) {
            $this->response[$report_type]["v"] = $disk_free_space_percentage;
        }
        break;
      }

      case ("drush"): {
        $commands["status"] = [
          "Drupal version",
          "Drupal bootstrap",
          "Database",
        ];
        $commands["vget leadtrekker_api_key"] = ["leadtrekker_api_key"];
        $commands["pmi leadtrekker"] = ["Status"];
        $this->response[$report_type] = $this->drush_request($commands);
        break;
      }

      default: {
        header("HTTP/1.1 404 Not Found");
        break;
      }
    }

    return TRUE;
  }

  public function drush_request(array $commands) {
    foreach ($commands as $command => $keys) {
      $drushData = `cd $this->web_root && php $this->drush_root $command`;
      if (is_null($drushData)) {
        $response[$command]['e'] = "Nothing here.";
      }
      else {
        $drushDataArray = explode(PHP_EOL, $drushData);

        if (is_array($drushDataArray) && !empty($drushDataArray)) {
          foreach ($drushDataArray as $row) {

            $this_row_array = explode(":", $row);

            $the_key = trim($this_row_array[0]);
            if (in_array($the_key, $keys)) {
              $response[$command]['v'][] = $this_row_array;
            }
          }
        }
        else {
          $this->response[$command]['e'] = "Drush: No data returned.";
        }
      }
    }

    return TRUE;
  }
}