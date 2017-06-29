<?php
date_default_timezone_set('UTC');
$cronname = "BATCH_TEST";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";

//SEND INVOICE
$createinvoice = array(
        "COMMAND" => "CreateInvoice",
        "USER" => 1,
        "DOMAIN" => "anthony1.com",
        "TYPE" => "FULL",
        "BACKORDERID" => 52
);
$r = backorder_backend_api_call($createinvoice);
print_r($r);

echo date("Y-m-d H:i:s")." $cronname done.\n";
?>
