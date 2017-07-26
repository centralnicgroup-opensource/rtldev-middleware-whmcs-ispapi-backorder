<?php // $command, $userid
use WHMCS\Database\Capsule;

if ( !$userid )	return backorder_api_response(531);
$r = backorder_api_response(200);

$currencyid=NULL;

//ori
// $result = select_query('tblclients','currency',array("id" => $userid ));


//T
$result = Capsule::select('SELECT currency from tblclients where id = ?', [$userid]);
// //
$arrays = array();
foreach($result as $object)
{
    $arrays =  (array) $object;
}

// echo "<pre>"; print_r($arrays); echo "</pre>";
//T end

// ori
// $data = mysql_fetch_assoc($result);
// if ( $data ) {
// 	$currencyid= $data["currency"];
//
// }

//T
if ($arrays) {
	$currencyid = $arrays["currency"];
}
//end T

if ( $currencyid==NULL ) return backorder_api_response(541, "PRICELIST - USER CURRENCY ERROR");

$currency=NULL;
//ori
// $result = select_query('tblcurrencies','*',array("id" => $currencyid ));

//T
$result = Capsule::table('tblcurrencies')->where('id', $currencyid)->first();

$result = (array)$result;
// echo "<pre>"; print_r($result); echo "</pre>";
if($result){
	$currency= $result;
}
// echo "<pre> > "; print_r($currency); echo "< </pre>";
//end T

// ori
// $data = mysql_fetch_assoc($result);
// if ( $data ) {
// 	$currency= $data;
// }


if ( $currency==NULL ) return backorder_api_response(541, "PRICELIST - CURRENCY ERROR");

$r = backorder_api_response(200);
$price=NULL;

//ori
// $result = select_query('backorder_pricing','*',array("currency_id" => $currencyid ));

//T
$result1 = Capsule::table('backorder_pricing')->where('currency_id', $currencyid)->get();
//
$result = array();
foreach($result1 as $object)
{
    $result[] =  (array) $object;
}
// $result = (array)$result;
// echo "<pre>"; print_r($result); echo "</pre>";
// //end T


//ori
// while ( $data = mysql_fetch_assoc($result) ) {
	// $r["PROPERTY"][$data["extension"]]["tld"] = $data["extension"];
	// $r["PROPERTY"][$data["extension"]]["LITE"] = 0;
	// $r["PROPERTY"][$data["extension"]]["FULL"] = 0;
	// $r["PROPERTY"][$data["extension"]]["total"] = 0;
// }

//T
foreach($result as $key=> $value){
    // print $value["extension"]."\n";
    $r["PROPERTY"][$value["extension"]]["tld"] = $value["extension"];
	$r["PROPERTY"][$value["extension"]]["LITE"] = 0;
	$r["PROPERTY"][$value["extension"]]["FULL"] = 0;
	$r["PROPERTY"][$value["extension"]]["total"] = 0;
}
//end T

//ori
$condition = array("userid" => $userid);
// $result = full_query('SELECT count(*) as anzahl, tld, type FROM  `backorder_domains` WHERE `userid` ='.$userid.' GROUP BY tld, type ');

// while ($data = mysql_fetch_assoc($result)) {
// 	$r["PROPERTY"][ $data["tld"] ][ $data["type"] ] = $data["anzahl"];
// 	$r["PROPERTY"][ $data["tld"] ]["total"] += $data["anzahl"];
// }

//T
// $result = Capsule::select('SELECT count(*) as anzahl, tld, type FROM  `backorder_domains` WHERE `userid` = userid GROUP BY tld, type' , ['userid' => $userid] );
$result = Capsule::select('SELECT count(*) as anzahl, tld, type
					FROM  `backorder_domains`
					WHERE `userid` = userid
					GROUP BY tld, type' , ['userid' => $userid] );

$result = (array)$result;
// echo "<pre> >"; print_r($result); echo "< </pre>";

while($result){
    	$r["PROPERTY"][ $result["tld"] ][ $result["type"] ] = $result["anzahl"];
    	$r["PROPERTY"][ $result["tld"] ]["total"] += $result["anzahl"];
}
//end T

return $r;

?>
