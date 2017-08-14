<?php
date_default_timezone_set('UTC');
$cronname = "BATCH_POLLING";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";

use WHMCS\Database\Capsule;

try{
	$pdo = Capsule::connection()->getPdo();

	function getRenewPrice($userid, $tld){
		global $pdo;
		$stmt = $pdo->prepare("SELECT p.msetupfee FROM tblpricing p, tbldomainpricing dp, tblclients c WHERE dp.id=p.relid AND c.currency=p.currency AND c.id=? AND dp.extension=? AND p.type='domainrenew'");
		$stmt->execute(array($userid, ".".$tld));
		$renewprice = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if(isset($renewprice[0]["msetupfee"])){
			return $renewprice[0]["msetupfee"];
		}else{
			return 0;
		}
	}

	//ITERATE OVER PROCESSING AND AUCTION-PENDING APPLICATIONS
	$stmt = $pdo->prepare("SELECT * FROM backorder_domains WHERE status='PROCESSING' OR status='AUCTION-PENDING' AND reference!=''");
   	$stmt->execute();
   	$locals = $stmt->fetchAll(PDO::FETCH_ASSOC);

	foreach($locals as $local){
		//CHECK STATUSDOMAINAPPLICATION
		$command =  array(
			"COMMAND" => "StatusDomainApplication",
			"APPLICATION" => $local["reference"]
		);
		$backorder = ispapi_api_call($command);

		if($backorder["CODE"] == 200){

			//###############################################
			//### AUCTION-PENDING
			//###############################################
			if(in_array($backorder["PROPERTY"]["STATUS"][0], array("AUCTION-PENDING"))){

				//CHECK IF BACKORDER STATUS ALREADY SET TO AUCTION-PENDING IN WHMCS
				$check_status_stmt = $pdo->prepare("SELECT status FROM backorder_domains WHERE id=?");
				$check_status_stmt->execute(array($local["id"]));
				$data = $check_status_stmt->fetch(PDO::FETCH_ASSOC);

				if(!in_array($data["status"], array("AUCTION-PENDING"))){
					//GET OLD STATUS
					$oldstatus = $data["status"];

					//SET BACKORDER STATUS TO AUCTION-PENDING
					$update_stmt = $pdo->prepare("UPDATE backorder_domains SET status='AUCTION-PENDING', updateddate=NOW() WHERE id=?");
					$update_stmt->execute(array($local["id"]));
					if($update_stmt->rowCount() != 0){
						$message = "BACKORDER ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].") set from ".$oldstatus." to AUCTION-PENDING";
						logmessage($cronname, "ok", $message);
					}
				}

			//###############################################
			//### FAILED - AUCTION-LOST
			//###############################################
			}elseif(in_array($backorder["PROPERTY"]["STATUS"][0], array("FAILED", "AUCTION-LOST"))){

				//CHECK IF BACKORDER STATUS ALREADY SET TO FAILED OR AUCTION-LOST IN WHMCS
				$check_status_stmt = $pdo->prepare("SELECT status FROM backorder_domains WHERE id=?");
				$check_status_stmt->execute(array($local["id"]));
				$data = $check_status_stmt->fetch(PDO::FETCH_ASSOC);

				if(!in_array($data["status"], array("FAILED", "AUCTION-LOST"))){
					//GET OLD STATUS
					$oldstatus = $data["status"];

					//SET BACKORDER STATUS TO AUCTION-FAILED
					$update_stmt = $pdo->prepare("UPDATE backorder_domains SET status=?, updateddate=NOW() WHERE id=?");
					$update_stmt->execute(array($backorder["PROPERTY"]["STATUS"][0], $local["id"]));

					if($update_stmt->rowCount() != 0){
						$message = "BACKORDER ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].") set from ".$oldstatus." to ".$backorder["PROPERTY"]["STATUS"][0];
						logmessage($cronname, "ok", $message);
					}

				}

			//###############################################
			//### AUCTION-WON
			//###############################################
			}elseif(in_array($backorder["PROPERTY"]["STATUS"][0], array("AUCTION-WON"))){

				//CHECK IF BACKORDER STATUS ALREADY SET TO AUCTION-WON IN WHMCS
				$check_status_stmt = $pdo->prepare("SELECT status FROM backorder_domains WHERE id=?");
				$check_status_stmt->execute(array($local["id"]));
				$data = $check_status_stmt->fetch(PDO::FETCH_ASSOC);

				if(!in_array($data["status"], array("AUCTION-WON"))){
					//GET OLD STATUS
					$oldstatus = $data["status"];

					//STATUSDOMAIN TO GET THE CREATED DATE
					$command = array(
							"COMMAND" => "StatusDomain",
							"DOMAIN" => $local["domain"].".".$local["tld"]
					);
					$statusdomain = ispapi_api_call($command);

					if($statusdomain["CODE"] == 200){
						$createddate = substr($statusdomain["PROPERTY"]["CREATEDDATE"][0], 0, -9);
						$expirationdate = substr($statusdomain["PROPERTY"]["EXPIRATIONDATE"][0], 0, -9);

						$tmpdate = new DateTime($expirationdate);
						$tmpdate->modify('-15 days');
						$nextduedate = $tmpdate->format('Y-m-d');

						//GET RENEW PRICE OF TLD
						$renewprice = getRenewPrice($local["userid"], $local["tld"]);

						//IMPORT DOMAIN IN WHMCS
						$insert_stmt = $pdo->prepare("INSERT INTO tbldomains (userid, domain, registrar, registrationdate, expirydate, nextduedate, nextinvoicedate, dnsmanagement, emailforwarding, status, donotrenew, recurringamount) VALUES(:userid, :domain, :registrar, :registrationdate, :expirydate, :nextduedate, :nextinvoicedate, :dnsmanagement, :emailforwarding, :status, :donotrenew, :recurringamount)");
						$insert_stmt->execute(array(':userid' => $local["userid"], ':domain' => $local["domain"].".".$local["tld"], ':registrar' => "ispapi", ':registrationdate' => $createddate, ':expirydate' => $expirationdate, ':nextduedate' => $nextduedate, ':nextinvoicedate' => $nextduedate, ':dnsmanagement' => 1, ':emailforwarding' => 1, ':status' => "Active", ':donotrenew' => 0, ':recurringamount' => $renewprice));

						if($insert_stmt->rowCount() != 0){
							$update_stmt = $pdo->prepare("UPDATE backorder_domains SET status='AUCTION-WON', updateddate=NOW() WHERE id=?");
							$update_stmt->execute(array($local["id"]));

							if($update_stmt->rowCount() != 0){
								$message = "BACKORDER ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].", userid=".$local["userid"].") set from ".$oldstatus." to AUCTION-WON, domain imported in user account";
								logmessage($cronname, "ok", $message);
							}
						}
					}else{
						$message = "StatusDomain for ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].") currently not possible (Please wait)";
						logmessage($cronname, "error", $message);
					}
				}

			//###############################################
			//### SUCCESSFUL
			//###############################################
			}elseif(in_array($backorder["PROPERTY"]["STATUS"][0], array("SUCCESSFUL"))){

				//CHECK IF BACKORDER STATUS ALREADY SET TO SUCCESSFUL IN WHMCS
				$check_status_stmt = $pdo->prepare("SELECT status FROM backorder_domains WHERE id=?");
				$check_status_stmt->execute(array($local["id"]));
				$data = $check_status_stmt->fetch(PDO::FETCH_ASSOC);

				if(!in_array($data["status"], array("SUCCESSFUL"))){
					//GET OLD STATUS
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
				$check_status_stmt = $pdo->prepare("SELECT status FROM backorder_domains WHERE id=?");
				$check_status_stmt->execute(array($local["id"]));
				$data = $check_status_stmt->fetch(PDO::FETCH_ASSOC);
				$oldstatus = $data["status"];

				//SET BACKORDER TO CANCELLED
				$update_stmt = $pdo->prepare("UPDATE backorder_domains SET status='CANCELLED', updateddate=NOW() WHERE id=?");
				$update_stmt->execute(array($local["id"]));

				if($update_stmt->rowCount() != 0){
					$message = "BACKORDER ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].", userid=".$local["userid"].") set from ".$oldstatus." to CANCELLED (backorder application deleted by admin)";
					logmessage($cronname, "ok", $message);
				}
			}
		}
	}


	//GET ADMIN USERNAME
	$stmt = $pdo->prepare("SELECT value FROM tbladdonmodules WHERE module='ispapibackorder' AND setting='username'");
	$stmt->execute();
	$data = $stmt->fetch(PDO::FETCH_ASSOC);
	$adminuser = $data["value"];

	if(empty($adminuser)){
		$message = "MISSING ADMIN USERNAME IN MODULE CONFIGURATION";
		logmessage($cronname, "error", $message);
	}

	//ITERATE OVER PENDING-PAYMENT APPLICATIONS
	$stmt = $pdo->prepare("SELECT * FROM backorder_domains WHERE status='PENDING-PAYMENT'");
	$stmt->execute();
	$locals = $stmt->fetchAll(PDO::FETCH_ASSOC);

	foreach ($locals as $local) {
		$invoice = localAPI("getinvoice", array("invoiceid" => $local["invoice"]), $adminuser);
		if($invoice["status"] == "error"){
			//INVOICE NOT FOUND = INVOICE DELETED BY THE ADMIN
			$update_stmt = $pdo->prepare("UPDATE backorder_domains SET status='CANCELLED', updateddate=NOW() WHERE id=?");
			$update_stmt->execute(array($local["id"]));

			if($update_stmt->rowCount() != 0){
				$message = "BACKORDER APPLICATION ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].") set from PENDING-PAYMENT to CANCELLED (invoice deleted by admin)";
				logmessage($cronname, "ok", $message);
			}
		}else{
			//STATUSDOMAIN TO GET THE CREATED DATE
			$command = array(
				 "COMMAND" => "StatusDomain",
				 "DOMAIN" => $local["domain"].".".$local["tld"]
			);
			$statusdomain = ispapi_api_call($command);

			if($statusdomain["CODE"] == 200){
				$createddate = substr($statusdomain["PROPERTY"]["CREATEDDATE"][0], 0, -9);
				$expirationdate = substr($statusdomain["PROPERTY"]["EXPIRATIONDATE"][0], 0, -9);

				$tmpdate = new DateTime($expirationdate);
				$tmpdate->modify('-15 days');
				$nextduedate = $tmpdate->format('Y-m-d');

				if($invoice["status"] == "Paid"){
					//GET RENEW PRICE OF TLD
					$renewprice = getRenewPrice($local["userid"], $local["tld"]);

					//IMPORT DOMAIN IN WHMCS
					$insert_stmt = $pdo->prepare("INSERT INTO tbldomains (userid, domain, registrar, registrationdate, expirydate, nextduedate, nextinvoicedate, dnsmanagement, emailforwarding, status, donotrenew, recurringamount) VALUES(:userid, :domain, :registrar, :registrationdate, :expirydate, :nextduedate, :nextinvoicedate, :dnsmanagement, :emailforwarding, :status, :donotrenew, :recurringamount)");
					$insert_stmt->execute(array(':userid' => $local["userid"], ':domain' => $local["domain"].".".$local["tld"], ':registrar' => "ispapi", ':registrationdate' => $createddate, ':expirydate' => $expirationdate, ':nextduedate' => $nextduedate, ':nextinvoicedate' => $nextduedate, ':dnsmanagement' => 1, ':emailforwarding' => 1, ':status' => "Active", ':donotrenew' => 0, ':recurringamount' => $renewprice));

					if($insert_stmt->rowCount() != 0){
						$update_stmt = $pdo->prepare("UPDATE backorder_domains SET status='SUCCESSFUL', updateddate=NOW() WHERE id=?");
						$update_stmt->execute(array($local["id"]));

						if($update_stmt->rowCount() != 0){
							$message = "BACKORDER APPLICATION ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].", userid=".$local["userid"].") set from PENDING-PAYMENT to SUCCESSFUL, invoice paid, domain imported in user account";
							logmessage($cronname, "ok", $message);
						}
					}
				}elseif($invoice["status"] == "Cancelled"){
					$update_stmt = $pdo->prepare("UPDATE backorder_domains SET status='CANCELLED', updateddate=NOW() WHERE id=?");
					$update_stmt->execute(array($local["id"]));

					if($update_stmt->rowCount() != 0){
						$message = "BACKORDER APPLICATION ".$backorder["domain"].".".$backorder["tld"]." (backorderid=".$backorder["id"].") set from PENDING-PAYMENT to CANCELLED (invoice set to cancelled)";
						logmessage($cronname, "ok", $message);
					}
				}

			}else{
				$message = "StatusDomain for ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].") currently not possible (Please wait)";
				logmessage($cronname, "error", $message);
			}
		}
	 }

	//ITERATE OVER REQUESTED AND ACTIVE APPLICATIONS WITH DROPDATE > 2 DAYS IN THE PAST
	$stmt = $pdo->prepare("SELECT * FROM backorder_domains WHERE (status = 'REQUESTED' OR status = 'ACTIVE') AND dropdate != '0000-00-00 00:00:00' AND dropdate < DATE_SUB(NOW(), INTERVAL 2 DAY)");
	$stmt->execute();
	$locals = $stmt->fetchAll(PDO::FETCH_ASSOC);

	foreach ($locals as $local) {
		//SET BACKORDER TO FAILED
		$update_stmt = $pdo->prepare("UPDATE backorder_domains SET status='FAILED', updateddate=NOW() WHERE id=?");
		$update_stmt->execute(array($local["id"]));

		if($update_stmt->rowCount() != 0){
			$message = "BACKORDER ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].", userid=".$local["userid"].") set from ".$local["status"]." to FAILED";
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
