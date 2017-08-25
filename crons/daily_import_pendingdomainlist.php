<?php
date_default_timezone_set('UTC');
$cronname = "BATCH_IMPORT_PENDINGDOMAINLIST";
require_once dirname(__FILE__)."/../backend/PendingDomainListPDO.class.php";
require_once dirname(__FILE__)."/../backend/api.php";
include(dirname(__FILE__)."/../../../../configuration.php");

logmessage($cronname, "ok", "BATCH_IMPORT_PENDINGDOMAINLIST started");

$pd = new PendingDomainListPDO('mysql:host='.$db_host.';dbname='.$db_name, $db_username, $db_password);
$pd->createTable();
$pd->import();
//$pd->import(array(), dirname(__FILE__)."/../tmp/pending_delete_domain_list.csv");

logmessage($cronname, "ok", "$cronname done");
echo date("Y-m-d H:i:s")." $cronname done.\n";

?>
