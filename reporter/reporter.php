<?php
namespace reporter;

trait convenience
{
    public function r_print($d)
    {
        print "<pre>" . print_r($d, 1) . "<pre>";
    }

    public function make_list(array $data)
    {
        print "<dl>";
        foreach ($data as $result) {
            $definition = $result[1];
            if (is_array($definition)) {
                print "<dt>" . trim($result[0]) . "</dt><dd>" . $this->make_list($definition) . "</dd>";
            } else {
                print "<dt>" . trim($result[0]) . "</dt><dd>" . trim($definition) . "</dd>";
            }
        }
        print "</dl>";
    }
}

class reporter
{
    use convenience;
    protected $curl = NULL;

    public function __destruct()
    {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
        }
    }

    protected function curl_fetch()
    {
        if (!is_resource($this->curl)) {
            $this->curl_connect();
        }
        $returned_from_server = curl_exec($this->curl);

        if ($returned_from_server != "") {
            $json_from_server = json_decode($returned_from_server);

            if (json_encode($json_from_server) === $returned_from_server) {
                return $json_from_server;
            }
        }

        return FALSE;
    }

    /**
     * @param string $domain
     * @return bool
     */
    protected function curl_connect($domain = "")
    {
        /**
         * If $domain is set, we're dropping the old connection,
         * and making a new one (because we're changing URLs).
         */
        if ($domain != "") {
            if (is_resource($this->curl)) {
                curl_close($this->curl);
            }
            $this->curl = $this->curl_init($domain);
        } else {
            /**
             * This is the default route.
             */
            if (is_resource($this->curl)) {
                return TRUE;
            } elseif (isset($this->domain) && $this->domain != "") {
                $this->curl = $this->curl_init($this->domain);
            }
        }
        if ($this->curl) {
            return $this->curl;
        }

        return FALSE;
    }

    private static function curl_init($domain)
    {
        $curl = curl_init($domain);
        if (is_resource($curl)) {
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            return $curl;
        }

        return FALSE;
    }
}


class reporter_frontend extends reporter
{

    public $domain;

    public function __construct($domain)
    {
        $this->domain = $domain;
        $this->curl_connect();
    }

    public function query(array $type)
    {
        $response = [];
        $url_variables = [];


        foreach ($type as $this_type) {
            $fixes['pre'][$this_type] = "";
            $fixes['post'][$this_type] = "%";

            switch ($this_type) {
                case "analytics": {
                    $c = curl_init($this->domain);
                    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                    $html_from_server = curl_exec($c);
                    curl_close($c);
                    $outcome = $this->_is_analytics($html_from_server);
                    $response[$this_type] = $outcome ? "Analytics code found: " . $outcome : "No Analytics found.";
                    break;
                }

                case "psi": {
                    $psi_url_string = "https://www.googleapis.com/pagespeedonline/v2/runPagespeed?url={$this->domain}/&strategy=mobile&screenshot=true&key=" . GOOGLE_PSI_API_KEY;
                    $this->curl_connect($psi_url_string); // New connection.
                    $psi_object = $this->curl_fetch();

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
                    $response[$this_type] = $this->_query($this_type, $fixes);
                }
            }
        }

        return [$this->domain => $response];
    }

    protected function _query($this_type, $fixes)
    {

        $json_from_server = $this->curl_fetch();

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

    function process(array $data, $time_taken)
    {
        foreach ($data as $domain_url => $report) {
            print "<div class='reports'>";
            print "<h2>$domain_url</h2>";
            $this->_process_report($report);
            print "<div class='time-taken'>Time taken: <b>{$time_taken}</b>s</div>";
            print "</div>";
        }
        return;
    }

    private function _is_analytics($str)
    {
        preg_match('/GTM-[\w\d]{6,9}/im', strval($str), $matches);
        return $matches ? count($matches) . " match: " . $matches[0] : FALSE;
    }

    private function _process_psi ($report) {
        if(is_object($report) && isset($report->ruleGroups)) {
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

    private function _process_report($report) {
        foreach($report as $type_of_report => $reporter) {
            print "<div class='report $type_of_report'>";
            switch($type_of_report) {
                case "psi":
                    //type should be object
                    print "<div class='reporter $type_of_report'><h3>$type_of_report: </h3>";
                    $this->_process_psi($reporter);
                    print "</div>";
                    break;

                case "analytics":
                    //Just a string
                    print "<div class='reporter $type_of_report'><h3>$type_of_report: </h3>$reporter</div>";
                    break;

                default:
                    //Assumed to be a reporter object.
                    $this->_process_reporter($type_of_report, $reporter);
            }
            print "</div>";
        }
    }

    private function _process_reporter($type_of_report, $reporter) {
        $value_or_error = "e";

        if(!is_object($reporter)) {
            print "<b>$type_of_report:</b><br> No remote reporter.php file found.";
            return false;
        }
        print "<div class='reporter $type_of_report'><h3>$type_of_report: </h3>";
        print "<div class='update-status'><em>" .nl2br(trim($reporter->update)). "</em> @ " . $reporter->info->version ."</div>";

        if(isset($reporter->info->response->v)) {
            $value_or_error = "v";
        }

        print "<div class='value $value_or_error'>";
        $the_value = $reporter->info->response->$value_or_error;

        if(is_array($the_value)){
            //Drush
            $this->make_list($the_value);
        } else {
            //Server metrics
            print $the_value;
            if(is_numeric($the_value)) {
                print "%";
            }
        }

        print "</div></div>";
        return TRUE;
    }

}