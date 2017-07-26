<?php // $command, $userid
use WHMCS\Database\Capsule;

if ( !$userid )	return backorder_api_response(531);
$r = backorder_api_response(200);

// ori
// $result = full_query("SHOW COLUMNS FROM `backorder_domains` LIKE 'status'");

// T
$result1 = Capsule::select("SHOW COLUMNS FROM `backorder_domains` LIKE 'status'");
$result = array();
foreach($result1 as $object)
{
    $result =  (array) $object;
}
$result = (array)$result;
// echo "<pre> > "; print_r($result); echo"< </pre>";

foreach($result as $key){
	preg_match_all('~\'([^\']*)\'~', $result['Type'], $matches);
}
 // echo "matches variable >"; echo "<pre>"; print_r($matches); echo "</pre>";
// end T

// while ($data = mysql_fetch_assoc($result)) {
// 	// echo "<pre>"; print_r($data); echo "</pre>";
//	// echo "<pre>"; print_r($data['Type']); echo "</pre>";
// 	preg_match_all('~\'([^\']*)\'~', $data['Type'], $matches);
// 	// echo "<pre>"; print_r($matches); echo "</pre>";
// }
// // echo "matches variable >"; echo "<pre>"; print_r($matches); echo "</pre>";
foreach($matches[1] as $status)
{
	$r["PROPERTY"][$status]['status'] = $status;
	$r["PROPERTY"][$status]["anzahl"] = 0;
}
$r["total"] += 0;

$condition = array("userid" => $userid);
// $result = full_query('SELECT count(*) as anzahl, status FROM  `backorder_domains` WHERE `userid` ='.$userid.' GROUP BY status ');
//
// while ($data = mysql_fetch_assoc($result)) {
// 	echo "<pre>"; print_r($data); echo "</pre>";
// 	$r["PROPERTY"][$data["status"]]['status'] = $data["status"];
// 	$r["PROPERTY"][$data["status"]]["anzahl"] = $data["anzahl"];
// 	$r["total"] += $data["anzahl"];
// }

// T
$result = Capsule::select('SELECT count(*) as anzahl, status
					FROM  `backorder_domains`
					WHERE `userid` = userid
					GROUP BY status' , ['userid' => $userid]);

$result = (array)$result;
	// echo "<pre> >"; print_r($result); echo "< </pre>";
while ($result) {
	$r["PROPERTY"][$result["status"]]['status'] = $result["status"];
	$r["PROPERTY"][$result["status"]]["anzahl"] = $result["anzahl"];
	$r["total"] += $result["anzahl"];
}
// end T
return $r;

?>
