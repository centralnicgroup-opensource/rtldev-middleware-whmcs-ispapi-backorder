<?php // $command
use WHMCS\Database\Capsule;
try {
    //GET DPO CONNECTION
	$pdo = Capsule::connection()->getPdo();
    if(!isset($_SESSION['adminid']) || $_SESSION['adminid'] <= 0){
        return backorder_api_response(500, "ONLY AUTHORIZED FOR ADMIN USERS");
    }
    $r = backorder_api_response(200);

    #########################
    $result = $pdo->prepare("SELECT b.*, c.firstname as firstname, c.lastname as lastname FROM backorder_domains b, tblclients c WHERE b.userid = c.id ORDER BY id DESC");
    $result->execute();
	$data = $result->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as $key => $value) {
        $r["PROPERTY"][] = $value;
    }
    ############################

    return $r;
} catch (\Exception $e) {
   logmessage("command.CreateBackorder", "DB error", $e->getMessage());
   return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}

?>
