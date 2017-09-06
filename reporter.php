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
$cache_file_expiry_in_minutes = 1;
$update_expiry_in_minutes = 30;

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
$debug["use_cache"] = $cache_use;
$debug["cache_difference"] = $cache_difference;
$debug["cache_file_expiry_in_minutes"] = $cache_file_expiry_in_minutes;

if ($cache_use && 0) {
    $cache_expire_http_header = 'Expires: '. date('D, d M Y H:i:s e', strtotime("+".$cache_difference." seconds"));
    //Debug
    $debug["message"] = "Serving a cached version.";
    $debug["cache_expire_http_header"] = $cache_expire_http_header;
    
    header($cache_expire_http_header);
    print file_get_contents($request_tmp_name);
} else {
    /**
     * NO CACHED FILE AVAILABLE
     *
     * Create Result
     */
    $expire_http_header = 'Expires: '. date('D, d M Y H:i:s e', strtotime("+".$expiry_cache_in_seconds." seconds"));
    //Debug
    $debug["message"] = "No cache file or has expired. Creating a result.";
    $debug["_expire_http_header"] = $expire_http_header;

    //Execute
    $result_raw = new _reporterRemote($_SERVER['SERVER_NAME']);
    //Attach debug
    $result_raw->debug = [$debug];
    //Encode for transport
    $result = json_encode($result_raw);

    //Cache this version
    umask();
    file_put_contents($request_tmp_name, $result);

    header($expire_http_header);
    print $result;
}

// Silent
$expiry_update_in_seconds = $update_expiry_in_minutes * 60;
$then = `git log -1 --pretty=format:%ct`;
$update_difference_in_seconds = $now - $then;

if ($update_difference_in_seconds > $expiry_update_in_seconds) {
    `git pull --quiet`;
}