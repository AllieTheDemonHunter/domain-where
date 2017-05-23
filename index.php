<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Domain Where</title>
    <style>
        body {
            font-family: "monospace";
            color: white;
            background-color: rgba(0,0,0,.8);
            font-size: 14px;
        }

        h3 {
            margin: 1em auto 0.1em;
            text-transform: capitalize;
        }

        #wrapper {
            display: flex;
            flex-flow: row wrap;
            justify-content: space-around;
            margin: 1em;
            background-color: rgba(0,0,0,.8);
            border-radius: 4px;
            filter: drop-shadow(0 0 3px black);
        }

        .report {
            flex: 0 1 25%;
            padding: 0.6em 0.8em;
            background-color: rgba(255, 255, 255, 0.22);
            border-radius: 3px;
            margin-bottom: 1px;
        }

        dt {
            text-transform: uppercase;
        }

        .v {
            color: green;
        }

        .e {
            color: red;
        }
        
        .reporter {
            position: relative;
        }

        .update-status {
            font-size: 0.8em;
            border: 1px solid lightskyblue;
            padding: 0.2em;
            text-align: center;
            border-radius: 2px;
            background-color: rgba(173, 216, 230, 0.34);
            position: absolute;
            top: -2em;
            right: -0.8em;
            opacity: 0.2;
        }

        .update-status:hover {
            opacity: 1;
        }
    </style>
</head>
<body>
<div id="wrapper">
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
                $response[$this_type] = _query($this_type, $end_point_url, $fixes);
            }
        }
    }


    return [$url => $response];
}

$domains["zdspsarazz"] = "http://z-dspsa.co.za.dedi179.cpt3.host-h.net/";
//$domains["ezraiwbykk"] = "https://ezrails.co.za/";

foreach ($domains as $user => $domain) {
    $time_taken = 0;
    $start_time = 0;
    $end_time = 0;

    $start_time = time() + microtime();
    $data = query(["analytics", "cpu", "ram", "disk", "drush", "leadtrekker", "pathauto", "leadtrekker_api_key", "psi"], $domain);
    $end_time = time() + microtime();

    $time_taken = ($end_time - $start_time);
    print process($data);
    print "<div class='time-taken'>Time taken: <b>{$time_taken}</b>s</div>";
}

function process(array $data) {
    foreach($data as $domain_url => $report) {
        print "<div class='reports'>";
        _process_report($report);
        print "</div>";
    }
    return;
}

function process_psi (stdClass $report) {
    if(isset($report->ruleGroups)) {
        print "<dl>";
        foreach($report->ruleGroups as $rule_heading => $value_object) {
            $score = trim($value_object->score);
            if($score < 50) {
                $class = "e";
            } else {
                $class = "v";
            }
            print "<dt class='$class'>" .trim($rule_heading). "</dt><dd class='$class'>" .$score. "%</dd>";
        }
        print "<dl>";
    }
}

function _process_report($report) {
    foreach($report as $type_of_report => $reporter) {
        print "<div class='report $type_of_report'>";
        switch($type_of_report) {
            case "psi":
                //type should be object
                print "<div class='reporter $type_of_report'><h3>$type_of_report: </h3>";
                process_psi($reporter);
                print "</div>";
                break;

            case "analytics":
                //Just a string
                print "<div class='reporter $type_of_report'><h3>$type_of_report: </h3>$reporter</div>";
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
    print "<div class='update-status'><em>" .nl2br(trim($reporter->update)). "</em> @ " . $reporter->info->version ."</div>";
    if(isset($reporter->info->response->v)) {
      $value_or_error = "v";
    } elseif(isset($reporter->info->response->e)) {
        $value_or_error = "e";
    }

    print "<div class='value $value_or_error'>";
    $the_value = $reporter->info->response->$value_or_error;

    if(is_array($the_value)){
        //Drush
        make_list($the_value);
    } else {
        //Server metrics
        print $the_value . "%";
    }


    print "</div></div>";
}

function make_list(array $data) {
    print "<dl>";
    foreach($data as $result) {
        $definition = $result[1];
        if(is_array($definition)) {
            print "<dt>" .trim($result[0]). "</dt><dd>" .make_list($definition). "</dd>";
        } else {
            print "<dt>" .trim($result[0]). "</dt><dd>" .trim($definition). "</dd>";
        }
    }
    print "</dl>";
}
?>
</div>
</body>
</html>
