<?php
$cronname = "BATCH_POLLING";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";

logmessage($cronname, "ok", "BATCH_POLLING started");

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
;					logmessage($cronname, "ok", $message);
				}
			}

		//FAILED - AUCTION-LOST
		}elseif(in_array($backorder["PROPERTY"]["STATUS"][0], array("FAILED", "AUCTION-LOST"))){
			$oldstatus = $local["status"];

			//CHECK IF BACKORDER STATUS ALREADY SET TO AUCTION-LOST IN WHMCS
			$check = select_query("backorder_domains","*",array("id" => $local["id"], "status" => "AUCTION-LOST"));
			$data = mysql_fetch_array($check);
			if(!$data){
				//GET OLD STATUS
				$r = select_query("backorder_domains","*",array("id" => $local["id"]));
				$d = mysql_fetch_array($r);
				$oldstatus = $d["status"];

				//SET BACKORDER STATUS TO AUCTION-FAILED
				if(update_query('backorder_domains', array("status" => $backorder["PROPERTY"]["STATUS"][0], "updateddate" => date("Y-m-d H:i:s")) , array("id" => $local["id"]))){
					$message = "BACKORDER ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].") set from ".$oldstatus." to AUCTION-LOST";
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

				if($status["CODE"] == 200){
					$createddate = substr($status["PROPERTY"]["CREATEDDATE"][0], 0, -9);
					//IMPORT DOMAIN IN WHMCS
					insert_query("tbldomains",array(
							"userid" => $local["userid"],
							"domain" => $local["domain"].".".$local["tld"],
							"registrar" => "ispapi",
							"registrationdate" => $createddate,
							"dnsmanagement" => 1,
							"emailforwarding" => 1,
							"status" => "Active",
					));
					if(update_query('backorder_domains', array("status" => "AUCTION-WON", "updateddate" => date("Y-m-d H:i:s")) , array("id" => $local["id"]))){
						$message = "BACKORDER ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].", userid=".$local["userid"].") set from ".$oldstatus." to AUCTION-WON, DOMAIN IMPORTED IN USER ACCOUNT";
						logmessage($cronname, "ok", $message);
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
	}

}

//ITERATE OVER PENDING-PAYMENT APPLICATIONS
$result = select_query("backorder_domains", "*", array("status" => "PENDING-PAYMENT"));
while ($backorder = mysql_fetch_array($result)) {

	$invoice = localAPI("getinvoice", array("invoiceid" => $backorder["invoice"]), "admin");
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

logmessage($cronname, "ok", "BATCH_POLLING done");
echo date("Y-m-d H:i:s")." BATCH_POLLING done.\n";
?>
