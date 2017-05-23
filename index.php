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
$domains["ezraiwbykk"] = "https://ezrails.co.za/";

foreach ($domains as $user => $domain) {
    $time_taken = 0;
    $start_time = 0;
    $end_time = 0;

    $start_time = time() + microtime();
    $data = query(["analytics", "cpu", "ram", "disk", "drush", "leadtrekker", "leadtrekker_api_key", "psi"], $domain, $user);
    $end_time = time() + microtime();

    $time_taken = ($end_time - $start_time);
    print process($data);
    print "Time taken: <b>{$time_taken}</b>s";
}

function process(array $data) {
    foreach($data as $domain_url => $report) {
        print "<div class='reports'>";
        process_report($report);
        print "</div>";
    }
}

function _process_report($report) {
    foreach($report as $type_of_report => $reporter) {
        print "<div class='report $type_of_report'>";
        switch($type_of_report) {
            case "psi":
                //type should be object
                break;
            case "analytics":
                //Just a string
                break;
            default:
                //Assumed to be a reporter object.
                _process_reporter($type_of_report, $reporter);
        }
        print "</div>";
    }
}

function _process_reporter($type_of_report, $reporter) {
    print "<div class='reporter $type_of_report'><h3>$type_of_report: </h3>";

    if(isset($reporter->info->response['v'])) {
      $value_or_error = "v";
    } elseif(isset($reporter->info->response['e'])) {
        $value_or_error = "e";
    }

    print "<div class='value $value_or_error'>";
    print $reporter->info->response[$value_or_error];

    print "</div></div>";
}