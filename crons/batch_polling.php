<?php
date_default_timezone_set('UTC');
$cronname = "BATCH_POLLING";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";

use WHMCS\Database\Capsule;

try{
	//GET PDO CONNECTION
	$pdo = Capsule::connection()->getPdo();

	function getRenewPrice($userid, $tld){
		global $pdo;
		$pricereq=$pdo->prepare("SELECT p.msetupfee FROM tblpricing p, tbldomainpricing dp, tblclients c WHERE dp.id=p.relid AND c.currency=p.currency AND c.id=? AND dp.extension=? AND p.type=? ");
		$pricereq->execute(array($userid, ".".$tld, "domainrenew"));
		$renewprice = $pricereq->fetchAll(PDO::FETCH_ASSOC);

		if(isset($renewprice[0]["msetupfee"])){
			$renewprice = $renewprice[0]["msetupfee"];
		}else{
			$renewprice = 0;
		}

		return $renewprice;
	}

	//logmessage($cronname, "ok", "BATCH_POLLING started");

	//ITERATE OVER PROCESSING APPLICATIONS
	$result=$pdo->prepare("SELECT * FROM backorder_domains WHERE status=? OR status=? AND reference!='' ");
   	$result->execute(array("PROCESSING", "AUCTION-PENDING"));
   	$local = $result->fetchAll(PDO::FETCH_ASSOC);

	foreach($local as $key => $value){
		//CHECK STATUSDOMAINAPPLICATION
		$command =  array(
			"COMMAND" => "StatusDomainApplication",
			"APPLICATION" => $value["reference"]
		);
		$backorder = ispapi_api_call($command);

		if($backorder["CODE"] == 200){
			//AUCTION-PENDING
			if(in_array($backorder["PROPERTY"]["STATUS"][0], array("AUCTION-PENDING"))){
				//CHECK IF BACKORDER STATUS ALREADY SET TO AUCTION-PENDING IN WHMCS
				$check = $pdo->prepare("SELECT * FROM backorder_domains WHERE id=? AND status=?");
				$check->execute(array($value["id"], "AUCTION-PENDING"));
				$data = $check->fetchAll(PDO::FETCH_ASSOC);

				if(!$data){
					//GET OLD STATUS
					$r = $pdo->prepare("SELECT * FROM backorder_domains WHERE id=?");
					$r->execute(array($value["id"]));
					$d = $r->fetchAll(PDO::FETCH_ASSOC);

					foreach ($d as $key => $value) {
						$oldstatus = $value["status"];
					}

					//SET BACKORDER STATUS TO AUCTION-PENDING
					$update= $pdo->prepare("UPDATE backorder_domains SET status=?, updateddate=? WHERE id=?");
					$update->execute(array("AUCTION-PENDING", date("Y-m-d H:i:s"), $value["id"]));
					$affected_rows = $update->rowCount();
					if($affected_rows != 0){
						$message = "BACKORDER ".$value["domain"].".".$value["tld"]." (backorderid=".$value["id"].") set from ".$oldstatus." to AUCTION-PENDING";
						logmessage($cronname, "ok", $message);
					}

				}

			//FAILED - AUCTION-LOST
			}elseif(in_array($backorder["PROPERTY"]["STATUS"][0], array("FAILED", "AUCTION-LOST"))){
				$oldstatus = $value["status"];

				//CHECK IF BACKORDER STATUS ALREADY SET TO FAILED OR AUCTION-LOST IN WHMCS
				$check=$pdo->prepare("SELECT * FROM backorder_domains WHERE id=? AND (status=? OR status=?)");
				$check->execute(array($value["id"], "FAILED", "AUCTION-LOST"));
				$data = $check->fetchAll(PDO::FETCH_ASSOC);
				if(!$data){
					//GET OLD STATUS
					$r = $pdo->prepare("SELECT * FROM backorder_domains WHERE id=?");
					$r->execute(array($value["id"]));
					$d = $r->fetchAll(PDO::FETCH_ASSOC);

					foreach ($d as $key => $value) {
						$oldstatus = $value["status"];
					}

					//SET BACKORDER STATUS TO AUCTION-FAILED
					$update= $pdo->prepare("UPDATE backorder_domains SET status=?, updateddate=? WHERE id=?");
					$update->execute(array($backorder["PROPERTY"]["STATUS"][0], date("Y-m-d H:i:s"), $value["id"]));
					$affected_rows = $update->rowCount();

					if($affected_rows != 0){
						$message = "BACKORDER ".$value["domain"].".".$value["tld"]." (backorderid=".$value["id"].") set from ".$oldstatus." to ".$backorder["PROPERTY"]["STATUS"][0];
						logmessage($cronname, "ok", $message);
					}

				}

			//AUCTION-WON
			}elseif(in_array($backorder["PROPERTY"]["STATUS"][0], array("AUCTION-WON"))){

				//CHECK IF BACKORDER STATUS ALREADY SET TO AUCTION-WON IN WHMCS
				$check=$pdo->prepare("SELECT * FROM backorder_domains WHERE id=? AND status=?");
				$check->execute(array($value["id"], "AUCTION-WON"));
				$data = $check->fetchAll(PDO::FETCH_ASSOC);

				if(!$data){
					//GET OLD STATUS
					$r = $pdo->prepare("SELECT * FROM backorder_domains WHERE id=?");
					$r->execute(array($value["id"]));
					$d = $r->fetchAll(PDO::FETCH_ASSOC);

					foreach ($d as $key => $value) {
						$oldstatus = $value["status"];
					}

					//STATUSDOMAIN TO GET THE CREATED DATE
					$command = array(
							"COMMAND" => "StatusDomain",
							"DOMAIN" => $value["domain"].".".$value["tld"]
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
						$renewprice = getRenewPrice($value["userid"], $value["tld"]);

						//IMPORT DOMAIN IN WHMCS
						$insert=$pdo->prepare("INSERT INTO tbldomains (userid, domain, registrar, registrationdate, expirydate, nextduedate, nextinvoicedate, dnsmanagement, emailforwarding, status, donotrenew, recurringamount) VALUES(:userid, :domain, :registrar, :registrationdate, :expirydate, :nextduedate, :nextinvoicedate, :dnsmanagement, :emailforwarding, :status, :donotrenew, :recurringamount)");
						$insert->execute(array(':userid' => $value["userid"], ':domain' => $value["domain"].".".$value["tld"], ':registrar' => "ispapi", ':registrationdate' => $createddate, ':expirydate' => $expirationdate, ':nextduedate' => $nextduedate, ':nextinvoicedate' => $nextduedate, ':dnsmanagement' => 1, ':emailforwarding' => 1, ':status' => "Active", ':donotrenew' => 0, ':recurringamount' => $renewprice));
						$affected_rows = $insert->rowCount();

						if($affected_rows != 0){
							$update = $pdo->prepare("UPDATE backorder_domains SET status=?, updateddate=? WHERE id=?");
							$update->execute(array("AUCTION-WON", date("Y-m-d H:i:s"), $value["id"]));
							$affected_rows = $update->rowCount();

							if($affected_rows != 0){
								$message = "BACKORDER ".$value["domain"].".".$value["tld"]." (backorderid=".$value["id"].", userid=".$value["userid"].") set from ".$oldstatus." to AUCTION-WON, domain imported in user account";
								logmessage($cronname, "ok", $message);
							}
						}
					}else{
						$message = "StatusDomain for ".$value["domain"].".".$value["tld"]." (backorderid=".$value["id"].") currently not possible (Please wait)";
						logmessage($cronname, "error", $message);
					}
				}

			//SUCCESSFUL
			}elseif(in_array($backorder["PROPERTY"]["STATUS"][0], array("SUCCESSFUL"))){

				//CHECK IF BACKORDER STATUS ALREADY SET TO SUCCESSFUL IN WHMCS
				$check=$pdo->prepare("SELECT * FROM backorder_domains WHERE id=? and status=?");
				$check->execute(array($value["id"], "SUCCESSFUL"));
				$data = $check->fetchAll(PDO::FETCH_ASSOC);
				if(!$data){
					//GET OLD STATUS
					$r = $pdo->prepare("SELECT * FROM backorder_domains WHERE id=?");
					$r->execute(array($value["id"]));
					$d = $r->fetchAll(PDO::FETCH_ASSOC);

					foreach ($d as $key => $value) {
						$oldstatus = $value["status"];
					}
					//SEND INVOICE
					$createinvoice = array(
							"COMMAND" => "CreateInvoice",
							"USER" => $value["userid"],
							"DOMAIN" => $value["domain"].".".$value["tld"],
							"TYPE" => $value["type"],
							"BACKORDERID" => $value["id"]
					);
					$r = backorder_backend_api_call($createinvoice);
					if($r["CODE"] != 200){
						$message = "BACKORDER ".$value["domain"].".".$value["tld"]." (backorderid=".$value["id"].") - invoice creation error";
						logmessage($cronname, "error", $message);
					}
				}
			}
		}else{
			$message = "BACKORDER APPLICATION ".$value["domain"].".".$value["tld"]." (backorderid=".$value["id"].") NOT FOUND (".$backorder["CODE"].", ".$backorder["DESCRIPTION"].")";
			logmessage($cronname, "error", $message);

			//IF APPLICATION NOT FOUND (545), THEN SET THE BACKORDER TO CANCELLED. (MEANS THE BACKORDER HAS BEEN DELETED BY THE ADMIN)
			if($backorder["CODE"] == 545){
				//GET OLD STATUS
				$r=$pdo->prepare("SELECT * FROM backorder_domains WHERE id=?");
				$r->execute(array($value["id"]));
				$d = $r->fetchAll(PDO::FETCH_ASSOC);

				foreach ($d as $key => $value) {
					$oldstatus = $value["status"];
				}

				//SET BACKORDER TO CANCELLED
				$update = $pdo->prepare("UPDATE backorder_domains SET status=?, updateddate=? WHERE id=?");
				$update->execute(array("CANCELLED", date("Y-m-d H:i:s"), $value["id"]));
				$affected_rows = $update->rowCount();

				if($affected_rows != 0){
					$message = "BACKORDER ".$value["domain"].".".$value["tld"]." (backorderid=".$value["id"].", userid=".$value["userid"].") set from ".$oldstatus." to CANCELLED (backorder application deleted by admin)";
					logmessage($cronname, "ok", $message);
				}
			}
		}
	}
	//GET ADMIN USERNAME
	$r=$pdo->prepare("SELECT value FROM tbladdonmodules WHERE module=? AND setting=? ");
	$r->execute(array("ispapibackorder", "username"));
	$d = $r->fetchAll(PDO::FETCH_ASSOC);
	foreach ($d as $key => $value) {
		$adminuser = $value["value"];
	}

	if(empty($adminuser)){
		$message = "MISSING ADMIN USERNAME IN MODULE CONFIGURATION";
		logmessage($cronname, "error", $message);
	}

	//ITERATE OVER PENDING-PAYMENT APPLICATIONS
	$result = $pdo->prepare("SELECT * FROM backorder_domains WHERE status=? ");
	$result->execute(array("PENDING-PAYMENT"));
	$backorder = $result->fetchAll(PDO::FETCH_ASSOC);

	 foreach ($backorder as $key => $value) {
		 $invoice = localAPI("getinvoice", array("invoiceid" => $value["invoice"]), $adminuser);
		 if($invoice["status"] == "error"){
			 //INVOICE NOT FOUND = INVOICE DELETED BY THE ADMIN
			 $update = $pdo->prepare("UPDATE backorder_domains SET status=?, updateddate=? WHERE id=?");
			 $update->execute(array("CANCELLED", date("Y-m-d H:i:s"), $value["id"]));
			 $affected_rows = $update->rowCount();

			 if($affected_rows != 0){
				 $message = "BACKORDER APPLICATION ".$value["domain"].".".$value["tld"]." (backorderid=".$value["id"].") set from PENDING-PAYMENT to CANCELLED (invoice deleted by admin)";
				 logmessage($cronname, "ok", $message);
			 }

		 }else{
			 //STATUSDOMAIN TO GET THE CREATED DATE
			 $command = array(
					 "COMMAND" => "StatusDomain",
					 "DOMAIN" => $value["domain"].".".$value["tld"]
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
					 $renewprice = getRenewPrice($value["userid"], $value["tld"]);

					 //IMPORT DOMAIN IN WHMCS
					 $insert=$pdo->prepare("INSERT INTO tbldomains (userid, domain, registrar, registrationdate, expirydate, nextduedate, nextinvoicedate, dnsmanagement, emailforwarding, status, donotrenew, recurringamount) VALUES(:userid, :domain, :registrar, :registrationdate, :expirydate, :nextduedate, :nextinvoicedate, :dnsmanagement, :emailforwarding, :status, :donotrenew, :recurringamount)");
					 $insert->execute(array(':userid' => $value["userid"], ':domain' => $value["domain"].".".$value["tld"], ':registrar' => "ispapi", ':registrationdate' => $createddate, ':expirydate' => $expirationdate, ':nextduedate' => $nextduedate, ':nextinvoicedate' => $nextduedate, ':dnsmanagement' => 1, ':emailforwarding' => 1, ':status' => "Active", ':donotrenew' => 0, ':recurringamount' => $renewprice));
					 $affected_rows = $insert->rowCount();

					 if($affected_rows != 0){
						 $update = $pdo->prepare("UPDATE backorder_domains SET status=?, updateddate=? WHERE id=?");
						 $update->execute(array("SUCCESSFUL", date("Y-m-d H:i:s"), $value["id"]));
						 $affected_rows = $update->rowCount();

						 if($affected_rows != 0){
							 $message = "BACKORDER APPLICATION ".$value["domain"].".".$value["tld"]." (backorderid=".$value["id"].", userid=".$value["userid"].") set from PENDING-PAYMENT to SUCCESSFUL, invoice paid, domain imported in user account";
							 logmessage($cronname, "ok", $message);
						 }
					 }

				 }elseif($invoice["status"] == "Cancelled"){
					 $update = $pdo->prepare("UPDATE backorder_domains SET status=?, updateddate=? WHERE id=?");
					 $update->execute(array("CANCELLED", date("Y-m-d H:i:s"), $value["id"]));
					 $affected_rows = $update->rowCount();

					 if($affected_rows != 0){
						 $message = "BACKORDER APPLICATION ".$value["domain"].".".$value["tld"]." (backorderid=".$value["id"].", userid=".$value["userid"].") set from PENDING-PAYMENT to SUCCESSFUL, invoice paid, domain imported in user account";
						 logmessage($cronname, "ok", $message);
					 }
				 }

			 }else{
				 $message = "StatusDomain for ".$value["domain"].".".$value["tld"]." (backorderid=".$value["id"].") currently not possible (Please wait)";
				 logmessage($cronname, "error", $message);
			 }
		 }
	 }

	//ITERATE OVER REQUESTED AND ACTIVE APPLICATIONS WITH DROPDATE > 2 DAYS IN THE PAST
	$result=$pdo->prepare("SELECT * FROM backorder_domains WHERE (status =? OR status =?) AND dropdate !=? AND dropdate < DATE_SUB(NOW(), INTERVAL 2 DAY)");
	$result->execute(array("REQUESTED", "ACTIVE", "0000-00-00 00:00:00"));
	$local = $result->fetchAll(PDO::FETCH_ASSOC);
	foreach ($local as $key => $value) {
		$update = $pdo->prepare("UPDATE backorder_domains SET status=?, updateddate=? WHERE id=?");
		$update->execute(array("FAILED", date("Y-m-d H:i:s"), $value["id"]));
		$affected_rows = $update->rowCount();

		if($affected_rows != 0){
			$message = "BACKORDER APPLICATION ".$value["domain"].".".$value["tld"]." (backorderid=".$value["id"].", userid=".$value["userid"].") set from PENDING-PAYMENT to SUCCESSFUL, invoice paid, domain imported in user account";
			logmessage($cronname, "ok", $message);
		}
	}
	//logmessage($cronname, "ok", "BATCH_POLLING done");
	echo date("Y-m-d H:i:s")." BATCH_POLLING done.\n";

} catch (\Exception $e) {
   logmessage("batch_polling", "DB error", $e->getMessage());
   return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}

?>
