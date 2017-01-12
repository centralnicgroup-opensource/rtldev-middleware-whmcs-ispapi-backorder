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
		$list[$local["userid"]]["backorders"][] = array("id" => $local["id"], "tld" => $local["tld"], "type" => $local["type"] );
	}
}

//echo "<pre>";
//print_r($list);

//CHANGE STATUS FROM REQUESTED TO ACTIVE FOR CUSTOMER WITH CREDIT
foreach($list as $key => $l){ //for each user
	$notactivated = array();
	foreach($l["backorders"] as $backorder){ //for each backorder

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
		if(isset($backorder_price)){
			//echo "Backorder id=".$backorder["id"]." costs ".$backorder_price."<br>";

			//GET CURRENT CREDIT BALANCE
			$command = array(
					"COMMAND" => "GetAvailableFunds",
					"USER" => $key
			);
			$result = backorder_backend_api_call($command);
			if($result["CODE"] == 200){
				$current_credit = $result["PROPERTY"]["AMOUNT"];
			}else{
				$current_credit = 0;
			}

			if(($current_credit - $backorder_price) >= 0){
				//SET BACKORDER STATUS TO ACTIVE
				##########################################
				$res = select_query("backorder_domains", "id,status,domain,tld", array("id" => $backorder["id"]));
				while($data = mysql_fetch_array($res)){
					//SET STATUS TO ACTIVE IF NOT ALREADY ACTIVE
					if($data["status"] == "REQUESTED"){
						$oldstatus = $data["status"];
						if(update_query('backorder_domains',array("status" => "ACTIVE", "updateddate" => date("Y-m-d H:i:s")) , array("id" => $data["id"]) )){
							$message = "BACKORDER ".$data["domain"].".".$data["tld"]." (backorderid=".$data["id"].") set from ".$oldstatus." to ACTIVE";
							logmessage($cronname, "ok", $message);
						}
					}
				}
				##########################################
			}else  {
				array_push($notactivated, $backorder);
			}
		}
	}
	if(!empty($notactivated)){
		$could_not_be_set_to_active[$key] = $notactivated;
	}
}

//echo "<pre>";
//print_r($could_not_be_set_to_active);

logmessage($cronname, "ok", "BATCH_REQUESTED_ACTIVE done");
echo date("Y-m-d H:i:s")." BATCH_REQUESTED_ACTIVE done.\n";
?>
