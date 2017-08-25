<?php

use WHMCS\Database\Capsule;
try{
    $pdo = Capsule::connection()->getPdo();

    if(!$userid) return backorder_api_response(531);
    if(!isset($command["DOMAIN"]) || !strlen($command["DOMAIN"])) return backorder_api_response(504, "DOMAIN");
    if(!backorder_api_check_syntax_domain($command["DOMAIN"])) return backorder_api_response(505, "DOMAIN");
    if(!backorder_api_check_valid_tld($command["DOMAIN"], $userid)) return backorder_api_response(541, "NOT SUPPORTED");

    if(isset($command["DROPDATE"])){
        $dropdate = $command["DROPDATE"];
    }else{
        $dropdate = "0000-00-00 00:00:00";
    }

    $values = array(
        "userid" => $userid,
        "createddate" => date("Y-m-d H:i:s"),
        "updateddate" => date("Y-m-d H:i:s"),
        "dropdate" => $dropdate,
        "type" => "FULL",
        "status" => "REQUESTED",
        "reference" => ""
    );

    if(preg_match('/^([^\.^ ]{0,61})\.([a-zA-Z\.]+)$/', $command["DOMAIN"], $m)){
        $values["domain"] = strtolower($m[1]);
        $values["tld"] = strtolower($m[2]);

        //CHECK IF PRICING EXISTING FOR THIS TLD
        $querypricelist = array(
            "COMMAND" => "QueryPriceList",
            "USER" => $userid,
            "TLD" => $values["tld"]
        );

        $result = backorder_backend_api_call($querypricelist);

        if($result["CODE"] == 200){
            $backorder_price = $result["PROPERTY"][$values["tld"]]["PRICEFULL"];
            if(empty($backorder_price)){
                return backorder_api_response(549, "TLD NOT SUPPORTED");
            }
        }else{
            return backorder_api_response(549, "TLD NOT SUPPORTED");
        }
        //------------------------------------------------------

        //CHECK IF THERE IS A DROPDATE EXISTING IN THE DROPLIST
        $stmt = $pdo->prepare("SELECT * FROM backorder_pending_domains WHERE domain=? AND zone=?");
        $stmt->execute(array($values["domain"], $values["tld"]));
        $d1 = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!empty($d1)){
            $values["dropdate"] = $d1["drop_date"];
        }

        $stmt = $pdo->prepare("SELECT * FROM backorder_domains WHERE userid=? AND domain=? AND tld=?");
        $stmt->execute(array($userid, $values["domain"], $values["tld"]));
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if($data){
            if(in_array($data["status"], array("REQUESTED", "ACTIVE", "FAILED", "SUCCESSFUL", "AUCTION-LOST", "AUCTION-WON"))){
                return backorder_api_response(549, "BACKORDER ALREADY EXISTING");
            }else{
                return backorder_api_response(549, "THIS BACKORDER CANNOT BE MODIFIED");
            }
        }else{
            //IF NOT EXISTING, INSERT IT
            $insert_stmt = $pdo->prepare("INSERT INTO backorder_domains ( userid, createddate, updateddate, dropdate, type, status, reference, domain, tld ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ? )");
            $insert_stmt->execute(array($values["userid"], $values["createddate"], $values["updateddate"], $values["dropdate"], $values["type"], $values["status"], $values["reference"], $values["domain"], $values["tld"]));
            if($insert_stmt->rowCount() == 0){
                return backorder_api_response(549, "CREATE FAILED");
            }
        }

        $message = "BACKORDER ".$command["DOMAIN"]." set to REQUESTED";
        logmessage("command.CreateBackorder", "ok", $message);
        return backorder_api_response(200);
    }else{
		return backorder_api_response(549, "CREATE FAILED");
	}
}catch(\Exception $e){
    logmessage("command.CreateBackorder", "DB error", $e->getMessage());
 	return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}

?>
