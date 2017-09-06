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

    protected $curl;

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
    public static function make_list($data)
    {
        if (is_object($data)) {
            foreach ($data as $key => $value) {
                $array_data[] = [$key, $value];
            }
        } else {
            $array_data = $data;
        }

        $out = "<dl>";
        foreach ($array_data as $result) {
            $definition = $result[1];
            if (is_array($definition)) {
                $out .= "<dt>" . trim($result[0]) . "</dt><dd>" . self::make_list($definition) . "</dd>";
            } else {
                $out .= "<dt>" . trim($result[0]) . "</dt><dd>" . trim($definition) . "</dd>";
            }
        }
        $out .= "</dl>";

        return $out;
    }

    /**
     * @param $domain
     *
     * @return bool|resource
     */
    public function curl_init($domain)
    {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
        }

        $curl = curl_init($domain);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]
        );

        $this->curl = $curl;
    }

    public static function getInputFromRequestBody()
    {
        $body = file_get_contents('php://input');
        return json_decode($body, TRUE);
    }

    public static function make_machine_name($human_readable)
    {
        $machine_readable = strtolower($human_readable);
        $machine_readable = preg_replace('@[^a-z0-9_]+@', '_', $machine_readable);
        return $machine_readable;
    }
}