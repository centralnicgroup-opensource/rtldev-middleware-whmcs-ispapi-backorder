<?php // $command

if(!isset($_SESSION['adminid']) || $_SESSION['adminid'] <= 0){
    return backorder_api_response(531, "AUTHORIZATION FAILED");
}

$r = backorder_api_response(200);
$result = full_query("SELECT * FROM backorder_logs ORDER BY id DESC");
while ( $data = mysql_fetch_assoc($result) ) {
    $r["PROPERTY"][] = $data;
}
return $r;
?>
