<?php

use WHMCS\View\Menu\Item as MenuItem;

add_hook('ClientAreaPrimaryNavbar', 1, function (MenuItem $primaryNavbar) {
    $client = Menu::context('client');
    if ($client) {
        $key = "ispapibackorder";
        $language = (isset($_SESSION["Language"]) ? $_SESSION["Language"] : "english");
        $file = getcwd() . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . "addons" . DIRECTORY_SEPARATOR . $key . DIRECTORY_SEPARATOR . "lang" . DIRECTORY_SEPARATOR . $language . ".php";
        if (file_exists($file)) {
            include($file);
        }

        $primaryNavbar->addChild($key, array(
            "label" => $_ADDONLANG["backorder_nav"],
            "uri" => "index.php?m={$key}&p=manage",
            "order" => "70"
        ));

        $pc = $primaryNavbar->getChild($key);
        if (!is_null($pc)) {
            $pc->addChild($key . "_manage", array(
                "label" => $_ADDONLANG["managebackorders"],
                "uri" => "index.php?m={$key}&p=manage",
                "order" => "20"
            ));
            $pc->addChild($key . "_droplist", array(
                "label" => $_ADDONLANG["domainheader"],
                "uri" => "index.php?m={$key}&p=dropdomains",
                "order" => "10"
            ));
        }
    }
});
