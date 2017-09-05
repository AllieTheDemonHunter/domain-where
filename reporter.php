<?php
namespace reporter;

include_once "reporter/convenience.php";
include_once "reporter/reporter.php";
include_once "reporter/reporterRemote.php";
`git pull`;
print json_encode(new _reporterRemote($_SERVER['SERVER_NAME']));