<?php
/**
 * Created by PhpStorm.
 * User: allie
 * Date: 17/05/16
 * Time: 6:18 PM
 */
include_once "settings.php";

function rprint($d)
{
    print "<pre>" . print_r($d, 1) . "<pre>";
}

function is_analytics($str)
{
    preg_match('/GTM-[\w\d]{6,9}/im', strval($str), $matches);
    return $matches ? count($matches) . " match: " . $matches[0] : FALSE;
}

function query(array $type, $url)
{
    $response = [];
    $url_variables = [];


    foreach ($type as $this_type) {
        $fixes['pre'][$this_type] = "";
        $fixes['post'][$this_type] = "%";

        switch ($this_type) {
            case "analytics": {
                $c = curl_init($url);
                curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                $html_from_server = curl_exec($c);
                curl_close($c);
                $outcome = is_analytics($html_from_server);
                $response[$this_type] = $outcome ? "Analytics code found: " . $outcome : "No Analytics found.";
            }
                break;

            //Remove
            case "drush" || "disk": {

                switch ($this_type) {
                    case "disk": {
                        $url_variables['l'] = "/usr/home";
                        $fixes['post'][$this_type] = "%";
                        break; // this is being done in this switch statement, not the main one that contains it above.
                    }

                    case "drush": {
                        $fixes['post'][$this_type] = "";
                    }
                }
            }

            default: {
                $url_variables['t'] = $this_type;
                $end_point_url = $url . "/" . SITE_REPORTER . "?" . http_build_query($url_variables);
                $c = curl_init($end_point_url);
                curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                $returned_from_server = curl_exec($c);
                $json_from_server = json_decode($returned_from_server);
                curl_close($c);

                if (is_object($json_from_server) && isset($json_from_server->response->v)) {
                    if (is_object($json_from_server->response->v)) {
                        $response[$this_type] = $json_from_server->response->v;
                    } else {
                        $response[$this_type] = $fixes['pre'][$this_type] . $json_from_server->response->v . $fixes['post'][$this_type];
                    }
                } else {
                    $response[$this_type] = $returned_from_server;// "ABORTING: No reporter file: <b>'".SITE_REPORTER."</b>' on " . $url;
                    return [$url => $response];
                }

            }
        }

        unset($this_type);
    }


    return [$url => $response];
}

$domains = ["http://z-dspsa.co.za.dedi179.cpt3.host-h.net"];

foreach ($domains as $domain) {
    $time_taken = 0;
    $start_time = 0;
    $end_time = 0;

    $start_time = time() + microtime();
    rprint(query(["analytics", "cpu", "ram", "disk", "drush"], $domain));
    $end_time = time() + microtime();

    $time_taken = ($end_time - $start_time);
    print "Time taken: <b>{$time_taken}</b>s";
}