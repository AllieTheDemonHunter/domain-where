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
    //@TODO This function has mixed functionality that has to get encapsulated.
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
            if (!$reporter) {
                //print "problems with report: " . $type_of_report;
                continue;
            }


            switch ($type_of_report) {
                case "psi":
                    //type should be object
                    $this->_process_psi($reporter);
                    break;

                case "analytics":
                    //Just a string
                    print "<div class='report $type_of_report'><h3>$type_of_report: </h3>$reporter</div>";
                    break;

                default:
                    print "<div class='report $type_of_report'>";
                    //Assumed to be a reporter object.
                    $this->_process_reporter($type_of_report, $reporter);
                    print "</div>";
            }
        }
    }

    /**
     * Checks, validates and builds markup for Google Page Speed Insights.
     * @param $report
     */
    private function _process_psi($report)
    {
        if (is_object($report)) {
            $out = "<div class='reporter psi'><h3>psi: </h3>";
            if(isset($report->ruleGroups)) {
                $out .= "<dl>";
                foreach ($report->ruleGroups as $rule_heading => $value_object) {
                    $score = trim($value_object->score);
                    if ($score < 50) {
                        $class = "e";
                    } else {
                        $class = "v";
                    }
                    $out .= "<dt class='$class'>" . trim($rule_heading) . "</dt><dd class='$class'>" . $score . "%</dd>";
                }
                $out .= "<dl>";
            } elseif ($report->error->errors[0]) {
                $this->make_list($report->error->errors[0]);
            }

            if (isset($report->screenshot)) {
                $google_data = $report->screenshot->data;
                $base64_data = str_replace("-", "+", str_replace("_", "/", $google_data)); // This is a Google thing.
                $out .= '<div><img src="data:' . $report->screenshot->mime_type . ';charset=utf-8;base64, ' . $base64_data . '"></div>';
            }

            $out .= "</div>";

            print "<div class='report psi'>$out</div>";
        }
    }

    /**
     * @param $type_of_report
     * @param $reporter
     *
     * @return bool
     * @throws \Exception
     */
    private function _process_reporter($type_of_report, $reporter)
    {
        if(is_string($reporter)) {
            print $reporter;
            return;
        }

        //Error flag default set to error.
        $value_or_error = "e";

        try {
            $object_error_message = sprintf("<b>%s</b><br> Reporter object error. Check remote reporter.php file.", $type_of_report);

            print "<div class='reporter $type_of_report'><h3>$type_of_report: </h3>";

            switch ($type_of_report) {

                case "drush":
                    //Drush

                    foreach ($reporter->response->$type_of_report as $drush_command => $value_or_error) {
                        if($result = $value_or_error->v) {
                            $this->make_list($result, 1);
                        } elseif ($result = $value_or_error->e) {
                            $this->make_list([[$drush_command, $value_or_error->e]], 0);
                        } else {
                            //problems
                        }
                    }

                    break;
                default :
                    //Error flag update, set to 'value'.
                    if (isset($reporter->response->$type_of_report->v)) {
                        $value_or_error = "v";
                    }

                    print "<div class='value $value_or_error'>";
                    $the_value = $reporter->response->$type_of_report->$value_or_error;
                    //Server metrics
                    print $the_value;
                    if (is_numeric($the_value)) {
                        print "%";
                    }
                    print "</div>";
                    break;
            }

            print "</div>";

        } catch (\Exception $exception) {
            throw new \Exception($object_error_message, E_ERROR);
        }

        return TRUE;
    }
}