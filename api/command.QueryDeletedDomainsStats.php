<?php // $command, $userid

if ( !isset($command["LIMIT"]) ) $command["LIMIT"] = 100;
if ( !isset($command["FIRST"]) ) $command["FIRST"] = 0;

$limit = isset($command["LIMIT"])? $command["FIRST"].",".$command["LIMIT"] : "";
/*
$host = "qbk-db-slave.fs.de.hexonet.net.";
$user = "backorderde";
$pass = "QHvhGXGBSWv6AcjG";

$options = array(
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
);

try {
	$db = new PDO("mysql:dbname=dvm_live;host=$host", $user, $pass, $options);
} catch (PDOException $ex) {
//	return backorder_api_response(549, "DB Connect failed: ".$ex->getMessage());
	return backorder_api_response(549, "DB Connect failed");
}
*/

include(dirname(__FILE__)."/../../../../configuration.php");

$options = array(
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
);

try {
	$db = new PDO("mysql:dbname=".$db_name.";host=".$db_host, $db_username, $db_password, $options);
} catch (PDOException $ex) {
	return backorder_api_response(549, "DB Connect failed");
}

$r = backorder_api_response(200);

$r["PROPERTY"]["TOTAL"] = [0];
$r["PROPERTY"]["TOTAL1DAY"] = [0];
$r["PROPERTY"]["TOTAL7DAY"] = [0];

//GET LIST OF ALL EXTENSIONS AVAILABLE FOR BACKORDER TO ONLY DISPLAY THOSE ONES
$allextensions=array();
$result = full_query("select extension from backorder_pricing GROUP BY extension");
while ($b = mysql_fetch_array($result)) {
    array_push($allextensions, $b["extension"]);
}

$stmt = $db->prepare("
	SELECT count(*) as c, DATE(drop_date) as drop_day,
		(DATE(drop_date) <= DATE(DATE_ADD(NOW(), INTERVAL 1 DAY))) as drop_1day,
		(DATE(drop_date) <= DATE(DATE_ADD(NOW(), INTERVAL 6 DAY))) as drop_7day
	FROM pending_domains
	WHERE zone IN ('".join("','", $allextensions)."')
	GROUP BY drop_day
	ORDER BY drop_day
");

$stmt->execute($conditions_values);

while ( $data = $stmt->fetch() ) {
	$r["PROPERTY"]["TOTAL"][0] += $data["c"];
	if ( $data["drop_1day"] )
		$r["PROPERTY"]["TOTAL1DAY"][0] += $data["c"];
	if ( $data["drop_7day"] )
		$r["PROPERTY"]["TOTAL7DAY"][0] += $data["c"];
	$r["PROPERTY"]["DROPDAY"][] = $data["drop_day"];
	$r["PROPERTY"]["DROPCOUNT"][] = $data["c"];
}

return $r;

?>
