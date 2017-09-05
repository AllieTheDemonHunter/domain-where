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
$updating = FALSE;
$execute_report = FALSE;
if ($difference_in_seconds > $limit_in_seconds) {
    print `git pull`;
    $updating = TRUE;
}
if (file_exists("tmp.json") && $updating) {
    print "Updating with a cached result.";
    print file_get_contents("tmp.json");
} elseif ($updating) {
    print "Updating with no cached result. Creating a result.";
    //Last option is to return live results.
    $result = json_encode(new _reporterRemote($_SERVER['SERVER_NAME']));
    file_put_contents("tmp.json", $result);
    print $result;
} elseif (file_exists("tmp.json")) {
    print "Not updating, and has 'new enough' version cached.";
    print file_get_contents("tmp.json");
}