<?php // $command, $userid

if ( !$userid )	return backorder_api_response(531, "AUTHORIZATION FAILED");

if ( !isset($command["DOMAIN"]) || !strlen($command["DOMAIN"]) )
	return backorder_api_response(504, "DOMAIN");

if ( !preg_match('/^([^\.^ ]{0,61})\.([a-zA-Z\.]+)$/', $command["DOMAIN"], $m) )
	return backorder_api_response(505, "DOMAIN");

$result = select_query('backorder_domains','*',array("userid" => $userid, "domain" => $m[1], "tld" => $m[2]));

if (!($data = mysql_fetch_assoc($result))) {
	return backorder_api_response(545, "DOMAIN");
}else{
	if(in_array($data["status"], array("PENDING-PAYMENT", "AUCTION-PENDING")) || ($data["status"]=="PROCESSING" && !empty($data["reference"]) ) ){
		return backorder_api_response(549, "THIS BACKORDER CANNOT BE DELETED");
	}
	$message = "BACKORDER ".$command["DOMAIN"]." DELETED";
	logmessage("command.DeleteBackorder", "ok", $message);
	delete_query('backorder_domains', array('id' => $data['id']) );
}

return backorder_api_response(200);

?>
