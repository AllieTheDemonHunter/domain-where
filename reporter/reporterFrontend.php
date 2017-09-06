<?php

namespace reporter;

include_once "convenience.php";
spl_autoload_register();

/**
 * _reporterFrontend is the client side controller which processes data returned remotely.
 *
 * @package reporter
 */
class _reporterFrontend extends reporter
{

    /**
     * reporterFrontend starts off by launching its parent and then connecting.
     *
     * @param $domain
     */
    public function __construct($domain)
    {
        parent::__construct($domain);
        $this->curl_connect();
    }

    /**
     * Run all the registered requests.
     *
     * @param array $type
     *
     * @return $this
     */
    public function query(array $type)
    {
        $url_variables = [];

        foreach ($type as $this_type) {

            switch ($this_type) {
                case "analytics": {
                    $this->request_analytics();
                    break;
                }

                case "psi": {
                    $this->request_psi();
                    break;
                }

                default: {
                $url_variables['t'] = $this_type;
                $this->response[$this_type] = $this->_query($this_type);
                }
            }
        }

        /**
         * This will cause recursion.
         */
        return $this;
    }

    /**
     * Called by $this->query()
     * @param $this_type
     *
     * @return bool|string
     */
    private function _query($this_type)
    {
        curl_setopt($this->curl, CURLOPT_URL, $this->domain . "/domain-where/reporter.php?t=" . $this_type);
        $json_from_server = $this->curl_fetch();

        if (isset($json_from_server->remote->response->$this_type->v) && is_object($json_from_server)) {
            $response = $json_from_server->remote->response->$this_type->v;
        } else {
            $response = $json_from_server;
        }

        return $response;
    }

    /**
     * Starts output and runs the reporting action.
     * @param $start_time
     */
    function process($start_time)
    {
        print "<div class='reports'>";
        print "<h2>$this->domain</h2>";
        $this->_process_reports($this->response);
        $end_time = time() + microtime();
        $time_taken = ($end_time - $start_time);
        print "<div class='time-taken'>Time taken: <b>{$time_taken}</b>s";
        print "<div class='update-status'>" . $this->version . "</div>";
        print "</div></div>";

    }

    /**
     * Iteratively run private report functions.
     *
     * @param $reports
     */
    private function _process_reports($reports)
    {
        foreach ($reports as $type_of_report => $reporter) {
            switch ($type_of_report) {
                case "psi":
                    //type should be object
                    $this->_process_psi($reporter);
                    break;

                case "analytics":
                    $this->_process_analytics($reporter);
                    break;

                case "loadaverage":
                    $this->_process_loadaverage($reporter);
                    break;

                case "drush":
                    $this->_process_drush($reporter);
                    break;

                default:
                    $this->_process_default($reporter, $type_of_report);
                    break;
            }
        }
    }

    /**
     * @param $reporter
     */
    private function _process_analytics($reporter)
    {
        //Just a string
        if ($reporter == "No Analytics found.") {
            $analytics_status = "red";
        } else {
            $analytics_status = "green";
        }
        // oop way (reccomended)
        $n = new \Nicer($reporter->debug[0]); //Nicer($reporter->debug[0]);
        print "<div class='report analytics $analytics_status'><h3>analytics: </h3>";
        print "<div class='reporter'>$reporter</div>";
        print "<div class='debug print-r'>".$n->render($reporter->debug[0], 1)."</div>";
        print "</div>";
    }

    /**
     * @param $reporter
     */
    private function _process_loadaverage($reporter)
    {
        //Error flag update, set to 'value'.
        if (isset($reporter->response->loadaverage->v)) {
            $value_or_error = "v";
            $loadaverage_status = "green";
        } else {
            $loadaverage_status = "red";
        }
        $out = "<div class='value $value_or_error'>";
        $the_value = $reporter->response->loadaverage->$value_or_error;
        //Server metrics
        $out .= $the_value;
        if (is_numeric($the_value)) {
            $out .= "%";
        }
        $out .= "</div>";

        print "<div class='report loadaverage $loadaverage_status'><h3>loadaverage: </h3>";
        print "<div class='reporter'>$out</div>";
        print "<div class='debug print-r'>".print_r($reporter->debug[0], 1)."</div>";
        print "</div>";
    }

    /**
     * Checks, validates and builds markup for Google Page Speed Insights.
     * @param $report
     */
    private function _process_psi($report)
    {
        if (is_object($report)) {
            $status = "green";
            $out = "<div class='reporter psi'><h3>psi: </h3>";
            if (isset($report->ruleGroups)) {
                $out .= "<dl>";
                foreach ($report->ruleGroups as $rule_heading => $value_object) {
                    $score = trim($value_object->score);
                    if ($score < 50) {
                        $class = "e";
                        $status = "red";
                    } else {
                        $class = "v";
                    }
                    $out .= "<dt class='$class'>" . trim($rule_heading) . "</dt><dd class='$class'>" . $score . "%</dd>";
                }
                $out .= "<dl>";
            } elseif ($report->error->errors[0]) {
                $out .= $this->make_list($report->error->errors[0]);
            }

            if (isset($report->screenshot)) {
                $google_data = $report->screenshot->data;
                $base64_data = str_replace("-", "+", str_replace("_", "/", $google_data)); // This is a Google thing.
                $out .= '<div><img src="data:' . $report->screenshot->mime_type . ';charset=utf-8;base64, ' . $base64_data . '"></div>';
            }

            $out .= "</div>";

            print "<div class='report psi $status'>$out</div>";
        }
    }

    /**
     * @param $reporter
     */
    private function _process_drush($reporter)
    {
        $out = "";
        foreach ($reporter->response->drush as $drush_command => $value_or_error) {
            $out .= "<code>$drush_command</code>";
            if ($result = $value_or_error->v) {
                $out .= $this->make_list($result, 1);
            } elseif ($result = $value_or_error->e) {
                $out .= $this->make_list([[$drush_command, $value_or_error->e]], 0);
            } else {
                $out .= "Problems";
            }
        }

        print "<div class='report drush unknown-status'><h3>drush: </h3>";
        print "<div class='reporter'>$out</div>";
        print "<div class='debug print-r'>".print_r($reporter->debug[0], 1)."</div>";
        print "</div>";
    }

    /**
     * @param $reporter
     * @param $type_of_report
     */
    private function _process_default($reporter, $type_of_report)
    {
        //Error flag update, set to 'value'.
        if (isset($reporter->response->$type_of_report->v)) {
            $value_or_error = "v";
        }

        $out = "<div class='value $value_or_error'>";
        $the_value = $reporter->response->$type_of_report->$value_or_error;
        //Server metrics
        $out .= $the_value;
        if (is_numeric($the_value)) {
            $out .= "%";
        }
        $out .= "</div>";

        print "<div class='report $type_of_report unknown-status'><h3>$type_of_report: </h3>";
        print "<div class='reporter'>$out</div>";
        print "<div class='debug print-r'>".print_r($reporter->debug[0], 1)."</div>";
        print "</div>";
    }
}