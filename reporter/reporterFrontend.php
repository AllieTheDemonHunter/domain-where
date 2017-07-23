<?php

namespace reporter;

include_once "convenience.php";
spl_autoload_register();

/**
 * _reporterFrontend is the client side controller which processes data returned remotely.
 *
 * @package reporter
 */
class _reporterFrontend extends reporter {

  /**
   * reporterFrontend starts off by launching its parent and then connecting.
   *
   * @param $domain
   */
  public function __construct($domain) {
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
  public function query(array $type) {
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
          $fixes['pre'][$this_type] = "";
          $fixes['post'][$this_type] = "%";

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
          $this->response[$this_type] = $this->_query($this_type, $fixes);
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
   * @param $fixes
   *
   * @return bool|string
   */
  private function _query($this_type, $fixes) {
    curl_setopt($this->curl, CURLOPT_URL, $this->domain . "/domain-where/reporter.php?t=" . $this_type);
    $json_from_server = $this->curl_fetch();

    if (isset($json_from_server->response->v) && is_object($json_from_server)) {
      if (is_object($json_from_server->response->v)) {
        $response = $json_from_server->response->v;
      }
      else {
        $response = $fixes['pre'][$this_type] . $json_from_server->response->v . $fixes['post'][$this_type];
      }
    }
    else {
      $response = $json_from_server;
    }

    return $response;
  }

  /**
   * Starts output and runs the reporting action.
   * @param $start_time
   */
  function process($start_time) {
    print "<div class='reports'>";
    print "<h2>$this->domain</h2>";
    $this->_process_report($this->response);
    $end_time = time() + microtime();
    $time_taken = ($end_time - $start_time);
    print "<div class='time-taken'>Time taken: <b>{$time_taken}</b>s</div>";
    print "</div>";

  }

  /**
   * Iteratively run private report functions.
   *
   * @param $reports
   */
  private function _process_report($reports) {
    foreach ($reports as $type_of_report => $reporter) {
      print "<div class='report $type_of_report'>";
      switch ($type_of_report) {
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

  /**
   * Checks, validates and builds markup for Google Page Speed Insights.
   * @param $report
   */
  private function _process_psi($report) {
    if (is_object($report) && isset($report->ruleGroups)) {
      print "<dl>";
      foreach ($report->ruleGroups as $rule_heading => $value_object) {
        $score = trim($value_object->score);
        if ($score < 50) {
          $class = "e";
        }
        else {
          $class = "v";
        }
        print "<dt class='$class'>" . trim($rule_heading) . "</dt><dd class='$class'>" . $score . "%</dd>";
      }
      print "<dl>";
    }

    if (isset($report->screenshot)) {
      $google_data = $report->screenshot->data;
      $base64_data = str_replace("-", "+", str_replace("_", "/", $google_data)); // This is a Google thing.
      print '<div><img src="data:' . $report->screenshot->mime_type . ';charset=utf-8;base64, ' . $base64_data . '"></div>';
    }
  }

  /**
   * @param $type_of_report
   * @param $reporter
   *
   * @return bool
   * @throws \Exception
   */
  private function _process_reporter($type_of_report, \stdClass $reporter) {
    //Error flag default set to error.
    $value_or_error = "e";

    try {
      $object_error_message = sprintf("<b>%s</b><br> Reporter object error. Check remote reporter.php file.", $type_of_report);

      print "<div class='reporter $type_of_report'><h3>$type_of_report: </h3>";
      print "<div class='update-status'><em>" . nl2br(trim($reporter->update)) . "</em> @ " . $reporter->remote->version . "</div>";

      //Error flag update, set to 'value'.
      if (isset($reporter->remote->response->$type_of_report->v)) {
        $value_or_error = "v";
      }

      print "<div class='value $value_or_error'>";
      $the_value = $reporter->remote->response->$type_of_report->$value_or_error;

      if (is_array($the_value)) {
        //Drush
        $this->make_list($the_value);
      }
      else {
        //Server metrics
        print $the_value;
        if (is_numeric($the_value)) {
          print "%";
        }
      }
    } catch (\Exception $exception) {
      throw new \Exception($object_error_message, E_ERROR);
    }

    print "</div></div>";
    return TRUE;
  }
}