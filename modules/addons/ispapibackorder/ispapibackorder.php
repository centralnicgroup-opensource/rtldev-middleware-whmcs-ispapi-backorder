<?php

use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\ispapibackorder\Xhr\XhrDispatcher;
use WHMCS\Module\Addon\ispapibackorder\Admin\AdminDispatcher;
use WHMCS\Module\Addon\ispapibackorder\Client\ClientDispatcher;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function ispapibackorder_config()
{
    $logo_src = file_get_contents(implode(DIRECTORY_SEPARATOR, [ROOTDIR, "modules", "addons", "ispapibackorder", "logo.png"]));
    $logo_data = ($logo_src) ? 'data:image/png;base64,' . base64_encode($logo_src) : '';
    return [
        "name" => "ISPAPI Backorder",
        "description" => "This addon allows you to provide backorders to your customers.",
        "author" => '<a href="https://www.hexonet.net/" target="_blank"><img style="max-width:100px" src="' . $logo_data . '" alt="HEXONET" /></a>',
        "language" => "english",
        "version" => "4.0.7"
    ];
}

function ispapibackorder_activate()
{
    try {
        $pdo = Capsule::connection()->getPdo();

        //CREATE backorder_domains TABLE IF NOT EXISTING
        $r = $pdo->prepare("SHOW TABLES LIKE 'backorder_domains'");
        $r->execute();
        if (!$r->rowCount()) {
            $query = $pdo->prepare("CREATE TABLE backorder_domains (
                        	id int(11) NOT NULL AUTO_INCREMENT,
                        	userid int(11) NOT NULL,
                        	domain varchar(255) NOT NULL,
                        	tld varchar(32) NOT NULL,
                        	type enum('FULL','LITE') CHARACTER SET ascii NOT NULL,
                        	status enum('REQUESTED','ACTIVE','PROCESSING','SUCCESSFUL','FAILED','CANCELLED','AUCTION-PENDING','AUCTION-WON','AUCTION-LOST','PENDING-PAYMENT') CHARACTER SET ascii NOT NULL,
                        	createddate datetime NOT NULL,
                        	updateddate datetime NOT NULL,
                        	dropdate datetime NOT NULL,
                        	reference varchar(255) NOT NULL,
                        	invoice varchar(255) NOT NULL,
                            lowbalance_notification INT(11) NOT NULL,
                        	PRIMARY KEY (id),
                        	UNIQUE KEY userid (userid,domain,tld)
                    )ENGINE=InnoDB DEFAULT CHARSET=utf8;");
            $query->execute();
        }

        //CREATE backorder_logs TABLE IF NOT EXISTING
        $r = $pdo->prepare("SHOW TABLES LIKE 'backorder_logs'");
        $r->execute();
        if (!$r->rowCount()) {
            $query = $pdo->prepare("CREATE TABLE backorder_logs ( `id` int(11) NOT NULL AUTO_INCREMENT, `cron` varchar(255) NOT NULL, `date` datetime, `status` varchar(20) NOT NULL, `message` text, `query` text, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
            $query->execute();
        }

        //CREATE backorder_pricing TABLE IF NOT EXISTING
        $r = $pdo->prepare("SHOW TABLES LIKE 'backorder_pricing'");
        $r->execute();
        if (!$r->rowCount()) {
            $query = $pdo->prepare("CREATE TABLE backorder_pricing ( `id` int(11) NOT NULL AUTO_INCREMENT, `extension` varchar(20) NOT NULL, `currency_id` int(11) NOT NULL, `fullprice` float, `liteprice` float, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
            $query->execute();
        }

        //ADD backorder_lowbalance_notification TEMPLATE IF NOT EXISTING
        $r = $pdo->prepare("SELECT * FROM tblemailtemplates WHERE name=?");
        $r->execute(array("backorder_lowbalance_notification"));
        if (!$r->rowCount()) {
            $query = $pdo->prepare('INSERT INTO tblemailtemplates (type, name, subject, message, disabled, custom, plaintext) VALUES ("general", "backorder_lowbalance_notification", "Low Balance Notification from {$company_name}", "<p>Hello {$client_name},<br /><br />Unfortunately, you have insufficient funds in your account to process your requested backorder(s). Kindly log in to charge your account so that the following backorder(s) may be processed:<br />{foreach from=$list item=data}- <strong>{$data.domain}</strong> / {$data.dropdate}<br />{/foreach}</p><p><span>{$signature}</span></p>", 0, 1, 0)');
            $query->execute();
        }

        return array('status' => 'success','description' => 'Installed');
    } catch (\Exception $e) {
        return array('status' => 'error','description' => $e->getMessage());
    }
}

function ispapibackorder_deactivate()
{
    //DO NOT DELETE TABLES WHEN DEACTIVATING DOMAINS - DEVELOPPER HAS TO DO IT MANUALLY IF WANTED
    full_query("DROP TABLE backorder_domains");
    full_query("DROP TABLE backorder_pricing");
    full_query("DROP TABLE backorder_logs");
    full_query("DROP TABLE backorder_pending_domains");
    full_query("DELETE FROM tblemailtemplates WHERE name='backorder_lowbalance_notification'");
    return array("status" => "success","description" => "Uninstalled (All database tables starting with 'backorder_' have to be deleted manually)");
}

/**
 * Admin Area ...
 */
function ispapibackorder_output($vars)
{
    add_hook('AdminAreaHeadOutput', 1, function ($vars) {
        $cfg = ispapibackorder_config();
        $version = $cfg["version"];
        $wr = $vars['WEB_ROOT'];
        return <<<HTML
        <script>
            const wr = "{$wr}";
            const xr = wr.replace(/admin\/addonmodules\.php.+$/, '');
        </script>
HTML;
        //<script src="{$wr}/modules/addons/ispapidomaincheck/lib/Admin/assets/admin.all.min.js?t={$version}"></script>
        //<link href="{$wr}/modules/addons/ispapidomaincheck/lib/Admin/assets/admin.all.min.css?t={$version}" rel="stylesheet" type="text/css" />
    });

    $data = json_decode(file_get_contents("php://input"), true);
    if (isset($data["type"]) && $data["type"] === "xhr") {
        $dispatcher = new XHRDispatcher();
        $r = $dispatcher->dispatch($data["action"], $vars);

        //send json response headers
        header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Content-type: application/json; charset=utf-8');
        //do not echo as this would add template html code around!
        die(json_encode($r));
    }

    //init smarty and call admin dispatcher
    $smarty = new Smarty();
    $smarty->escape_html = true;
    $smarty->caching = false;
    $smarty->setCompileDir($GLOBALS['templates_compiledir']);
    $smarty->setTemplateDir(implode(DIRECTORY_SEPARATOR, [__DIR__, "lib", "Admin", "templates"]));
    $smarty->assign($vars);
    //call the dispatcher with action and data
    $dispatcher = new AdminDispatcher();
    $r = $dispatcher->dispatch($_REQUEST['action'], $vars, $smarty);
    if ($_REQUEST['action']) {
        //send json response headers
        header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Content-type: application/json; charset=utf-8');
        //do not echo as this would add template html code around!
        die(json_encode($r));
    }
    echo $r;
}

function ispapibackorder_managebackorders_content($modulelink)
{
    echo '<div id="tab0box" class="tabbox tab-content" style="display:none;">';
    echo "<H2>Manage Backorders</H2>";
    include(dirname(__FILE__) . "/controller/backend.managebackorders.php");
    echo '</div>';
}

function ispapibackorder_pricing_content($modulelink)
{
    echo '<div id="tab1box" class="tabbox tab-content" style="display:none;">';
    echo "<H2>Backorder Pricing</H2>";
    include(dirname(__FILE__) . "/controller/backend.pricing.php");
    echo '</div>';
}

function ispapibackorder_logs_content($modulelink)
{
    echo '<div id="tab2box" class="tabbox tab-content" style="display:none;">';
    echo "<H2>Backorder Logs</H2>";
    include(dirname(__FILE__) . "/controller/backend.logs.php");
    echo '</div>';
}


function ispapibackorder_clientarea($vars)
{
    $modulename = "ispapibackorder";
    $modulepath = "modules" . DIRECTORY_SEPARATOR . "addons" . DIRECTORY_SEPARATOR . $modulename;

    if (!preg_match("/^(manage|dropdomains)$/", $_GET["p"])) {
        //just to ensure %00 attacks are not working - WHMCS filters it out, but still white-listing is more secure
        die("not allowed");
    }
    $controller = getcwd() . DIRECTORY_SEPARATOR . $modulepath . DIRECTORY_SEPARATOR . "controller"  . DIRECTORY_SEPARATOR . $_GET["p"] . ".php";
    if (file_exists($controller)) {
        include $controller;
    } else {
        die("controller not found");
    }

    $vars["moduletemplatepath"] = $modulepath . DIRECTORY_SEPARATOR . "templates";
    $vars["modulepath"] = $modulepath . DIRECTORY_SEPARATOR;

    return array(
        'pagetitle' => "Backorder",
        'breadcrumb' => array('index.php?m=ispapibackorder' => 'Backorder'),
        'templatefile' => "templates/" . $_GET["p"],
        'requirelogin' => true,
        'vars' => $vars
    );
}
