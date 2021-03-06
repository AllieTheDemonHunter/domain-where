<?php

namespace reporter;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once "convenience.php";
include_once "reporter.php";
spl_autoload_register();

class _reporterRemote extends reporter
{

    public $user;

    public $web_root;

    public $debug;

    public $drush_root = "/usr/home/%s/vendor/bin/drush";

    public function __construct($domain, $drush_root_hint = NULL)
    {
        parent::__construct($domain);
        $this->user = get_current_user();
        $this->web_root = $_SERVER['DOCUMENT_ROOT'];
        $this->drush_root = sprintf($this->drush_root, $this->user);
        $this->report();
    }

    /**
     * The run function.
     *
     * @return bool
     */

    // @TODO The amount of things this method does isn't defensible.
    public function report()
    {
        $infoSource = $_SERVER['REQUEST_METHOD'] == "POST" ? $this->getInputFromRequestBody() :
            ($_SERVER['REQUEST_METHOD'] == "GET" ? $_GET :
                []);

        /**
         * Default action if none is specified.
         */
        if (!array_key_exists("t", $infoSource)) {
            $infoSource["t"] = "loadaverage";
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
                } else {
                    array_walk($topData, function(&$average_interval){
                        $average_interval = round($average_interval, 2);
                    });
                    $this->response[$report_type]["v"] = implode(" | ", $topData);
                }
                break;
            }

            case ("disk"): {
                $free_space = disk_free_space(".");
                $total_space = disk_total_space(".");
                $disk_free_space_percentage = (($total_space - $free_space) / $total_space) * 100;
                if (is_null($disk_free_space_percentage)) {
                    $this->response[$report_type]["e"] = "<code>disk_free_space()</code> is denied on server.";
                } elseif ($disk_free_space_percentage > 0) {
                    $this->response[$report_type]["v"] = round($disk_free_space_percentage, 2);
                }
                break;
            }

            case ("drush"): {
                $commands["status"] = [
                    "Drupal version",
                    //"Drupal bootstrap",
                    //"Database",
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

    public function drush_request(array $commands)
    {
        $response = array();
        $responses = array();
        foreach ($commands as $command => $keys) {
            $drushData = `php $this->drush_root --root=$this->web_root $command`;
            //Make the command string safe to use as a key in returned JSON.
            $command = $this::make_machine_name($command);
            if (is_null($drushData)) {
                //This doesn't get used / returned.
                $response[$command]['e'] = "Nothing here.";
            } else {
                $drushDataArray = explode(PHP_EOL, $drushData);

                if (is_array($drushDataArray) && !empty($drushDataArray)) {
                    foreach ($drushDataArray as $row) {

                        $this_row_array = explode(":", $row);

                        $the_key = trim($this_row_array[0]);
                        if (in_array($the_key, $keys)) {
                            $response[$command]['v'][] = $this_row_array;
                        }
                    }
                } else {
                    $response[$command]['e'] = "Drush: No data returned.";
                }
            }
        }

        return $response;
  }
}