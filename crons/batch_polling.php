<?php
$cronname = "BATCH_POLLING";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";

function getRenewPrice($userid, $tld){
	$pricereq = full_query("SELECT p.msetupfee FROM tblpricing p, tbldomainpricing dp, tblclients c
							WHERE dp.id=p.relid
							AND c.currency=p.currency
							AND c.id=".$userid."
							AND dp.extension='.".$tld."'
							AND p.type='domainrenew'");
	$renewprice = mysql_fetch_array($pricereq);
	if(isset($renewprice["msetupfee"])){
		$renewprice = $renewprice["msetupfee"];
	}else{
		$renewprice = 0;
	}
	return $renewprice;
}

//logmessage($cronname, "ok", "BATCH_POLLING started");

//ITERATE OVER PROCESSING APPLICATIONS
$result = full_query("SELECT * FROM backorder_domains WHERE (status = 'PROCESSING' OR status = 'AUCTION-PENDING') AND  reference != '' ");
while ($local = mysql_fetch_array($result)) {

	//CHECK STATUSDOMAINAPPLICATION
	$command =  array(
		"COMMAND" => "StatusDomainApplication",
		"APPLICATION" => $local["reference"]
	);
	$backorder = ispapi_api_call($command);

	if($backorder["CODE"] == 200){

		//AUCTION-PENDING
		if(in_array($backorder["PROPERTY"]["STATUS"][0], array("AUCTION-PENDING"))){
			//CHECK IF BACKORDER STATUS ALREADY SET TO AUCTION-PENDING IN WHMCS
			$check = select_query("backorder_domains","*",array("id" => $local["id"], "status" => "AUCTION-PENDING"));
			$data = mysql_fetch_array($check);
			if(!$data){
				$oldstatus = $data["status"];
				//SET BACKORDER STATUS TO AUCTION-PENDING
				if(update_query('backorder_domains', array("status" => "AUCTION-PENDING", "updateddate" => date("Y-m-d H:i:s")) , array("id" => $local["id"]))){
					$message = "BACKORDER ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].") set from ".$oldstatus." to AUCTION-PENDING";
					logmessage($cronname, "ok", $message);
				}
			}

		//FAILED - AUCTION-LOST
		}elseif(in_array($backorder["PROPERTY"]["STATUS"][0], array("FAILED", "AUCTION-LOST"))){
			$oldstatus = $local["status"];

			//CHECK IF BACKORDER STATUS ALREADY SET TO FAILED OR AUCTION-LOST IN WHMCS
			$check = full_query("SELECT * FROM backorder_domains WHERE id = ".$local["id"]." (status = 'FAILED' OR status = 'AUCTION-LOST')");
			$data = mysql_fetch_array($check);
			if(!$data){
				//GET OLD STATUS
				$r = select_query("backorder_domains","*",array("id" => $local["id"]));
				$d = mysql_fetch_array($r);
				$oldstatus = $d["status"];

				//SET BACKORDER STATUS TO AUCTION-FAILED
				if(update_query('backorder_domains', array("status" => $backorder["PROPERTY"]["STATUS"][0], "updateddate" => date("Y-m-d H:i:s")) , array("id" => $local["id"]))){
					$message = "BACKORDER ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].") set from ".$oldstatus." to ".$backorder["PROPERTY"]["STATUS"][0];
					logmessage($cronname, "ok", $message);
				}
			}

		//AUCTION-WON
		}elseif(in_array($backorder["PROPERTY"]["STATUS"][0], array("AUCTION-WON"))){

			//CHECK IF BACKORDER STATUS ALREADY SET TO AUCTION-WON IN WHMCS
			$check = select_query("backorder_domains","*",array("id" => $local["id"], "status" => "AUCTION-WON"));
			$data = mysql_fetch_array($check);
			if(!$data){
				$oldstatus = $data["status"];

				//STATUSDOMAIN TO GET THE CREATED DATE
				$command = array(
						"COMMAND" => "StatusDomain",
						"DOMAIN" => $local["domain"].".".$local["tld"]
				);
				$status = ispapi_api_call($command);

				$createddate = "";
				if($status["CODE"] == 200){
					$createddate = substr($status["PROPERTY"]["CREATEDDATE"][0], 0, -9);
					$expirationdate = substr($status["PROPERTY"]["EXPIRATIONDATE"][0], 0, -9);

					$tmpdate = new DateTime($expirationdate);
					$tmpdate->modify('-15 days');
					$nextduedate = $tmpdate->format('Y-m-d');

					//GET RENEW PRICE OF TLD
					$renewprice = getRenewPrice($local["userid"], $local["tld"]);

					//IMPORT DOMAIN IN WHMCS
					if(insert_query("tbldomains",array(
							"userid" => $local["userid"],
							"domain" => $local["domain"].".".$local["tld"],
							"registrar" => "ispapi",
							"registrationdate" => $createddate,
							"expirydate" => $expirationdate,
							"nextduedate" => $nextduedate,
							"nextinvoicedate" => $nextduedate,
							"dnsmanagement" => 1,
							"emailforwarding" => 1,
							"status" => "Active",
							//"donotrenew" => 1, //THIS ATTRIBUT IS REQUIRED TO BLOCK THE SENDING OF A SECOND PAYMENT CONFIRMATION OF 0€
							"donotrenew" => 0,
							"recurringamount" => $renewprice
					))){
						if(update_query('backorder_domains', array("status" => "AUCTION-WON", "updateddate" => date("Y-m-d H:i:s")) , array("id" => $local["id"]))){
							$message = "BACKORDER ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].", userid=".$local["userid"].") set from ".$oldstatus." to AUCTION-WON, domain imported in user account";
							logmessage($cronname, "ok", $message);
						}
					}

				}else{
					$message = "StatusDomain for ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].") currently not possible (Please wait)";
					logmessage($cronname, "error", $message);
				}

			}

		//SUCCESSFUL
		}elseif(in_array($backorder["PROPERTY"]["STATUS"][0], array("SUCCESSFUL"))){

			//CHECK IF BACKORDER STATUS ALREADY SET TO SUCCESSFUL IN WHMCS
			$check = select_query("backorder_domains","*",array("id" => $local["id"], "status" => "SUCCESSFUL"));
			$data = mysql_fetch_array($check);
			if(!$data){
				$oldstatus = $data["status"];

				//SEND INVOICE
				$createinvoice = array(
						"COMMAND" => "CreateInvoice",
						"USER" => $local["userid"],
						"DOMAIN" => $local["domain"].".".$local["tld"],
						"TYPE" => $local["type"],
						"BACKORDERID" => $local["id"]
				);
				$r = backorder_backend_api_call($createinvoice);
				if($r["CODE"] != 200){
					$message = "BACKORDER ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].") - invoice creation error";
					logmessage($cronname, "error", $message);
				}

			}

		}
	}else{
		$message = "BACKORDER APPLICATION ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].") NOT FOUND (".$backorder["CODE"].", ".$backorder["DESCRIPTION"].")";
		logmessage($cronname, "error", $message);

		//IF APPLICATION NOT FOUND (545), THEN SET THE BACKORDER TO CANCELLED. (MEANS THE BACKORDER HAS BEEN DELETED BY THE ADMIN)
		if($backorder["CODE"] == 545){
			//GET OLD STATUS
			$r = select_query("backorder_domains","*",array("id" => $local["id"]));
			$d = mysql_fetch_array($r);
			$oldstatus = $d["status"];

			//SET BACKORDER TO CANCELLED
			if(update_query('backorder_domains', array("status" => "CANCELLED", "updateddate" => date("Y-m-d H:i:s")) , array("id" => $local["id"]))){
				$message = "BACKORDER ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].", userid=".$local["userid"].") set from ".$oldstatus." to CANCELLED (backorder application deleted by admin)";
				logmessage($cronname, "ok", $message);
			}
		}

	}

}

//GET ADMIN USERNAME
$r = mysql_fetch_array(full_query("SELECT value FROM tbladdonmodules WHERE module='ispapibackorder' and setting='username'"));
$adminuser = $r["value"];
if(empty($adminuser)){
	$message = "MISSING ADMIN USERNAME IN MODULE CONFIGURATION";
	logmessage($cronname, "error", $message);
}

//ITERATE OVER PENDING-PAYMENT APPLICATIONS
$result = select_query("backorder_domains", "*", array("status" => "PENDING-PAYMENT"));
while ($backorder = mysql_fetch_array($result)) {

	$invoice = localAPI("getinvoice", array("invoiceid" => $backorder["invoice"]), $adminuser);
	if($invoice["status"] == "error"){
		//INVOICE NOT FOUND = INVOICE DELETED BY THE ADMIN
		if(update_query('backorder_domains', array("status" => "CANCELLED", "updateddate" => date("Y-m-d H:i:s")) , array("id" => $backorder["id"]))){
			$message = "BACKORDER APPLICATION ".$backorder["domain"].".".$backorder["tld"]." (backorderid=".$backorder["id"].") set from PENDING-PAYMENT to CANCELLED (invoice deleted by admin)";
			logmessage($cronname, "ok", $message);
		}
	}else{

		//STATUSDOMAIN TO GET THE CREATED DATE
		$command = array(
				"COMMAND" => "StatusDomain",
				"DOMAIN" => $backorder["domain"].".".$backorder["tld"]
		);
		$status = ispapi_api_call($command);

		$createddate = "";
		if($status["CODE"] == 200){
			$createddate = substr($status["PROPERTY"]["CREATEDDATE"][0], 0, -9);
			$expirationdate = substr($status["PROPERTY"]["EXPIRATIONDATE"][0], 0, -9);

			$tmpdate = new DateTime($expirationdate);
			$tmpdate->modify('-15 days');
			$nextduedate = $tmpdate->format('Y-m-d');


			if($invoice["status"] == "Paid"){
				//GET RENEW PRICE OF TLD
				$renewprice = getRenewPrice($backorder["userid"], $backorder["tld"]);

				//IMPORT DOMAIN IN WHMCS
				if(insert_query("tbldomains",array(
						"userid" => $backorder["userid"],
						"domain" => $backorder["domain"].".".$backorder["tld"],
						"registrar" => "ispapi",
						"registrationdate" => $createddate,
						"expirydate" => $expirationdate,
						"nextduedate" => $nextduedate,
						"nextinvoicedate" => $nextduedate,
						"dnsmanagement" => 1,
						"emailforwarding" => 1,
						"status" => "Active",
						//"donotrenew" => 1, //THIS ATTRIBUT IS REQUIRED TO BLOCK THE SENDING OF A SECOND PAYMENT CONFIRMATION OF 0€
						"donotrenew" => 0,
						"recurringamount" => $renewprice
				))){
					if(update_query('backorder_domains', array("status" => "SUCCESSFUL" , "updateddate" => date("Y-m-d H:i:s")) , array("id" => $backorder["id"]))){
						$message = "BACKORDER APPLICATION ".$backorder["domain"].".".$backorder["tld"]." (backorderid=".$backorder["id"].", userid=".$backorder["userid"].") set from PENDING-PAYMENT to SUCCESSFUL, invoice paid, domain imported in user account";
						logmessage($cronname, "ok", $message);
					}
				}

			}else if($invoice["status"] == "Cancelled"){
				if(update_query('backorder_domains', array("status" => "CANCELLED", "updateddate" => date("Y-m-d H:i:s")) , array("id" => $backorder["id"]))){
					$message = "BACKORDER APPLICATION ".$backorder["domain"].".".$backorder["tld"]." (backorderid=".$backorder["id"].") set from PENDING-PAYMENT to CANCELLED (invoice set to cancelled)";
					logmessage($cronname, "ok", $message);
				}
			}



		}else{
			$message = "StatusDomain for ".$backorder["domain"].".".$backorder["tld"]." (backorderid=".$backorder["id"].") currently not possible (Please wait)";
			logmessage($cronname, "error", $message);
		}


	}

}

//ITERATE OVER REQUESTED AND ACTIVE APPLICATIONS WITH DROPDATE > 2 DAYS IN THE PAST
$result = full_query("SELECT * FROM backorder_domains WHERE (status = 'REQUESTED' OR status = 'ACTIVE') AND dropdate != '0000-00-00 00:00:00' AND dropdate < DATE_SUB(NOW(), INTERVAL 2 DAY)");
while ($local = mysql_fetch_array($result)) {
	//SET BACKORDER TO FAILED
	if(update_query('backorder_domains', array("status" => "FAILED", "updateddate" => date("Y-m-d H:i:s")) , array("id" => $local["id"]))){
		$message = "BACKORDER ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].", userid=".$local["userid"].") set from ".$local["status"]." to FAILED";
		logmessage($cronname, "ok", $message);
	}
}

//logmessage($cronname, "ok", "BATCH_POLLING done");
echo date("Y-m-d H:i:s")." BATCH_POLLING done.\n";
?>
