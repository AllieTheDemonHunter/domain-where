<?php
namespace reporter;

include_once "reporter/convenience.php";
include_once "reporter/reporter.php";
include_once "reporter/reporterRemote.php";
$now = time();

/**
 * The IDEA here, is to balance three variables:
 * FILE creation date.
 *  0 = DOES NOT EXIST
 *      Whether the code is being updated.
 *          0 = Not updating, create NEW file AND SERVE
 *          1 = Updating, not waiting RETURN/SERVE: "Domain in creation queue."
 *
 *      ---  DOES NOT EXIST == IS OLD ---
 *
 *  0 = IS OLD.
 *      Whether the code is being updated.
 *          0 = Not updating, create NEW file AND SERVE
 *          1 = Updating, not waiting RETURN/SERVE: "Domain in creation queue."
 *
 *
 *  1 = Serve as cache.
 *      Whether the code is being updated.
 *          0 = Not updating, SERVE CACHED
 *          1 = Updating, SERVE CACHED
 */


/**
 * Cache - refresh intervals.
 */
$cache_file_expiry_in_minutes = 2;
$expiry_cache_in_seconds = $cache_file_expiry_in_minutes * 60;
$modification_time_cache = @filemtime("tmp.json");
$modification_time_cache ?: 0;
$cache_difference = $now - $modification_time_cache;

if ($cache_difference < $expiry_cache_in_seconds) {
    $cache_use = TRUE;
} else {
    $cache_use = FALSE;
}
$debug[] =  "CACHE:" . $cache_difference;
/**
 * Update - refresh intervals.
 */
$update_expiry_in_minutes = 2;
$expiry_update_in_seconds = $update_expiry_in_minutes * 60;
$then = `git log -1 --pretty=format:%ct`;
$update_difference_in_seconds = $now - $then;
$updating = FALSE;

if ($update_difference_in_seconds < $expiry_update_in_seconds) {
    $current_git_status = `git pull`;
    if ($current_git_status != "Already up-to-date.") {
        $updating = TRUE;
    }
} else {
    $updating = FALSE;
}
$debug[] =  "UPDATE:" . $update_difference_in_seconds;

if ($cache_use && $updating) {
    $debug[] =  "Updating with a cached result.";
    print file_get_contents("tmp.json");
} elseif ($updating) {
    $debug[] =  "Updating with no cached result. Creating a result.";
    //Last option is to return live results.
    $result = json_encode(new _reporterRemote($_SERVER['SERVER_NAME']));
    file_put_contents("tmp.json", $result);
    print $result;
} elseif ($cache_use) {
    $debug[] =  "Not updating, and has 'new enough' version cached.";
    print file_get_contents("tmp.json");
} else {
    $reasons = print_r($debug,1);
    print "Updating, please wait.<pre>{$reasons}</pre>";
}