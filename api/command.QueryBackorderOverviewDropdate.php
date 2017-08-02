<?php // $command, $userid
use WHMCS\Database\Capsule;
try {
	//GET DPO CONNECTION
	$pdo = Capsule::connection()->getPdo();

	if ( !$userid )	return backorder_api_response(531);
	$r = backorder_api_response(200);

	for($i=1; $i<=31; $i++)
	{
		$datetime = strtotime("today +".$i."day");
		$date = date("Y-m-d",$datetime);
		$r["PROPERTY"][$date]['day'] = $date;
		$r["PROPERTY"][$date]['datetime'] = strtotime("today +".$i."day");
		$r["PROPERTY"][$date]["anzahl"] = 0;
		$r["PROPERTY"][$date]["anzahlFULL"] = 0;
		$r["PROPERTY"][$date]["anzahlLITE"] = 0;
	}

	$condition = array("userid" => $userid);

	//#########################################
	$result=$pdo->prepare("SELECT DATE(dropdate) AS dropdateday, type, COUNT( * ) AS anzahl
	 					FROM  backorder_domains
	 					WHERE DATE(dropdate)!=?
						AND DATE(dropdate)>?
						AND userid=?
						GROUP BY DATE(dropdate),type");
	$result->execute(array('0000-00-00', date("Y-m-d"), $userid));
	$data = $result->fetchAll(PDO::FETCH_ASSOC);
	foreach ($data as $key => $value) {
		$r["PROPERTY"][$value["dropdateday"]]["anzahl"] += $value["anzahl"];
		$r["PROPERTY"][$value["dropdateday"]]["anzahl".$value["type"]] = $value["anzahl"];
	}
	//##########################################

	return $r;

} catch (\Exception $e) {
   logmessage("command.CreateBackorder", "DB error", $e->getMessage());
   return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}


?>
