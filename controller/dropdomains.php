<?php
//inlude this _language file in all controllers
include(dirname(__FILE__)."/_language.php");

$statusheader = $_LANG['setallbackorder'];

$fields = array(); //fieldname, apifieldname
$fields[]=array ("fieldname" => "",     "apifieldname" => "BACKORDERTYPE");
$fields[]=array ("fieldname" => $_LANG['domainname'],   "apifieldname" => "DOMAIN");
$fields[]=array ("fieldname" => $_LANG['dropdate'],     "apifieldname" => "DROPDATE");
$fields[]=array ("fieldname" => $_LANG['chars'],        "apifieldname" => "NUMBEROFCHARACTERS");
$fields[]=array ("fieldname" => $_LANG['digits'],       "apifieldname" => "NUMBEROFDIGITS");
$fields[]=array ("fieldname" => $_LANG['hyphens'],      "apifieldname" => "NUMBEROFHYPHENS");

$vars["fields"] = $fields;

if (isset($_POST['COMMAND'])) {
    // ************************************
    if ($_POST['COMMAND']=="QueryDeletedDomainsList") {
        $mypost=array();
        $mypost['COMMAND']= "QueryDeletedDomainsList";
        $mypost['FIRST']= $_POST['start'];
        $mypost['LIMIT']= $_POST['length'];
        $mypost['ORDERBY']= $fields[ $_POST['order'][0]['column'] ]['apifieldname'];
        if ($_POST['order'][0]['dir'] == "desc") {
            $mypost['ORDERBY'].= "DESC";
        }
        foreach ($_POST as $postname => $postvalue) {
            if ($postname!="start" && $postname!="length" &&  $postname!="order") {
                $mypost[$postname]= $postvalue;
            }
        }

        require_once '../backend/api.php';
        $command = array_change_key_case($mypost, CASE_UPPER);
        $result = backorder_api_query_list($command);

        $mypost2=array();
        $mypost2['COMMAND']= "QueryPriceList";
        $command2 = array_change_key_case($mypost2, CASE_UPPER);
        $pricelist = backorder_api_query_list($command2);
        $pricelist = $pricelist['ITEMS'];


        $datatableobject=array();
        $datatableobject["runtime"] = $result['RUNTIME'];
        $datatableobject["draw"] = $_POST['draw'];
        $datatableobject["recordsTotal"] = $result['TOTAL'];
        $datatableobject["recordsFiltered"] = $result['TOTAL'];
        $datatableobject["data"] = array();
        $newitem = array();
        foreach ($result["ITEMS"] as $cnt => $item) {
            $newitem = array();#
            foreach ($fields as $field) {
                if ($field['apifieldname']=="BACKORDERTYPE") {
                    $tmpfield = '<div class="btn-group btn-group">';
                    if ($item["BACKORDERTYPE"]=="FULL") {
                        $tmpfield .= '<button placeholder2="'.$item['DROPDATE'].'" placeholder="'.$cnt.'" value="'.$item['DOMAIN'].'" class="line'.$cnt.' setbackorder btn btn-success btn-sm active">BACKORDER</button>';
                    } else {
                        $tmpfield .= '<button placeholder2="'.$item['DROPDATE'].'" placeholder="'.$cnt.'" value="'.$item['DOMAIN'].'" class="line'.$cnt.' setbackorder btn btn-default btn-sm">BACKORDER</button>';
                    }
                    $tmpfield .= '</div>';
                    $newitem[] = $tmpfield;
                } elseif ($field['apifieldname']!="") {
                    $newitem[]= $item[$field['apifieldname']];
                } else {
                    $newitem[]= '';
                }
            }
            $datatableobject["data"][] = $newitem;
        }
        echo json_encode($datatableobject);
    }
    // ************************************
}

$vars["breadcrumb"][] = array("last" => false, "link" => "", "label" => "Backorder" );
$vars["breadcrumb"][] = array("last" => true, "link" => "", "label" => $_LANG["domainheader"]);
$vars["displayTitle"] = $_LANG["domainheader"];
