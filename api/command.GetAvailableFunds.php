<?php // $command
use WHMCS\Database\Capsule;
//RETURN THE CURRENT AVAILABLE CREDIT !
//AVAILABLE CREDIT = CURRENT CREDIT BALANCE - UNPAID INVOICES - RESERVED AMOUNT
//LIST OF ALL STATUS: 'REQUESTED','ACTIVE','PROCESSING','SUCCESSFUL','FAILED','CANCELLED','AUCTION-PENDING','AUCTION-WON','AUCTION-LOST','PENDING-PAYMENT'
 try {
	 //GET DPO CONNECTION
    $pdo = Capsule::connection()->getPdo();

	if ( !$userid )	return backorder_api_response(531);

	$r = backorder_api_response(200);

	//GET THE CLIENT CURRENCY
	$result = $pdo->prepare("SELECT cur.* FROM tblcurrencies cur, tblclients cli WHERE cli.currency=cur.id AND cli.id=?");
	$result->execute(array($userid));
	$cur = $result->fetchAll(PDO::FETCH_ASSOC);
	$cur = $cur[0];

    //GET THE CURRENT CREDIT BALANCE
    #######################################################

	//GET ADMIN USERNAME
	$admin_request= $pdo->prepare("SELECT value FROM tbladdonmodules WHERE module=? AND setting=?");
	$admin_request->execute(array('ispapibackorder', 'username'));
	$rows = $admin_request->fetchAll(PDO::FETCH_ASSOC);
	$rows = $rows[0];
	$adminuser = $rows["value"];

	$d = localAPI("getclientsdetails", array("clientid" => $userid, "stats" => true), $adminuser);
	$credit = 0;
	if($d["client"]["credit"]){
		$credit = $d["client"]["credit"];
	}
	$r["PROPERTY"]["CREDITBALANCE"]["VALUE"] = $credit;
	$r["PROPERTY"]["CREDITBALANCE"]["VALUE_FORMATED"] = formatPrice($credit, $cur);
	//echo "CREDIT BALANCE: ".$credit;
	//echo "<br>";
	#######################################################

	//GET ALL UNPAID INVOICES
	#######################################################
	$results = localAPI("getinvoices", array("userid" => $userid, "limitnum" => "99999999999") ,"admin");
	$unpaidamount = 0;

	foreach($results["invoices"]["invoice"] as $invoice){
		if($invoice["status"] == "Unpaid")
			$ignore = false;
			//THE TYPE OF THE INVOICE IS NOT RETURNED BY THE WHMCS API, WE NEED TO IGNORE THE ADD FUNDS INVOICES.
			//###############################
			$result = $pdo->prepare("SELECT type FROM tblinvoiceitems WHERE invoiceid=?");
			$result->execute(array($invoice["id"]));
			$data = $admin_request->fetchAll(PDO::FETCH_ASSOC);
			$data = $data[0];
			while($data){
				if($data["type"] == "AddFunds"){
					$ignore = true;
				}
			}
			if(!$ignore){
				$unpaidamount = $unpaidamount + $invoice["total"];
			}
	}
	$r["PROPERTY"]["UNPAIDINVOICES"]["VALUE"] = $unpaidamount;
	$r["PROPERTY"]["UNPAIDINVOICES"]["VALUE_FORMATED"] = formatPrice($unpaidamount, $cur);
	//echo "UNPAID INVOICES: ".$unpaidamount;
	//echo "<br>";
	#######################################################

	//GET THE RESERVED AMOUNT
	#######################################################
	//ALL BACKORDER WITH STATUS ACTIVE OR PROCESSING OR AUCTION-PENDING.
	//DON'T ADD PENDING-PAYMENT HERE BECAUSE THIS ONE WILL BE HANDLED WITH THE UNPAID INVOICES
	$reserved_amount = 0;
	$open_backorders = array();
	$result=$pdo->prepare("SELECT id, tld, type FROM backorder_domains WHERE userid=? AND (status=? OR status=? OR status=?)");
	$result->execute(array($userid, 'ACTIVE', 'PROCESSING', 'AUCTION-PENDING'));
	$data = $result->fetchAll(PDO::FETCH_ASSOC);

	foreach ($data as $key => $value) {
		array_push($open_backorders, array("id" => $value["id"], "tld" => $value["tld"], "type" => $value["type"]));
	}

	//if credit < = 0 there is no need to continue.
	if($credit > 0){
		foreach($open_backorders as $backorder){
			//GET THE PRICE OF THE BACKORDER
			$command = array(
					"COMMAND" => "QueryPriceList",
					"USER" => $userid,
					"TLD" => $backorder["tld"]
			);
			$result = backorder_backend_api_call($command);
			if($result["CODE"] == 200){
				if($backorder["type"] == "FULL"){
					$backorder_price = $result["PROPERTY"][$backorder["tld"]]["PRICEFULL"];
				}
				if($backorder_price) {
					$reserved_amount = $reserved_amount + $backorder_price;
				}
			}
		}
	}
	$r["PROPERTY"]["RESERVEDAMOUNT"]["VALUE"] = $reserved_amount;
	$r["PROPERTY"]["RESERVEDAMOUNT"]["VALUE_FORMATED"] = formatPrice($reserved_amount, $cur);
	//echo "RESERVED AMOUNT: ".$reserved_amount;
	//echo "<br>";
	#######################################################

	$available_credit = $credit - $unpaidamount - $reserved_amount;
	if($available_credit <= 0){
		$available_credit = 0;
	}

	$r["PROPERTY"]["AMOUNT"]["VALUE"] = $available_credit; //property used to activated backorders
	$r["PROPERTY"]["AMOUNT"]["VALUE_FORMATED"] = formatPrice($available_credit, $cur);

	return $r;
 } catch (\Exception $e) {
 	logmessage("command.GetAvailableFunds", "DB error", $e->getMessage());
 	return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
 }


?>
