<?php

use WHMCS\Database\Capsule;

try {
    $pdo = Capsule::connection()->getPdo();

    if (!$userid) {
        return backorder_api_response(531);
    }

    $r = backorder_api_response(200);

    $stmt = $pdo->prepare("SHOW COLUMNS FROM backorder_domains LIKE 'status'");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($data as $value) {
        preg_match_all('~\'([^\']*)\'~', $value['Type'], $matches);
    }
    foreach ($matches[1] as $status) {
        $r["PROPERTY"][$status]['status'] = $status;
        $r["PROPERTY"][$status]["anzahl"] = 0;
    }
    $r["total"] += 0;

    $condition = array("userid" => $userid);
    $stmt = $pdo->prepare("SELECT count(*) as anzahl, status FROM backorder_domains WHERE userid=? GROUP BY status");
    $stmt->execute(array($userid));
    $backorders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($backorders as $backorder) {
        $r["PROPERTY"][$backorder["status"]]['status'] = $backorder["status"];
        $r["PROPERTY"][$backorder["status"]]["anzahl"] = $backorder["anzahl"];
        $r["total"] += $backorder["anzahl"];
    }

    return $r;
} catch (\Exception $e) {
    logmessage("command.QueryBackorderOverviewStatus", "DB error", $e->getMessage());
    return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}
