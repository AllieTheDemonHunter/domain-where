<?php

namespace reporter;

include_once "convenience.php";

/**
 * Constants
 */
define("SITE_REPORTER", "domain-where/reporter.php");
define("GOOGLE_PSI_API_KEY", "AIzaSyANxegBGZ1GmVGTSyW8wRPgVh7MLrNQKJA");

/**
 * Manages reporting and remote connections.
 *
 * @package reporter
 */
class reporter {

  use convenience;

  public $response = [];

  public $domain = "";

  public $version = "v0.8";

  protected $curl = NULL;

  /**
   * Set domain to be queried.
   *
   * @param $domain
   */
  public function __construct($domain) {
    $this->domain = $domain;
    $this->version = $this->version . " @ " . date("F j, Y, g:i a");
  }

  /**
   * Closes the Curl connection.
   */
  public function __destruct() {
    if (is_resource($this->curl)) {
      curl_close($this->curl);
    }
  }

  /**
   * Query Google's Page Speed Insights (resets connection to new URL)
   */
  final public function request_psi() {
    $psi_url_string = "https://www.googleapis.com/pagespeedonline/v2/runPagespeed?url={$this->domain}/&strategy=mobile&screenshot=true&key=" . GOOGLE_PSI_API_KEY;
    $this->curl_connect($psi_url_string); // New connection.
    $psi_object = $this->curl_fetch();

    try {
      unset($psi_object->formattedResults);
      $this->response['psi'] = $psi_object;
    }
    catch (\Exception $exception){
      throw new \Exception("The remote server returned an error page.", E_RECOVERABLE_ERROR);
    }
  }

  /**
   * Returns or resets current curl connection.
   *
   * @param string $reset_domain_to
   *
   * @return resource | bool
   */
  final protected function curl_connect($reset_domain_to = "") {
    /**
     * If $domain is set, we're dropping the old connection,
     * and making a new one (because we're changing URLs).
     */
    if ($reset_domain_to != "") {
      if (is_resource($this->curl)) {
        curl_close($this->curl);
      }
      $this->curl_init($reset_domain_to);
    }
    else {
      /**
       * This is the default route.
       */
      if (is_resource($this->curl)) {
        return $this->curl;
      }
      elseif (isset($this->domain) && $this->domain != "") {
        $this->curl_init($this->domain);
      }
    }

    if ($this->curl) {
      return $this->curl;
    }

    return FALSE;
  }

  /**
   * Execute server transaction.
   *
   * @return bool|mixed
   */
  final protected function curl_fetch() {
    if (!is_resource($this->curl)) {
      $this->curl_connect();
    }
    $returned_from_server = curl_exec($this->curl);

    if ($returned_from_server != "") {
      $json_from_server = json_decode($returned_from_server);

      return $json_from_server;
    }

    if (is_string($returned_from_server)) {
      return $returned_from_server;
    }

    return FALSE;
  }

  /**
   * Nice to have magic method.
   *
   * @return string
   * @throws \Exception
   */
  function __toString() {
    // TODO: Implement __toString() method.
    throw new \Exception("This class doesn't output strings.", E_USER_ERROR);
  }

  /**
   * Looks through code and matches text patterns to check for Analytics etc.
   */
  //@TODO This should be generalised to "parse_and_match, run_text_scans", or the like. So it could be used to match any/all requested "scans".
  protected function request_analytics() {
    $html_from_server = $this->curl_fetch(); //No variables specified should return the site's html.
    $outcome = $this->is_analytics($html_from_server);
    $this->response['analytics'] = $outcome ? "Analytics code found: " . $outcome : "No Analytics found.";
  }

  /**
   * Finds and returns GTM- codes from text (markup usually).
   *
   * @param $str
   *
   * @return bool|string
   */
  public static function is_analytics(string $str) {
    preg_match('/GTM-[\w\d]{6,9}/im', strval($str), $matches);
    return $matches ? count($matches) . " match: " . $matches[0] : FALSE;
  }
}