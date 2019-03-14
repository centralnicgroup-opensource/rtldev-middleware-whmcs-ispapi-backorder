<?php

date_default_timezone_set('UTC');
$cronname = "BATCH_ACTIVE_PROCESSING";
require_once dirname(__FILE__)."/../backend/api.php";

use WHMCS\Database\Capsule;

try {
    $pdo = Capsule::connection()->getPdo();
    $stmt = $pdo->prepare("SELECT * FROM backorder_domains WHERE status=?");
    $stmt->execute(array("ACTIVE"));
    $locals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($locals as $local) {
        $today = new DateTime(date("Y-m-d H:i:s"));
        $dropdate = new DateTime($local["dropdate"]);
        $diff_timestamp = $dropdate->getTimestamp() - $today->getTimestamp();

        //CHECK IF TIMESTAMP >=0 AND <= 7200 (2 HOURS)
        if ($diff_timestamp >=0 && $diff_timestamp <=  7200) {
            //CHANGE STATUS FROM ACTIVE TO PROCESSING
            $stmt = $pdo->prepare("UPDATE backorder_domains SET status='PROCESSING', updateddate=NOW() WHERE id=?");
            $stmt->execute(array($local["id"]));

            if ($stmt->rowCount() != 0) {
                $message = "BACKORDER ".$local["domain"].".".$local["tld"]." (backorderid=".$local["id"].") set from ACTIVE to PROCESSING";
                logmessage($cronname, "ok", $message);
            }
        }
    }

    //logmessage($cronname, "ok", "$cronname done");
    echo date("Y-m-d H:i:s")." $cronname done.\n";
} catch (\Exception $e) {
    logmessage($cronname, "DB error", $e->getMessage());
    return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}
