<?php
namespace reporter;

include_once "reporter/convenience.php";
include_once "reporter/reporter.php";
include_once "reporter/reporterRemote.php";

$reporter = new _reporterRemote($_SERVER['SERVER_NAME']);
$response['remote'] = $reporter;
print json_encode($response);