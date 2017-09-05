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
if($difference_in_seconds > $limit_in_seconds) {
    print `git pull`;
    die("updating");
}
print json_encode(new _reporterRemote($_SERVER['SERVER_NAME']));