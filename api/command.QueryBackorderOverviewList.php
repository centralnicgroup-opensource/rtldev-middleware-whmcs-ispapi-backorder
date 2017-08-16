<?php

use WHMCS\Database\Capsule;
try{
	$pdo = Capsule::connection()->getPdo();

	if(!$userid) return backorder_api_response(531);

	$r = backorder_api_response(200);

	$currencyid=NULL;

	$stmt = $pdo->prepare("SELECT currency FROM tblclients WHERE id=?");
	$stmt->execute(array($userid));
	$data = $stmt->fetch(PDO::FETCH_ASSOC);
	if($data){
		$currencyid= $data["currency"];
	}
	if($currencyid==NULL) return backorder_api_response(541, "PRICELIST - USER CURRENCY ERROR");

	$currency=NULL;

	$stmt = $pdo->prepare("SELECT * FROM tblcurrencies WHERE id=?");
	$stmt->execute(array($userid));
	$data = $stmt->fetch(PDO::FETCH_ASSOC);
	if($data){
		$currency=$data;
	}

	if($currency==NULL) return backorder_api_response(541, "PRICELIST - CURRENCY ERROR");

	$price=NULL;

	$stmt = $pdo->prepare("SELECT * FROM backorder_pricing WHERE currency_id=?");
	$stmt->execute(array($currencyid));
	$pricings = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($pricings as $pricing) {
		$r["PROPERTY"][$pricing["extension"]]["tld"] = $pricing["extension"];
		$r["PROPERTY"][$pricing["extension"]]["LITE"] = 0;
		$r["PROPERTY"][$pricing["extension"]]["FULL"] = 0;
		$r["PROPERTY"][$pricing["extension"]]["total"] = 0;
	}

	$condition = array("userid" => $userid);
	$stmt = $pdo->prepare("SELECT count(*) as anzahl, tld, type FROM backorder_domains WHERE userid=? GROUP BY tld, type");
	$stmt->execute(array($userid));
	$backorders = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($backorders as $backorder) {
		$r["PROPERTY"][$backorder["tld"]][$backorder["type"]] = $backorder["anzahl"];
		$r["PROPERTY"][$backorder["tld"]]["total"] += $backorder["anzahl"];
	}

	return $r;
}catch(\Exception $e){
   logmessage("command.QueryBackorderOverviewList", "DB error", $e->getMessage());
   return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}

?>
