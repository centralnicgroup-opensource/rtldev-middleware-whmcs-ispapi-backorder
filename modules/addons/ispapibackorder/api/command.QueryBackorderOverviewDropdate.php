<?php

use WHMCS\Database\Capsule;

try {
    $pdo = Capsule::connection()->getPdo();

    if (!$userid) {
        return backorder_api_response(531);
    }
    $r = backorder_api_response(200);

    for ($i = 1; $i <= 31; $i++) {
        $datetime = strtotime("today +" . $i . "day");
        $date = date("Y-m-d", $datetime);
        $r["PROPERTY"][$date]['day'] = $date;
        $r["PROPERTY"][$date]['datetime'] = strtotime("today +" . $i . "day");
        $r["PROPERTY"][$date]["anzahl"] = 0;
        $r["PROPERTY"][$date]["anzahlFULL"] = 0;
        $r["PROPERTY"][$date]["anzahlLITE"] = 0;
    }

    $condition = array("userid" => $userid);

    $stmt = $pdo->prepare("SELECT DATE(dropdate) AS dropdateday, type, COUNT( * ) AS anzahl FROM  backorder_domains
		 				   WHERE DATE(dropdate)!='0000-00-00' AND DATE(dropdate)>NOW() AND userid=?
						   GROUP BY DATE(dropdate),type");
    $stmt->execute(array($userid));
    $backorders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($backorders as $backorder) {
        $r["PROPERTY"][$backorder["dropdateday"]]["anzahl"] += $backorder["anzahl"];
        $r["PROPERTY"][$backorder["dropdateday"]]["anzahl" . $backorder["type"]] = $backorder["anzahl"];
    }

    return $r;
} catch (\Exception $e) {
    logmessage("command.QueryBackorderOverviewDropdate", "DB error", $e->getMessage());
    return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}
