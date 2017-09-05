<?php
namespace reporter;

include_once "reporter/convenience.php";
include_once "reporter/reporter.php";
include_once "reporter/reporterRemote.php";
$limit_in_minutes = 2;
print $then = `git log -1 --pretty=format:%ct`;
print "---";
$now = time();
print $difference_in_seconds = "difference_in_seconds:".($then - $now);
print json_encode(new _reporterRemote($_SERVER['SERVER_NAME']));