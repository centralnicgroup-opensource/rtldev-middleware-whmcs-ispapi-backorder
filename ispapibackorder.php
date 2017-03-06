<?php

$module_version = "1.0.0";

//if (!defined("WHMCS"))
//    die("This file cannot be accessed directly");

function ispapibackorder_config() {
    global $module_version;
    $configarray = array(
    "name" => "ISPAPI Backorder",
    "description" => "This addon allows you to provide backorders to your customers.",
    "version" => $module_version,
    "author" => "",
    "language" => "english",
    "fields" => array("username" => array ("FriendlyName" => "Admin username", "Type" => "text", "Size" => "30", "Description" => "[REQUIRED]", "Default" => "admin"))
    );
    return $configarray;
}

function ispapibackorder_activate() {

    //CREATE backorder_domains TABLE IF NOT EXISTING
    $r = full_query("SHOW TABLES LIKE 'backorder_domains'");
    $exist = mysql_num_rows($r) > 0;
    if(!$exist){
        //Create backorder_domains table
    	$query = "CREATE TABLE backorder_domains (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $result = full_query($query);
    }

    //CREATE backorder_logs TABLE IF NOT EXISTING
    $r = full_query("SHOW TABLES LIKE 'backorder_logs'");
    $exist = mysql_num_rows($r) > 0;
    if(!$exist){
        $query = "CREATE TABLE `backorder_logs` ( `id` int(11) NOT NULL AUTO_INCREMENT, `cron` varchar(255) NOT NULL, `date` datetime, `status` varchar(20) NOT NULL, `message` text, `query` text, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $result = full_query($query);
    }

    //CREATE backorder_pricing TABLE IF NOT EXISTING
    $r = full_query("SHOW TABLES LIKE 'backorder_pricing'");
    $exist = mysql_num_rows($r) > 0;
    if(!$exist){
        $query = "CREATE TABLE `backorder_pricing` ( `id` int(11) NOT NULL AUTO_INCREMENT, `extension` varchar(20) NOT NULL, `currency_id` int(11) NOT NULL, `fullprice` float, `liteprice` float, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $result = full_query($query);
    }

    //ADD backorder_lowbalance_notification TEMPLATE IF NOT EXISTING
    $r = full_query("SELECT * FROM tblemailtemplates WHERE name='backorder_lowbalance_notification'");
    $exist = mysql_num_rows($r) > 0;
    if(!$exist){
        $query = 'INSERT INTO tblemailtemplates (type, name, subject, message, disabled, custom, plaintext) VALUES ("general", "backorder_lowbalance_notification", "Low Balance Notification from {$company_name}", "<p>Hello {$client_name},<br /><br />Unfortunately, you have insufficient funds in your account to process your requested backorder(s). Kindly log in to charge your account so that the following backorder(s) may be processed:<br />{foreach from=$list item=data}- <strong>{$data.domain}</strong> / {$data.dropdate}<br />{/foreach}</p><p><span>{$signature}</span></p>", 0, 1, 0)';
        $result = full_query($query);
    }

    return array('status'=>'success','description'=>'Installed');

}

function ispapibackorder_deactivate() {
    //DO NOT DELETE TABLES WHEN DEACTIVATING DOMAINS - DEVELOPPER HAS TO DO IT MANUALLY IF WANTED
    //full_query("DROP TABLE backorder_domains");
    //full_query("DROP TABLE backorder_pricing");
    //full_query("DROP TABLE backorder_logs");
    //full_query("DROP TABLE pending_domains");
    //full_query("DELETE FROM tblemailtemplates WHERE name='backorder_lowbalance_notification'");
    return array('status'=>'success','description'=>'Uninstalled');
}

function ispapibackorder_upgrade($vars) {
    $version = $vars['version'];

    # Run SQL Updates for V1.0 to V1.1
    /*if ($version < 1.1) {
        $query = "CREATE TABLE `mylogs4` ( `id` int(11) NOT NULL AUTO_INCREMENT, `cron` varchar(255) NOT NULL, `date` datetime, `status` varchar(20) NOT NULL, `message` text, `query` text, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $result = mysql_query($query);
    }*/

}

function ispapibackorder_output($vars) {
    if(empty($vars["username"])){
        echo '<div class="errorbox"><strong><span class="title">Missing field!</span></strong><br>MISSING ADMIN USERNAME IN MODULE CONFIGURATION</div>';
    }

	if(!isset($_GET["tab"])){
		$_GET["tab"] = 0;
	}
	$modulelink = $vars['modulelink'];

    //includes MENU with JS and CSS
    include(dirname(__FILE__)."/controller/backend.mainpage.php");

    //includes all tabs
	ispapibackorder_managebackorders_content($modulelink."&tab=0");
	ispapibackorder_pricing_content($modulelink."&tab=1");
	ispapibackorder_logs_content($modulelink."&tab=2");
}

function ispapibackorder_managebackorders_content($modulelink){
	echo '<div id="tab0box" class="tabbox tab-content" style="display:none;">';
	echo "<H2>Manage Backorders</H2>";
    include(dirname(__FILE__)."/controller/backend.managebackorders.php");
	echo '</div>';
}

function ispapibackorder_pricing_content($modulelink){
	echo '<div id="tab1box" class="tabbox tab-content" style="display:none;">';
	echo "<H2>Backorder Pricing</H2>";
    include(dirname(__FILE__)."/controller/backend.pricing.php");
	echo '</div>';
}

function ispapibackorder_logs_content($modulelink){
	echo '<div id="tab2box" class="tabbox tab-content" style="display:none;">';
	echo "<H2>Backorder Logs</H2>";
    include(dirname(__FILE__)."/controller/backend.logs.php");
	echo '</div>';
}


function ispapibackorder_clientarea($vars) {
    if(empty($vars["username"])){
        die("USERNAME MISSING IN MODULE CONFIGURATION");
    }

	$modulename = "ispapibackorder";
	$modulepath = "../modules/addons/".$modulename;

	//include language files
	$language = $_SESSION["Language"];
	if(!isset($language)){
		$language = "english";
	}
	$file = getcwd()."/lang/".$language.".php";
	$file_backorder = getcwd()."/modules/addons/ispapibackorder/lang/".$language.".php";
	include($file);
	if ( file_exists($file_backorder) ) {
		include($file_backorder);
	}

	//include controller file
	$vars = array();
	$controller = getcwd()."/modules/addons/".$modulename."/controller/".$_GET["p"].".php";
	if (file_exists($controller)) {
		include  $controller;
	}

	return array(
			'pagetitle' => "Backorder",
			'breadcrumb' => array('index.php?m=ispapibackorder'=>'Backorder'),
			'templatefile' => "templates/".$_GET["p"],
			'requirelogin' => true,
			'vars' => array_merge($vars, array(
					'moduletemplatepath' => $modulepath."/templates",
					'modulepath' => $modulepath."/"
			)),
	);
}
