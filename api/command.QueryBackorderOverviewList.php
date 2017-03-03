<?php // $command, $userid

if ( !$userid )	return backorder_api_response(531);
$r = backorder_api_response(200);

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
$result = select_query('backorder_pricing','*',array("currency_id" => $currencyid ));
while ( $data = mysql_fetch_assoc($result) ) {
	$r["PROPERTY"][$data["extension"]]["tld"] = $data["extension"];
	$r["PROPERTY"][$data["extension"]]["LITE"] = 0;
	$r["PROPERTY"][$data["extension"]]["FULL"] = 0;
	$r["PROPERTY"][$data["extension"]]["total"] = 0;
}

$condition = array("userid" => $userid);
$result = full_query('SELECT count(*) as anzahl, tld, type FROM  `backorder_domains` WHERE `userid` ='.$userid.' GROUP BY tld, type ');

while ($data = mysql_fetch_assoc($result)) {
	$r["PROPERTY"][ $data["tld"] ][ $data["type"] ] = $data["anzahl"];
	$r["PROPERTY"][ $data["tld"] ]["total"] += $data["anzahl"];
}
return $r;

?>
