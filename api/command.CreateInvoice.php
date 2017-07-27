<?php // $command, $userid
use WHMCS\Database\Capsule;

try {

	if ( !$userid )	return backorder_api_response(531);

	if ( !isset($command["DOMAIN"]) || !strlen($command["DOMAIN"]) )
		return backorder_api_response(504, "DOMAIN");

	if ( !preg_match('/^(.*)\.(.*)$/', $command["DOMAIN"], $m) )
		return backorder_api_response(505, "DOMAIN");

	$domain = $m[1];
	$tld = $m[2];

	//GET ADMIN USERNAME
	$adminuser = Capsule::table('tbladdonmodules')
	                        ->where('module', 'ispapibackorder')
	                        ->where('setting', 'username')
	                        ->value('value');
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
				//GET OLD STATUS
				$backorderbeforeupdate = Capsule::table('backorder_domains')->where('id', $command["BACKORDERID"])->first();
				$oldstatus = $backorderbeforeupdate->status;

				Capsule::table('backorder_domains')
				            ->where('userid', $userid)
				            ->where('id', $command["BACKORDERID"])
				            ->update(['status' => 'PENDING-PAYMENT', 'invoice' => $invoicing["invoiceid"], 'updateddate' => date("Y-m-d H:i:s")]);

				$message = "BACKORDER ".$domain.".".$tld." (backorderid=".$command["BACKORDERID"].") set from ".$oldstatus." to PENDING-PAYMENT, invoice created (".$invoicing["invoiceid"].")";
				logmessage("command.CreateInvoice", "ok", $message);

				return backorder_api_response(200);

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

}catch (\Exception $e) {
	logmessage("command.CreateInvoice", "DB error", $e->getMessage());
	return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}

?>
