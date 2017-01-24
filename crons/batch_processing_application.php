<?php
$cronname = "BATCH_PROCESSING_APPLICATION";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";


function get_contact_details($type="USER", $userid = NULL){
	//GET ADMIN USERNAME
	$r = mysql_fetch_array(full_query("SELECT value FROM tbladdonmodules WHERE module='ispapibackorder' and setting='username'"));
	$adminuser = $r["value"];
	if(empty($adminuser)){
		$message = "MISSING ADMIN USERNAME IN MODULE CONFIGURATION";
		logmessage($cronname, "error", $message);
	}

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
		$result = select_query('tblconfiguration','*');
		while ($configuration = mysql_fetch_array($result)) {
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

$result = select_query('backorder_domains','*', array("status" => "PROCESSING"));
while ($local = mysql_fetch_array($result)) {
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
			"BILLINGCONTACT0" => get_contact_details("USER", $local["userid"])//get_contact_details("SYSTEM")
		);

		$backorder = ispapi_api_call($command);
		if($backorder["CODE"] == 200){
			//SET BACKORDER REFERENCE
			if(update_query('backorder_domains',array("reference" => $backorder["PROPERTY"]["APPLICATION"][0], "updateddate" => date("Y-m-d H:i:s")) , array("id" => $local["id"]))){
				$message = "BACKORDER APPLICATION ".$local["domain"].".".$local["tld"]." SENT TO HEXONET (reference=".$backorder["PROPERTY"]["APPLICATION"][0].")";
				logmessage($cronname, "ok", $message);
			}
		}else{
			$message = "ERROR SENDING BACKORDER APPLICATION ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].", userid=".$local["userid"].") TO HEXONET: ".$backorder["DESCRIPTION"];
			logmessage($cronname, "error", $message);
		}
	}
}

//logmessage($cronname, "ok", "BATCH_PROCESSING_APPLICATION done");
echo date("Y-m-d H:i:s")." BATCH_PROCESSING_APPLICATION done.\n";
?>
