<?php

ini_set('max_execution_time', 0);
date_default_timezone_set('UTC');

$cronname = "DAILY_IMPORT_PENDINGDOMAINLIST";

require_once dirname(__FILE__) . "/../backend/PendingDomainList.class.php";
require_once dirname(__FILE__) . "/../backend/api.php";
include(dirname(__FILE__) . "/../../../../configuration.php");

logmessage($cronname, "ok", "DAILY_IMPORT_PENDINGDOMAINLIST started");

$pd = new \HEXONET\PendingDomainList();
$pd->download()
    ->clearTable()//TODO
    ->import();

logmessage($cronname, "ok", "$cronname done");
echo date("Y-m-d H:i:s") . " $cronname done.\n";
