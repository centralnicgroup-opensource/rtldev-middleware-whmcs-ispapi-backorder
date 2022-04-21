<?php
ini_set('max_execution_time', 0);
ini_set('auto_detect_line_endings',TRUE); // auto-detect EOL in CSV

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, "..", "backend", "api.php"]);
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, "..", "backend", "PendingDomainList.class.php"]);

const CRONNAME = "DAILY_IMPORT_PENDINGDOMAINLIST";
logmessage(CRONNAME, "ok", CRONNAME . " started");

(new \HEXONET\PendingDomainList())->download()->import();

logmessage(CRONNAME, "ok", CRONNAME . " done");
echo date("Y-m-d H:i:s ") . CRONNAME . " done.\n";
