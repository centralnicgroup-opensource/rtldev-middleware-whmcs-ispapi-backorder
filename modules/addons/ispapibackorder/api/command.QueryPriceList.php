<?php

use WHMCS\Database\Capsule;

try {
    $pdo = Capsule::connection()->getPdo();

    $currencyid = null;
    $stmt = $pdo->prepare("SELECT currency FROM tblclients WHERE id=?");
    $stmt->execute(array($userid));
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    $currencyid = $data["currency"];
    if ($currencyid == null) {
        return backorder_api_response(541, "PRICELIST - USER CURRENCY ERROR");
    }

    $currency = null;
    $stmt = $pdo->prepare("SELECT * FROM tblcurrencies WHERE id=?");
    $stmt->execute(array($currencyid));
    $currency = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($currency == null) {
        return backorder_api_response(541, "PRICELIST - CURRENCY ERROR");
    }

    $r = backorder_api_response(200);
    $params = array("currency_id" => $currencyid);
    if (isset($command["TLD"])) {
        $params["extension"] = $command["TLD"];
    }

    if ($params["extension"]) {
        $stmt = $pdo->prepare("SELECT * FROM backorder_pricing WHERE currency_id=? AND extension=?");
        $stmt->execute(array($params["currency_id"], $params["extension"]));
        $pricings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($pricings as $pricing) {
            if (!empty($pricing["fullprice"])) { //USE || $data["fullprice"]=="0" IF FREE BACKORDER ARE ALLOWED TO BE DISPLAYED
                $r["PROPERTY"][$pricing["extension"]]["PRICEFULL"] = $pricing["fullprice"];
                $r["PROPERTY"][$pricing["extension"]]["PRICEFULL_FORMATED"] = formatPrice($pricing["fullprice"], $currency);
                $r["PROPERTY"][$pricing["extension"]]["CURRENCYSUFFIX"] = $currency["suffix"];
                $r["PROPERTY"][$pricing["extension"]]["CURRENCY"] = $currency["code"];
                $r["PROPERTY"][$pricing["extension"]]["TLD"] = $pricing["extension"];
            }
        }
    } else {
        $stmt = $pdo->prepare("SELECT * FROM backorder_pricing WHERE currency_id=?");
        $stmt->execute(array($params["currency_id"]));
        $pricings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($pricings as $pricing) {
            if (!empty($pricing["fullprice"])) { //USE || $data["fullprice"]=="0" IF FREE BACKORDER ARE ALLOWED TO BE DISPLAYED
                $r["PROPERTY"][$pricing["extension"]]["PRICEFULL"] = $pricing["fullprice"];
                $r["PROPERTY"][$pricing["extension"]]["PRICEFULL_FORMATED"] = formatPrice($pricing["fullprice"], $currency);
                $r["PROPERTY"][$pricing["extension"]]["CURRENCYSUFFIX"] = $currency["suffix"];
                $r["PROPERTY"][$pricing["extension"]]["CURRENCY"] = $currency["code"];
                $r["PROPERTY"][$pricing["extension"]]["TLD"] = $pricing["extension"];
            }
        }
    }

    return $r;
} catch (\Exception $e) {
    logmessage("command.QueryLogList", "DB error", $e->getMessage());
    return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}
