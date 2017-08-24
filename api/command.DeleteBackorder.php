<?php

use WHMCS\Database\Capsule;
try{
    $pdo = Capsule::connection()->getPdo();

    if(!$userid) return backorder_api_response(531, "AUTHORIZATION FAILED");
    if(!isset($command["DOMAIN"]) || !strlen($command["DOMAIN"])) return backorder_api_response(504, "DOMAIN");
    if(!preg_match('/^([^\.^ ]{0,61})\.([a-zA-Z\.]+)$/', $command["DOMAIN"], $m)) return backorder_api_response(505, "DOMAIN");

    $stmt = $pdo->prepare("SELECT * FROM backorder_domains WHERE userid=? AND domain=? AND tld=?");
    $stmt->execute(array($userid, $m[1], $m[2]));
    $rows = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$rows){
        return backorder_api_response(545, "DOMAIN");
    }else{
        if(in_array($rows["status"], array("PENDING-PAYMENT", "AUCTION-PENDING")) || ($rows["status"]=="PROCESSING" && !empty($rows["reference"]))){
            return backorder_api_response(549, "THIS BACKORDER CANNOT BE DELETED");
        }
        $message = "BACKORDER ".$command["DOMAIN"]." DELETED";
        logmessage("command.DeleteBackorder", "ok", $message);

        $stmt = $pdo->prepare("DELETE FROM backorder_domains WHERE id=?");
        $stmt->execute(array($rows['id']));
    }
    return backorder_api_response(200);
}catch(\Exception $e){
    logmessage("command.DeleteBackorder", "DB error", $e->getMessage());
    return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}

?>
