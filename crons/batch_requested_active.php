<?php
$cronname = "BATCH_REQUESTED_ACTIVE";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";

$could_not_be_set_to_active = array();
$list = array();

$result = select_query('backorder_domains','*', array("status" => "REQUESTED"));
while ($local = mysql_fetch_array($result)) {
	$today = new DateTime(date("Y-m-d H:i:s"));
	$dropdate = new DateTime($local["dropdate"]);

	$diff_timestamp = $dropdate->getTimestamp() - $today->getTimestamp();

	//CHECK IF TIMESTAMP >=0 AND <= 259200 (3 DAYS) AND ADD TO THE LIST
	if($diff_timestamp >=0 && $diff_timestamp <= 259200){
		if(!isset($list[$local["userid"]])){
			$list[$local["userid"]]["backorders"] = array();
		}
		$list[$local["userid"]]["backorders"][] = array("id" => $local["id"],
														"tld" => $local["tld"],
														"type" => $local["type"],
														"domain" => $local["domain"].".".$local["tld"],
														"dropdate" => $local["dropdate"],
														"status" => $local["status"],
														"lowbalance_notification" => $local["lowbalance_notification"] );
	}
}

//CHANGE STATUS FROM REQUESTED TO ACTIVE FOR CUSTOMER WITH CREDIT
foreach($list as $key => $l){ //for each user
	$notactivated = array();
	foreach($l["backorders"] as $backorder){ //for each backorder
		$backorder_price = "";

		//GET PRICE OF BACKORDER
		$command = array(
				"COMMAND" => "QueryPriceList",
				"USER" => $key,
				"TLD" => $backorder["tld"]
		);
		$result = backorder_backend_api_call($command);
		if($result["CODE"] == 200){
			if($backorder["type"] == "FULL"){
				$backorder_price = $result["PROPERTY"][$backorder["tld"]]["PRICEFULL"];
			}
		}

		//echo "Backorder id=".$backorder["id"]." costs ".$backorder_price."<br>";
		if(!empty($backorder_price)){ //USE || $backorder_price=="0" IF FREE BACKORDER ARE ALLOWED TO GO TO ACTIVE
			//echo "Backorder id=".$backorder["id"]." costs ".$backorder_price."<br>";

			//GET CURRENT CREDIT BALANCE
			$command = array(
					"COMMAND" => "GetAvailableFunds",
					"USER" => $key
			);
			$result = backorder_backend_api_call($command);

			if($result["CODE"] == 200){
				if(isset($result["PROPERTY"]["AMOUNT"]["VALUE"])){
					$current_credit = $result["PROPERTY"]["AMOUNT"]["VALUE"];
				}else{
					$current_credit = 0;
				}
			}else{
				$current_credit = 0;
			}
			//echo "<br>Current credit:".$current_credit;

			if(($current_credit - $backorder_price) >= 0){
				//SET BACKORDER STATUS TO ACTIVE
				##########################################
				$res = select_query("backorder_domains", "id,status,domain,tld", array("id" => $backorder["id"]));
				while($data = mysql_fetch_array($res)){
					//SET STATUS TO ACTIVE IF NOT ALREADY ACTIVE
					if($data["status"] == "REQUESTED"){
						$oldstatus = $data["status"];
						if(update_query('backorder_domains',array("status" => "ACTIVE", "updateddate" => date("Y-m-d H:i:s")) , array("id" => $data["id"]) )){
							$message = "BACKORDER ".$data["domain"].".".$data["tld"]." (backorderid=".$data["id"].", userid=".$key.") set from ".$oldstatus." to ACTIVE";
							logmessage($cronname, "ok", $message);
						}
					}
				}
				##########################################
			}else  {
				if($backorder["lowbalance_notification"] == 0){
					array_push($notactivated, $backorder);
				}
			}
		}else{
			$message = "BACKORDER ".$backorder["domain"]." (userid=".$key.") cound not be set to ACTIVE (no pricing set for TLD: .".$backorder["tld"].")";
			logmessage($cronname, "error", $message);
		}
	}
	if(!empty($notactivated)){
		$could_not_be_set_to_active[$key] = $notactivated;
	}
}

//GET ADMIN USERNAME
$r = mysql_fetch_array(full_query("SELECT value FROM tbladdonmodules WHERE module='ispapibackorder' and setting='username'"));
$adminuser = $r["value"];
if(empty($adminuser)){
	$message = "MISSING ADMIN USERNAME IN MODULE CONFIGURATION";
	logmessage($cronname, "error", $message);
}

//HANDLE ALL LOW BALANCE NOTIFICATIONS
foreach($could_not_be_set_to_active as $key => $backorders){
		#SEND LOW BALANCE NOTIFICATION TO THE CUSTOMER
		$command = "sendemail";
		$values["messagename"] = "backorder_lowbalance_notification";
		$values["id"] = $key;
		$values["customvars"] = array("list"=> $backorders);
		$results = localAPI($command, $values, $adminuser);

		#SET THE LOWBALANCENOTIFICATION FLAG TO 1
		foreach($backorders as $backorder){
			if(update_query('backorder_domains',array("updateddate" => date("Y-m-d H:i:s"), "lowbalance_notification" => 1) , array("id" => $backorder["id"]) )){
				$message = "BACKORDER ".$backorder["domain"]." (backorderid=".$backorder["id"].", userid=".$key.") insufficient funds - low balance notification sent";
				logmessage($cronname, "error", $message);
			}
		}
}

//logmessage($cronname, "ok", "BATCH_REQUESTED_ACTIVE done");
echo date("Y-m-d H:i:s")." BATCH_REQUESTED_ACTIVE done.\n";
?>
