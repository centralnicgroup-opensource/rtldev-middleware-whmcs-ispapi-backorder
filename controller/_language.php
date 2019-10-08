<?php
if (isset($_SESSION["Language"])) {
    $language = $_SESSION["Language"];
}
if (!isset($language)) {
    $language = "english";
}
$file_backorder = dirname(__FILE__). DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "lang" . DIRECTORY_SEPARATOR . $language . ".php";
if (file_exists($file_backorder)) {
    include($file_backorder);
} else {
    include(dirname(__FILE__). DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "lang" . DIRECTORY_SEPARATOR . "english.php");
}
