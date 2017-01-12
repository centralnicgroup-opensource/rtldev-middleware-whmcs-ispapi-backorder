<?php // $command, $userid

if ( !$userid )	return backorder_api_response(531);

if ( !isset($command["DOMAIN"]) || !strlen($command["DOMAIN"]) )
	return backorder_api_response(504, "DOMAIN");

if ( !preg_match('/^(.*)\.(.*)$/', $command["DOMAIN"], $m) )
	return backorder_api_response(505, "DOMAIN");

$result = select_query('backorder_domains','*',array("userid" => $userid, "domain" => $m[1], "tld" => $m[2]));
if (!($data = mysql_fetch_assoc($result))) {
	return backorder_api_response(545, "DOMAIN");
}else{
	if(in_array($data["status"], array("PROCESSING", "PENDING-PAYMENT", "AUCTION-PENDING"))){
		return backorder_api_response(549, "THIS BACKORDER CANNOT BE DELETED");
	}
	delete_query('backorder_domains', array('id' => $data['id']) );
}

return backorder_api_response(200);

?>
