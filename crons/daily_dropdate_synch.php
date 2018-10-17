<?php

date_default_timezone_set('UTC');
$cronname = "DAILY_DROPDATE_SYNCH";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";

use WHMCS\Database\Capsule;

try {
    $pdo = Capsule::connection()->getPdo();
    $stmt = $pdo->prepare("SELECT * FROM backorder_domains WHERE status in ('REQUESTED','ACTIVE','PROCESSING')");
    $stmt->execute();
    $locals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($locals as $local) {
        $stmt = $pdo->prepare("SELECT domain, zone, drop_date FROM backorder_pending_domains WHERE domain=? and zone=? limit 1");
        $stmt->execute(array($local["domain"],$local["tld"]));
        $online = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($stmt->rowCount() != 0) {
            if ($local["dropdate"] != $online["drop_date"] && $online["drop_date"] > date("Y-m-d H:i:s")) {
                $old_dropdate = $local["dropdate"];
                $new_dropdate = $online["drop_date"];

                $update_stmt = $pdo->prepare("UPDATE backorder_domains SET dropdate=?, updateddate=NOW() WHERE domain=? AND tld=?");
                $update_stmt->execute(array($online["drop_date"], $local["domain"], $local["tld"]));
                if ($update_stmt->rowCount() != 0) {
                    $message = "DROPDATE OF BACKORDER ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].") SYNCHRONIZED ($old_dropdate => $new_dropdate)";
                    logmessage($cronname, "ok", $message);
                }
            }
        }
        // else{
        //  $old_dropdate = $local["dropdate"];
        //  $update_stmt = $pdo->prepare("UPDATE backorder_domains SET dropdate='0000-00-00 00:00:00', updateddate=NOW() WHERE domain=? AND tld=? AND dropdate!='0000-00-00 00:00:00'");
        //  $update_stmt->execute(array($local["domain"], $local["tld"]));
        //  if($update_stmt->rowCount() != 0){
        //      $message = "DROPDATE OF BACKORDER ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].") SYNCHRONIZED ($old_dropdate => '0000-00-00 00:00:00') DOMAIN NO LONGER IN RGP";
        //      logmessage($cronname, "ok", $message);
        //  }
        // }
    }

    logmessage($cronname, "ok", "$cronname done");
    echo date("Y-m-d H:i:s")." $cronname done.\n";
} catch (\Exception $e) {
    logmessage($cronname, "DB error", $e->getMessage());
    return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}
