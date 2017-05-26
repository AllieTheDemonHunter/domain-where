<?php

namespace reporter;
include_once "reporter/reporter_remote.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);

try {
    $response['update'] = `git checkout master && git pull`;
} catch (Exception $e) {
    print "Shell access is not allowed on the operating system.";
}

$reporter = new _reporterRemote($_SERVER['SERVER_NAME']);
$response['remote'] = $reporter;

print json_encode($response);