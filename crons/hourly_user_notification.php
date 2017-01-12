<?php
$cronname = "HOURLY_USER_NOTIFICATION";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";

$list = array();

$result = select_query('backorder_domains','*', array("status" => "Processing"));
while ($local = mysql_fetch_array($result)) {
	$today = new DateTime(date("Y-m-d H:i:s"));
	$dropdate = new DateTime($local["dropdate"]);

	//ADD TO THE LIST IF DROPDATE > NOW  - IT MEANS DROPDATE IS WITHIN THE NEXT 2 HOURS
	if($dropdate->getTimestamp() > $today->getTimestamp()){
		if(!isset($list[$local["userid"]])){
			$list[$local["userid"]]["backorders"] = array();
		}
		$list[$local["userid"]]["backorders"][] = array("domain" => $local["domain"].".".$local["tld"], "dropdate" => $local["dropdate"], "status" => $local["status"]);
	}
}

//Backorder Notification for the customers
foreach($list as $key => $l){
	$command = "sendemail";
	$adminuser = "admin";
	$values["messagename"] = "backorder_2_hours_before";
	$values["id"] = $key;
	$values["customvars"] = array("list"=> $l["backorders"]);
	$results = localAPI($command, $values, $adminuser);
}

logmessage($cronname, "ok", "HOURLY_USER_NOTIFICATION done");
echo date("Y-m-d H:i:s")." HOURLY_USER_NOTIFICATION done.\n";
?>
