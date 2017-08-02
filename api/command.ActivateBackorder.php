<?php // $command, $userid
 use WHMCS\Database\Capsule;
 try {
	 //GET DPO CONNECTION
    $pdo = Capsule::connection()->getPdo();

	if ( !$userid )	return backorder_api_response(531, "AUTHORIZATION FAILED");

	if ( !isset($command["DOMAIN"]) || !strlen($command["DOMAIN"]) )
		return backorder_api_response(504, "DOMAIN");

	if ( !preg_match('/^(.*)\.(.*)$/', $command["DOMAIN"], $m) )
		return backorder_api_response(505, "DOMAIN");
	//
	$result = $pdo->prepare("SELECT * FROM backorder_domains WHERE userid=? AND domain=? AND tld=?");
	$result->execute(array($userid, $m[1], $m[2]));
	$rows = $result->fetchAll(PDO::FETCH_ASSOC);
	$rows = $rows[0];
	if (!($rows)) {
		return backorder_api_response(545, "DOMAIN");
	}else{
			if($rows["status"] != "REQUESTED"){
				return backorder_api_response(549, "ONLY BACKORDER WITH STATUS REQUESTED CAN BE ACTIVATED");
			}
	}
	
	$stmt = $pdo->prepare("UPDATE backorder_domains SET status=?, updateddate=? WHERE id=?");
	$stmt->execute(array("ACTIVE", date("Y-m-d H:i:s"), $rows['id']));
	// $affected_rows = $stmt->rowCount();
 //   	echo "Affected rows: ".$affected_rows;
	//

	return backorder_api_response(200);

} catch (\Exception $e) {
	logmessage("command.CreateBackorder", "DB error", $e->getMessage());
	return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}

?>
