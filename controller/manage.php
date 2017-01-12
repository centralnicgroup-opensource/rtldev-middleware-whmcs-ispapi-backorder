<?php
//inlude this _language file in all controllers
include(dirname(__FILE__)."/_language.php");


$vars["breadcrumb"][] = array("last" => false, "link" => "", "label" => "Backorder" );
$vars["breadcrumb"][] = array("last" => true, "link" => "", "label" => $_LANG["managebackorders"]);
$vars["displayTitle"] = $_LANG["managebackorders"];

$statusheader = $_LANG['setallbackorder'];

$fields = array(); //fieldname, apifieldname
$fields[]=array ("fieldname" => $statusheader, 	"apifieldname" => "BACKORDERTYPE");
$fields[]=array ("fieldname" => "Domain Name", 	"apifieldname" => "DOMAIN");
$fields[]=array ("fieldname" => "Drop Date", 	"apifieldname" => "DROPDATE");
$fields[]=array ("fieldname" => "Status", 		"apifieldname" => "STATUS");
/*$fields[]=array ("fieldname" => "Chars", 		"apifieldname" => "NUMBEROFCHARACTERS");
$fields[]=array ("fieldname" => "Digits", 		"apifieldname" => "NUMBEROFDIGITS");
$fields[]=array ("fieldname" => "Hyphens", 		"apifieldname" => "NUMBEROFHYPHENS");
$fields[]=array ("fieldname" => "Umlaute", 		"apifieldname" => "NUMBEROFUMLAUTS");*/
$vars["fields"] = $fields;

if(isset($_POST['COMMAND']))
{
	if($_POST['COMMAND']=="QueryBackorderList")
	{
		$mypost=array();
		$mypost['COMMAND']= "QueryBackorderList";
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
						$tmpfield .= '<button placeholder2="'.$item['DROPDATE'].'" placeholder="'.$cnt.'" value="'.$item['DOMAIN'].'" class="line'.$cnt.' setbackorder btn btn-default btn-sm active">'.$_LANG['deletebutton'].'</button>';
					/*else
						$tmpfield .= '<button placeholder2="'.$item['DROPDATE'].'" placeholder="'.$cnt.'" value="'.$item['DOMAIN'].'" class="line'.$cnt.' setbackorder btn btn-default">BACKORDER</button>';*/


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


$vars["BackorderSidebar"] .= '<div menuitemname="Client Details" class="panel panel-default">
<div class="panel-heading">
<h3 class="panel-title"><i class="fa fa-question-circle"></i>&nbsp;'.$_LANG['overviewbackorderstatus'].'</h3>
</div>
<div class="panel-body">
	<table width="100%">
		<tr>
			<td align="center" width="75%"><b>'.$_LANG['tldstatus'].'</b></td>
			<td align="center" ><b>'.$_LANG['tldnumber'].'</b></td>
		</tr>
	</table>
	<hr style="margin:5px;">
	<table width="100%" id="overviewbackorderstatus">
	</table>

</div>
</div>
<script>
	$(document).ready(function() {
			$.ajax({
			type: "POST",
			async: false,
			dataType: "json",
			url: "'.$modulepath.'/backend/call.php",
			data: {COMMAND : "QueryBackorderOverviewStatus"},
			success: function(data){
				$("#overviewbackorderstatus").html();
				var totallite=0;
				var totalfull=0;
				var total=0;
				var output="";
				$.each(data.PROPERTY, function(i, obj) {
					  totallite = totallite + parseInt(obj.lite);
					  totalfull = totalfull + parseInt(obj.full);
					  total = total + parseInt(obj.lite) + parseInt(obj.full);
					  output="";
					  output += \'<tr onclick="setvalue(\\\'status\\\', \\\'\'+obj.status+\'\\\', $(this));">\';
					  output += \'<td align="center" width="75%"><strong>\'+obj.status+\'</strong></td>\';
					  output += \'<td align="center" >\'+obj.anzahl +\'</td>\';
					  output += \'</tr>\';
					  $("#overviewbackorderstatus").append(output);
				});

				$("#boxbackordertotal").text(data.total);
				$("#boxbackordersuccessful").text(data.PROPERTY[\'SUCCESSFUL\'][\'anzahl\']);
				$("#boxbackordermissed").text(data.PROPERTY[\'FAILED\'][\'anzahl\']);
				$("#boxbackorderrequest").text(data.PROPERTY[\'REQUESTED\'][\'anzahl\']);
			},
			error: function(data){
			}
		});
	});
</script>';


$vars["BackorderSidebar"] .= '<div menuitemname="Client Details" class="panel panel-default">
<div class="panel-heading">
<h3 class="panel-title"><i class="fa fa-bars"></i>&nbsp;<b>'.$_LANG['overviewbackorder'].'</b></h3>
</div>
<div class="panel-body">
	<table width="100%">
		<tr>
			<td align="center" width="25%"><b>TLD</b></td>
			<td align="center" width="25%">&nbsp;<b>'.$_LANG['tldnumber'].'</b></td>
		</tr>
	</table>
	<hr style="margin:5px;">
	<table class="tldselection" width="100%" id="overviewlist">
	</table>
	<hr style="margin:5px;">
	<table class="tldselection" width="100%" id="overviewlistfooter">
	</table>
</div>
</div>
<script>
	$(document).ready(function() {
			$.ajax({
			type: "POST",
			async: false,
			dataType: "json",
			url: "'.$modulepath.'/backend/call.php",
			data: {COMMAND : "QueryBackorderOverviewList"},
			success: function(data){
				$("#overviewlist").html();
				var totallite=0;
				var totalfull=0;
				var total=0;
				var output="";
				$.each(data.PROPERTY, function(i, obj) {
					  totallite = totallite + parseInt(obj.LITE);
					  totalfull = totalfull + parseInt(obj.FULL);
					  total = total + parseInt(obj.LITE) + parseInt(obj.FULL);
					  output="";
					  output += \'<tr>\';
					  output += \'<td width="25%" align="center" onclick="setvalue(\\\'tld\\\', \\\'\'+obj.tld+\'\\\', $(this) );"><strong>\'+obj.tld+\'</strong></td>\';
					  output += \'<td width="25%" align="center" onclick="setvalue(\\\'tld\\\', \\\'\'+obj.tld+\'\\\', $(this), false ); setvalue(\\\'type\\\', \\\'FULL\\\', $(this), true, false  );">\'+obj.FULL +\'</td>\';
					  output += \'</tr>\';
					  $("#overviewlist").append(output);
				});
				  output="";
				  output += \'<tr>\';
				  output += \'<td width="25%" align="center" onclick="setvalue(\\\'type\\\', \\\'\\\', $(this), true, true  );" ><strong>TOTAL</strong></td>\';
				  output += \'<td width="25%" align="center" onclick="setvalue(\\\'type\\\', \\\'FULL\\\', $(this), true, true  );" >\'+totalfull +\'</td>\';
				  output += \'</tr>\';
				  $("#overviewlistfooter").append(output);
			},
			error: function(data){
			}
		});
	});
</script>';






?>
