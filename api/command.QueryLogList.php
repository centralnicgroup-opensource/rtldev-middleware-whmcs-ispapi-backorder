<?php
use WHMCS\Database\Capsule;
try{
	$pdo = Capsule::connection()->getPdo();

    if(!isset($_SESSION['adminid']) || $_SESSION['adminid'] <= 0) return backorder_api_response(531, "AUTHORIZATION FAILED");

    $r = backorder_api_response(200);

    $stmt = $pdo->prepare("SELECT * FROM backorder_logs ORDER BY id DESC");
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($logs as $log) {
         $r["PROPERTY"][] = $log;
    }

    return $r;
}catch(\Exception $e){
   logmessage("command.QueryLogList", "DB error", $e->getMessage());
   return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}


?>
