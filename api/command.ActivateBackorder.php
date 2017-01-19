<?php // $command, $userid

if ( !$userid )	return backorder_api_response(531, "AUTHORIZATION FAILED");

if ( !isset($command["DOMAIN"]) || !strlen($command["DOMAIN"]) )
	return backorder_api_response(504, "DOMAIN");

if ( !preg_match('/^(.*)\.(.*)$/', $command["DOMAIN"], $m) )
	return backorder_api_response(505, "DOMAIN");


$result = select_query('backorder_domains','*',array("userid" => $userid, "domain" => $m[1], "tld" => $m[2]));
if (!($data = mysql_fetch_assoc($result))) {
	return backorder_api_response(545, "DOMAIN");
}else{
	if($data["status"] != "REQUESTED"){
		return backorder_api_response(549, "ONLY BACKORDER WITH STATUS REQUESTED CAN BE ACTIVATED");
	}
}
update_query('backorder_domains', array('status' => "ACTIVE", "updateddate" => date("Y-m-d H:i:s")), array('id' => $data['id']));

return backorder_api_response(200);

?>
