<?php
//inlude this _language file in all controllers
include(dirname(__FILE__)."/_language.php");

$statusheader = $_LANG['setallbackorder'];


$fields = array(); //fieldname, apifieldname
$fields[]=array ("fieldname" => $statusheader, 	"apifieldname" => "BACKORDERTYPE");
$fields[]=array ("fieldname" => "Domain Name", 	"apifieldname" => "DOMAIN");
$fields[]=array ("fieldname" => "Drop Date", 	"apifieldname" => "DROPDATE");
$fields[]=array ("fieldname" => "Chars", 		"apifieldname" => "NUMBEROFCHARACTERS");
$fields[]=array ("fieldname" => "Digits", 		"apifieldname" => "NUMBEROFDIGITS");
$fields[]=array ("fieldname" => "Hyphens", 		"apifieldname" => "NUMBEROFHYPHENS");
$fields[]=array ("fieldname" => "Umlauts", 		"apifieldname" => "NUMBEROFUMLAUTS");

$vars["fields"] = $fields;

if(isset($_POST['COMMAND']))
{
	if($_POST['COMMAND']=="QueryDeletedDomainsList")
	{
		$mypost=array();
		$mypost['COMMAND']= "QueryDeletedDomainsList";
		$mypost['FIRST']= $_POST['start'];
		$mypost['LIMIT']= $_POST['length'];
		$mypost['ORDERBY']= $fields[ $_POST['order'][0]['column'] ]['apifieldname'];
		if( $_POST['order'][0]['dir'] == "desc") $mypost['ORDERBY'].= "DESC";
		foreach($_POST as $postname => $postvalue)
		{
			if($postname!="start" && $postname!="length" &&  $postname!="order" )
			{
				$mypost[$postname]= $postvalue;
			}
		}

		require_once '../backend/api.php';
		$command = array_change_key_case($mypost,CASE_UPPER);
		$result = backorder_api_query_list($command);

		$mypost2=array();
		$mypost2['COMMAND']= "QueryPriceList";
		$command2 = array_change_key_case($mypost2,CASE_UPPER);
		$pricelist = backorder_api_query_list($command2);
		$pricelist = $pricelist['ITEMS'];


		$datatableobject=array();
		$datatableobject["runtime"] = $result['RUNTIME'];
		$datatableobject["draw"] = $_POST['draw'];
		$datatableobject["recordsTotal"] = $result['TOTAL'];
		$datatableobject["recordsFiltered"] = $result['TOTAL'];
		$datatableobject["data"] = array();
		$newitem = array();
		foreach($result["ITEMS"] as $cnt => $item)
		{
			$newitem = array();#
			foreach($fields as $field)
			{
				if($field['apifieldname']=="BACKORDERTYPE")
				{
					$tmpfield = '<div class="btn-group btn-group">';

					if($item[$field['apifieldname']]=="FULL")
						$tmpfield .= '<button placeholder2="'.$item['DROPDATE'].'" placeholder="'.$cnt.'" value="'.$item['DOMAIN'].'" class="line'.$cnt.' setbackorder btn btn-success btn-sm active">BACKORDER</button>';
					else
						$tmpfield .= '<button placeholder2="'.$item['DROPDATE'].'" placeholder="'.$cnt.'" value="'.$item['DOMAIN'].'" class="line'.$cnt.' setbackorder btn btn-default btn-sm">BACKORDER</button>';

					$tmpfield .= '</div>';
					$newitem[] = $tmpfield;
				}

				else if($field['apifieldname']!="") $newitem[]= $item[$field['apifieldname']];
				else $newitem[]= '';
			}
			$datatableobject["data"][] = $newitem;
		}
		echo json_encode($datatableobject);
		//ENDE AJAX REQUEST
	}

}


$vars["breadcrumb"][] = array("last" => false, "link" => "", "label" => "Backorder" );
$vars["breadcrumb"][] = array("last" => true, "link" => "", "label" => $_LANG["domainheader"]);
$vars["displayTitle"] = $_LANG["domainheader"];

$vars["BackorderSidebar"] .= '<div menuitemname="Client Details" class="panel panel-default">
<div class="panel-heading">
<h3 class="panel-title"><i class="fa fa-calendar"></i>&nbsp; '.$_LANG['freedomains'].'</h3>
</div>
<div class="list-group" id="droppingdomains">
</div>
</div>';

?>
