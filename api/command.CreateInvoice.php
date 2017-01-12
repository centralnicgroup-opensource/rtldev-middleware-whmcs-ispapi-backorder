<?php // $command, $userid

if ( !$userid )	return backorder_api_response(531);

if ( !isset($command["DOMAIN"]) || !strlen($command["DOMAIN"]) )
	return backorder_api_response(504, "DOMAIN");

if ( !preg_match('/^(.*)\.(.*)$/', $command["DOMAIN"], $m) )
	return backorder_api_response(505, "DOMAIN");

$domain = $m[1];
$tld = $m[2];

//GET TLD PRICING
$querypricelist = array(
		"COMMAND" => "QueryPriceList",
		"USER" => $userid,
		"TLD" => $tld
);
$result = backorder_backend_api_call($querypricelist);

if($result["CODE"] == 200){

	if($command["TYPE"] == "FULL"){
		$backorder_price = $result["PROPERTY"][$tld]["PRICEFULL"];
	}
	if($command["TYPE"] == "LITE"){
		$backorder_price = $result["PROPERTY"][$tld]["PRICELITE"];
	}

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
	), "admin");

	if ($invoicing["result"] == "success"){
		//GET OLD STATUS
		$r = select_query("backorder_domains","*",array("id" =>$command["BACKORDERID"]));
		$d = mysql_fetch_array($r);
		$oldstatus = $d["status"];

		if(update_query('backorder_domains', array("status" => "PENDING-PAYMENT", "invoice" => $invoicing["invoiceid"], "updateddate" => date("Y-m-d H:i:s")) , array("userid" => $userid, "id" => $command["BACKORDERID"] ))){
			$message = "BACKORDER ".$domain.".".$tld." (backorderid=".$command["BACKORDERID"].") set from ".$oldstatus." to PENDING-PAYMENT, invoice created (".$invoicing["invoiceid"].")";
			logmessage("command.CreateInvoice", "ok", $message);
		}

		return backorder_api_response(200);
	}else {
		$message = "BACKORDER ".$domain.".".$tld." (backorderid=".$command["BACKORDERID"].") invoicing failed";
		logmessage("command.CreateInvoice", "ok", $message);
		return backorder_api_response(549, "INVOICING FAILED");
	}

}else{
	$message = "BACKORDER ".$domain.".".$tld." (backorderid=".$command["BACKORDERID"].") invoicing failed, can't get TLD pricing";
	logmessage("command.CreateInvoice", "ok", $message);
	return backorder_api_response(549, "CAN'T GET TLD PRICING");
}



?>
