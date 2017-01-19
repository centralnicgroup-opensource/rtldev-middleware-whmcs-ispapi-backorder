<?php
$cronname = "BATCH_IMPORT_PENDINGDOMAINLIST";
require_once dirname(__FILE__)."/../backend/PendingDomainListPDO.class.php";
require_once dirname(__FILE__)."/../backend/api.php";
include(dirname(__FILE__)."/../../../../configuration.php");

logmessage($cronname, "ok", "BATCH_IMPORT_PENDINGDOMAINLIST started");

//GET LIST OF ALL EXTENSIONS AVAILABLE FOR BACKORDER
$available_extensions = array();
$result = full_query("select extension from backorder_pricing GROUP BY extension");
while ($b = mysql_fetch_array($result)) {
    array_push($available_extensions, $b["extension"]);
}

$pd = new PendingDomainListPDO('mysql:host='.$db_host.';dbname='.$db_name, $db_username, $db_password);
$pd->createTable();
//$pd->import($available_extensions, dirname(__FILE__)."/../tmp/pending_delete_domain_list.csv");
$pd->import($available_extensions);

logmessage($cronname, "ok", "BATCH_IMPORT_PENDINGDOMAINLIST done");
echo date("Y-m-d H:i:s")." BATCH_IMPORT_PENDINGDOMAINLIST done.\n";

?>
