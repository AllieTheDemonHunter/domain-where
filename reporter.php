<?php
namespace reporter;

include_once "reporter/convenience.php";
include_once "reporter/reporter.php";
include_once "reporter/reporterRemote.php";
$limit_in_minutes = 2;
$limit_in_seconds = $limit_in_minutes * 60;
$then = `git log -1 --pretty=format:%ct`;
$now = time();
$difference_in_seconds = abs($then - $now);
if($difference_in_seconds < $limit_in_seconds && file_exists("tmp.json")) {
    print `git pull`;
    die(file_get_contents("tmp.json"));
}
$result = json_encode(new _reporterRemote($_SERVER['SERVER_NAME']));
file_put_contents("tmp.json", $result);