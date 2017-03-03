<?php // $command, $userid

if ( !isset($command["LIMIT"]) ) $command["LIMIT"] = 100;
if ( !isset($command["FIRST"]) ) $command["FIRST"] = 0;

$limit = isset($command["LIMIT"])? $command["FIRST"].",".$command["LIMIT"] : "";

include(dirname(__FILE__)."/../../../../configuration.php");

$options = array(
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
);

try {
	$db = new PDO("mysql:dbname=".$db_name.";host=".$db_host, $db_username, $db_password, $options);
} catch (PDOException $ex) {
	return backorder_api_response(549, "DB Connect failed");
}

$r = backorder_api_response(200);

$orderby = "";

$orders = array(
	"DOMAIN" => "domain,zone",
	"DOMAINDESC" => "domain DESC,zone DESC",
	"DROPDATE" => "DATE(drop_date),domain,zone",
	"DROPDATEDESC" => "DATE(drop_date) DESC,domain DESC,zone DESC",
	"NUMBEROFCHARACTERS" => "domain_number_of_characters,domain,zone",
	"NUMBEROFCHARACTERSDESC" => "domain_number_of_characters DESC,domain DESC,zone DESC",
	"NUMBEROFDIGITS" => "domain_number_of_digits,domain,zone",
	"NUMBEROFDIGITSDESC" => "domain_number_of_digits DESC,domain DESC,zone DESC",
	"NUMBEROFHYPHENS" => "domain_number_of_hyphens,domain,zone",
	"NUMBEROFHYPHENSDESC" => "domain_number_of_hyphens DESC,domain DESC,zone DESC",
);

if ( isset($command["ORDERBY"]) && isset($orders[$command["ORDERBY"]]) ) {
	$orderby = "ORDER BY ".$orders[$command["ORDERBY"]];
}

$limit = isset($command["LIMIT"])? "LIMIT ".$command["FIRST"].",".$command["LIMIT"] : "";

$conditions = "";
$conditions_values = array();

//TLD FILTER
if ( isset($command["TLD"]) && $command["TLD"] != "_all_" ){
    //TLD SELECTED
    $conditions .= "AND zone = :TLD\n";
	$conditions_values[":TLD"] = $command["TLD"];
}else{
    //TLD NOT SELECTED
    //GET LIST OF ALL EXTENSIONS AVAILABLE FOR BACKORDER TO ONLY DISPLAY THOSE ONES
    $result = full_query("select extension from backorder_pricing GROUP BY extension");
    $i=0;
    while ($b = mysql_fetch_array($result)) {
        if($i==0){
            $conditions .= "AND ( zone = :TLD$i\n";
        	$conditions_values[":TLD$i"] = $b["extension"];
        }else{
            $conditions .= "OR zone = :TLD$i\n";
        	$conditions_values[":TLD$i"] = $b["extension"];
        }
        $i++;
    }
    //CLOSE THE PARENTHESIS
    if($i!=0){
        $conditions .= ")\n";
    }
}

if ( isset($command["MAXNUMBEROFHYPHENS"]) && preg_match('/^([0-9]+)$/', $command["MAXNUMBEROFHYPHENS"]) ) {
	$conditions .= "AND domain_number_of_hyphens <= :MAXNUMBEROFHYPHENS\n";
	$conditions_values[":MAXNUMBEROFHYPHENS"] = $command["MAXNUMBEROFHYPHENS"];
}
if ( isset($command["MAXNUMBEROFUMLAUTS"]) && preg_match('/^([0-9]+)$/', $command["MAXNUMBEROFUMLAUTS"]) ) {
	$conditions .= "AND domain_number_of_umlauts <= :MAXNUMBEROFUMLAUTS\n";
	$conditions_values[":MAXNUMBEROFUMLAUTS"] = $command["MAXNUMBEROFUMLAUTS"];
}
if ( isset($command["MAXNUMBEROFDIGITS"]) && preg_match('/^([0-9]+)$/', $command["MAXNUMBEROFDIGITS"]) ) {
	$conditions .= "AND domain_number_of_digits <= :MAXNUMBEROFDIGITS\n";
	$conditions_values[":MAXNUMBEROFDIGITS"] = $command["MAXNUMBEROFDIGITS"];
}
if ( isset($command["MAXNUMBEROFLETTERS"]) && preg_match('/^([0-9]+)$/', $command["MAXNUMBEROFLETTERS"]) ) {
	$conditions .= "AND (domain_number_of_characters - domain_number_of_digits - domain_number_of_hyphens) <= :MAXNUMBEROFLETTERS\n";
	$conditions_values[":MAXNUMBEROFLETTERS"] = $command["MAXNUMBEROFLETTERS"];
}
if ( isset($command["DOMAINREGEXP"]) && strlen($command["DOMAINREGEXP"]) ) {
	$conditions .= "AND domain REGEXP :DOMAINREGEXP\n";
	$conditions_values[":DOMAINREGEXP"] = $command["DOMAINREGEXP"];
}
if ( isset($command["DOMAINNOTREGEXP"]) && strlen($command["DOMAINNOTREGEXP"]) ) {
	$conditions .= "AND domain NOT REGEXP :DOMAINNOTREGEXP\n";
	$conditions_values[":DOMAINNOTREGEXP"] = $command["DOMAINNOTREGEXP"];
}
if ( isset($command["CHARS_COUNT_MIN"]) && preg_match('/^([0-9]+)$/', $command["CHARS_COUNT_MIN"]) ) {
	$conditions .= "AND (domain_number_of_characters) >= :CHARS_COUNT_MIN\n";
	$conditions_values[":CHARS_COUNT_MIN"] = $command["CHARS_COUNT_MIN"];
}
if ( isset($command["CHARS_COUNT_MAX"]) && preg_match('/^([0-9]+)$/', $command["CHARS_COUNT_MAX"]) ) {
	$conditions .= "AND (domain_number_of_characters) <= :CHARS_COUNT_MAX\n";
	$conditions_values[":CHARS_COUNT_MAX"] = $command["CHARS_COUNT_MAX"];
}
if ( isset($command["LETTERS_COUNT_MIN"]) && preg_match('/^([0-9]+)$/', $command["LETTERS_COUNT_MIN"]) ) {
	$conditions .= "AND (domain_number_of_characters - domain_number_of_digits - domain_number_of_hyphens - domain_number_of_umlauts) >= :LETTERS_COUNT_MIN\n";
	$conditions_values[":LETTERS_COUNT_MIN"] = $command["LETTERS_COUNT_MIN"];
}
if ( isset($command["LETTERS_COUNT_MAX"]) && preg_match('/^([0-9]+)$/', $command["LETTERS_COUNT_MAX"]) ) {
	$conditions .= "AND (domain_number_of_characters - domain_number_of_digits - domain_number_of_hyphens - domain_number_of_umlauts) <= :LETTERS_COUNT_MAX\n";
	$conditions_values[":LETTERS_COUNT_MAX"] = $command["LETTERS_COUNT_MAX"];
}
if ( isset($command["DIGITS_COUNT_MIN"]) && preg_match('/^([0-9]+)$/', $command["DIGITS_COUNT_MIN"]) ) {
	$conditions .= "AND (domain_number_of_digits) >= :DIGITS_COUNT_MIN\n";
	$conditions_values[":DIGITS_COUNT_MIN"] = $command["DIGITS_COUNT_MIN"];
}
if ( isset($command["DIGITS_COUNT_MAX"]) && preg_match('/^([0-9]+)$/', $command["DIGITS_COUNT_MAX"]) ) {
	$conditions .= "AND (domain_number_of_digits) <= :DIGITS_COUNT_MAX\n";
	$conditions_values[":DIGITS_COUNT_MAX"] = $command["DIGITS_COUNT_MAX"];
}
if ( isset($command["DIGITS_NO"]) && $command["DIGITS_NO"]=="true" ) {
	$conditions .= "AND domain_number_of_digits = 0\n";
}
if ( isset($command["DIGITS_ONLY"]) && $command["DIGITS_ONLY"]=="true" ) {
	$conditions .= "AND domain_number_of_digits = domain_number_of_characters\n";
}
if ( isset($command["HYPHENS_COUNT_MIN"]) && preg_match('/^([0-9]+)$/', $command["HYPHENS_COUNT_MIN"]) ) {
	$conditions .= "AND (domain_number_of_hyphens) >= :HYPHENS_COUNT_MIN\n";
	$conditions_values[":HYPHENS_COUNT_MIN"] = $command["HYPHENS_COUNT_MIN"];
}
if ( isset($command["HYPHENS_COUNT_MAX"]) && preg_match('/^([0-9]+)$/', $command["HYPHENS_COUNT_MAX"]) ) {
	$conditions .= "AND (domain_number_of_hyphens) <= :HYPHENS_COUNT_MAX\n";
	$conditions_values[":HYPHENS_COUNT_MAX"] = $command["HYPHENS_COUNT_MAX"];
}
if ( isset($command["HYPHENS_NO"]) && $command["HYPHENS_NO"]=="true" ) {
	$conditions .= "AND domain_number_of_hyphens = 0\n";
}
if ( isset($command["DROPDATE_FROM"]) && $command["DROPDATE_FROM"]!="" ) {
	$conditions .= "AND DATE(drop_date) >= :DROPDATE_FROM\n";  //CONVERT_TZ(DATE_ADD(drop_date, INTERVAL 31 DAY), 'UTC', 'Europe/Berlin') >= :DROPDATE_FROM\n";
	$conditions_values[":DROPDATE_FROM"] = $command["DROPDATE_FROM"];
}
if ( isset($command["DROPDATE_TO"]) && $command["DROPDATE_TO"]!=""  ) {
	$conditions .= "AND DATE(drop_date) <= :DROPDATE_TO\n";  //CONVERT_TZ(DATE_ADD(drop_date, INTERVAL 31 DAY), 'UTC', 'Europe/Berlin') <= :DROPDATE_TO\n";
	$conditions_values[":DROPDATE_TO"] = $command["DROPDATE_TO"];
}

$stmt = $db->prepare("
	SELECT SQL_CALC_FOUND_ROWS zone, domain, drop_date, domain_number_of_characters, domain_number_of_hyphens, domain_number_of_digits, domain_number_of_umlauts
	FROM pending_domains
	WHERE drop_date > NOW()
	$conditions
	$orderby
	$limit;
");
$stmt->execute($conditions_values);

while ( $data = $stmt->fetch() ) {
	$r["PROPERTY"]["DOMAIN"][] = utf8_decode($data["domain"].".".$data["zone"]);
	$r["PROPERTY"]["LABEL"][] = $data["domain"];
	$r["PROPERTY"]["TLD"][] = $data["zone"];
	$r["PROPERTY"]["DROPDATE"][] = $data["drop_date"];
	$r["PROPERTY"]["STATUS"][] = '-';
	$r["PROPERTY"]["BACKORDERTYPE"][] = '';
	$r["PROPERTY"]["NUMBEROFCHARACTERS"][] = $data["domain_number_of_characters"];
	$r["PROPERTY"]["NUMBEROFHYPHENS"][] = $data["domain_number_of_hyphens"];
	$r["PROPERTY"]["NUMBEROFDIGITS"][] = $data["domain_number_of_digits"];
}

if ( isset($r["PROPERTY"]["DOMAIN"]) && $userid ) {
	foreach ( $r["PROPERTY"]["DOMAIN"] as $index => $domain ) {
		if ( preg_match('/^(.*)\.(.*)$/', $domain, $m) ) {
			$result = select_query('backorder_domains','*',array("userid" => $userid, "domain" => $m[1], "tld" => $m[2] ));
			$data = mysql_fetch_assoc($result);
			if ( $data ) {
				$r["PROPERTY"]["STATUS"][$index] = strtoupper($data["status"]);
				$r["PROPERTY"]["BACKORDERTYPE"][$index] = strtoupper($data["type"]);
			}
		}
	}
}

$data = $db->query("SELECT FOUND_ROWS() AS `found_rows`;")->fetch();
$r["PROPERTY"]["TOTAL"][] = $data['found_rows'];

return $r;

?>
