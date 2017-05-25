<?php

namespace reporter;
spl_autoload("reporter");

class _reporterRemote extends reporter
{
    public $user;
    public $web_root = "/usr/home/%s/public_html";
    public $drush_root = "/usr/home/%s/vendor/bin/drush.php";

    public function __construct()
    {
        parent::__construct(remote);
        $this->user = trim(`whoami`);
        // Replace in the user name into string.
        // Note that if a webroot argument is passed, it's assumed that the user part is already present.
        $this->web_root = sprintf($this->web_root, $this->user);
        $this->drush_root = sprintf($this->drush_root, $this->user);

        $this->report();
    }

    public function report()
    {
        header('Content-Type: application/json; charset=utf-8');
        $infoSource = $_SERVER['REQUEST_METHOD'] == "POST" ? $this->getInputFromRequestBody() : ($_SERVER['REQUEST_METHOD'] == "GET" ? $_GET : array());
        if (!array_key_exists("t", $infoSource)) {
            $infoSource["t"] = "cpu";
        }

        $report_type = $infoSource["t"];

        switch ($report_type) {
            case ("version"):
                break;
            case ("cpu"): {
                $topData = `top -b -n 4 -d 01.00 -i`;
                if (is_null($topData)) {
                    $this->response[$report_type]["e"] = "cpu: shell_exec is denied on server";
                } else {
                    if (preg_match_all('|([\.\d]*)%?\s*?id|m', $topData, $data)) {
                        $sum = 0;
                        foreach ($data[1] as $value)
                            $sum += (float)$value;
                        $this->response[$report_type]["v"] = round(100.0 - $sum / 4, 2);
                    } else {
                        $this->response[$report_type]["e"] = "top command returned an unexpected result";
                    }
                }
                break;
            }

            case ("ram"): {
                $freeData = `free -k`;
                if (is_null($freeData)) {
                    $this->response[$report_type]["e"] = "shell_exec is denied on server";
                } else {
                    if (preg_match('/^.*?\n.*?\s(\d+)\s*(\d+)\s*(\d+)\s.*/m', $freeData, $data)
                    ) {
                        $this->response[$report_type]["v"] = round(100.0 - ((float)$data[3] / (float)$data[1]) * 100.0, 2);
                    } else {
                        $this->response[$report_type]["e"] = "free command returned unexpected result";
                    }
                }
                break;
            }

            case ("disk"): //file system
            {
                if (!array_key_exists("l", $infoSource)) {
                    $this->response[$report_type]["e"] = "file system mounting path was not specified";
                } else {
                    $dfData = `df -h`;
                    if (array_key_exists("dbg", $_GET)) {
                        echo exec('whoami');
                        echo "\r\n";
                        echo $dfData;
                    }
                    if (is_null($dfData)) {
                        $this->response[$report_type]["e"] = "shell_exec is denied on server";
                    } else {
                        $numOfFsFound = preg_match_all('/([0-9][0-9]?0?)%\s+?([^\s]+)/', $dfData, $data);
                        if ($numOfFsFound !== FALSE && $numOfFsFound > 0) {
                            $fsFound = array_search($infoSource["l"], $data[2]);
                            if ($fsFound !== FALSE && $fsFound >= 0) {
                                $this->response[$report_type]["v"] = round((float)$data[1][$fsFound], 2);
                            } else {
                                $this->response[$report_type]["e"] = "file system for path '{$infoSource["l"]}' not found";
                            }
                        } else {
                            $this->response[$report_type]["e"] = "file system for path '{$infoSource["l"]}' not found";
                        }
                    }
                }
                break;
            }

            case ("drush"): {
                $commands["status"] = ["Drupal version", "Drupal bootstrap", "Database"];
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
        foreach ($commands as $command => $keys) {
            $drushData = `cd $this->web_root && php $this->drush_root $command`;
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
                    $this->response[$command]['e'] = "No data.";
                }
            }
        }

        return TRUE;
    }
}

new _reporterRemote();