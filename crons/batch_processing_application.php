<?php

date_default_timezone_set('UTC');
$cronname = "BATCH_PROCESSING_APPLICATION";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";

use WHMCS\Database\Capsule;
try{
	//GET PDO CONNECTION
	$pdo = Capsule::connection()->getPdo();

	function get_contact_details($type="USER", $userid = NULL){
		global $pdo;
		//GET ADMIN USERNAME
		$rquery=$pdo->prepare("SELECT value FROM tbladdonmodules WHERE module=? AND setting=? ");
		$rquery->execute(array("ispapibackorder", "username"));
		$r = $rquery->fetch(PDO::FETCH_ASSOC);
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
			$result=$pdo->prepare("SELECT * FROM tblconfiguration");
			$result->execute();
			$configuration = $result->fetchAll(PDO::FETCH_ASSOC);

			foreach ($configuration as $key => $value) {
				if($value["setting"] == "RegistrarAdminFirstName"){
					$values["FIRSTNAME"] = $value["value"];
				}
				if($value["setting"] == "RegistrarAdminLastName"){
					$values["LASTNAME"] = $value["value"];
				}
				if($value["setting"] == "RegistrarAdminCompanyName"){
					$values["ORGANIZATION"] = $value["value"];
				}
				if($value["setting"] == "RegistrarAdminAddress1"){
					$values["STREET"] = $value["value"];
				}
				if($value["setting"] == "RegistrarAdminCity"){
					$values["CITY"] = $value["value"];
				}
				if($value["setting"] == "RegistrarAdminStateProvince"){
					$values["STATE"] = $value["value"];
				}
				if($value["setting"] == "RegistrarAdminPostalCode"){
					$values["ZIP"] = $value["value"];
				}
				if($value["setting"] == "RegistrarAdminCountry"){
					$values["COUNTRY"] = $value["value"];
				}
				if($value["setting"] == "RegistrarAdminPhone"){
					$values["PHONE"] = $value["value"];
				}
				if($value["setting"] == "RegistrarAdminFax"){
					$values["FAX"] = $value["value"];
				}
				if($value["setting"] == "RegistrarAdminEmailAddress"){
					$values["EMAIL"] = $value["value"];
				}
			}
		}
		return $values;
	}

	$result=$pdo->prepare("SELECT * FROM backorder_domains WHERE status=?");
	$result->execute(array("PROCESSING"));
	$local = $result->fetchAll(PDO::FETCH_ASSOC);

	foreach ($local as $key => $value) {
		if($value["reference"] == ""){

			//SEND APPLICATION TO THE BACKEND
			$command = array(
			 	"COMMAND" => "AddDomainApplication",
				"NEW" => 1,
				//"INCOMPLETE" => 1,
				"CLASS" => "BACKORDER", //strtoupper($local["tld"])."_BACKORDER",
				"DOMAIN" => $value["domain"].".".$value["tld"],
				"OWNERCONTACT0" => get_contact_details("USER", $value["userid"]),
				"ADMINCONTACT0" => get_contact_details("USER", $value["userid"]),
				"TECHCONTACT0" => get_contact_details("USER", $value["userid"]),
				"BILLINGCONTACT0" => get_contact_details("USER", $value["userid"]) //get_contact_details("SYSTEM")
			);

			$backorder = ispapi_api_call($command);

			if($backorder["CODE"] == 200){
				$update=$pdo->prepare("UPDATE backorder_domains SET reference=?, updateddate=? WHERE id=?");
				$update->execute(array($backorder["PROPERTY"]["APPLICATION"][0], date("Y-m-d H:i:s"), $value["id"]));
				$affected_rows = $update->rowCount();

				if($affected_rows != 0){
					$message = "BACKORDER APPLICATION ".$value["domain"].".".$value["tld"]." SENT TO HEXONET (reference=".$backorder["PROPERTY"]["APPLICATION"][0].")";
					logmessage($cronname, "ok", $message);
				}

			}else{
				$message = "ERROR SENDING BACKORDER APPLICATION ".$value["domain"].".".$value["tld"]." (backorderid=".$value["id"].", userid=".$value["userid"].") TO HEXONET: ".$backorder["DESCRIPTION"];
				logmessage($cronname, "error", $message);
			}
		}
	}

	//logmessage($cronname, "ok", "BATCH_PROCESSING_APPLICATION done");
	echo date("Y-m-d H:i:s")." BATCH_PROCESSING_APPLICATION done.\n";

} catch (\Exception $e) {
   logmessage("batch_processing_application", "DB error", $e->getMessage());
   return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}

?>
