<?php
/**
 * Created by PhpStorm.
 * User: allie
 * Date: 17/05/16
 * Time: 4:28 PM
 */

/**
 * Testing monitor
 *
 * -- Several counters are accessible through HTTP GET request of installed handler. --
 * For example, if you place handler by path <basepath>/ht.ashx:
 * 1. <basepath>/ht.ashx?t=cpu - for retrieving cpu usage
 * 2. <basepath>/ht.ashx?t=ram - for retrieving ram usage
 * 3. <basepath>/ht.ashx?t=disk&l=%2Fdev%2Fsda1 - for retrieving /dev/sda1 file system usage
 * 4. <basepath>/ht.ashx?t=port&a=myotherserver.com&p=443
 * - for checking access to port 443 of myotherserver.com from your server
 * Our system uses HTTP POST requests for catching monitored value.
 */

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(-1);


function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

function getInputFromRequestBody()
{
    $body = file_get_contents('php://input');
    return json_decode($body, TRUE);
}

class domain_where
{
    public $version = "v0.61", $response = [];
    public $user;
    public $web_root = "/usr/home/%s/public_html";
    public $drush_root = "/usr/home/%s/vendor/bin/drush.php";

    public function __construct()
    {
        $this->user = trim(`whoami`);
        // Replace in the user name into string.
        // Note that if a webroot argument is passed, it's assumed that the user part is already present.
        $this->web_root = sprintf($this->web_root, $this->user);
        $this->drush_root = sprintf($this->drush_root, $this->user);

        $this->report();
        return (array) $this->response;
    }

    public function drush_request($command, $keys) {
        $drushData = `cd $this->web_root && php $this->drush_root $command`;
        if (is_null($drushData)) {
            $response['e'] = "Nothing here.";
        } else {
            $drushDataArray = explode(PHP_EOL, $drushData);

            if(is_array($drushDataArray) && !empty($drushDataArray)) {
                foreach($drushDataArray as $row) {

                    $this_row_array = explode(":", $row);

                    $the_key = trim($this_row_array[0]);
                    if(in_array($the_key, $keys)) {
                        $response['v'][] = $this_row_array;
                    }
                }
            } else {
                $response['e'] = "No data.";
            }
        }

        return $response;
    }

    public function psi ($url) {
        $psiData = `psi $url --nokey --strategy=mobile --format=json --threshold=0`;
        if (is_null($psiData)) {
            $response["e"] = "psi is denied on server: psi";
        } else {
            $psi_object = json_decode($psiData);
            $response['v'] = $psi_object->overview;
        }

        return $response;
    }

    public function report()
    {
        try {
            header('Content-Type: application/json; charset=utf-8');
            $infoSource = $_SERVER['REQUEST_METHOD'] == "POST" ? getInputFromRequestBody() : ($_SERVER['REQUEST_METHOD'] == "GET" ? $_GET : array());
            if (!array_key_exists("t", $infoSource)) {
                $infoSource["t"] = "cpu";
            }

            switch ($infoSource["t"]) {
                case ("version"):
                    break;
                case ("cpu"): {
                    $topData = `top -b -n 4 -d 01.00 -i`;
                    if (is_null($topData)) {
                        $this->response["e"] = "shell_exec is denied on server";
                    } else {
                        if (preg_match_all('|([\.\d]*)%?\s*?id|m', $topData, $data)) {
                            $sum = 0;
                            foreach ($data[1] as $value)
                                $sum += (float)$value;
                            $this->response["v"] = round(100.0 - $sum / 4, 2);
                        } else {
                            $this->response["e"] = "top command returned unexpected result";
                        }
                    }
                    break;
                }
                case ("ram"): {
                    $freeData = `free -k`;
                    if (is_null($freeData)) {
                        $this->response["e"] = "shell_exec is denied on server";
                    } else {
                        if (preg_match('/^.*?\n.*?\s(\d+)\s*(\d+)\s*(\d+)\s.*/m', $freeData, $data)
                        ) {
                            $this->response["v"] = round(100.0 - ((float)$data[3] / (float)$data[1]) * 100.0, 2);
                        } else {
                            $this->response["e"] = "free command returned unexpected result";
                        }
                    }
                    break;
                }
                case ("disk"): //file system
                {
                    if (!array_key_exists("l", $infoSource)) {
                        $this->response["e"] = "file system mounting path was not specified";
                    } else {
                        $dfData = `df -h`;
                        if (array_key_exists("dbg", $_GET)) {
                            echo exec('whoami');
                            echo "\r\n";
                            echo $dfData;
                        }
                        if (is_null($dfData)) {
                            $this->response["e"] = "shell_exec is denied on server";
                        } else {
                            $numOfFsFound = preg_match_all('/([0-9][0-9]?0?)%\s+?([^\s]+)/', $dfData, $data);
                            if ($numOfFsFound !== FALSE && $numOfFsFound > 0) {
                                $fsFound = array_search($infoSource["l"], $data[2]);
                                if ($fsFound !== FALSE && $fsFound >= 0) {
                                    $this->response["v"] = round((float)$data[1][$fsFound], 2);
                                } else {
                                    $this->response["e"] = "file system for path '{$infoSource["l"]}' not found";
                                }
                            } else {
                                $this->response["e"] = "file system for path '{$infoSource["l"]}' not found";
                            }
                        }
                    }
                    break;
                }

                case ("drush"): {
                    $this->response = $this->drush_request("status", ["Drupal version", "Drupal bootstrap", "Database"]);
                    break;
                }

                case ("leadtrekker"): {
                    $this->response = $this->drush_request("pmi leadtrekker", ["Status"]);
                    break;
                }

                case ("pathauto"): {
                    $this->response = $this->drush_request("pmi pathauto", ["Status"]);
                    break;
                }

                case ("leadtrekker_api_key"): {
                    $this->response = $this->drush_request("vget leadtrekker_api_key", ["leadtrekker_api_key"]);
                    break;
                }

                case ("psi.remote"): {
                    $actual_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
                    $this->response = $this->psi($actual_url);
                    break;
                }

                default: {
                    header("HTTP/1.1 404 Not Found");
                    break;
                }
            }
        } catch (Exception $e) {
            $this->response["e"] = $e->getMessage();
        }

        return $this->response;
    }
}

try {
    $response['update'] = `git pull`;
} catch (Exception $e) {
    print "FAILED TO UPDATE";
}

$response['info'] = new domain_where();

if (array_key_exists("e", $response['info']) && (strlen($response['info']["e"]) >= 255)) {
    $response['info']["e"] = substr($response['info']["e"], 0, 250) . "...";
}

echo json_encode($response);