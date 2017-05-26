<?php

namespace reporter;
include_once "convenience.php";

define("SITE_REPORTER", "domain-where/reporter.php");
define("GOOGLE_PSI_API_KEY", "AIzaSyANxegBGZ1GmVGTSyW8wRPgVh7MLrNQKJA");

/**
 * Class reporter
 * @package reporter
 */
class reporter
{
    use convenience;
    public $response = [];
    public $domain = "";
    public $version = "v0.7";
    protected $curl = NULL;

    /**
     * reporter constructor.
     * @param $domain
     */
    public function __construct($domain)
    {
        $this->domain = $domain;
    }

    public function __destruct()
    {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
        }
    }


    public function request_psi()
    {
        $psi_url_string = "https://www.googleapis.com/pagespeedonline/v2/runPagespeed?url={$this->domain}/&strategy=mobile&screenshot=true&key=" . GOOGLE_PSI_API_KEY;
        $this->curl_connect($psi_url_string); // New connection.
        $psi_object = $this->curl_fetch();

        if (isset($psi_object->responseCode) && $psi_object->responseCode == "200") {
            unset($psi_object->formattedResults);
            $this->response['psi'] = $psi_object;
        }
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

    protected function curl_fetch()
    {
        if (!is_resource($this->curl)) {
            $this->curl_connect();
        }
        $returned_from_server = curl_exec($this->curl);

        if ($returned_from_server != "") {
            $json_from_server = json_decode($returned_from_server);

            // TODO: This is probably going to break.
            return $json_from_server;
        }

        if (is_string($returned_from_server)) {
            return $returned_from_server;
        }

        return FALSE;
    }

    public function request_analytics()
    {
        $html_from_server = $this->curl_fetch(); //No variables specified should return the site's html.
        $outcome = $this->_is_analytics($html_from_server);
        $this->response['analytics'] = $outcome ? "Analytics code found: " . $outcome : "No Analytics found.";
    }

    public static function _is_analytics($str)
    {
        preg_match('/GTM-[\w\d]{6,9}/im', strval($str), $matches);
        return $matches ? count($matches) . " match: " . $matches[0] : FALSE;
    }

    function __toString()
    {
        // TODO: Implement __toString() method.
        return "This class doesn't output strings.";
    }
}