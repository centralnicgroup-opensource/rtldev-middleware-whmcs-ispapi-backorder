<?php

use WHMCS\Database\Capsule;

try {
    $pdo = Capsule::connection()->getPdo();

    if (!$userid) {
        return backorder_api_response(531);
    }
    if (!isset($command["LIMIT"]) || !is_numeric($command["LIMIT"])) {
        $command["LIMIT"] = 100;
    }
    if (!isset($command["FIRST"]) || !is_numeric($command["FIRST"])) {
        $command["FIRST"] = 0;
    }
    $limit = "LIMIT ".$command["FIRST"].",".$command["LIMIT"];

    $r = backorder_api_response(200);

    $where = "WHERE userid=".$pdo->quote($userid);
    if (isset($command['STATUS']) && $command['STATUS']!="") {
        $where .= " AND status=".$pdo->quote($command['STATUS']);
    }

    $orders = array(
        "ID" => "id ASC",
        "IDDESC" => "id DESC",
        "DOMAIN" => "domain ASC",
        "DOMAINDESC" => "domain DESC",
        "DROPDATE" => "dropdate ASC",
        "DROPDATEDESC" => "dropdate DESC",
        "STATUS" => "status ASC",
        "STATUSDESC" => "status DESC",
    );

    if (isset($command["ORDERBY"]) && isset($orders[$command["ORDERBY"]])) {
        $order = $orders[$command["ORDERBY"]];
    } else {
        $order = "id";
    }
    $orderby = "ORDER BY $order";

    $sth = $pdo->prepare("SELECT * FROM backorder_domains $where $orderby $limit");
    $sth->execute();
    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $data) {
        $r["PROPERTY"]["ID"][] = $data["id"];
        $r["PROPERTY"]["DOMAIN"][] = $data["domain"].".".$data["tld"];
        $r["PROPERTY"]["LABEL"][] = $data["domain"];
        $r["PROPERTY"]["TLD"][] = $data["tld"];
        $r["PROPERTY"]["STATUS"][] = strtoupper($data["status"]);
        $r["PROPERTY"]["DROPDATE"][] = strtoupper($data["dropdate"]);
        $r["PROPERTY"]["CREATEDDATE"][] = strtoupper($data["createddate"]);
        $r["PROPERTY"]["UPDATEDDATE"][] = strtoupper($data["updateddate"]);
    }

    if (isset($r["PROPERTY"]["DOMAIN"]) && $userid) {
        foreach ($r["PROPERTY"]["DOMAIN"] as $index => $domain) {
            if (preg_match('/^(.*)\.(.*)$/', $domain, $m)) {
                $sth = $pdo->prepare("SELECT status, type FROM backorder_domains WHERE userid=? AND domain=? AND tld=?");
                $sth->execute(array($userid,$m[1],$m[2]));
                $data = $sth->fetch(PDO::FETCH_ASSOC);
                if ($data) {
                    $r["PROPERTY"]["STATUS"][$index] = strtoupper($data["status"]);
                    $r["PROPERTY"]["BACKORDERTYPE"][$index] = strtoupper($data["type"]);
                }
            }
        }
    }

    //get the total number of backorders
    $sth = $pdo->prepare("SELECT count(*) FROM backorder_domains $where ");
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_ASSOC);
    $r["PROPERTY"]["TOTAL"][] = $data['count(*)'];

    return $r;
} catch (\Exception $e) {
    logmessage("command.QueryBackorderList", "DB error", $e->getMessage());
    return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}
