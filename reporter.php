<?php
namespace reporter;

include_once "reporter/convenience.php";
include_once "reporter/reporter.php";
include_once "reporter/reporterRemote.php";

/**
 * The IDEA here, is to prioritize one of three outcomes in context of this remote file:
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
 * Configuration
 */
$now = time();
$request_tmp_name = "tmp_" . $_GET['t'] . ".json";
$cache_file_expiry_in_minutes = 15;
$update_expiry_in_minutes = 15;

/**
 * Cache - refresh intervals.
 */
$expiry_cache_in_seconds = $cache_file_expiry_in_minutes * 60;
$modification_time_cache = @filemtime($request_tmp_name);
$cache_difference = $now - $modification_time_cache;

if ($cache_difference < $expiry_cache_in_seconds) {
    $cache_use = 1;
} else {
    $cache_use = 0;
}
$debug[] = "CACHE({$cache_use}):" . $cache_difference . " < " . $expiry_cache_in_seconds;

if ($cache_use) {
    $debug[] = "Not updating, and has 'new enough' version cached.";

    header('Expires: '. gmdate('D, d M Y H:i:s' . ' GMT+2', strtotime("+".$cache_difference." seconds")));

    print file_get_contents($request_tmp_name);
} else {
    /**
     * NO CACHED FILE AVAILABLE
     *
     * Create Result
     */
    $debug[] = "Updating with no cached result. Creating a result.";
    //Last option is to return live results.
    make_result:
    $result_raw = new _reporterRemote($_SERVER['SERVER_NAME']);
    $result = json_encode($result_raw);

    //Cache this version
    umask();
    file_put_contents($request_tmp_name, $result);

    header('Expires: '. gmdate('D, d M Y H:i:s' . ' GMT+2', strtotime("+".$expiry_cache_in_seconds." seconds")));

    print $result;
}

// Silent

$expiry_update_in_seconds = $update_expiry_in_minutes * 60;
$then = `git log -1 --pretty=format:%ct`;
$update_difference_in_seconds = $now - $then;

if ($update_difference_in_seconds > $expiry_update_in_seconds) {
    `git pull --quiet`;
}

//$reasons = print_r($debug,1);
//print "<pre>{$reasons}</pre>";