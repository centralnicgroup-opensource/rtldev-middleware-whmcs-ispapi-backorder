<?php // $command, $userid
use WHMCS\Database\Capsule;

if ( !$userid )	return backorder_api_response(531);
$r = backorder_api_response(200);

$currencyid=NULL;

//ori
// $result = select_query('tblclients','currency',array("id" => $userid ));



//tulsi
$result = Capsule::select('SELECT currency from tblclients where id = ?', [$userid]);
// //
$arrays = array();
foreach($result as $object)
{
    $arrays =  (array) $object;
}

// echo "<pre>"; print_r($arrays); echo "</pre>";
//tulsi end

// ori
// $data = mysql_fetch_assoc($result);
// if ( $data ) {
// 	$currencyid= $data["currency"];
//
// }

//tulsi
if ($arrays) {
	$currencyid = $arrays["currency"];
}
//end tulsi

if ( $currencyid==NULL ) return backorder_api_response(541, "PRICELIST - USER CURRENCY ERROR");

$currency=NULL;
//ori
// $result = select_query('tblcurrencies','*',array("id" => $currencyid ));

//tulsi
$result = Capsule::table('tblcurrencies')->where('id', $currencyid)->first();
$result = (array)$result;
// // echo "<pre>"; print_r($result); echo "</pre>";
if($result){
	$currency= $result;
}
//end tulsi

//ori
// $data = mysql_fetch_assoc($result);
// if ( $data ) {
// 	$currency= $data;
// }
if ( $currency==NULL ) return backorder_api_response(541, "PRICELIST - CURRENCY ERROR");

$r = backorder_api_response(200);
$price=NULL;
//ori
$result = select_query('backorder_pricing','*',array("currency_id" => $currencyid ));

//tulsi
// $result1 = Capsule::table('backorder_pricing')->where('currency_id', $currencyid)->get();
//
// $result = array();
// foreach($result1 as $object)
// {
//     $result[] =  (array) $object;
// }
// echo "<pre>"; print_r($result); echo "</pre>";
//end tulsi

//ori
// while ( $data = mysql_fetch_assoc($result) ) {
//     echo " <pre>"; print_r($data); echo "</pre>";
// 	$r["PROPERTY"][$data["extension"]]["tld"] = $data["extension"];
// 	$r["PROPERTY"][$data["extension"]]["LITE"] = 0;
// 	$r["PROPERTY"][$data["extension"]]["FULL"] = 0;
// 	$r["PROPERTY"][$data["extension"]]["total"] = 0;
// }

//tulsi
// while($result){
//     	$r["PROPERTY"][$result["extension"]]["tld"] = $result["extension"];
//     	$r["PROPERTY"][$result["extension"]]["LITE"] = 0;
//     	$r["PROPERTY"][$result["extension"]]["FULL"] = 0;
//     	$r["PROPERTY"][$result["extension"]]["total"] = 0;
// }
//end tulsi

//ori
$condition = array("userid" => $userid);
$result = full_query('SELECT count(*) as anzahl, tld, type FROM  `backorder_domains` WHERE `userid` ='.$userid.' GROUP BY tld, type ');

//tulsi
// $result = Capsule::select('SELECT count(*) as anzahl, tld, type FROM  `backorder_domains` WHERE `userid` = userid GROUP BY tld, type' , ['userid' => $userid] );


while ($data = mysql_fetch_assoc($result)) {
	$r["PROPERTY"][ $data["tld"] ][ $data["type"] ] = $data["anzahl"];
	$r["PROPERTY"][ $data["tld"] ]["total"] += $data["anzahl"];
}
return $r;

?>
