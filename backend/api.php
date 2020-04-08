<?php
date_default_timezone_set('UTC');
$root_path = $_SERVER["DOCUMENT_ROOT"];
$script_path = preg_replace("/.modules.addons..+$/", "", dirname($_SERVER["SCRIPT_NAME"]));
if (!empty($script_path)) {
    $root_path .= $script_path;
}
$init_path = implode(DIRECTORY_SEPARATOR, array($root_path,"init.php"));
if (isset($GLOBALS["customadminpath"])) {
    $init_path = preg_replace("/(\/|\\\)" . $GLOBALS["customadminpath"] . "(\/|\\\)init.php$/", DIRECTORY_SEPARATOR . "init.php", $init_path);
}
if (file_exists($init_path)) {
    require_once($init_path);
} else {
    exit("cannot find init.php");
}

require_once dirname(__FILE__)."/helper.php"; //HELPER WHICH CONTAINS HELPER FUNCTIONS

use WHMCS\Database\Capsule;
use WHMCS\Module\Registrar\Ispapi\Ispapi;

//############################
//HELPER FUNCTIONS
//############################
function logmessage($cronname, $status, $message, $query = null)
{
    try {
        $pdo = Capsule::connection()->getPdo();
        $insert_stmt = $pdo->prepare("INSERT INTO backorder_logs(cron, date, status, message, query) VALUES(:cron, :date, :status, :message, :query)");
        $insert_stmt->execute(array(':cron' => $cronname, ':date' => date("Y-m-d H:i:s") , ':status' => $status, ':message' => $message, ':query' => $query));
    } catch (\Exception $e) {
        die($e->getMessage());
    }
}

//THIS FUNCTION CALLS OUR HEXONET API AND IS USED FOR CRONS AND IN THE WHMCS ADMIN AREA
function ispapi_api_call($command)
{
    //check registrar module availability and version number
    $higher_version_required_message = "The ISPAPI Backorder Module requires ISPAPI Registrar Module v3.0.0 or higher!";
    $version = Ispapi::getRegistrarModuleVersion('ispapi');
    if ($version == "N/A" || version_compare($version, '3.0.0') < 0) {
        return [
            "CODE" => 549,
            "DESCRIPTION" => $higher_version_required_message
        ];
    }
    //check authentication
    $r = Ispapi::call([
        "COMMAND" => "CheckAuthentication"
    ]);
    if ($r["CODE"] != "200") {
        return [
            "CODE" => 549,
            "DESCRIPTION" => "The ISPAPI registrar authentication failed! Please verify your registrar credentials and try again."
        ];
    }

    return Ispapi::call($command);
}

//THIS FUNCTION CALLS OUR LOCAL API AND IS USED FOR CUSTOMER AND ADMIN
function backorder_api_call($command)
{
    $time = microtime(true);
    $ca = new WHMCS_ClientArea();

    $userid = $ca->getUserID();

    //CHECK IF ADMIN LOGGED IN AND AUTHORIZE PASSING USERID TO COMMAND
    if (isset($_SESSION['adminid']) && $_SESSION['adminid'] > 0 && isset($command["USERID"])) {
        $userid = $command["USERID"];
    }

    $dir = opendir(dirname(__FILE__)."/../api");
    $files = array();
    while (($file = readdir($dir)) !== false) {
        if (preg_match('/^command\.(.*)\.php$/', $file, $m)) {
            $files[strtoupper($m[1])] = $file;
        }
    }
    $c = strtoupper($command["COMMAND"]);
    if (isset($files[$c])) {
        $response = include dirname(__FILE__)."/../api/".$files[$c];
    }

    if (empty($response)) {
        $response["CODE"] = 500;
        $response["DESCRIPTION"] = "Command invalid";
    } else {
        if (!isset($response["QUEUETIME"])) {
            $response["QUEUETIME"] = "0.000";
        }
        if (!isset($response["RUNTIME"])) {
            $response["RUNTIME"] = sprintf("%0.3f", microtime(true) - $time);
        }
    }

    return $response;
}

//THIS FUNCTION CALLS OUR LOCAL API AND IS USED FOR CRONS AND SOME OTHER COMMANDS
//$userid WILL TAKE THE VALUE OF $command["USER"]
//THIS COMMAND IS NOT AVAILABLE FROM OUTSIDE
function backorder_backend_api_call($command)
{
    $time = microtime(true);
    $ca = new WHMCS_ClientArea();

    $userid = $command["USER"];
    $dir = opendir(dirname(__FILE__)."/../api");
    $files = array();
    while (($file = readdir($dir)) !== false) {
        if (preg_match('/^command\.(.*)\.php$/', $file, $m)) {
            $files[strtoupper($m[1])] = $file;
        }
    }
    $c = strtoupper($command["COMMAND"]);
    if (isset($files[$c])) {
        $response = include dirname(__FILE__)."/../api/".$files[$c];
    }

    if (empty($response)) {
        $response["CODE"] = 500;
        $response["DESCRIPTION"] = "Command invalid";
    } else {
        if (!isset($response["QUEUETIME"])) {
            $response["QUEUETIME"] = "0.000";
        }
        if (!isset($response["RUNTIME"])) {
            $response["RUNTIME"] = sprintf("%0.3f", microtime(true) - $time);
        }
    }
    return $response;
}

//THIS FUNCTION IS USED FOR LISTINGS
function backorder_api_query_list($command, $config = "")
{
    $response = backorder_api_call($command, $config);

    $list = array(
            "CODE" => $response["CODE"],
            "DESCRIPTION" => $response["DESCRIPTION"],
            "RUNTIME" => $response["RUNTIME"],
            "QUEUETIME" => $response["QUEUETIME"],
            "ITEMS" => array()
    );
    foreach ($response["PROPERTY"] as $property => $values) {
        if (preg_match('/^(FIRST|LAST|COUNT|LIMIT|TOTAL|ITEMS|COLUMN)$/', $property)) {
            $list[$property] = $response["PROPERTY"][$property][0];
        } else {
            foreach ($values as $index => $value) {
                $list["ITEMS"][$index][$property] = $value;
            }
        }
    }

    if (isset($command["FIRST"]) && !isset($list["FIRST"])) {
        $list["FIRST"] = $command["FIRST"];
    }

    if (isset($command["LIMIT"]) && !isset($list["LIMIT"])) {
        $list["LIMIT"] = $command["LIMIT"];
    }

    if (isset($list["FIRST"]) && isset($list["LIMIT"])) {
        $list["PAGE"] = floor($list["FIRST"] / $list["LIMIT"]) + 1;
        if ($list["PAGE"] > 1) {
            $list["PREVPAGE"] = $list["PAGE"] - 1;
            $list["PREVPAGEFIRST"] = ($list["PREVPAGE"]-1) * $list["LIMIT"];
        }
        $list["NEXTPAGE"] = $list["PAGE"] + 1;
        $list["NEXTPAGEFIRST"] = ($list["NEXTPAGE"]-1) * $list["LIMIT"];
    }
    if (isset($list["TOTAL"]) && isset($list["LIMIT"])) {
        $list["PAGES"] = floor(($list["TOTAL"] + $list["LIMIT"] - 1) / $list["LIMIT"]);
        $list["LASTPAGEFIRST"] = ($list["PAGES"] - 1) * $list["LIMIT"];
        if (isset($list["NEXTPAGE"]) && ($list["NEXTPAGE"] > $list["PAGES"])) {
            unset($list["NEXTPAGE"]);
            unset($list["NEXTPAGEFIRST"]);
        }
    }

    if (!isset($list["COUNT"])) {
        $list["COUNT"] = count($list["ITEMS"]);
    }

    if (isset($list["FIRST"]) && !isset($list["LAST"])) {
        $list["LAST"] = $list["FIRST"] + count($list["ITEMS"]) - 1;
    }

    return $list;
}

// CREATE AN API RESPONSE
function backorder_api_response($code, $info = "")
{
    $r = array("CODE" => $code, "DESCRIPTION" => "Error", "PROPERTY" => array());
    $codes = array(
            "200" => "Command completed successfully",
            "504" => "Missing required attribute",
            "505" => "Invalid attribute value syntax",
            "540" => "Attribute value is not unique",
            "541" => "Invalid attribute value",
            "545" => "Entity reference not found",
            "549" => "Command failed",
    );
    if (isset($codes[$code])) {
        $r["DESCRIPTION"] = $codes[$code];
    }
    if (strlen($info)) {
        $r["DESCRIPTION"] .= "; ".$info;
    }
    return $r;
}

// perform idn conversion
function backoder_idnconvert($input)
{
    ispapi_api_call([
        "COMMAND" => "ConvertIDN",
        "DOMAIN0" => $input
    ]);
    if ($r["CODE"] == 200 && !empty($r["PROPERTY"]["ACE"][0])) {
        return $r["PROPERTY"]["ACE"][0];// punycode xn--...
    }
    return $input;
}

//CHECK THE DOMAIN SYNTAX
function backorder_api_check_syntax_domain($domain)
{
    if (strlen($domain) > 223) {
        return false;
    }
    $converted = backoder_idnconvert($domain);
    if (!preg_match('/^([a-z0-9](\-*[a-z0-9])*)(\.([a-z0-9](\-*[a-z0-9]+)*))+$/i', $converted)) {
        return false;
    }
    return true;
}

//CHECK IF TLD IN THE PRICELIST
function backorder_api_check_valid_tld($domain, $userid)
{
    try {
        $pdo = Capsule::connection()->getPdo();
        $currencyid = null;

        $stmt = $pdo->prepare("SELECT currency FROM tblclients WHERE id=?");
        $stmt->execute(array($userid));
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $currencyid = $data["currency"];
        }
        $tlds = "";
        $stmt = $pdo->prepare("SELECT extension FROM backorder_pricing WHERE currency_id=?");
        $stmt->execute(array($currencyid));
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($data as $value) {
            $tlds .= "|.".$value["extension"];
        }
        $tld_list = substr($tlds, 1);
        $converted = backoder_idnconvert($domain);
        if (!preg_match('/^([a-z0-9](\-*[a-z0-9])*)\\'.$tld_list.'$/i', $converted)) {
            return false;
        }
        return true;
    } catch (\Exception $e) {
        die($e->getMessage());
    }
}
