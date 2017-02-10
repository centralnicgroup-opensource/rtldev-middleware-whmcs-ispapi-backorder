<?php
//INSERT ALL MISSING PRICES - NEEDED WHEN RESELLER ADDS NEW CURRENCIES
###############################################################################
//GET TOTAL NUMBER OF CURRENCIES
$currencies = array();
$nb_currencies = 0;
$r0 = mysql_query("SELECT * FROM tblcurrencies");
while ($d0 = mysql_fetch_array($r0)){
    array_push($currencies, $d0);
    $nb_currencies++;
}
//GO THROUGH ALL EXTENSIONS
$r = mysql_query("SELECT distinct(extension) FROM backorder_pricing");
while ($d= mysql_fetch_array($r)) {
        $extension =$d["extension"];
        $r1= mysql_query("SELECT count(*) as nb_prices FROM backorder_pricing WHERE extension='".$extension."'");
        $d1 = mysql_fetch_array($r1);
        $nb_prices = $d1["nb_prices"];

        if($nb_prices < $nb_currencies){
            foreach($currencies as $currency){
                $r2 = mysql_query("SELECT * FROM backorder_pricing WHERE extension='".$extension."' AND currency_id=".$currency["id"]);
                $d2 = mysql_fetch_array($r2);
                if(empty($d2)){
                    //echo "-> Add pricing for currency ".$currency["code"]." in extension $extension<br>";
                    insert_query("backorder_pricing",array("extension" => $extension, "currency_id" => $currency["id"], "fullprice" => "NULL"));
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
$r0 = mysql_query("DELETE FROM backorder_pricing WHERE currency_id NOT IN ($cur_string)");
###############################################################################

//Delete pricing
###############################################################################
if(isset($_REQUEST["deletepricing"])){
    mysql_query("DELETE FROM backorder_pricing WHERE extension='".mysql_real_escape_string($_REQUEST["deletepricing"])."'");
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
        update_query( "backorder_pricing", array("fullprice" => $price ), array("id" => $id));
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
            insert_query("backorder_pricing",array("extension" => $name, "currency_id" => $currency_id, "fullprice" => $price));
        }
    }
    echo '<div class="infobox"><strong><span class="title">Changes Saved Successfully!</span></strong><br>Your changes have been saved.</div>';
}
###############################################################################

//Get all pricing
###############################################################################
$extensions = array();
$result = mysql_query("SELECT distinct(extension) FROM backorder_pricing");
while ($data = mysql_fetch_array($result)) {
    //array_push($extensions, $data);
    $item = array("extension" => $data["extension"] );
    $r = mysql_query("SELECT BP.id as extension_id, BP.currency_id as currency_id, BP.extension, BP.fullprice, CUR.code FROM backorder_pricing BP, tblcurrencies CUR WHERE BP.extension='".$data["extension"]."' AND BP.currency_id=CUR.id");
    while ($d = mysql_fetch_array($r)) {
        //array_push($item["pricing"], array("id" => $d["id"], $d["code"] => $d["fullprice"]) );
        $item["pricing"][$d["code"]] = array("extension_id" => $d["extension_id"], "currency_id" => $d["currency_id"], "fullprice" => $d["fullprice"]);
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
$result = mysql_query("SELECT * FROM tblcurrencies");
while ($data = mysql_fetch_array($result)) {
    array_push($currencies, $data);
    $currency_collumns .= "<th>".$data["code"]."</th>";

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

?>
