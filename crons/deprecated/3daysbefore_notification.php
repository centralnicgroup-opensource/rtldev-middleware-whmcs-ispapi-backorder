<?php
$cronname = "3DAYSBEFORE_NOTIFICATION";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";

$list = array();
$result = full_query('SELECT * FROM backorder_domains where status = "REQUESTED" OR status = "ACTIVE" OR status = "PROCESSING"');
while ($local = mysql_fetch_array($result)) {
	$today = new DateTime(date("Y-m-d H:i:s"));
	$dropdate = new DateTime($local["dropdate"]);

	$diff_timestamp = $dropdate->getTimestamp() - $today->getTimestamp();

	//CHECK IF TIMESTAMP >=0 AND <= 260100 (3 DAYS + 15min) AND ADD TO THE LIST
	if($diff_timestamp > 7200 && $diff_timestamp <= 260100){
		if($local["3daysbefore_notification"] != 1){
			if(!isset($list[$local["userid"]])){
				$list[$local["userid"]]["backorders"] = array();
			}
			$list[$local["userid"]]["backorders"][] = array("id" => $local["id"], "domain" => $local["domain"].".".$local["tld"], "dropdate" => $local["dropdate"], "status" => $local["status"]);
		}
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

	foreach($l["backorders"] as $backorder){
		if(update_query('backorder_domains',array("updateddate" => date("Y-m-d H:i:s"), "3daysbefore_notification" => 1) , array("id" => $backorder["id"]) )){
			$message = "BACKORDER ".$backorder["domain"]." (backorderid=".$backorder["id"].", userid=".$key.") 3 days before notification sent";
			logmessage($cronname, "ok", $message);
		}
	}

}

logmessage($cronname, "ok", "3DAYSBEFORE_NOTIFICATION done");
echo date("Y-m-d H:i:s")." 3DAYSBEFORE_NOTIFICATION done.\n";
?>
