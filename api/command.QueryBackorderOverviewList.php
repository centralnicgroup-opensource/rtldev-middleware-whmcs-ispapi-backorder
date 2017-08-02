<?php // $command, $userid
use WHMCS\Database\Capsule;
try {
	//GET DPO CONNECTION
	$pdo = Capsule::connection()->getPdo();

	if ( !$userid )	return backorder_api_response(531);
	$r = backorder_api_response(200);

	$currencyid=NULL;

	//##################
	$result=$pdo->prepare("SELECT currency FROM tblclients WHERE id=?");
	$result->execute(array($userid));
	$data = $result->fetchAll(PDO::FETCH_ASSOC);
	$data = $data[0];
	//###############

	if ( $data ) {
		$currencyid= $data["currency"];
	}

	if ( $currencyid==NULL ) return backorder_api_response(541, "PRICELIST - USER CURRENCY ERROR");

	$currency=NULL;

	//####################################
	$result = $pdo->prepare("SELECT * FROM tblcurrencies WHERE id=?");
	$result->execute(array($userid));
	$data = $result->fetchAll(PDO::FETCH_ASSOC);
	$data = $data[0];
	//###################################
	if ( $data ) {
		$currency=$data;
	}

	if ( $currency==NULL ) return backorder_api_response(541, "PRICELIST - CURRENCY ERROR");

	$r = backorder_api_response(200);
	$price=NULL;

	$result = $pdo->prepare("SELECT * FROM backorder_pricing WHERE currency_id=?");
	$result->execute(array($currencyid));
	$data = $result->fetchAll(PDO::FETCH_ASSOC);
	// echo "<pre> data 2 \n"; print_r($data); echo "</pre>";
	foreach ($data as $key => $value) {
			$r["PROPERTY"][$value["extension"]]["tld"] = $value["extension"];
			$r["PROPERTY"][$value["extension"]]["LITE"] = 0;
			$r["PROPERTY"][$value["extension"]]["FULL"] = 0;
			$r["PROPERTY"][$value["extension"]]["total"] = 0;
	}

	$condition = array("userid" => $userid);
	############################
	$result = $pdo->prepare("SELECT count(*) as anzahl, tld, type FROM backorder_domains WHERE userid=? GROUP BY tld, type");
	$result->execute(array($userid));
	$data = $result->fetchAll(PDO::FETCH_ASSOC);
	foreach ($data as $key => $value) {
			$r["PROPERTY"][ $value["tld"] ][ $value["type"] ] = $value["anzahl"];
			$r["PROPERTY"][ $value["tld"] ]["total"] += $value["anzahl"];
	}
	############################


	return $r;

} catch (\Exception $e) {
   logmessage("command.CreateBackorder", "DB error", $e->getMessage());
   return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}

?>
