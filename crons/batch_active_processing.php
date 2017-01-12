<?php
$cronname = "BATCH_ACTIVE_PROCESSING";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";

$result = select_query('backorder_domains','*', array("status" => "ACTIVE"));
while ($local = mysql_fetch_array($result)) {
	$today = new DateTime(date("Y-m-d H:i:s"));
	$dropdate = new DateTime($local["dropdate"]);

	$diff_timestamp = $dropdate->getTimestamp() - $today->getTimestamp();

	//CHECK IF TIMESTAMP >=0 AND <= 7200 (2 HOURS)
	if($diff_timestamp >=0 && $diff_timestamp <= 7200){
		//CHANGE STATUS FROM ACTIVE TO PROCESSING
		if(update_query('backorder_domains',array("status" => "PROCESSING", "updateddate" => date("Y-m-d H:i:s")) , array("id" => $local["id"]))){
			$message = "BACKORDER ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].") set from ACTIVE to PROCESSING";
			logmessage($cronname, "ok", $message);
		}

	}
}

logmessage($cronname, "ok", "BATCH_ACTIVE_PROCESSING done");
echo date("Y-m-d H:i:s")." BATCH_ACTIVE_PROCESSING done.\n";
?>
