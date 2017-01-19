<?php

require_once dirname(__FILE__)."/../../../../init.php";

if(isset($_SESSION["Language"])){
	$language = $_SESSION["Language"];
}

if(!isset($language)){
	$language = "english";
}

$file_backorder = dirname(__FILE__)."/../lang/".$language.".php";
if ( file_exists($file_backorder) ) {
	include($file_backorder);
}else{
	include(dirname(__FILE__)."/../lang/english.php");
}

//replaced getcwd() with dirname(__FILE__) on jan 13, 2017

?>
