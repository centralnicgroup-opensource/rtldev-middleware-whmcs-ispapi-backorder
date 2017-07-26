<?php // $command, $userid
use WHMCS\Database\Capsule;
$currencyid=NULL;
// $result = select_query('tblclients','currency',array("id" => $userid ));
// $data = mysql_fetch_assoc($result);
// echo "<pre> data> "; print_r($data); echo " <data </pre>";
// $currencyid= $data["currency"];
// echo "<pre> currency: "; print_r($currencyid); echo " :currency </pre>";

//T
$result1 = Capsule::select('SELECT currency from tblclients where id = ?', [$userid]);
$result = array();
foreach($result1 as $object)
{
    $result =  (array) $object;
}
// echo "<pre> > "; print_r($result); echo " < </pre>";
if ($result) {
	$currencyid = $result["currency"];
}
//end T

if($currencyid==NULL){
	return backorder_api_response(541, "PRICELIST - USER CURRENCY ERROR");
}

$currency=NULL;

// $result = select_query('tblcurrencies','*',array("id" => $currencyid ));
// $currency = mysql_fetch_assoc($result);
// T
$result = Capsule::table('tblcurrencies')->where('id', $currencyid)->first();
$result = (array)$result;
$currency = $result;
// echo "<pre> > "; print_r($result); echo " < </pre>";
// end T

if ($currency==NULL){
	return backorder_api_response(541, "PRICELIST - CURRENCY ERROR");
}

$r = backorder_api_response(200);
$params = array("currency_id" => $currencyid);

if(isset($command["TLD"])){
	$params["extension"] = $command["TLD"];
}

// $result = select_query('backorder_pricing','*',$params);
while ( $data = mysql_fetch_assoc($result) ) {
	echo "<pre> > "; print_r($data); echo " < </pre>";
	if(!empty($data["fullprice"])){ //USE || $data["fullprice"]=="0" IF FREE BACKORDER ARE ALLOWED TO BE DISPLAYED
		$r["PROPERTY"][$data["extension"]]["PRICEFULL"] = $data["fullprice"];
		$r["PROPERTY"][$data["extension"]]["PRICEFULL_FORMATED"] = formatPrice($data["fullprice"], $currency);
		$r["PROPERTY"][$data["extension"]]["CURRENCYSUFFIX"] = $currency["suffix"];
		$r["PROPERTY"][$data["extension"]]["CURRENCY"] = $currency["code"];
		$r["PROPERTY"][$data["extension"]]["TLD"] = $data["extension"];
	}
}

// T
$result1 = Capsule::table('backorder_pricing')->where($params)->get();
$result = array();
foreach($result1 as $object)
{
    $result[] =  (array) $object;
}
// echo "<pre> > "; print_r($result); echo " < </pre>";
foreach ($result as $key => $value) {
	// echo "<pre> > "; print_r($value["fullprice"]); echo " < </pre>";
	if(!empty($value["fullprice"])){ //USE || $data["fullprice"]=="0" IF FREE BACKORDER ARE ALLOWED TO BE DISPLAYED
		$r["PROPERTY"][$value["extension"]]["PRICEFULL"] = $value["fullprice"];
		$r["PROPERTY"][$value["extension"]]["PRICEFULL_FORMATED"] = formatPrice($value["fullprice"], $currency);
		$r["PROPERTY"][$value["extension"]]["CURRENCYSUFFIX"] = $currency["suffix"];
		$r["PROPERTY"][$value["extension"]]["CURRENCY"] = $currency["code"];
		$r["PROPERTY"][$value["extension"]]["TLD"] = $value["extension"];
	}
}
//end T
return $r;
?>
