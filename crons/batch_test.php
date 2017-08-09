<?php
date_default_timezone_set('UTC');
$cronname = "BATCH_TEST";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";

//SEND INVOICE
$createinvoice = array(
        "COMMAND" => "QueryBackorderList",

);
// QueryPriceList

// ActivateBackorder
// "DOMAIN" => "a-anand.com",

// "COMMAND" => "CreateInvoice",
// "DOMAIN" => "a-charity.com",
// "TYPE" => "FULL",
// "BACKORDERID" => 307,




$r = backorder_api_call($createinvoice);
echo "list <pre>";
print_r($r);
echo "</pre>";

echo date("Y-m-d H:i:s")." $cronname done.\n";
?>
