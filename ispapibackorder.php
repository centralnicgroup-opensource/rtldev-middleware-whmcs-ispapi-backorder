<?php

$module_version = "1.0";

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

function ispapibackorder_config() {
    $configarray = array(
    "name" => "ISPAPI Backorder",
    "description" => "This addon allows you to provide backorders to your customers.",
    "version" => $module_version,
    "author" => "",
    "language" => "english");
    return $configarray;
}

function ispapibackorder_activate() {

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
	provider varchar(255) NOT NULL,
	reference varchar(255) NOT NULL,
	invoice varchar(255) NOT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY userid (userid,domain,tld)
	) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8;";
  $result = full_query($query);

  $query = "CREATE TABLE `backorder_logs` ( `id` int(11) NOT NULL AUTO_INCREMENT, `cron` varchar(255) NOT NULL, `date` datetime, `status` varchar(20) NOT NULL, `message` text, `query` text, PRIMARY KEY (`id`)) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8;";
  $result = full_query($query);

  $query = "CREATE TABLE `backorder_pricing` ( `id` int(11) NOT NULL AUTO_INCREMENT, `extension` varchar(20) NOT NULL, `currency_id` int(11) NOT NULL, `fullprice` float, `liteprice` float, PRIMARY KEY (`id`)) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8;";
  $result = full_query($query);

  $query = 'INSERT INTO tblemailtemplates (type, name, subject, message, disabled, custom, plaintext) VALUES (\'general\', \'backorder_failed_notification\', \'Backorder Failded Notification\', \'Hello {$client_name} ,&lt;br /&gt;&lt;br /&gt;The following backorders have failed today:&lt;br /&gt;{foreach from=$list item=data}&lt;br /&gt; - &lt;strong&gt;{$data.domain} &lt;/strong&gt;({$data.status}) &lt;br /&gt; {/foreach}&lt;br /&gt;&lt;br /&gt;&lt;span&gt;{$signature}&lt;/span&gt;\', 0, 1, 0)';
  $result = full_query($query);

  $query = 'INSERT INTO tblemailtemplates (type, name, subject, message, disabled, custom, plaintext) VALUES (\'general\', \'backorder_2_hours_before\', \'Backorder Notification from {$company_name}\', \'Hello {$client_name} ,&lt;br /&gt;&lt;br /&gt;The following domains will be backordered within the next 2 hours:&lt;br /&gt;{foreach from=$list item=data}&lt;br /&gt; - &lt;strong&gt;{$data.domain} &lt;/strong&gt;({$data.dropdate} / {$data.status}) &lt;br /&gt; {/foreach}&lt;br /&gt;&lt;br /&gt;&lt;span&gt;{$signature}&lt;/span&gt;\', 0, 1, 0)';
  $result = full_query($query);

  $query = 'INSERT INTO tblemailtemplates (type, name, subject, message, disabled, custom, plaintext) VALUES (\'general\', \'backorder_3_days_before\', \'Backorder Notification from {$company_name}\',\'&lt;p&gt;Dear {$client_name},&lt;/p&gt;&lt;p&gt;The following domains will be backordered within the next 3 days:&lt;/p&gt;{foreach from=$list item=data}&lt;p&gt; - &lt;strong&gt;{$data.domain} &lt;/strong&gt;({$data.dropdate} / {$data.status})&lt;/p&gt;{/foreach}&lt;br /&gt;&lt;p&gt;{$client_credit}&lt;/p&gt;&lt;p&gt;{$signature}&lt;/p&gt;\', 0, 1, 0)';
  $result = full_query($query);

	return array('status'=>'success','description'=>'Installed');

}

function ispapibackorder_deactivate() {
  //full_query("DROP TABLE backorder_domains");
  //full_query("DROP TABLE backorder_pricing");
  //full_query("DROP TABLE backorder_logs");
  //full_query("DROP TABLE pending_domains");
  //full_query("DELETE FROM tblemailtemplates WHERE name='backorder_failed_notification' or name='backorder_2_hours_before' or name='backorder_3_days_before'");

  return array('status'=>'success','description'=>'Uninstalled');
}


function ispapibackorder_output($vars) {
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
	echo '<!--<li id="tab0" class="tab" data-toggle="tab" role="tab" aria-expanded="true"><a href="javascript:;">General Settings</a></li>-->';
	if($_GET["tab"] == 0){$active = "active";}else{$active="";}
	echo '<li id="tab0" class="tab '.$active.'" data-toggle="tab" role="tab" aria-expanded="true"><a href="javascript:;">Manage Backorders</a></li>';
	if($_GET["tab"] == 1){$active = "active";}else{$active="";}
	echo '<li id="tab1" class="tab '.$active.'" data-toggle="tab" role="tab" aria-expanded="true"><a href="javascript:;">Backorder Pricing</a></li>';

	echo '</ul></div>';


	//ispapibackorder_generalsettings_content($modulelink."&tab=0");
	ispapibackorder_manage_backorders($modulelink."&tab=0");
	ispapibackorder_pricing_content($modulelink."&tab=1");
}

function ispapibackorder_generalsettings_content($modulelink){
	echo '<div id="tab0box" class="tabbox tab-content">';
	echo "<H2>TAB1</H2>";
	echo '</div>';
}

function ispapibackorder_pricing_content($modulelink){
	echo '<div id="tab1box" class="tabbox tab-content">';

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
	echo '<div class="tablebg" align="center"><table id="domainpricing" class="datatable" cellspacing="1" cellpadding="3" border="0"><thead><th>Extension</th><th>Currency</th><th>Backorder Price</th><th></th></thead><tbody>';
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


function ispapibackorder_manage_backorders($modulelink){
	echo '<div id="tab0box" class="tabbox tab-content">';
	echo "<H2>Manage Backorders</H2>";

	if(isset($_REQUEST["delete"])){
		require_once dirname(__FILE__)."/../../../init.php";
		require_once dirname(__FILE__).'/backend/api.php';

		$command = array(
				"COMMAND" => "DeleteBackorder",
				"DOMAIN" => $_GET["delete"],
				"USER" => $_GET["user"]
		);

		$result = backorder_backend_api_call($command);
		if($result["CODE"] == 200){
			echo '<div class="infobox"><strong><span class="title">Backorder deleted!</span></strong><br>The backorder has been successfuly deleted.</div>';
		}else{
			echo '<div class="errorbox"><strong><span class="title">Error!</span></strong><br>'.$result["DESCRIPTION"].'</div>';
		}
	}

	if(isset($_REQUEST["activate"])){
		require_once dirname(__FILE__)."/../../../init.php";
		require_once dirname(__FILE__).'/backend/api.php';

		$command = array(
				"COMMAND" => "ActivateBackorder",
				"DOMAIN" => $_GET["activate"],
				"USER" => $_GET["user"]
		);

		$result = backorder_backend_api_call($command);

		if($result["CODE"] == 200){
			echo '<div class="infobox"><strong><span class="title">Backorder Activated!</span></strong><br>The backorder has been successfuly activated.</div>';
		}else{
			echo '<div class="errorbox"><strong><span class="title">Error!</span></strong><br>'.$result["DESCRIPTION"].'</div>';
		}
	}

	$backorders = array();
	$result = mysql_query("SELECT bd.*, bd.id as backorder_id, c.id as client_id, c.firstname as firstname, c.lastname as lastname FROM backorder_domains as bd LEFT JOIN tblclients AS c ON bd.userid = c.id order by bd.domain asc");
	while ($data = mysql_fetch_array($result)) {
		array_push($backorders, $data);
	}

	echo '<form action="'.$modulelink.'" method="post">';
	echo '<div class="tablebg" align="center">';
	echo '<table id="domainpricing" class="datatable" cellspacing="1" cellpadding="3" border="0" width="100%"><thead><th>Domain</th><th>Client</th><th>Drop Date</th><th>Created Date</th><th>Updated Date</th><th>Status</th><th>Provider</th><th>Reference</th><th>Actions</th></thead><tbody>';

	foreach($backorders as $backorder){
		if($backorder["status"] == "REQUESTED"){
			$activate_link = "<a href='".$modulelink."&activate=".$backorder["domain"].".".$backorder["tld"]."&user=".$backorder["client_id"]."'>Activate</a>";
		}else{
			$activate_link = "";
		}
		if($backorder["status"] == "PROCESSING"){
			$delete_link = "";
		}else{
			$delete_link = "<a href='".$modulelink."&delete=".$backorder["domain"].".".$backorder["tld"]."&user=".$backorder["client_id"]."'>Delete</a>";
		}
		echo "<tr class='".$backorder["status"]."'><td class='".$backorder["type"]."'>".$backorder["domain"].".".$backorder["tld"]."</td><td><a href='clientssummary.php?userid=".$backorder["client_id"]."'>".$backorder["firstname"]." ".$backorder["lastname"]."</a></td><td>".$backorder["dropdate"]."</td><td>".$backorder["createddate"]."</td><td>".$backorder["updateddate"]."</td><td>".$backorder["status"]."</td><td>".$backorder["provider"]."</td><td>".$backorder["reference"]."</td><td>".$delete_link." ".$activate_link."</td></tr>";
	}

	echo '</tbody></table></div>';
	echo '</div>';
	echo '</form>';
}

function ispapibackorder_clientarea($vars) {
	$modulename = "backorder";
	$modulepath = "../modules/addons/".$modulename;

	//include language files
	$language = $_SESSION["Language"];
	if(!isset($language)){
		$language = "english";
	}
	$file = getcwd()."/lang/".$language.".php";
	$file_backorder = getcwd()."/modules/addons/backorder/lang/".$language.".php";
	include($file);
	if ( file_exists($file_backorder) ) {
		include($file_backorder);
	}

	if(!isset($_GET["p"])){
		$_GET["p"] = "test1"; //"pendingdomainlist";
	}

	//include controller file
	$vars = array();
	$controller = getcwd()."/modules/addons/".$modulename."/controller/".$_GET["p"].".php";
	if (file_exists($controller)) {
		include  $controller;
	}

	return array(
			'pagetitle' => "Backorder",
			'breadcrumb' => array('index.php?m=backorder'=>'Backorder'),
			'templatefile' => "templates/".$_GET["p"],
			'requirelogin' => true,
			'vars' => array_merge($vars, array(
					'moduletemplatepath' => $modulepath."/templates",
					'modulepath' => $modulepath."/"
			)),
	);
}
