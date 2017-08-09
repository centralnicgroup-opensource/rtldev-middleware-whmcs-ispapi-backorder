<?php // $command, $userid
use WHMCS\Database\Capsule;
try {
	//GET DPO CONNECTION
	$pdo = Capsule::connection()->getPdo();

	if ( !$userid )	return backorder_api_response(531);
	$r = backorder_api_response(200);

	$result = $pdo->prepare("SHOW COLUMNS FROM backorder_domains LIKE 'status'");
	$result->execute();
	$data = $result->fetchAll(PDO::FETCH_ASSOC);

	foreach ($data as $key => $value) {
		preg_match_all('~\'([^\']*)\'~', $value['Type'], $matches);
	}

	foreach($matches[1] as $status)
	{
		$r["PROPERTY"][$status]['status'] = $status;
		$r["PROPERTY"][$status]["anzahl"] = 0;
	}
	$r["total"] += 0;

	$condition = array("userid" => $userid);
	$result=$pdo->prepare("SELECT count(*) as anzahl, status FROM backorder_domains WHERE userid=? GROUP BY status");
	$result->execute(array($userid));
	$data = $result->fetchAll(PDO::FETCH_ASSOC);

	foreach ($data as $key => $value) {
		$r["PROPERTY"][$value["status"]]['status'] = $value["status"];
		$r["PROPERTY"][$value["status"]]["anzahl"] = $value["anzahl"];
		$r["total"] += $value["anzahl"];
	}

	return $r;
} catch (\Exception $e) {
   logmessage("command.QueryBackorderOverviewStatus", "DB error", $e->getMessage());
   return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}

?>
