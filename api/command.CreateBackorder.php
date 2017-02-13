<?php // $command, $userid

if ( !$userid )	return backorder_api_response(531);

if ( !isset($command["DOMAIN"]) || !strlen($command["DOMAIN"]) )
	return backorder_api_response(504, "DOMAIN");

if ( !backorder_api_check_syntax_domain($command["DOMAIN"]) )
	return backorder_api_response(505, "DOMAIN");

if ( !backorder_api_check_valid_tld($command["DOMAIN"], $userid) )
	return backorder_api_response(541, "NOT SUPPORTED");

$backordertype = "FULL";

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
	"reference" => ""
);

if ( preg_match('/^(.*)\.(.*)$/', $command["DOMAIN"], $m) ) {
	$values["domain"] = strtolower($m[1]);
	$values["tld"] = strtolower($m[2]);

	//CHECK IF PRICING IS SET FOR THIS TLD
	$querypricelist = array(
			"COMMAND" => "QueryPriceList",
			"USER" => $userid,
			"TLD" => $values["tld"]
	);
	$result = backorder_backend_api_call($querypricelist);

	if($result["CODE"] == 200){
		$backorder_price = $result["PROPERTY"][$values["tld"]]["PRICEFULL"];
		if(empty($backorder_price)){ //IF PRICE SET FOR THIS TLD
			return backorder_api_response(549, "TLD NOT SUPPORTED");
		}
	}else{
		return backorder_api_response(549, "TLD NOT SUPPORTED");
	}
	//------------------------------------------------------

	//CHECK IF THERE IS A DROPDATE EXISTING IN THE DROPLIST
	$r1 = select_query('pending_domains','*', array("domain" => $values["domain"], "zone" => $values["tld"] ));
	$d1 = mysql_fetch_array($r1);
	if(!empty($d1)){
		$values["dropdate"] = $d1["drop_date"];
	}
	//------------------------------------------------------

	$result = select_query('backorder_domains','*', array("userid" => $userid, "domain" => $values["domain"], "tld" => $values["tld"] ));

	//IF BACKORDER ALREADY EXISTING, UPDATE IT
	if ($data = mysql_fetch_assoc($result)) {
		if (in_array($data["status"], array("REQUESTED", "ACTIVE", "FAILED", "SUCCESSFUL", "AUCTION-LOST", "AUCTION-WON"))){
			return backorder_api_response(549, "BACKORDER ALREADY EXISTING");
		}else{
			return backorder_api_response(549, "THIS BACKORDER CANNOT BE MODIFIED");
		}
	//IF NOT EXISTING, INSERT IT
	}else{
		if ( !insert_query('backorder_domains', $values) )
			return backorder_api_response(549, "CREATE FAILED");
	}

	$message = "BACKORDER ".$command["DOMAIN"]." set to REQUESTED";
	logmessage("command.CreateBackorder", "ok", $message);
	return backorder_api_response(200);
}else{
	return backorder_api_response(549, "CREATE FAILED");
}


?>
