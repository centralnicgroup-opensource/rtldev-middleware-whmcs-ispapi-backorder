<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

function ispapibackorder_config() {
    $configarray = array(
    "name" => "ISPAPI Backorder",
    "description" => "This addon allows you to provide backorders to your customers.",
    "version" => "1.0",
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
        $query = 'INSERT INTO tblemailtemplates (type, name, subject, message, disabled, custom, plaintext) VALUES ("general", "backorder_lowbalance_notification", "Low Balance Notification from {$company_name}", "<p>Hello {$client_name},<br /><br />unfortunately, your account has insufficient funds. You need to charge your account with the required funds, so that we can process your backorder requests.<br />If you fail to charge your account, the following backorders will be ignored:<br />{foreach from=$list item=data}<br />- <strong>{$data.domain} </strong>({$data.dropdate}){/foreach}<br /><br /><span>{$signature}</span></p>", 0, 1, 0)';
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

	echo'
	<script>
	$( document ).ready(function() {

		$(".tabbox").css("display","none");
			var selectedTab;
			$(".tab").click(function(){
				var elid = $(this).attr("id");
				$(".tab").removeClass("tabselected");
				$("#"+elid).addClass("tabselected");
				if (elid != selectedTab) {
					$(".tabbox").slideUp();
					$("#"+elid+"box").slideDown();
					selectedTab = elid;
				}
			$("#tab").val(elid.substr(3));
		});

		selectedTab = "tab'.$_GET["tab"].'";
		$("#" + selectedTab).addClass("tabselected");
		$("#" + selectedTab + "box").css("display","");


		$(".toggle_users").bind("click", function(){
			$(".users_" + $(this).attr("id")).toggleClass("hidden");
		});

	});
	</script>

	<style>

	.tablebg td.fieldlabel {
	    background-color:#FFFFFF;
	    text-align: right;
	}

	.tablebg td.fieldarea {
	    background-color:#F3F3F3;
	    text-align: left;
	}

	.tab-content {
		border-left: 1px solid #ccc;
		border-right: 1px solid #ccc;
		border-bottom: 1px solid #ccc;
		padding:10px;
	}

	div.tablebg {
		margin:0px;
	}

	div.infobox{
		margin:0px;
		margin-bottom:10px;
	}

	td.FULL {
		color: #449d44;
		font-weight:bold;
	}

	td.LITE {
		color: #ec971f;
		font-weight:bold;
	}

	tr.PROCESSING td {
		background-color: #D6F7E6;
	}

	tr.FAILED td {
		background-color: #ffe5e5;
	}

	.toggle_users{
		cursor:pointer;
		font-weight:bold;
	}

	.usersarea {
		background-color:#EDEDED;
		padding:5px;
	}

	</style>';

	echo '<div id="tabs"><ul class="nav nav-tabs admin-tabs" role="tablist">';
    if($_GET["tab"] == 0){$active = "active";}else{$active="";}
	echo '<li id="tab0" class="tab '.$active.'" data-toggle="tab" role="tab" aria-expanded="true"><a href="javascript:;">Manage</a></li>';
    if($_GET["tab"] == 1){$active = "active";}else{$active="";}
	echo '<li id="tab1" class="tab '.$active.'" data-toggle="tab" role="tab" aria-expanded="true"><a href="javascript:;">Logs</a></li>';
    if($_GET["tab"] == 2){$active = "active";}else{$active="";}
	echo '<li id="tab2" class="tab '.$active.'" data-toggle="tab" role="tab" aria-expanded="true"><a href="javascript:;">Pricing</a></li>';
	echo '</ul></div>';

	ispapibackorder_managebackorders_content($modulelink."&tab=0");
	ispapibackorder_logs_content($modulelink."&tab=1");
	ispapibackorder_pricing_content($modulelink."&tab=2");
}

function ispapibackorder_managebackorders_content($modulelink){
	echo '<div id="tab0box" class="tabbox tab-content">';
	echo "<H2>Manage Backorders</H2>";
    include(dirname(__FILE__)."/controller/backend.managebackorders.php");
	echo '</div>';
}

function ispapibackorder_pricing_content($modulelink){
	echo '<div id="tab2box" class="tabbox tab-content">';
	echo "<H2>Backorder Pricing</H2>";
	//Delete pricing
	###############################################################################
	if(isset($_REQUEST["deletepricing"])){
		mysql_query("DELETE FROM backorder_pricing WHERE id=".$_REQUEST["deletepricing"]);
		echo '<div class="infobox"><strong><span class="title">Deletion Successfully!</span></strong><br>Your backorder pricing has been deleted.</div>';
	}
	###############################################################################

	//Save pricing
	###############################################################################
	if(isset($_REQUEST["savepricing"])){
		foreach($_POST["EXT"] as $id => $categorie){
            if( substr( $categorie["EXT"], 0, 1 ) === "." ){
                $categorie["EXT"] = substr($categorie["EXT"], 1, strlen($categorie["EXT"]));
            }
			update_query( "backorder_pricing", array( "extension" => strtolower($categorie["EXT"]), "currency_id" => $categorie["CURRENCY"], "fullprice" => $categorie["FULLPRICE"] ), array( "id" => $id) );
		}
		if(!empty($_POST["ADDEXT"]["NAME"])){
			insert_query("backorder_pricing",array("extension" => strtolower($_POST["ADDEXT"]["NAME"]), "currency_id" => $_POST["ADDEXT"]["CURRENCY"], "fullprice" => $_POST["ADDEXT"]["FULLPRICE"] ));
		}
		echo '<div class="infobox"><strong><span class="title">Changes Saved Successfully!</span></strong><br>Your changes have been saved.</div>';
	}
	###############################################################################

	//Get all pricing
	###############################################################################
	$extensions = array();
	$result = mysql_query("SELECT * FROM backorder_pricing");
	while ($data = mysql_fetch_array($result)) {
		array_push($extensions, $data);
	}

	###############################################################################

	//Get all currencies
	###############################################################################
	$currencies = array();
	$result = mysql_query("SELECT * FROM tblcurrencies");
	while ($data = mysql_fetch_array($result)) {
		array_push($currencies, $data);
	}

	###############################################################################

	echo '<form action="'.$modulelink.'" method="post">';
	echo '<div class="tablebg" align="center"><table id="domainpricing" class="table table-bordered table-hover table-condensed dt-bootstrap" cellspacing="1" cellpadding="3" border="0"><thead><th>Extension</th><th>Currency</th><th>Backorder Price</th><th></th></thead><tbody>';
	foreach($extensions as $extension){
		echo '<tr><td width="50"><input style="font-weight:bold;" type="text" name="EXT['.$extension["id"].'][EXT]" value="'.$extension["extension"].'"/></td>';
		//echo '<td width="50"><input type="text" name="EXT['.$extension["id"].'][CURRENCY]" value="'.$extension["currency_id"].'"/></td>';

		echo '<td width="50"><select name="EXT['.$extension["id"].'][CURRENCY]">';
		foreach($currencies as $currency){
			if($currency["id"] == $extension["currency_id"]){
				echo "<option selected value='".$currency["id"]."'>".$currency["code"]."</option>";
			}else{
				echo "<option value='".$currency["id"]."'>".$currency["code"]."</option>";
			}
		}
		echo '</select></td>';

		echo '<td width="50"><input type="text" name="EXT['.$extension["id"].'][FULLPRICE]" value="'.$extension["fullprice"].'"/></td>';
		echo '<td width="20"><a href="'.$modulelink."&deletepricing=".$extension["id"].'"><img border="0" width="16" height="16" alt="Delete" src="images/icons/delete.png"></a></td></tr>';
	}
	echo '<tr><td width="50"><input style="font-weight:bold;" type="text" name="ADDEXT[NAME]"/></td>';
	//echo '<td width="50"><input type="text" name="ADDEXT[CURRENCY]"/></td>';

	echo '<td width="50"><select name="ADDEXT[CURRENCY]">';
	foreach($currencies as $currency){
		echo "<option value='".$currency["id"]."'>".$currency["code"]."</option>";
	}
	echo '</select></td>';

	echo '<td width="50"><input type="text" name="ADDEXT[FULLPRICE]"/></td>';
	echo '<td width="20"></td></tr>';
	echo '</tbody></table></div>';
	echo '<p align="center"><input class="btn" name="savepricing" type="submit" value="Save Changes"></p>';
	echo '</form>';
	echo '</div>';
}

function ispapibackorder_logs_content($modulelink){
	echo '<div id="tab1box" class="tabbox tab-content">';
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

	/*if(!isset($_GET["p"])){
		$_GET["p"] = "test1";
	}*/

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
