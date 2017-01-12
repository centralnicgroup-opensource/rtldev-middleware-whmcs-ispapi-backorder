<?php // $command, $userid

if ( !$userid )	return backorder_api_response(531);
$r = backorder_api_response(200);

for($i=1; $i<=31; $i++)
{
	$datetime = strtotime("today +".$i."day");
	$date = date("Y-m-d",$datetime);
	$r["PROPERTY"][$date]['day'] = $date;
	$r["PROPERTY"][$date]['datetime'] = strtotime("today +".$i."day");
	$r["PROPERTY"][$date]["anzahl"] = 0;
	$r["PROPERTY"][$date]["anzahlFULL"] = 0;
	$r["PROPERTY"][$date]["anzahlLITE"] = 0;
}

$condition = array("userid" => $userid);
$result = full_query("SELECT DATE(dropdate) AS dropdateday, type, COUNT( * ) AS anzahl
					FROM  `backorder_domains`
					WHERE DATE(dropdate) !=  '0000-00-00'
					AND DATE(dropdate)  > '".date("Y-m-d")."'
					AND `userid` =".$userid."
					GROUP BY DATE(dropdate),type");

while ($data = mysql_fetch_assoc($result)) {
	$r["PROPERTY"][$data["dropdateday"]]["anzahl"] += $data["anzahl"];
	$r["PROPERTY"][$data["dropdateday"]]["anzahl".$data["type"]] = $data["anzahl"];
}


return $r;

?>
