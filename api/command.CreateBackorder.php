<?php // $command, $userid
use WHMCS\Database\Capsule;

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

// echo "<pre>";
// print_r($values);
// echo "</pre>";

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
	// $r1 = select_query('pending_domains','*', array("domain" => $values["domain"], "zone" => $values["tld"] ));
	// $d1 = mysql_fetch_array($r1);
	// if(!empty($d1)){
	// 	$values["dropdate"] = $d1["drop_date"];
	// }


	//------------------------------------------------------

	//CHECK IF THERE IS A DROPDATE EXISTING IN THE DROPLIST
		$r1 = Capsule::table('pending_domains')
					->where('domain', $values["domain"])
					->where('zone', $values["tld"])
					->first();
		//$r1 is stdClass oject.. therefore, cast it to an array first
		$dropdate = (array)$r1;
		if(!empty($dropdate)){
			$values["dropdate"] = $dropdate["drop_date"];
		}
		// echo "<pre>";
		// print_r($dropdate);
		// echo "</pre>";

	// $result = select_query('backorder_domains','*', array("userid" => $userid, "domain" => $values["domain"], "tld" => $values["tld"] ));

		$result1 = Capsule::table('backorder_domains')
					->where('userid', $userid)
					->where('domain', $values["domain"])
					->where('tld', $values["tld"])
					->first();
		//Cast to an array
		$result = (array)$result1;

		// echo "<pre> result variable <br>";
		// print_r($result);
		// echo "</pre>";


	//IF BACKORDER ALREADY EXISTING, UPDATE IT
	// if ($data = mysql_fetch_assoc($result)) {
	// 	if (in_array($data["status"], array("REQUESTED", "ACTIVE", "FAILED", "SUCCESSFUL", "AUCTION-LOST", "AUCTION-WON"))){
	// 		return backorder_api_response(549, "BACKORDER ALREADY EXISTING");
	// 	}else{
	// 		return backorder_api_response(549, "THIS BACKORDER CANNOT BE MODIFIED");
	// 	}
	// //IF NOT EXISTING, INSERT IT
	// }else{
	// 	if ( !insert_query('backorder_domains', $values) )
	// 		return backorder_api_response(549, "CREATE FAILED");
	// }

	//IF BACKORDER ALREADY EXISTING, UPDATE IT
	if($result){
		if(in_array($result["status"], array("REQUESTED", "ACTIVE", "FAILED", "SUCCESSFUL", "AUCTION-LOST", "AUCTION-WON"))){
			return backorder_api_response(549, "BACKORDER ALREADY EXISTING");
		}else{
			return backorder_api_response(549, "THIS BACKORDER CANNOT BE MODIFIED");
		}
		//IF NOT EXISTING, INSERT IT
	}else { //am not sure if this step is executing properly - need to be checked
		$insert1 = Capsule::table('backorder_domainsI')->insert(
			[$values]
		);
		$insert = (array)$insert1;
		if(!$insert)
		return backorder_api_response(549, "CREATE FAILED");
	}

	$message = "BACKORDER ".$command["DOMAIN"]." set to REQUESTED";
	logmessage("command.CreateBackorder", "ok", $message);
	return backorder_api_response(200);
}else{
	return backorder_api_response(549, "CREATE FAILED");
}

?>
