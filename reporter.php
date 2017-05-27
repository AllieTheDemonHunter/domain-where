<?php
namespace reporter;

include_once "reporter/convenience.php";
include_once "reporter/reporter.php";
include_once "reporter/reporter_remote.php";

try {
  //$response['update'] = `git checkout master && git pull`;
} catch (Exception $e) {
    print "Shell access is not allowed on the operating system.";
}

$reporter = new _reporterRemote($_SERVER['SERVER_NAME']);
$response['remote'] = $reporter;

//header('Content-Type: application/json; charset=utf-8;');
//header("Transfer-Encoding: identity;");
print json_encode($reporter);