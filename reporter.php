<?php
namespace reporter;

include_once "reporter/convenience.php";
include_once "reporter/reporter.php";
include_once "reporter/reporterRemote.php";
$limit_in_minutes = 2;
$then = `git log -1 --pretty=format:%ct`;
$now = time();
print $difference_in_seconds = "k".$then - $now;
print json_encode(new _reporterRemote($_SERVER['SERVER_NAME']));