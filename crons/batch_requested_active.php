<?php
date_default_timezone_set('UTC');
$cronname = "BATCH_REQUESTED_ACTIVE";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";

use WHMCS\Database\Capsule;
try{
	//GET PDO CONNECTION
	$pdo = Capsule::connection()->getPdo();

	$could_not_be_set_to_active = array();
	$list = array();

	$result=$pdo->prepare("SELECT * FROM backorder_domains WHERE status=? ");
	$result->execute(array("REQUESTED"));
	$local = $result->fetchAll(PDO::FETCH_ASSOC);
	foreach ($local as $key => $value) {
		$today = new DateTime(date("Y-m-d H:i:s"));
		$dropdate = new DateTime($value["dropdate"]);

		$diff_timestamp = $dropdate->getTimestamp() - $today->getTimestamp();
		$diff_timestamp =259200;
		//CHECK IF TIMESTAMP >=0 AND <= 259200 (3 DAYS) AND ADD TO THE LIST
		if($diff_timestamp >=0 && $diff_timestamp <= 259200){
			if(!isset($list[$value["userid"]])){
				$list[$value["userid"]]["backorders"] = array();
			}
			$list[$value["userid"]]["backorders"][] = array("id" => $value["id"],
															"tld" => $value["tld"],
															"type" => $value["type"],
															"domain" => $value["domain"].".".$value["tld"],
															"dropdate" => $value["dropdate"],
															"status" => $value["status"],
															"lowbalance_notification" => $value["lowbalance_notification"] );
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
					$res=$pdo->prepare("SELECT id, status, domain, tld FROM backorder_domains WHERE id=? ");
					$res->execute(array($backorder["id"]));
					$data = $res->fetchAll(PDO::FETCH_ASSOC);
					foreach ($data as $ky => $value) {
						//SET STATUS TO ACTIVE IF NOT ALREADY ACTIVE
						if($value["status"] == "REQUESTED"){
							$oldstatus = $value["status"];
							$update=$pdo->prepare("UPDATE backorder_domains SET status=?, updateddate=? WHERE id=?");
							$update->execute(array("ACTIVE", date("Y-m-d H:i:s"), $value["id"]));
							$affected_rows = $update->rowCount();
							if($affected_rows != 0){
								$message = "BACKORDER ".$value["domain"].".".$value["tld"]." (backorderid=".$value["id"].", userid=".$key.") set from ".$oldstatus." to ACTIVE";
								logmessage($cronname, "ok", $message);
							}
						}
					}
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
	$rquery=$pdo->prepare("SELECT value FROM tbladdonmodules WHERE module=? AND  setting=? ");
	$rquery->execute(array("ispapibackorder", "username"));
	$r = $rquery->fetch(PDO::FETCH_ASSOC);
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
				$update = $pdo->prepare("UPDATE backorder_domains SET updateddate=?, lowbalance_notification=? WHERE id=?");
				$update->execute(array(date("Y-m-d H:i:s"), 1, $backorder["id"]));
				$affected_rows = $update->rowCount();
				if($affected_rows != 0){
					$message = "BACKORDER ".$backorder["domain"]." (backorderid=".$backorder["id"].", userid=".$key.") insufficient funds - low balance notification sent";
					logmessage($cronname, "error", $message);
				}
			}
	}

	//logmessage($cronname, "ok", "BATCH_REQUESTED_ACTIVE done");
	echo date("Y-m-d H:i:s")." BATCH_REQUESTED_ACTIVE done.\n";
} catch (\Exception $e) {
   logmessage("batch_requested_active", "DB error", $e->getMessage());
   return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}


?>
