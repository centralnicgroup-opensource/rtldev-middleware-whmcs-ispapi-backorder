<?php
$cronname = "DAILY_ADMIN_NOTIFICATION";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";

$list_in_3days = array();
$list_in_2days = array();
$list_in_1day = array();

$result = select_query('backorder_domains','*', array());
while ($local = mysql_fetch_array($result)) {
	$today = new DateTime(date("Y-m-d H:i:s"));
	$dropdate = new DateTime($local["dropdate"]);

	$diff_timestamp = $dropdate->getTimestamp() - $today->getTimestamp();

	//CHECK IF TIMESTAMP >=0 AND <= 86400 (1 DAY) AND ADD TO THE LIST
	if($diff_timestamp >= 0 && $diff_timestamp <= 86400){
		if(!isset($list_in_1day[$local["userid"]])){
			$list_in_1day[$local["userid"]]["backorders"] = array();
		}
		$list_in_1day[$local["userid"]]["backorders"][] = array("domain" => $local["domain"].".".$local["tld"], "dropdate" => $local["dropdate"], "status" => $local["status"]);
	}

	//CHECK IF TIMESTAMP >86400 (1 DAY) AND <= 172800 (2 DAYS) AND ADD TO THE LIST
	if($diff_timestamp  >86400 && $diff_timestamp <= 172800){
		if(!isset($list_in_2days[$local["userid"]])){
			$list_in_2days[$local["userid"]]["backorders"] = array();
		}
		$list_in_2days[$local["userid"]]["backorders"][] = array("domain" => $local["domain"].".".$local["tld"], "dropdate" => $local["dropdate"], "status" => $local["status"]);
	}

	//CHECK IF TIMESTAMP >172800 (2 DAYS) AND <= 259200 (3 DAYS) AND ADD TO THE LIST
	if($diff_timestamp > 172800 && $diff_timestamp <= 259200){
		if(!isset($list_in_3days[$local["userid"]])){
			$list_in_3days[$local["userid"]]["backorders"] = array();
		}
		$list_in_3days[$local["userid"]]["backorders"][] = array("domain" => $local["domain"].".".$local["tld"], "dropdate" => $local["dropdate"], "status" => $local["status"]);
	}

}

//SEND BACKORDER NOTIFICATION TO THE ADMIN
$send = false;
$content = "";
$tmp = "";
foreach($list_in_3days as $l){
	$send = true;
	foreach ($l["backorders"] as $backorder){
		$tmp .= "- ".$backorder["domain"]." ".$backorder["dropdate"]." ".$backorder["status"]."<br>";
	}
}
if($tmp != ""){
	$content .= "<b>The following backorders will be dropped in 3 days:</b><br>".$tmp."<br><br>";
}

$tmp = "";
foreach($list_in_2days as $l){
	$send = true;
	foreach ($l["backorders"] as $backorder){
		$tmp .= "- ".$backorder["domain"]." ".$backorder["dropdate"]." ".$backorder["status"]."<br>";
	}
}
if($tmp != ""){
	$content .= "<b>The following backorders will be dropped in 2 days:</b><br>".$tmp."<br><br>";
}

$tmp = "";
foreach($list_in_1day as $l){
	$send = true;
	foreach ($l["backorders"] as $backorder){
		$tmp .= "- ".$backorder["domain"]." ".$backorder["dropdate"]." ".$backorder["status"]."<br>";
	}
}
if($tmp != ""){
	$content .= "<b>The following backorders will be dropped in 1 day:</b><br>".$tmp."<br><br>";
}

$result = select_query('tblconfiguration','value', array("setting" => "Email"));
while ($data = mysql_fetch_array($result)) {
	$adminemail = $data["value"];
}

if($send){
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
	mail($adminemail, "Daily Backorder Admin Notification", $content, $headers);
}

logmessage($cronname, "ok", "DAILY_ADMIN_NOTIFICATION done");
echo date("Y-m-d H:i:s")." DAILY_ADMIN_NOTIFICATION done.\n";
?>
