<?php
date_default_timezone_set('UTC');
$cronname = "BATCH_TEST";
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";

//SEND INVOICE
$createinvoice = array(
        "COMMAND" => "QueryPriceList",
);
$r = backorder_api_call($createinvoice);
echo "list <pre>";
print_r($r);
echo "</pre>";

echo date("Y-m-d H:i:s")." $cronname done.\n";
?>
