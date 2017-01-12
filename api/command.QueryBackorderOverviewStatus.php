<?php // $command, $userid

if ( !$userid )	return backorder_api_response(531);
$r = backorder_api_response(200);










$result = full_query("SHOW COLUMNS FROM `backorder_domains` LIKE 'status'");

while ($data = mysql_fetch_assoc($result)) {
	preg_match_all('~\'([^\']*)\'~', $data['Type'], $matches);
}
foreach($matches[1] as $status)
{
	$r["PROPERTY"][$status]['status'] = $status;
	$r["PROPERTY"][$status]["anzahl"] = 0;
}
$r["total"] += 0;	


$condition = array("userid" => $userid);
$result = full_query('SELECT count(*) as anzahl, status FROM  `backorder_domains` WHERE `userid` ='.$userid.' GROUP BY status ');

while ($data = mysql_fetch_assoc($result)) {
	$r["PROPERTY"][$data["status"]]['status'] = $data["status"];
	$r["PROPERTY"][$data["status"]]["anzahl"] = $data["anzahl"];
	$r["total"] += $data["anzahl"];
}
return $r;

?>
