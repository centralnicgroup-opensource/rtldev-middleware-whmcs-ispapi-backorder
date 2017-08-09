<?php // $command, $userid
use WHMCS\Database\Capsule;
try {
	//GET DPO CONNECTION
	$pdo = Capsule::connection()->getPdo();

	if ( !$userid )	return backorder_api_response(531);

	if ( !isset($command["LIMIT"]) ) $command["LIMIT"] = 100;
	if ( !isset($command["FIRST"]) ) $command["FIRST"] = 0;

	$r = backorder_api_response(200);

	$limit = isset($command["LIMIT"])? $command["FIRST"].",".$command["LIMIT"] : "";

	$condition = array("userid" => $userid);
	$fields = 'SQL_CALC_FOUND_ROWS *';
	if ( $userid < 0 ) {
		$condition = array();
		$fields = 'SQL_CALC_FOUND_ROWS backorder_domains.*,(SELECT email FROM tblclients WHERE tblclients.id=backorder_domains.userid LIMIT 1) as useremail';
	}

	if(isset($command['STATUS']) && $command['STATUS']!="")
	{
		$condition['status'] = $command['STATUS'];
	}
	if(isset($command['TLD']) && $command['TLD']!="")
	{
		$condition['tld'] = $command['TLD'];
	}
	if(isset($command['TYPE']) && $command['TYPE']!="")
	{
		$condition['type'] = $command['TYPE'];
	}

	$orderby = "";
	$orders = array(
		"ID" => "id",
		"IDDESC" => "id",
		"DOMAIN" => "domain",
		"DOMAINDESC" => "domain",
		"DROPDATE" => "dropdate",
		"DROPDATEDESC" => "dropdate",
		"NUMBEROFCHARACTERS" => "domain_number_of_characters",
		"NUMBEROFCHARACTERSDESC" => "domain_number_of_characters",
		"NUMBEROFDIGITS" => "domain_number_of_digitse",
		"NUMBEROFDIGITSDESC" => "domain_number_of_digits",
		"NUMBEROFHYPHENS" => "domain_number_of_hyphens",
		"NUMBEROFHYPHENSDESC" => "domain_number_of_hyphens",
	);

	if ( isset($command["ORDERBY"]) && isset($orders[$command["ORDERBY"]]) ) {
		$order = $orders[$command["ORDERBY"]];
		$sortorder = "ASC";
		if($command["ORDERBY"]=="IDDESC") $sortorder = "DESC";
		if($command["ORDERBY"]=="DOMAINDESC") $sortorder = "DESC";
		if($command["ORDERBY"]=="DROPDATEDESC") $sortorder = "DESC";
		if($command["ORDERBY"]=="NUMBEROFCHARACTERSDESC") $sortorder = "DESC";
		if($command["ORDERBY"]=="NUMBEROFDIGITSDESC") $sortorder = "DESC";
		if($command["ORDERBY"]=="NUMBEROFHYPHENSDESC") $sortorder = "DESC";
	}
	// echo "fields \n";
	// print_r($fields);
	// echo " conditon \n";
	// print_r($condition);
	// echo " order \n";
	// print_r($order);
	// echo " sortorder \n";
	// print_r($sortorder);
	// echo " limit \n";
	// print_r($limit);

	$result = select_query('backorder_domains',$fields,$condition, $order, $sortorder, $limit);

	while ($data = mysql_fetch_assoc($result)) {
		// echo "<pre> data 1 "; print_r($data); echo "</pre>";
		$r["PROPERTY"]["ID"][] = $data["id"];
		$r["PROPERTY"]["DOMAIN"][] = $data["domain"].".".$data["tld"];
		$r["PROPERTY"]["LABEL"][] = $data["domain"];
		$r["PROPERTY"]["TLD"][] = $data["tld"];
		$r["PROPERTY"]["STATUS"][] = strtoupper($data["status"]);
		$r["PROPERTY"]["DROPDATE"][] = strtoupper($data["dropdate"]);
		$r["PROPERTY"]["CREATEDDATE"][] = strtoupper($data["createddate"]);
		$r["PROPERTY"]["UPDATEDDATE"][] = strtoupper($data["updateddate"]);

		if ( $userid < 0 ) {
			$r["PROPERTY"]["USER"][] = $data["userid"];
			$r["PROPERTY"]["USEREMAIL"][] = $data["useremail"];
		}
	}

	if ( isset($r["PROPERTY"]["DOMAIN"]) && $userid ) {
		foreach ( $r["PROPERTY"]["DOMAIN"] as $index => $domain ) {
			if ( preg_match('/^(.*)\.(.*)$/', $domain, $m) ) {
				$result=$pdo->prepare("SELECT * FROM backorder_domains WHERE userid=? AND domain=? AND tld=?");
				$result->execute(array($userid, $m[1], $m[2]));
				$data = $result->fetchAll(PDO::FETCH_ASSOC);
				if( $data  ){
					foreach ($data as $key => $value) {
						$r["PROPERTY"]["STATUS"][$index] = strtoupper($value["status"]);
						$r["PROPERTY"]["BACKORDERTYPE"][$index] = strtoupper($value["type"]);
					}
				}
			}
		}
	}

print_r($fields) ;
	$result = select_query('backorder_domains',$fields,$condition);
	$data = mysql_fetch_assoc(mysql_query("SELECT FOUND_ROWS() AS `found_rows`;"));
	echo "<pre> data 2 "; print_r($data); echo "</pre>";

	// $result=$pdo->prepare("SELECT FOUND_ROWS() AS found_rows FROM backorder_domains WHERE ? AND ?");
	// $result->execute(array($fields, $condition));
	// $data = $result->fetchAll(PDO::FETCH_ASSOC);
	// echo "<pre> data 2 "; print_r($data); echo "</pre>";


	$r["PROPERTY"]["TOTAL"][] = $data['found_rows'];

	return $r;
} catch (\Exception $e) {
   logmessage("command.QueryLogList", "DB error", $e->getMessage());
   return backorder_api_response(599, "COMMAND FAILED. Please contact Support.");
}

?>
