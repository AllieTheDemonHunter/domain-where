<?php
/**
 * Created by PhpStorm.
 * User: allie
 * Date: 2017/05/24
 * Time: 8:17 AM
 */

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
                $psi_url_string = "https://www.googleapis.com/pagespeedonline/v2/runPagespeed?url={$url}/&strategy=mobile&screenshot=true&key=" . GOOGLE_PSI_API_KEY;
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

                    default: {
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

function process(array $data, $time_taken) {
    foreach($data as $domain_url => $report) {
        print "<div class='reports'>";
        print "<h2>$domain_url</h2>";
        _process_report($report);
        print "<div class='time-taken'>Time taken: <b>{$time_taken}</b>s</div>";
        print "</div>";
    }
    return;
}

function process_psi (stdClass $report) {
    //rprint($report);
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

    if(isset($report->screenshot)) {
        $google_data = $report->screenshot->data;
        $base64_data = str_replace("-","+", str_replace("_", "/", $google_data));
        print '<div><img src="data:'.$report->screenshot->mime_type.';charset=utf-8;base64, '.$base64_data.'"></div>';
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