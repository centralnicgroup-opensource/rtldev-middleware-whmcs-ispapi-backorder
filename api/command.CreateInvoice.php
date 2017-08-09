<?php // $command, $userid
use WHMCS\Database\Capsule;
try {
	//GET DPO CONNECTION
   $pdo = Capsule::connection()->getPdo();
   if ( !$userid )	return backorder_api_response(531);

   if ( !isset($command["DOMAIN"]) || !strlen($command["DOMAIN"]) )
   	return backorder_api_response(504, "DOMAIN");

   if ( !preg_match('/^(.*)\.(.*)$/', $command["DOMAIN"], $m) )
   	return backorder_api_response(505, "DOMAIN");

   $domain = $m[1];
   $tld = $m[2];

   //GET ADMIN USERNAME
   $result=$pdo->prepare("SELECT value FROM tbladdonmodules WHERE module=? and setting=?");
   $result->execute(array('ispapibackorder', 'username'));
   $r = $result->fetch(PDO::FETCH_ASSOC);
   $adminuser = $r["value"];

   if(empty($adminuser)){
   	return backorder_api_response(549, "MISSING ADMIN USERNAME IN MODULE CONFIGURATION");
   }

   //GET TLD PRICING
   $querypricelist = array(
   		"COMMAND" => "QueryPriceList",
   		"USER" => $userid,
   		"TLD" => $tld
   );
   $result = backorder_backend_api_call($querypricelist);

   if($result["CODE"] == 200){
   	$backorder_price="";
   	if($command["TYPE"] == "FULL"){
   		$backorder_price = $result["PROPERTY"][$tld]["PRICEFULL"];
   	}

   	if(!empty($backorder_price)){ //IF PRICE SET FOR THIS TLD
   		//SEND INVOICE AND CHANGE STATUS TO PENDING-PAYMENT
   		$invoicing = localAPI("createinvoice",array(
   				"userid" => $userid,
   				"date" => date("Ymd"),
   				"duedate" => date("Ymd", strtotime(date("Ymd")." +10 days")),
   				"autoapplycredit" => true,
   				"paymentmethod" => "",
   				"sendinvoice" => true,
   				"itemdescription1" => $command["TYPE"]." BACKORDER: ".$command["DOMAIN"],
   				"itemamount1" => $backorder_price,
   				"itemtaxed1" => 0
   		), $adminuser);

   		if ($invoicing["result"] == "success"){

            $r = $pdo->prepare("SELECT * FROM backorder_domains WHERE id=?");
            $r->execute(array($command["BACKORDERID"]));
            $d = $r->fetch(PDO::FETCH_ASSOC);
            $oldstatus = $d["status"];

            $update = $pdo->prepare("UPDATE backorder_domains SET status=?, invoice=?, updateddate=? WHERE userid=? AND id=?");
            $update->execute(array("PENDING-PAYMENT", $invoicing["invoiceid"], date("Y-m-d H:i:s"), $userid, $command["BACKORDERID"]));
            $affected_rows = $update->rowCount();
            if($affected_rows != 0){
                return backorder_api_response(200);
            }
   		}else {
   			$message = "BACKORDER ".$domain.".".$tld." (backorderid=".$command["BACKORDERID"].") invoicing failed";
   			logmessage("command.CreateInvoice", "ok", $message);
   			return backorder_api_response(549, "INVOICING FAILED");
   		}

   	}else{ //IF NO PRICE SET FOR THIS TLD
   		$message = "BACKORDER ".$domain.".".$tld." (backorderid=".$command["BACKORDERID"].") invoicing failed, can't get TLD pricing";
   		logmessage("command.CreateInvoice", "ok", $message);
   		return backorder_api_response(549, "CAN'T GET TLD PRICING");
   	}

   }else{
   	$message = "BACKORDER ".$domain.".".$tld." (backorderid=".$command["BACKORDERID"].") invoicing failed, can't get TLD pricing";
   	logmessage("command.CreateInvoice", "ok", $message);
   	return backorder_api_response(549, "CAN'T GET TLD PRICING");
   }

} catch (\Exception $e) {
	logmessage("command.CreateInvoice", "DB error", $e->getMessage());
	return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}


?>
