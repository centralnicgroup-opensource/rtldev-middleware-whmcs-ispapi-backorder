<?php

use WHMCS\Module\Addon\ispapibackorder\Xhr\XhrDispatcher;

require_once(implode(DIRECTORY_SEPARATOR, [__DIR__, "lib", "Xhr", "XhrDispatcher.php"]));

$data = json_decode(file_get_contents("php://input"), true);
if (isset($data["type"]) && $data["type"] === "xhr") {
    $dispatcher = new XHRDispatcher();
    $r = $dispatcher->dispatch($data["action"], $data);

    //send json response headers
    header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header('Content-type: application/json; charset=utf-8');
    //do not echo as this would add template html code around!
    die(json_encode($r));
}
