<?php // $command, $userid - DM 30.07.2015 / MODIFIED SA 17.08.2015

$currencyid=NULL;
$result = select_query('tblclients','currency',array("id" => $userid ));
$data = mysql_fetch_assoc($result);
if ( $data ) {
	$currencyid= $data["currency"];
}
if ( $currencyid==NULL ) return backorder_api_response(541, "PRICELIST - USER CURRENCY ERROR");


$currency=NULL;
$result = select_query('tblcurrencies','*',array("id" => $currencyid ));
$data = mysql_fetch_assoc($result);
if ( $data ) {
	$currency= $data;
}
if ( $currency==NULL ) return backorder_api_response(541, "PRICELIST - CURRENCY ERROR");


$r = backorder_api_response(200);
$price=NULL;

$params = array("currency_id" => $currencyid);

if(isset($command["TLD"])){
	$params["extension"] = $command["TLD"];
}

$result = select_query('backorder_pricing','*',$params);
while ( $data = mysql_fetch_assoc($result) ) {
	$r["PROPERTY"][$data["extension"]]["PRICELITE"] = $data["liteprice"];
	$r["PROPERTY"][$data["extension"]]["PRICEFULL"] = $data["fullprice"];
	$r["PROPERTY"][$data["extension"]]["CURRENCYSUFFIX"] = $currency["suffix"];
	$r["PROPERTY"][$data["extension"]]["CURRENCY"] = $currency["code"];
	$r["PROPERTY"][$data["extension"]]["TLD"] = $data["extension"];
}
return $r;
?>
