<?php
date_default_timezone_set('UTC');
$cronname = "DAILY_DROPDATE_SYNCH";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";
use WHMCS\Database\Capsule;
try{
	//GET PDO CONNECTION
	$pdo = Capsule::connection()->getPdo();

	$result=$pdo->prepare("SELECT * FROM backorder_domains ");
	$result->execute();
	$local = $result->fetchAll(PDO::FETCH_ASSOC);

	foreach ($local as $key => $value) {
		$stmt = $pdo->prepare("SELECT domain, zone, drop_date FROM pending_domains WHERE domain=? and zone=? limit 1");
   		$stmt->execute(array($value["domain"],$value["tld"]));
   		$online = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach ($online as $ky => $val) {
			if($value["dropdate"] != $val["drop_date"]) {
				$old_dropdate = $value["dropdate"];
				$new_dropdate = $val["drop_date"];

				$update=$pdo->prepare("UPDATE backorder_domains SET dropdate=?, updateddate=? WHERE domain=? AND tld=?");
				$update->execute(array($val["drop_date"], date("Y-m-d H:i:s"), $value["domain"], $value["tld"]));
				$affected_rows = $update->rowCount();
				if($affected_rows != 0){
					$message = "DROPDATE OF BACKORDER ".$value["domain"].".".$value["tld"]." (backorderid=".$value["id"].") SYNCHRONIZED ($old_dropdate => $new_dropdate)";
					logmessage($cronname, "ok", $message);
				}
			}
		}
	}

	logmessage($cronname, "ok", "DAILY_DROPDATE_SYNCH done");
	echo date("Y-m-d H:i:s")." DAILY_DROPDATE_SYNCH done.\n";
} catch (\Exception $e) {
   logmessage("daily_dropdate_synch", "DB error", $e->getMessage());
   return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}


?>
