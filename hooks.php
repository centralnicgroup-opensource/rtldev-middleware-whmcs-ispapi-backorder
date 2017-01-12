<?php

add_hook('ClientAreaPage', 1, function($templateVariables)
{
	$language = $templateVariables["language"];
	if(!isset($language)){
		$language = "english";
	}

	$file = getcwd()."/lang/".$language.".php";
	$file_backorder = getcwd()."/modules/addons/ispapibackorder/lang/".$language.".php";
	include($file);
	if ( file_exists($file_backorder) ) {
		include($file_backorder);
	}else{
		include(getcwd()."/modules/addons/ispapibackorder/lang/english.php");
	}

	return array("LANG" => $_LANG);
});


use WHMCS\View\Menu\Item as MenuItem;
add_hook('ClientAreaPrimaryNavbar', 1, function (MenuItem $primaryNavbar)
{
	//insert language file
	if(!isset($_SESSION["Language"])){
		$language = "english";
	}else{
		$language = $_SESSION["Language"];
	}

	$file = getcwd()."/lang/".$language.".php";
	$file_backorder = getcwd()."/modules/addons/ispapibackorder/lang/".$language.".php";
	include($file);
	if ( file_exists($file_backorder) ) {
		include($file_backorder);
	}else{
		include(getcwd()."/modules/addons/ispapibackorder/lang/english.php");
	}

	//create navigation
	$primaryNavbar->addChild($_LANG['backorder_nav'])->setOrder(70);

	if (!is_null($primaryNavbar->getChild($_LANG['backorder_nav']))) {

		$primaryNavbar->getChild($_LANG['backorder_nav'])->addChild($_LANG['managebackorders'], array(
				'label' => $_LANG['managebackorders'],
				'uri' => 'index.php?m=backorder&p=manage',
				'order' => '20',
		));

		$primaryNavbar->getChild($_LANG['backorder_nav'])->addChild($_LANG['domainheader'], array(
				'label' => $_LANG['domainheader'],
				'uri' => 'index.php?m=backorder&p=dropdomains',
				'order' => '10',
		));

	}

});
