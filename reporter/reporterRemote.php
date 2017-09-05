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

    public $web_root = "/usr/home/%s/public_html";

    public $drush_root;

    public function __construct($domain, $drush_root_hint = NULL)
    {
        parent::__construct($domain);
        // @TODO Replace shell calls.
        $this->user = get_current_user();
        $this->web_root = $_SERVER['DOCUMENT_ROOT'];
        $this->drush_root = `which drush`;
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
                    $this->response[$report_type]["v"] = implode(" | ", $topData);
                }
                break;
            }

            case ("disk"): {
                $free_space = disk_free_space("/");
                $total_space = disk_total_space("/");
                $disk_free_space_percentage = (($total_space - $free_space) / $total_space) * 100;
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
                print_r($this);
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
        foreach ($commands as $command => $keys) {
            $drushData = `cd $this->web_root && drush $command`;
            if (is_null($drushData)) {
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
                    $this->response[$command]['e'] = "Drush: No data returned.";
                }
            }
        }

        return $this->response;
  }
}