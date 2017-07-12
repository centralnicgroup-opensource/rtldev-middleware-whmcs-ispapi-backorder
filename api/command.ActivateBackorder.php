<?php // $command, $userid
use WHMCS\Database\Capsule;
if ( !$userid )	return backorder_api_response(531, "AUTHORIZATION FAILED");

if ( !isset($command["DOMAIN"]) || !strlen($command["DOMAIN"]) )
	return backorder_api_response(504, "DOMAIN");

if ( !preg_match('/^(.*)\.(.*)$/', $command["DOMAIN"], $m) )
	return backorder_api_response(505, "DOMAIN");

// $result = select_query('backorder_domains','*',array("userid" => $userid, "domain" => $m[1], "tld" => $m[2]));
//
// echo "<pre>";
// print_r($result);
// echo "</pre>";
//
//
// if (!($data = mysql_fetch_assoc($result))) {
// 	return backorder_api_response(545, "DOMAIN");
// }else{
// 	if($data["status"] != "REQUESTED"){
// 		return backorder_api_response(549, "ONLY BACKORDER WITH STATUS REQUESTED CAN BE ACTIVATED");
// 	}
// }
// update_query('backorder_domains', array('status' => "ACTIVE", "updateddate" => date("Y-m-d H:i:s")), array('id' => $data['id']));

$result1 = Capsule::table('backorder_domains')
						->where('userid', $userid)
						->where('domain', $m[1])
						->where('tld', $m[2])
						->first();
//cast to an array
$result = (array)$result1;

// echo "<pre>";
// print_r($result);
// echo "</pre>";

if(!$result){
	return backorder_api_response(545, "DOMAIN");
}else {
	if($result["status"] != "REQUESTED"){
		return backorder_api_response(549, "ONLY BACKORDER WITH STATUS REQUESTED CAN BE ACTIVATED");
	}
}

Capsule::table('backorder_domains')
			 ->where('id', $result['id'])
			 ->update(['status' => "ACTIVE", "updateddate" => date("Y-m-d H:i:s")]);

return backorder_api_response(200);

?>
