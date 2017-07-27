<?php // $command, $userid
use WHMCS\Database\Capsule;

try {

	if ( !$userid )	return backorder_api_response(531);

	if ( !isset($command["DOMAIN"]) || !strlen($command["DOMAIN"]) )
		return backorder_api_response(504, "DOMAIN");

	if ( !backorder_api_check_syntax_domain($command["DOMAIN"]) )
		return backorder_api_response(505, "DOMAIN");

	if ( !backorder_api_check_valid_tld($command["DOMAIN"], $userid) )
		return backorder_api_response(541, "NOT SUPPORTED");

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
		"type" => "FULL",
		"status" => "REQUESTED",
		"reference" => ""
	);

	if ( preg_match('/^([^\.^ ]{0,61})\.([a-zA-Z\.]+)$/', $command["DOMAIN"], $m) ) {
		$values["domain"] = strtolower($m[1]);
		$values["tld"] = strtolower($m[2]);

		//CHECK IF PRICING EXISTING FOR THIS TLD
		$querypricelist = array(
				"COMMAND" => "QueryPriceList",
				"USER" => $userid,
				"TLD" => $values["tld"]
		);
		$result = backorder_backend_api_call($querypricelist);

		if($result["CODE"] == 200){
			$backorder_price = $result["PROPERTY"][$values["tld"]]["PRICEFULL"];
			if(empty($backorder_price)){
				return backorder_api_response(549, "TLD NOT SUPPORTED");
			}
		}else{
			return backorder_api_response(549, "TLD NOT SUPPORTED");
		}
		//------------------------------------------------------

		//CHECK IF THERE IS A DROPDATE EXISTING IN THE DROPLIST
		$r1 = Capsule::table('pending_domains')
					->where('domain', $values["domain"])
					->where('zone', $values["tld"])
					->first();
		if(isset($r1->drop_date)){
			$values["dropdate"] = $r1->drop_date;
		}
		//------------------------------------------------------

		$result = Capsule::table('backorder_domains')
							->where('userid', $userid)
							->where('domain', $values["domain"])
							->where('tld', $values["tld"])
							->first();

		//IF BACKORDER ALREADY EXISTING, UPDATE IT
		if(isset($result)){
			if (in_array($result->status, array("REQUESTED", "ACTIVE", "FAILED", "SUCCESSFUL", "AUCTION-LOST", "AUCTION-WON"))){
				return backorder_api_response(549, "BACKORDER ALREADY EXISTING");
			}else{
				return backorder_api_response(549, "THIS BACKORDER CANNOT BE MODIFIED");
			}
		//IF NOT EXISTING, INSERT IT
		}else{
			$insert = Capsule::table('backorder_domains')->insert($values);
		}

		$message = "BACKORDER ".$command["DOMAIN"]." set to REQUESTED";
		logmessage("command.CreateBackorder", "ok", $message);
		return backorder_api_response(200);
	}else{
		return backorder_api_response(549, "CREATE FAILED");
	}

} catch (\Exception $e) {
	logmessage("command.CreateBackorder", "DB error", $e->getMessage());
	return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}

?>
