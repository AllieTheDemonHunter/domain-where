<?php
//phpinfo();
$version = "1";
$timeout=10000;
if (array_key_exists("dbg", $_GET))
{
 ini_set('display_errors',1);
 ini_set('display_startup_errors',1);
 error_reporting(-1);
}
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

function getInputFromRequetsBody() {
    $body = file_get_contents('php://input');    
    return json_decode($body, TRUE);
}

$response = array("vs" => $version);
try
{
header('Content-Type: application/json; charset=utf-8');
$infoSource = $_SERVER['REQUEST_METHOD'] == "POST" ? getInputFromRequetsBody() : ($_SERVER['REQUEST_METHOD'] == "GET" ? $_GET : array());    
if (!array_key_exists("t", $infoSource))
{
    $infoSource["t"] = "cpu";
}
switch ($infoSource["t"])
{
    case ("version"): break;
	case ("cpu"):
	{
        $topData = `top -b -n 4 -d 01.00 -i`;
        if (is_null($topData)) {
            $response["e"] = "shell_exec is denied on server";
        } else {            
            if (preg_match_all('|([\.\d]*)%?\s*?id|m', $topData, $data)) {
                    $sum = 0;
                    //print_r($data);
                    foreach ($data[1] as $value)
                        $sum += (float)$value;
                    $response["v"] = round(100.0 - $sum/4, 2); 
                }
            else {
                $response["e"] = "top command returned unexpected result";
            }
        }
		break;
	}
	case ("ram"):
	{
        $freeData = `free -o -k`;
        if (is_null($freeData)) {
            $response["e"] = "shell_exec is denied on server";
        } else {
			if (preg_match('/^.*?\n.*?\s(\d+)\s*(\d+)\s*(\d+)\s.*/m', $freeData, $data)
                ) {
                $response["v"] = round(100.0 - ((float)$data[3] / (float)$data[1])*100.0, 2);
                }
            else {
                $response["e"] = "free command returned unexpected result";
            }
        }
		break;
	}
	case ("disk"): //file system
	{
		if (!array_key_exists("l", $infoSource))
		{
			$response["e"] = "file system mounting path was not specified";
		} else 
		{
			$fileSystems = array();
            $dfData = `df`;
            if (array_key_exists("dbg", $_GET)) {
               echo exec('whoami');
               echo "\r\n";
               echo $dfData;
            }
            if (is_null($dfData)) {
                $response["e"] = "shell_exec is denied on server";
            } else {
				$numOfFsFound = preg_match_all('/([0-9][0-9]?0?)%\s+?([^\s]+)/', $dfData, $data);
                if ($numOfFsFound !== FALSE && $numOfFsFound > 0) {
					$fsFound = array_search($infoSource["l"], $data[2]);
					if ($fsFound !== FALSE && $fsFound >= 0) {
						$response["v"] = round((float)$data[1][$fsFound], 2);
					} else {
						$response["e"] = "file system for path '{$infoSource["l"]}' not found";
					}
				} else {
					$response["e"] = "file system for path '{$infoSource["l"]}' not found";
				}
            }
		}
		break;
	}
	case ("port"):
	{
		if (!array_key_exists("a", $infoSource))
		{
			$response["e"] = "address was not specified";
		} else 
		{
			$port = (int)(array_key_exists("p", $infoSource) ? $infoSource["p"] : "80");
			if (($port > 0) && ($port < 65535))
			{
				$start_point = microtime_float();
				$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);                                
				if (!$socket) {
					$response["e"] = socket_strerror(socket_last_error($socket));
				} else {
                    if (!socket_set_nonblock($socket)) {
                        $response["e"] = "could not set up nonblocking mode";
                    } else {
					    while (!@socket_connect($socket, $infoSource["a"], $port))
                        {
                            $err = socket_last_error($socket);
                            if ($err == 115 || $err == 114)
                            {
                                if ((microtime_float() - $start_point)*1000 >= $timeout)
                                {
                                    socket_close($socket);
                                    $response["e"] = "connection timed out";
                                    break;
                                }
                                sleep(1);
                                continue;
                            }
                            else {
                                $response["e"] = socket_strerror($err);
                                break;
                            }                    
                        }
					    if (!array_key_exists("e", $response)) {
						    $end_point = microtime_float();
						    $response["v"] = round(($end_point - $start_point)*1000);
						    socket_close($socket);						
					    }
                    }
				}				
			} else
			{
				$response["e"] = "specified port is incorrect";
			}
		}
		break;			
	}
	case ("mysql"):
	{
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            if (!array_key_exists("cs", $infoSource))
            {
                $response["e"] = "connection string was not specified";
            }
            else
            {
                preg_match_all("/\s*(.*?)\s*=\s*(.*?)\s*;/", $infoSource["cs"], $matches, PREG_PATTERN_ORDER);				
                $connArray = array();
                for ($i = 0; $i < count($matches[1]); $i++)
                    $connArray[$matches[1][$i]] = $matches[2][$i];
                $server = $connArray['Server'];
                $password = $connArray['Pwd'];
                $user = $connArray['Uid'];		
                $version = explode('.', PHP_VERSION);		
                if ((int)$version[0] < 5)
                {			
                    $start_point = microtime_float();
                    $conn = mysql_connect($server . (array_key_exists('Port', $connArray) ? (":" . $connArray['Port']) : ""), $user, $password, true);
                    if (!$conn)
                    {
                        $response["e"] = mysql_error();
                    } else {
                        $end_point = microtime_float();
                        $response["v"] = round(($end_point - $start_point)*1000);
                        mysql_close($conn);				
                    }
                } else 
                {
                    $start_point = microtime_float();
                    $conn = mysqli_connect($server, $user, $password, "", (array_key_exists('Port', $connArray) ? (int)$connArray['Port'] : -1));
                    if (!$conn)
                    {
                        $response["e"] = mysqli_connect_error();
                    } else {
                        $end_point = microtime_float();
                        $response["v"] = round(($end_point - $start_point)*1000);
                        mysqli_close ($conn);				
                    }					
                }
            }
        } else {
            header("HTTP/1.1 404 Not Found");
        }
		break;			
	}
	default:
	{
		header("HTTP/1.1 404 Not Found");
		break;
	}
}
} catch (Exception $e)
{
    print_r($e);
	$response["e"] = $e->getMessage();
}
if (array_key_exists("e", $response) && (strlen($response["e"]) >= 255)) {
    $response["e"] = substr($response["e"], 0, 250) . "...";
}
//print_r($response);
echo json_encode($response);
?>
