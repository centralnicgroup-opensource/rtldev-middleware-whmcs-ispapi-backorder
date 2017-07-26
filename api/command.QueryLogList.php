<?php // $command
use WHMCS\Database\Capsule;
if(!isset($_SESSION['adminid']) || $_SESSION['adminid'] <= 0){
    return backorder_api_response(531, "AUTHORIZATION FAILED");
}

$r = backorder_api_response(200);
// $result = full_query("SELECT * FROM backorder_logs ORDER BY id DESC");
// while ( $data = mysql_fetch_assoc($result) ) {
//     $r["PROPERTY"][] = $data;
// }

// T
$result1 = Capsule::select("SELECT * FROM backorder_logs ORDER BY id DESC");
$result = array();
foreach($result1 as $object)
{
    $result[] = (array)$object;

}
// echo "<pre> >"; print_r($result); echo "< </pre>";
foreach ($result as $key => $value) {
    $r["PROPERTY"][] = $value;
}
// end T

return $r;
?>
