<?php // $command

if(!isset($_SESSION['adminid']) || $_SESSION['adminid'] <= 0){
    return backorder_api_response(500, "ONLY AUTHORIZED FOR ADMIN USERS");
}

$r = backorder_api_response(200);
$result = full_query("SELECT b.*, c.firstname as firstname, c.lastname as lastname FROM backorder_domains b, tblclients c WHERE b.userid = c.id ORDER BY id DESC");
while ( $data = mysql_fetch_assoc($result) ) {
    $r["PROPERTY"][] = $data;
}

return $r;
?>
