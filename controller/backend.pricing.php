<?php
$root_path = $_SERVER["DOCUMENT_ROOT"];
$script_path = preg_replace("/.modules.addons..+$/", "", dirname($_SERVER["SCRIPT_NAME"]));
if (!empty($script_path)) {
    $root_path .= $script_path;
}
$init_path = implode(DIRECTORY_SEPARATOR, array($root_path,"init.php"));
$init_path = preg_replace("/(\/|\\\)" . $GLOBALS["customadminpath"] . "(\/|\\\)init.php$/", DIRECTORY_SEPARATOR . "init.php", $init_path);
if (file_exists($init_path)) {
    require_once($init_path);
} else {
    exit("cannot find init.php");
}

use WHMCS\Database\Capsule;

//INSERT ALL MISSING PRICES - NEEDED WHEN RESELLER ADDS NEW CURRENCIES
###############################################################################

try {
    $pdo = Capsule::connection()->getPdo();

   //GET TOTAL NUMBER OF CURRENCIES
    $currencies = array();
    $nb_currencies = 0;
    $stmt = $pdo->prepare("SELECT * FROM tblcurrencies");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as $row) {
        array_push($currencies, $row);
        $nb_currencies++;
    }

   //GO THROUGH ALL EXTENSIONS AND ADD MISSING PRICES (WHEN YOU ADD A NEW CURRENCY, IT WILL ADD THE PRICES FOR THIS CURRENCY)
   ###############################################################################
    $stmt = $pdo->prepare("SELECT distinct(extension) FROM backorder_pricing");
    $stmt->execute();
    $d = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($d as $value) {
        $extension = $value["extension"];
        $stmt = $pdo->prepare("SELECT count(*) as nb_prices FROM backorder_pricing WHERE extension=?");
        $stmt->execute(array($extension));
        $d1 = $stmt->fetch(PDO::FETCH_ASSOC);
        $nb_prices = $d1["nb_prices"];
        if ($nb_prices < $nb_currencies) {
            foreach ($currencies as $currency) {
                $stmt = $pdo->prepare("SELECT * FROM backorder_pricing WHERE extension=? AND currency_id=?");
                $stmt->execute(array($extension, $currency["id"]));
                $d2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (empty($d2)) {
                    $insert_stmt = $pdo->prepare("INSERT INTO backorder_pricing(extension, currency_id, fullprice) VALUES(:extension, :currency_id, :fullprice)");
                    $insert_stmt->execute(array(':extension' => $extension, ':currency_id' => $currency["id"], ':fullprice' => "NULL"));
                    //$affected_rows = $insert_stmt->rowCount();
                }
            }
        }
    }
   ###############################################################################

   //CLEAN ALL NON EXISTING CURRENCIES
   ###############################################################################
    $cur_string = "";
    foreach ($currencies as $currency) {
        $cur_string .= $pdo->quote($currency["id"]).",";
    }
    $cur_string .= "-1";
    $stmt = $pdo->prepare("DELETE FROM backorder_pricing WHERE currency_id NOT IN ($cur_string)");
    $stmt->execute();
   ###############################################################################

   //DELETE PRICING
   ###############################################################################
    if (isset($_REQUEST["deletepricing"])) {
        $delete_stmt = $pdo->prepare("DELETE FROM backorder_pricing WHERE extension=?");
        $delete_stmt->execute(array($_REQUEST["deletepricing"]));
        echo '<div class="infobox"><strong><span class="title">Deletion Successfully!</span></strong><br>Your backorder pricing has been deleted.</div>';
    }
   ###############################################################################

   //SAVE PRICING
   ###############################################################################
    if (isset($_REQUEST["savepricing"])) {
        foreach ($_POST["EXT"] as $id => $extension) {
            $price = $extension["PRICE"];
            if (empty($price) && !is_numeric($price)) { //IF PRICE EMPTY OR PRICE NOT INTEGER
                $price = "NULL";
            }
            $update_stmt = $pdo->prepare("UPDATE backorder_pricing SET fullprice=? WHERE id=?");
            $update_stmt->execute(array($price, $id));
        }
        if (isset($_POST["ADD"]["EXTENSION"]) && !empty($_POST["ADD"]["EXTENSION"])) {
            $name = strtolower($_POST["ADD"]["EXTENSION"]);
            if (substr($name, 0, 1) === ".") { //CASE IF CUSTOMER ADDED . BEFORE EXTENSION
                $name = substr($name, 1, strlen($name));
            }
            foreach ($_POST["ADD"]["PRICE"] as $currency_id => $price) {
                if (empty($price) && !is_numeric($price)) { //IF PRICE EMPTY OR PRICE NOT INTEGER
                    $price = "NULL";
                }
                $insert_stmt = $pdo->prepare("INSERT INTO backorder_pricing(extension, currency_id, fullprice) VALUES(:extension, :currency_id, :fullprice)");
                $insert_stmt->execute(array(':extension' => $name, ':currency_id' => $currency_id , ':fullprice' => $price));
            }
        }
        echo '<div class="infobox"><strong><span class="title">Changes Saved Successfully!</span></strong><br>Your changes have been saved.</div>';
    }
   ###############################################################################

   //GET ALL PRICING TO DISPLAY
   ###############################################################################
    $extensions = array();
    $stmt = $pdo->prepare("SELECT distinct(extension) FROM backorder_pricing");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($data as $value) {
        $item = array("extension" => $value["extension"] );
        $stmt = $pdo->prepare("SELECT BP.id as extension_id, BP.currency_id as currency_id, BP.extension, BP.fullprice, CUR.code FROM backorder_pricing BP, tblcurrencies CUR WHERE BP.extension=? AND BP.currency_id=CUR.id");
        $stmt->execute(array($value["extension"]));
        $d = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($d as $val) {
            $item["pricing"][$val["code"]] = array("extension_id" => $val["extension_id"], "currency_id" => $val["currency_id"], "fullprice" => number_format($val["fullprice"], 2, '.', ''));
        }
        array_push($extensions, $item);
    }
   ###############################################################################

   //GET ALL CURRENCIES
   ###############################################################################
    $currency_collumns = "";
    $currencies = array();
    $stmt = $pdo->prepare('SELECT * FROM tblcurrencies');
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($data as $value) {
        array_push($currencies, $value);
        $currency_collumns .= "<th>".$value["code"]."</th>";
    }
   ###############################################################################

    echo '<form action="'.$modulelink.'" method="post">';
    echo '<div class="tablebg" align="center">';
    echo '<table id="domainpricing" class="table table-bordered table-hover table-condensed dt-bootstrap datatable" cellspacing="1" cellpadding="3" border="0">';
    echo '<thead><th>Extension</th>'.$currency_collumns.'<th></th></thead>';
    echo '<tbody>';
   //DISPLAY CURRENT EXTENSIONS
    foreach ($extensions as $extension) {
        echo '<tr>';
           echo '<td style="width:150px;font-weight:bold;">'.$extension["extension"].'</td>';
        foreach ($currencies as $currency) {
            echo '<td style="width:100px;"><input name="EXT['.$extension["pricing"][$currency["code"]]["extension_id"].'][PRICE]" type="text" value="'.$extension["pricing"][$currency["code"]]["fullprice"].'" style="width:100px;"/></td>';
        }
           echo '<td width="20"><button name="deletepricing" class="btn btn-danger btn-sm" value="'.$extension["extension"].'">Delete</button></td></tr>';
        echo '</tr>';
    }
   //ADD NEW EXTENSION
    echo '<tr>';
       echo '<td style="width:150px;"><input type="text" name="ADD[EXTENSION]" style="width:150px;" /></td>';
    foreach ($currencies as $currency) {
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
    die($e->getMessage());
}
