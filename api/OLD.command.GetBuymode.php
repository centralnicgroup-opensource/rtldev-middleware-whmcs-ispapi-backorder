<?php // $command, $userid

//if ( !$userid )	return backorder_api_response(531);

if ( !isset($command["DOMAIN"]) || !strlen($command["DOMAIN"]) )
	return backorder_api_response(504, "DOMAIN");

if ( !preg_match('/^(.*)\.(.*)$/', $command["DOMAIN"], $m) )
	return backorder_api_response(505, "DOMAIN");

if ( !isset($command["LIMIT"]) ) $command["LIMIT"] = 100;
if ( !isset($command["FIRST"]) ) $command["FIRST"] = 0;

$limit = isset($command["LIMIT"])? $command["FIRST"].",".$command["LIMIT"] : "";

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

if ( preg_match('/^(.*)\.(.*)$/', $command["DOMAIN"], $m) ) {
	$domain = strtolower($m[1]);
	$tld = strtolower($m[2]);


	$r = backorder_api_response(200);
	$stmt = $db->prepare("SELECT backorder_action FROM domains WHERE domain='".$domain."' AND zone='".$tld."' LIMIT 1");
	
	$stmt->execute();
	
	while ( $data = $stmt->fetch() ) {
		$r["PROPERTY"] = $data;
	}

}

return $r;

?>
