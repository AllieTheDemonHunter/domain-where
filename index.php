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

function _query($this_type, $domain, $end_point_url, $fixes)
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
                break;
            }

            case "psi.local": {

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

                    case "psi.remote": {
                        $fixes['post'][$this_type] = "";
                        break;
                    }
                }

                $url_variables['t'] = $this_type;
                $end_point_url = $url . "/" . SITE_REPORTER . "?" . http_build_query($url_variables);
                $response[$this_type] = _query($this_type, $url, $end_point_url, $fixes);
            }
        }
    }


    return [$url => $response];
}


function psi ($url) {
    $psiData = `psi $url --nokey --strategy=mobile --format=json --threshold=0`;
    if (is_null($psiData)) {
        $response["e"] = "psi is denied on server: psi";
    } else {
        $psi_object = json_decode($psiData);
        $response['v'] = $psi_object->overview;
    }

    return $response;
}

$domains = ["http://aucor.starbright.co.za"];

foreach ($domains as $domain) {
    $time_taken = 0;
    $start_time = 0;
    $end_time = 0;

    $start_time = time() + microtime();
    rprint(query(["analytics", "cpu", "ram", "disk", "drush", "leadtrekker", "leadtrekker_api_key", "psi.remote", "psi.local"], $domain));
    $end_time = time() + microtime();

    $time_taken = ($end_time - $start_time);
    print "Time taken: <b>{$time_taken}</b>s";
}