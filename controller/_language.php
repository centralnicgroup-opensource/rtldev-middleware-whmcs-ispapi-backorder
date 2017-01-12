<?php 

require_once dirname(__FILE__)."/../../../../init.php";

if(isset($_SESSION["Language"])){
	$language = $_SESSION["Language"];
}

if(!isset($language)){
	$language = "english";
}

$file_backorder = getcwd()."/../lang/".$language.".php";
if ( file_exists($file_backorder) ) {
	include($file_backorder);
}else{
	include(getcwd()."/../lang/english.php");
}

?>