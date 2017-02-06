<?php

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

?>
