<?php

use WHMCS\Database\Capsule;
try{
	$pdo = Capsule::connection()->getPdo();

    if(!isset($_SESSION['adminid']) || $_SESSION['adminid'] <= 0) return backorder_api_response(500, "ONLY AUTHORIZED FOR ADMIN USERS");

    $r = backorder_api_response(200);

    $stmt = $pdo->prepare("SELECT b.*, c.firstname as firstname, c.lastname as lastname FROM backorder_domains b, tblclients c WHERE b.userid = c.id ORDER BY id DESC");
    $stmt->execute();
	$backorders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($backorders as $backorder) {
        $r["PROPERTY"][] = $backorder;
    }

    return $r;
}catch(\Exception $e){
   logmessage("command.QueryCompleteBackorderList", "DB error", $e->getMessage());
   return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}

?>
