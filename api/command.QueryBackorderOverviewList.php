<?php // $command, $userid
use WHMCS\Database\Capsule;
if ( !$userid )	return backorder_api_response(531);
$r = backorder_api_response(200);

$currencyid=NULL;

// $result = select_query('tblclients','currency',array("id" => $userid ));

//tulsi
$result = Capsule::table('tblclients')->select('currency')
					->where('id', $userid)
					->get();

// $result = Capsule::table('tblclients')
// 			->where('currency')
// 			->where('id', $userid);
// $result = (array)$result;
// echo "> <pre>";
// print_r($result);
// echo "< </pre>";

$data = mysql_fetch_assoc($result);
echo "> data <pre>";
print_r($data);
echo "< </pre>";
if ( $data ) {
	$currencyid= $data["currency"];

	echo "> currency id <pre>";
	print_r($currencyid);
	echo "< </pre>";



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
