<?php

/**
 * Created by PhpStorm.
 * User: allie
 * Date: 17/09/13
 * Time: 2:20 PM
 */
class db extends mysqli
{
    public function __construct()
    {
        $this->connect("127.0.0.1", "root", "ekiswit", "domainwhere");
    }
}

class Ga_track
{
    function get_ga_implemented($url)
    {
        $options = array(
            CURLOPT_RETURNTRANSFER => TRUE, // return web page
            CURLOPT_HEADER => TRUE, // Return headers
            CURLOPT_ENCODING => "", // handle all encodings
            CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 6.1; WOW64)", // who am i
            CURLOPT_SSL_VERIFYHOST => FALSE, //ssl verify host
            CURLOPT_SSL_VERIFYPEER => FALSE, //ssl verify peer
            CURLOPT_NOBODY => FALSE,
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);

        //2> Grab content of the url using CURL
        $content = curl_exec($ch);

        $flag1_trackpage = false; //FLag for the phrase '_trackPageview'
        $flag2_ga_js = false; //FLag for the phrase 'ga.js'

        // Script Regex
        $script_regex = "/<script\b[^>]*>([\s\S]*?)<\/script>/i";

        // UA_ID Regex
        $ua_regex = "/UA-[0-9]{5,}-[0-9]{1,}/";

        // Preg Match for Script
        //3> Extract all the script tags of the content
        preg_match_all($script_regex, $content, $inside_script);

        //4> Check for ga.gs and _trackPageview in all <script> tag
        for ($i = 0; $i < count($inside_script[0]); $i++) {
            if (stristr($inside_script[0][$i], "ga.js"))
                $flag2_ga_js = TRUE;
            if (stristr($inside_script[0][$i], "_trackPageview"))
                $flag1_trackpage = TRUE;
        }

        // Preg Match for UA ID
        //5> Extract UA-ID using regular expression
        preg_match_all($ua_regex, $content, $ua_id);

        //6> Check whether all 3 word phrases are present or not.
        if ($flag2_ga_js && $flag1_trackpage && count($ua_id > 0))
            return ($ua_id);
        else
            return (NULL);
    }
}

$ga_obj = new Ga_track();

//returns true, if domain is availible, false if not
function isDomainAvailible($domain)
{
    $domain = "http://" . $domain;
    //check, if a valid url is provided
    if (!filter_var($domain, FILTER_VALIDATE_URL)) {
        return false;
    }

    //initialize curl
    $curlInit = curl_init($domain);
    curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curlInit, CURLOPT_HEADER, true);
    curl_setopt($curlInit, CURLOPT_NOBODY, true);
    curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);

    //get answer
    $response = curl_exec($curlInit);
    curl_close($curlInit);

    $header_lines = explode(PHP_EOL, $response);
    $headers = [];
    foreach ($header_lines as $header_line) {
        $header_line = trim($header_line);
        $ex = explode(":", $header_line);

        if($ex[1] == null) {
            $headers["Response"] = $ex[0];
        } else {
            $headers[$ex[0]] = $ex[1];
        }
    }

    $response_out = [];

    if($headers['X-Generator'] != "") {
        $response_out[] = $headers['X-Generator'];
    }

    if($headers['X-Powered-By'] != "") {
        $response_out[] = "<br>".$headers['X-Powered-By'];
    }

    if(empty($response_out)) {
        $response_out[] = "Unknown";
    }

    return implode("<br>",$response_out);
}

$db = new db();
$all = $db->query("SELECT * FROM `domains` LIMIT 20");
while($row = $all->fetch_assoc()) {
    $rows[] = $row;
}