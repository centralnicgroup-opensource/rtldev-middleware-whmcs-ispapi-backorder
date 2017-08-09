<?php
//INSERT ALL MISSING PRICES - NEEDED WHEN RESELLER ADDS NEW CURRENCIES
###############################################################################
//GET TOTAL NUMBER OF CURRENCIES

require_once dirname(__FILE__).'/../../../../init.php'; // i added this
// https://forum.whmcs.com/showthread.php?112839-issue-with-custom-module

use WHMCS\Database\Capsule;
try {
	//GET DPO CONNECTION
   $pdo = Capsule::connection()->getPdo();

   $currencies = array();
   $nb_currencies = 0;

   $r0=$pdo->prepare("SELECT * FROM tblcurrencies");
   $r0->execute();
   $d0 = $r0->fetchAll(PDO::FETCH_ASSOC);

   foreach ($d0 as $key => $value) {
       array_push($currencies, $value);
       $nb_currencies++;
   }

   //GO THROUGH ALL EXTENSIONS
   $r=$pdo->prepare("SELECT distinct(extension) FROM backorder_pricing");
   $r->execute();
   $d = $r->fetchAll(PDO::FETCH_ASSOC);
   foreach ($d as $key => $value) {
       $extension =$value["extension"];
       $r1=$pdo->prepare("SELECT count(*) as nb_prices FROM backorder_pricing WHERE extension=?");
       $r1->execute(array($extension));
       $d1=$r1->fetch(PDO::FETCH_ASSOC);
       $nb_prices = $d1["nb_prices"];
       if($nb_prices < $nb_currencies){
           foreach($currencies as $currency){
               $r2=$pdo->prepare("SELECT * FROM backorder_pricing WHERE extension=? AND currency_id=?");
               $r2->execute(array($extension, $currency["id"]));
               $d2 = $r2->fetchAll(PDO::FETCH_ASSOC);
               if(empty($d2)){
                   $insert=$pdo->prepare("INSERT INTO backorder_pricing(extension, currency_id, fullprice) VALUES(:extension, :currency_id, :fullprice)");
                   $insert->execute(array(':extension' => $extension, ':currency_id' => $currency["id"] , ':fullprice' => "NULL"));
                   $affected_rows = $insert->rowCount();
               }
           }
       }
   }


   ###############################################################################

   //CLEAN ALL NON EXISTING CURRENCIES
   ###############################################################################
   $cur_string = "";
   foreach($currencies as $currency){
       $cur_string .= $currency["id"].",";
   }
   $cur_string .= "-1";
   $r0 = $pdo->prepare("DELETE FROM backorder_pricing WHERE currency_id NOT IN (?)");
   $r0->execute(array($cur_string));
   ###############################################################################

   //Delete pricing
   ###############################################################################
   if(isset($_REQUEST["deletepricing"])){
       $delete=$pdo->prepare("DELETE FROM backorder_pricing WHERE extension=?");
       $delete->execute(array(mysql_real_escape_string($_REQUEST["deletepricing"])));
       echo '<div class="infobox"><strong><span class="title">Deletion Successfully!</span></strong><br>Your backorder pricing has been deleted.</div>';
   }
   ###############################################################################

   //Save pricing
   ###############################################################################
   if(isset($_REQUEST["savepricing"])){
       /*echo "<pre>";
       print_r($_POST["EXT"]);
       echo "</pre>";*/
       foreach($_POST["EXT"] as $id => $extension){
           $price = $extension["PRICE"];
           if(empty($price) && !is_numeric($price)){ //IF PRICE EMPTY OR PRICE NOT INTEGER
               $price = "NULL";
           }

           $update = $pdo->prepare("UPDATE backorder_pricing SET fullprice=? WHERE id=?");
           $update->execute(array($price, $id));

       }
       if( isset($_POST["ADD"]["EXTENSION"]) && !empty($_POST["ADD"]["EXTENSION"]) ){
           $name = strtolower($_POST["ADD"]["EXTENSION"]);
           if(substr($name, 0, 1) === "."){ //CASE IF CUSTOMER ADDED . BEFORE EXTENSION
               $name = substr($name, 1, strlen($name));
           }
           foreach($_POST["ADD"]["PRICE"] as $currency_id => $price){
               if(empty($price) && !is_numeric($price)){ //IF PRICE EMPTY OR PRICE NOT INTEGER
                   $price = "NULL";
               }
               $insert = $pdo->prepare("INSERT INTO backorder_pricing(extension, currency_id, fullprice) VALUES(:extension, :currency_id, :fullprice)");
               $insert->execute(array(':extension' => $name, ':currency_id' => $currency_id , ':fullprice' => $price));
           }
       }
       echo '<div class="infobox"><strong><span class="title">Changes Saved Successfully!</span></strong><br>Your changes have been saved.</div>';
   }
   ###############################################################################

   //Get all pricing
   ###############################################################################
   $extensions = array();
   $result=$pdo->prepare("SELECT distinct(extension) FROM backorder_pricing");
   $result->execute();
   $data = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($data as $key => $value) {
        $item = array("extension" => $value["extension"] );
        $r=$pdo->prepare("SELECT BP.id as extension_id, BP.currency_id as currency_id, BP.extension, BP.fullprice, CUR.code FROM backorder_pricing BP, tblcurrencies CUR WHERE BP.extension=? AND BP.currency_id=CUR.id");
        $r->execute(array($value["extension"]));
        $d = $r->fetchAll(PDO::FETCH_ASSOC);
        foreach ($d as $ky => $val) {
            $item["pricing"][$val["code"]] = array("extension_id" => $val["extension_id"], "currency_id" => $val["currency_id"], "fullprice" => $val["fullprice"]);
        }
        array_push($extensions, $item);
    }
   //echo "<pre>";
   //print_r($extensions);
   ###############################################################################

   //Get all currencies
   ###############################################################################
   $currency_collumns = "";
   $currencies = array();
   $result=$pdo->prepare('SELECT * FROM tblcurrencies');
   $result->execute();
   $data= $result->fetchAll(PDO::FETCH_ASSOC);
   foreach ($data as $key => $value) {
       array_push($currencies, $value);
       $currency_collumns .= "<th>".$value["code"]."</th>";
   }
   ###############################################################################



   echo '<form action="'.$modulelink.'" method="post">';
   echo '<div class="tablebg" align="center">';
   echo '<table id="domainpricing" class="table table-bordered table-hover table-condensed dt-bootstrap" cellspacing="1" cellpadding="3" border="0">';
   echo '<thead><th>Extension</th>'.$currency_collumns.'<th></th></thead>';
   echo '<tbody>';
   //DISPLAY CURRENT EXTENSIONS
   foreach($extensions as $extension){
       echo '<tr>';
           echo '<td style="width:150px;font-weight:bold;">'.$extension["extension"].'</td>';
           foreach($currencies as $currency){
               echo '<td style="width:100px;"><input name="EXT['.$extension["pricing"][$currency["code"]]["extension_id"].'][PRICE]" type="text" value="'.$extension["pricing"][$currency["code"]]["fullprice"].'" style="width:100px;"/></td>';
           }
           echo '<td width="20"><button name="deletepricing" value="'.$extension["extension"].'">Delete</button></td></tr>';
       echo '</tr>';
   }
   //ADD NEW EXTENSION
   echo '<tr>';
       echo '<td style="width:150px;"><input type="text" name="ADD[EXTENSION]" style="width:150px;" /></td>';
       foreach($currencies as $currency){
           echo '<td style="width:100px;"><input name="ADD[PRICE]['.$currency["id"].']" type="text" style="width:100px;"/></td>';
       }
       echo '<td></td>';
   echo '</tr>';



   echo '</tbody>';
   echo '</table>';
   echo '</div>';
   echo '<p align="center"><input class="btn" name="savepricing" type="submit" value="Save Changes"></p>';
   echo '</form>';

} catch (\Exception $e) {
   logmessage("backend.pricing", "DB error", $e->getMessage());
   return backorder_api_response(599, "FAILED. Please contact Support.");
}



?>
