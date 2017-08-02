<?php // $command
use WHMCS\Database\Capsule;
try {
	//GET DPO CONNECTION
	$pdo = Capsule::connection()->getPdo();

    if(!isset($_SESSION['adminid']) || $_SESSION['adminid'] <= 0){
        return backorder_api_response(531, "AUTHORIZATION FAILED");
    }

    $r = backorder_api_response(200);

    #######################
    $result = $pdo->prepare("SELECT * FROM backorder_logs ORDER BY id DESC");
    $result->execute();
    $data = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($data as $key => $value) {
         $r["PROPERTY"][] = $value;
    }
    #######################

    return $r;
} catch (\Exception $e) {
   logmessage("command.CreateBackorder", "DB error", $e->getMessage());
   return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}


?>
