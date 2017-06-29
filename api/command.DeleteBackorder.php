<?php // $command, $userid
use WHMCS\Database\Capsule;

if ( !$userid )	return backorder_api_response(531, "AUTHORIZATION FAILED");

if ( !isset($command["DOMAIN"]) || !strlen($command["DOMAIN"]) )
	return backorder_api_response(504, "DOMAIN");

if ( !preg_match('/^([^\.^ ]{0,61})\.([a-zA-Z\.]+)$/', $command["DOMAIN"], $m) )
	return backorder_api_response(505, "DOMAIN");

$backorder = Capsule::table('backorder_domains')
						->where('userid', $userid)
						->where('domain', $m[1])
						->where('tld', $m[2])
						->first();

if(empty($backorder)){
	return backorder_api_response(545, "DOMAIN");
}else{

	if(in_array($backorder->status, array("PENDING-PAYMENT", "AUCTION-PENDING")) || ($backorder->status == "PROCESSING" && !empty($backorder->reference) ) ){
		return backorder_api_response(549, "THIS BACKORDER CANNOT BE DELETED");
	}
	$message = "BACKORDER ".$command["DOMAIN"]." DELETED";
	logmessage("command.DeleteBackorder", "ok", $message);

	Capsule::table('backorder_domains')
				->where('id', '=', $backorder->id)
				->delete();
}

return backorder_api_response(200);

?>
