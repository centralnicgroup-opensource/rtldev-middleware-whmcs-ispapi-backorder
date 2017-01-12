<?php // $command, $userid

if ( !$userid )	return backorder_api_response(531);

if ( !isset($command["DOMAIN"]) || !strlen($command["DOMAIN"]) )
	return backorder_api_response(504, "DOMAIN");

if ( !backorder_api_check_syntax_domain($command["DOMAIN"]) ) 
	return backorder_api_response(505, "DOMAIN");

if ( !backorder_api_check_valid_tld($command["DOMAIN"], $userid) ) 
	return backorder_api_response(541, "TLD NOT IN PRICELIST");

if(isset($command["TYPE"])){
	$backordertype = strtoupper($command["TYPE"]);
}else{
	$backordertype = "FULL";
}

if (!in_array($backordertype, array("FULL", "LITE"))){
	return backorder_api_response(505, "TYPE");
}

if(isset($command["DROPDATE"])){
	$dropdate = $command["DROPDATE"];
}else{
	$dropdate = "0000-00-00 00:00:00";
}

$values = array(
	"userid" => $userid,
	"createddate" => date("Y-m-d H:i:s"),
	"updateddate" => date("Y-m-d H:i:s"),
	"dropdate" => $dropdate,
	"type" => $backordertype,
	"status" => "REQUESTED",
	"provider" => "",
	"reference" => ""
);

if ( preg_match('/^(.*)\.(.*)$/', $command["DOMAIN"], $m) ) {
	$values["domain"] = strtolower($m[1]);
	$values["tld"] = strtolower($m[2]);
	
	$result = select_query('backorder_domains','*', array("userid" => $userid, "domain" => $values["domain"], "tld" => $values["tld"] ));
	
	//IF BACKORDER ALREADY EXISTING, UPDATE IT
	if ($data = mysql_fetch_assoc($result)) {
		if (in_array($data["status"], array("REQUESTED", "ACTIVE", "FAILED", "SUCCESSFUL", "AUCTION-LOST", "AUCTION-WON"))){
			$result = update_query('backorder_domains', array("type" => $values["type"], "updateddate" => date("Y-m-d H:i:s"), "status" => "REQUESTED", "provider" => "", "reference" => "", "invoice" => "" ), array("userid" => $userid, "domain" => $values["domain"], "tld" => $values["tld"]));
		}else{
			return backorder_api_response(549, "THIS BACKORDER CANNOT BE MODIFIED");
		}
	//IF NOT EXISTING, INSERT IT
	}else{
		if ( !insert_query('backorder_domains', $values) )
			return backorder_api_response(549, "CREATE FAILED");
	}
}



return backorder_api_response(200);

?>
