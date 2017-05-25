<?php
/**
 * Created by PhpStorm.
 * User: allie
 * Date: 2017/05/25
 * Time: 1:12 PM
 */

namespace reporter;

trait convenience
{
    /**
     * @param (mixed) $d
     */
    public static function r_print($d)
    {
        print "<pre>" . print_r($d, 1) . "<pre>";
    }

    /**
     * @param array $data
     */
    public static function make_list(array $data)
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

    /**
     * @param $domain
     * @return bool|resource
     */
    public static function curl_init($domain)
    {
        $curl = curl_init($domain);
        if (is_resource($curl)) {
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            return $curl;
        }

        return FALSE;
    }

    public static function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    public static function getInputFromRequestBody()
    {
        $body = file_get_contents('php://input');
        return json_decode($body, TRUE);
    }
}