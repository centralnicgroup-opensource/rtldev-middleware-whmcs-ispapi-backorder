<?php
date_default_timezone_set('UTC');
$cronname = "BATCH_ACTIVE_PROCESSING";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";

use WHMCS\Database\Capsule;

try{
	//GET DPO CONNECTION
	$pdo = Capsule::connection()->getPdo();

   	$result=$pdo->prepare("SELECT * FROM backorder_domains WHERE status=?");
   	$result->execute(array("ACTIVE"));
   	$local = $result->fetchAll(PDO::FETCH_ASSOC);

   	foreach ($local as $key => $value) {
		$today = new DateTime(date("Y-m-d H:i:s"));
		$dropdate = new DateTime($value["dropdate"]);

		$diff_timestamp = $dropdate->getTimestamp() - $today->getTimestamp();
		//CHECK IF TIMESTAMP >=0 AND <= 7200 (2 HOURS)
		if($diff_timestamp >=0 && $diff_timestamp <=  7200){
			//CHANGE STATUS FROM ACTIVE TO PROCESSING
			$result=$pdo->prepare("UPDATE backorder_domains SET status=?, updateddate=? WHERE id=?");
			$result->execute(array("PROCESSING", date("Y-m-d H:i:s"), $value["id"]));
			$affected_rows = $result->rowCount();

			if($affected_rows != 0){
				$message = "BACKORDER ".$value["domain"].".".$value["tld"]." (backorderid=".$value["id"].") set from ACTIVE to PROCESSING";
 				logmessage($cronname, "ok", $message);
    		}
 		}
	}
	logmessage($cronname, "ok", "BATCH_ACTIVE_PROCESSING done");
	echo date("Y-m-d H:i:s")." BATCH_ACTIVE_PROCESSING done.\n";

} catch (\Exception $e) {
   logmessage("batch_active_processing", "DB error", $e->getMessage());
   return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}

?>
