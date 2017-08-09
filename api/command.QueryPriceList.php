<?php // $command, $userid
use WHMCS\Database\Capsule;
try {
	//GET DPO CONNECTION
	$pdo = Capsule::connection()->getPdo();

	$currencyid=NULL;
	$result = $pdo->prepare("SELECT currency FROM tblclients WHERE id=?");
	$result->execute(array($userid));
    $data = $result->fetch(PDO::FETCH_ASSOC);
	$currencyid= $data["currency"];
	if($currencyid==NULL){
		return backorder_api_response(541, "PRICELIST - USER CURRENCY ERROR");
	}

	$currency=NULL;

	$result = $pdo->prepare("SELECT * FROM tblcurrencies WHERE id=?");
	$result->execute(array($currencyid));
    $currency = $result->fetchAll(PDO::FETCH_ASSOC);
	$currency = $currency[0];

	if ($currency==NULL){
		return backorder_api_response(541, "PRICELIST - CURRENCY ERROR");
	}

	$r = backorder_api_response(200);
	$params = array("currency_id" => $currencyid);
	if(isset($command["TLD"])){
		$params["extension"] = $command["TLD"];
	}

	if($params["extension"]){
		$result=$pdo->prepare("SELECT * FROM backorder_pricing WHERE currency_id=? AND extension=? ");
		$result->execute(array($params["currency_id"], $params["extension"]));
		$data = $result->fetchAll(PDO::FETCH_ASSOC);
		foreach ($data as $key => $value) {
			if(!empty($value["fullprice"])){ //USE || $data["fullprice"]=="0" IF FREE BACKORDER ARE ALLOWED TO BE DISPLAYED
				$r["PROPERTY"][$value["extension"]]["PRICEFULL"] = $value["fullprice"];
				$r["PROPERTY"][$value["extension"]]["PRICEFULL_FORMATED"] = formatPrice($value["fullprice"], $currency);
				$r["PROPERTY"][$value["extension"]]["CURRENCYSUFFIX"] = $currency["suffix"];
				$r["PROPERTY"][$value["extension"]]["CURRENCY"] = $currency["code"];
				$r["PROPERTY"][$value["extension"]]["TLD"] = $value["extension"];
			}
		}
	} else {
		$result=$pdo->prepare("SELECT * FROM backorder_pricing WHERE currency_id=?");
		$result->execute(array($params["currency_id"]));
		$data = $result->fetchAll(PDO::FETCH_ASSOC);
		foreach ($data as $key => $value) {
			if(!empty($value["fullprice"])){ //USE || $data["fullprice"]=="0" IF FREE BACKORDER ARE ALLOWED TO BE DISPLAYED
				$r["PROPERTY"][$value["extension"]]["PRICEFULL"] = $value["fullprice"];
				$r["PROPERTY"][$value["extension"]]["PRICEFULL_FORMATED"] = formatPrice($value["fullprice"], $currency);
				$r["PROPERTY"][$value["extension"]]["CURRENCYSUFFIX"] = $currency["suffix"];
				$r["PROPERTY"][$value["extension"]]["CURRENCY"] = $currency["code"];
				$r["PROPERTY"][$value["extension"]]["TLD"] = $value["extension"];
			}
		}
	}

	return $r;
} catch (\Exception $e) {
   logmessage("command.QueryLogList", "DB error", $e->getMessage());
   return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}

?>
