<?php
$cronname = "DAILY_USER_NOTIFICATION";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";

$list = array();

$result = full_query('SELECT * FROM backorder_domains where status = "REQUESTED" OR status = "ACTIVE" OR status = "PROCESSING"');
while ($local = mysql_fetch_array($result)) {
	$today = new DateTime(date("Y-m-d H:i:s"));
	$dropdate = new DateTime($local["dropdate"]);

	$diff_timestamp = $dropdate->getTimestamp() - $today->getTimestamp();

	//CHECK IF TIMESTAMP >=0 AND <= 259200 (3 DAYS) AND ADD TO THE LIST
	if($diff_timestamp >=0 && $diff_timestamp <= 259200){
		if(!isset($list[$local["userid"]])){
			$list[$local["userid"]]["backorders"] = array();
		}
		$list[$local["userid"]]["backorders"][] = array("domain" => $local["domain"].".".$local["tld"], "dropdate" => $local["dropdate"], "status" => $local["status"]);
	}
}

//SEND BACKORDER NOTIFICATION TO ALL USERS
foreach($list as $key => $l){
	$command = "sendemail";
	$adminuser = "admin";
	$values["messagename"] = "backorder_3_days_before";
	$values["id"] = $key;
	$values["customvars"] = array("list"=> $l["backorders"]);
	$results = localAPI($command, $values, $adminuser);
}

logmessage($cronname, "ok", "DAILY_USER_NOTIFICATION done");
echo date("Y-m-d H:i:s")." DAILY_USER_NOTIFICATION done.\n";
?>
