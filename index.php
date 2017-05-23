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

function _query($this_type, $end_point_url, $fixes)
{
    $c = curl_init($end_point_url);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    $returned_from_server = curl_exec($c);
    $json_from_server = json_decode($returned_from_server);
    curl_close($c);

    if (is_object($json_from_server) && isset($json_from_server->response->v)) {
        if (is_object($json_from_server->response->v)) {
            $response = $json_from_server->response->v;
        } else {
            $response = $fixes['pre'][$this_type] . $json_from_server->response->v . $fixes['post'][$this_type];
        }
    } else {
        $response = $json_from_server;
    }

    return $response;
}



function query(array $type, $url, $user)
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
                break;
            }

            case "psi": {
                $psi_url_string = "https://www.googleapis.com/pagespeedonline/v2/runPagespeed?url={$url}/&strategy=mobile&key=" . GOOGLE_PSI_API_KEY;
                $c = curl_init($psi_url_string);
                curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                $json_from_server = curl_exec($c);
                curl_close($c);

                $psi_object = json_decode($json_from_server);

                if (isset($psi_object->responseCode) && $psi_object->responseCode == "200") {
                    unset($psi_object->formattedResults);
                    $response[$this_type] = $psi_object;
                }
                break;
            }

            default: {

                switch ($this_type) {
                    case "disk": {
                        $url_variables['l'] = "/";
                        $fixes['post'][$this_type] = "%";
                        break; // this is being done in this switch statement, not the main one that contains it above.
                    }

                    case "drush": {
                        $fixes['post'][$this_type] = "";
                        break;
                    }

                    case "leadtrekker": {
                        $fixes['post'][$this_type] = "";
                        break;
                    }

                    case "leadtrekker_api_key": {
                        $fixes['post'][$this_type] = "";
                        break;
                    }
                }

                $url_variables['t'] = $this_type;
                $end_point_url = $url . "/" . SITE_REPORTER . "?" . http_build_query($url_variables);
                $response[$this_type] = _query($this_type, $end_point_url, $fixes, $user);
            }
        }
    }


    return [$url => $response];
}

$domains["zdspsarazz"] = "http://z-dspsa.co.za.dedi179.cpt3.host-h.net/";

foreach ($domains as $user => $domain) {
    $time_taken = 0;
    $start_time = 0;
    $end_time = 0;

    $start_time = time() + microtime();
    rprint(query(["analytics", "cpu", "ram", "disk", "drush", "leadtrekker", "leadtrekker_api_key"], $domain, $user));
    $end_time = time() + microtime();

    $time_taken = ($end_time - $start_time);
    print "Time taken: <b>{$time_taken}</b>s";
}