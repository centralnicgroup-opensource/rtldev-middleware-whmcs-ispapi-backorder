<?php // $command, $userid

$currencyid=NULL;
$result = select_query('tblclients','currency',array("id" => $userid ));
$data = mysql_fetch_assoc($result);
$currencyid= $data["currency"];
if($currencyid==NULL){
	return backorder_api_response(541, "PRICELIST - USER CURRENCY ERROR");
}

$currency=NULL;
$result = select_query('tblcurrencies','*',array("id" => $currencyid ));
$currency = mysql_fetch_assoc($result);
if ($currency==NULL){
	return backorder_api_response(541, "PRICELIST - CURRENCY ERROR");
}

$r = backorder_api_response(200);
$params = array("currency_id" => $currencyid);

if(isset($command["TLD"])){
	$params["extension"] = $command["TLD"];
}

$result = select_query('backorder_pricing','*',$params);
while ( $data = mysql_fetch_assoc($result) ) {
	if(!empty($data["fullprice"])){ //USE || $data["fullprice"]=="0" IF FREE BACKORDER ARE ALLOWED TO BE DISPLAYED
		$r["PROPERTY"][$data["extension"]]["PRICEFULL"] = $data["fullprice"];
		$r["PROPERTY"][$data["extension"]]["PRICEFULL_FORMATED"] = formatPrice($data["fullprice"], $currency);
		$r["PROPERTY"][$data["extension"]]["CURRENCYSUFFIX"] = $currency["suffix"];
		$r["PROPERTY"][$data["extension"]]["CURRENCY"] = $currency["code"];
		$r["PROPERTY"][$data["extension"]]["TLD"] = $data["extension"];
	}
}
return $r;
?>
