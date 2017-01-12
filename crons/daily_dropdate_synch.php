<?php
$cronname = "DAILY_DROPDATE_SYNCH";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";

$result = select_query('backorder_domains','*', array());
while ($local = mysql_fetch_array($result)) {
	$stmt = full_query("SELECT domain, zone, drop_date FROM pending_domains WHERE domain='".$local["domain"]."' and zone='".$local["tld"]."' limit 1");
	while ($online = mysql_fetch_array($stmt)) {
		//synch dropdate with drop-list
		if($local["dropdate"] != $online["drop_date"]) {
			$old_dropdate = $local["dropdate"];
			$new_dropdate = $online["drop_date"];
			if(update_query('backorder_domains',array("dropdate" => $online["drop_date"], "updateddate" => date("Y-m-d H:i:s")) , array("domain" => $local["domain"], "tld" => $local["tld"]))){
				$message = "DROPDATE OF BACKORDER ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].") SYNCHRONIZED ($old_dropdate => $new_dropdate)";
				logmessage($cronname, "ok", $message);
			}

		}
	}
}

logmessage($cronname, "ok", "DAILY_DROPDATE_SYNCH done");
echo date("Y-m-d H:i:s")." DAILY_DROPDATE_SYNCH done.\n";

?>
