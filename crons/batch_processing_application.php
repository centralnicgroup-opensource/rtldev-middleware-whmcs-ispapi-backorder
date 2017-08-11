<?php

date_default_timezone_set('UTC');
$cronname = "BATCH_PROCESSING_APPLICATION";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";

use WHMCS\Database\Capsule;
try{
	$pdo = Capsule::connection()->getPdo();

	function get_contact_details($type="USER", $userid = NULL){
		global $pdo;
		//GET ADMIN USERNAME
		$stmt = $pdo->prepare("SELECT value FROM tbladdonmodules WHERE module='ispapibackorder' AND setting='username'");
		$stmt->execute();
		$r = $stmt->fetch(PDO::FETCH_ASSOC);
		$adminuser = $r["value"];

		if($type == "USER"){
			$command = "getclientsdetails";
			$params["clientid"] = $userid;
			$results = localAPI($command, $params, $adminuser);

			$values["FIRSTNAME"] = $results["firstname"];
			$values["LASTNAME"] = $results["lastname"];
			$values["ORGANIZATION"] = $results["companyname"];
			$values["STREET"] = $results["address1"];
			$values["CITY"] = $results["city"];
			$values["STATE"] = $results["fullstate"];
			$values["ZIP"] = $results["postcode"];
			$values["COUNTRY"] = $results["country"];
			$values["PHONE"] = $results["phonenumber"];
			$values["EMAIL"] = $results["email"];
		}else{
			$values = array();
			$stmt = $pdo->prepare("SELECT * FROM tblconfiguration");
			$stmt->execute();
			$configurations = $stmt->fetchAll(PDO::FETCH_ASSOC);

			foreach ($configurations as $configuration) {
				if($configuration["setting"] == "RegistrarAdminFirstName"){
					$values["FIRSTNAME"] = $configuration["value"];
				}
				if($configuration["setting"] == "RegistrarAdminLastName"){
					$values["LASTNAME"] = $configuration["value"];
				}
				if($configuration["setting"] == "RegistrarAdminCompanyName"){
					$values["ORGANIZATION"] = $configuration["value"];
				}
				if($configuration["setting"] == "RegistrarAdminAddress1"){
					$values["STREET"] = $configuration["value"];
				}
				if($configuration["setting"] == "RegistrarAdminCity"){
					$values["CITY"] = $configuration["value"];
				}
				if($configuration["setting"] == "RegistrarAdminStateProvince"){
					$values["STATE"] = $configuration["value"];
				}
				if($configuration["setting"] == "RegistrarAdminPostalCode"){
					$values["ZIP"] = $configuration["value"];
				}
				if($configuration["setting"] == "RegistrarAdminCountry"){
					$values["COUNTRY"] = $configuration["value"];
				}
				if($configuration["setting"] == "RegistrarAdminPhone"){
					$values["PHONE"] = $configuration["value"];
				}
				if($configuration["setting"] == "RegistrarAdminFax"){
					$values["FAX"] = $configuration["value"];
				}
				if($configuration["setting"] == "RegistrarAdminEmailAddress"){
					$values["EMAIL"] = $configuration["value"];
				}
			}
		}
		return $values;
	}

	$stmt = $pdo->prepare("SELECT * FROM backorder_domains WHERE status=?");
	$stmt->execute(array("PROCESSING"));
	$locals = $stmt->fetchAll(PDO::FETCH_ASSOC);

	foreach ($locals as $local) {
		if($local["reference"] == ""){
			//SEND APPLICATION TO THE BACKEND
			$command = array(
			 	"COMMAND" => "AddDomainApplication",
				"NEW" => 1,
				//"INCOMPLETE" => 1,
				"CLASS" => "BACKORDER", //strtoupper($local["tld"])."_BACKORDER",
				"DOMAIN" => $local["domain"].".".$local["tld"],
				"OWNERCONTACT0" => get_contact_details("USER", $local["userid"]),
				"ADMINCONTACT0" => get_contact_details("USER", $local["userid"]),
				"TECHCONTACT0" => get_contact_details("USER", $local["userid"]),
				"BILLINGCONTACT0" => get_contact_details("USER", $local["userid"]) //get_contact_details("SYSTEM")
			);
			$backorder = ispapi_api_call($command);

			if($backorder["CODE"] == 200){
				$update_stmt = $pdo->prepare("UPDATE backorder_domains SET reference=?, updateddate=? WHERE id=?");
				$update_stmt->execute(array($backorder["PROPERTY"]["APPLICATION"][0], date("Y-m-d H:i:s"), $local["id"]));

				if($update_stmt->rowCount() != 0){
					$message = "BACKORDER APPLICATION ".$local["domain"].".".$local["tld"]." SENT TO HEXONET (reference=".$backorder["PROPERTY"]["APPLICATION"][0].")";
					logmessage($cronname, "ok", $message);
				}
			}else{
				$message = "ERROR SENDING BACKORDER APPLICATION ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].", userid=".$local["userid"].") TO HEXONET: ".$backorder["DESCRIPTION"];
				logmessage($cronname, "error", $message);
			}
		}
	}

	//logmessage($cronname, "ok", "$cronname done");
	echo date("Y-m-d H:i:s")." $cronname done.\n";
} catch (\Exception $e) {
   logmessage($cronname, "DB error", $e->getMessage());
   return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}

?>
