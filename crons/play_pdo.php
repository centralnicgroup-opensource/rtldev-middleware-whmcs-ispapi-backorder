<?php
//help: http://wiki.hashphp.org/PDO_Tutorial_for_MySQL_Developers

require_once dirname(__FILE__)."/../../../../init.php";

use WHMCS\Database\Capsule;

try {

    //GET DPO CONNECTION
    $pdo = Capsule::connection()->getPdo();


    //SELECT WITH PARAMS
    $stmt = $pdo->prepare("SELECT * FROM backorder_domains");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>"; print_r($rows); echo "</pre>";

    // //SELECT WITHOUT PARAMS
    // $stmt = $pdo->prepare('SELECT * FROM backorder_domains LIMIT 1');
    // $stmt->execute();
    // $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // echo "<pre>"; print_r($rows); echo "</pre>";
    //
    // //INSERT
    // $stmt = $pdo->prepare("INSERT INTO backorder_domains(userid, domain, tld) VALUES(:userid, :domain, :tld)");
    // $stmt->execute(array(':userid' => 1, ':domain' => "test2" , ':tld' => "com"));
    // $affected_rows = $stmt->rowCount();
    // echo "Affected rows: ".$affected_rows;
    //
    // //UPDATE
    // $stmt = $pdo->prepare("UPDATE backorder_domains SET status=? WHERE domain=? AND tld=?");
    // $stmt->execute(array("PROCESSING", "test2", "com"));
    // $affected_rows = $stmt->rowCount();
    // echo "Affected rows: ".$affected_rows;
    //
    // //DELETE
    // $stmt = $pdo->prepare("DELETE FROM backorder_domains WHERE domain=?");
    // $stmt->execute(array("test2"));
    // $affected_rows = $stmt->rowCount();
    // echo "Affected rows: ".$affected_rows;

} catch (\Exception $e) {
    echo $e;
}

?>
