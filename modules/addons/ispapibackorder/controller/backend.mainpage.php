<link rel="stylesheet" href="../modules/addons/ispapibackorder/templates/lib/jquery-ui-1.13.1/jquery-ui.min.css">
<script src="../modules/addons/ispapibackorder/templates/lib/jquery-ui-1.13.1/jquery-ui.min.js"></script>
<link rel="stylesheet" href="../modules/addons/ispapibackorder/templates/lib/DataTables/datatables.min.css">
<script src="../modules/addons/ispapibackorder/templates/lib/DataTables/datatables.min.js"></script>
<script src="../modules/addons/ispapibackorder/templates/lib/noty-2.4.1/jquery.noty.packaged.min.js"></script>

<script>
$( document ).ready(function() {

    selectedTab = "tab<?=$_GET["tab"]?>";
    $("#" + selectedTab).addClass("tabselected");
    $("#" + selectedTab + "box").show();

    $(".tab").click(function(){
        var elid = $(this).attr("id");
        $(".tab").removeClass("tabselected");
        $("#"+elid).addClass("tabselected");
        if (elid != selectedTab) {
            $(".tabbox").hide()
            $("#"+elid+"box").show()
            selectedTab = elid;
        }
    });

});
</script>

<?php
echo '<div id="tabs"><ul class="nav nav-tabs admin-tabs" role="tablist">';
if ($_GET["tab"] == 0) {
    $active = "active";
} else {
    $active = "";
}
echo '<li id="tab0" class="tab ' . $active . '" data-toggle="tab" role="tab" aria-expanded="true"><a href="javascript:;">Manage</a></li>';
if ($_GET["tab"] == 1) {
    $active = "active";
} else {
    $active = "";
}
echo '<li id="tab1" class="tab ' . $active . '" data-toggle="tab" role="tab" aria-expanded="true"><a href="javascript:;">Pricing</a></li>';
if ($_GET["tab"] == 2) {
    $active = "active";
} else {
    $active = "";
}
echo '<li id="tab2" class="tab ' . $active . '" data-toggle="tab" role="tab" aria-expanded="true"><a href="javascript:;">Logs</a></li>';

echo '</ul></div>';
?>

<style>

.tablebg td.fieldlabel {
    background-color:#FFFFFF;
    text-align: right;
}

.tablebg td.fieldarea {
    background-color:#F3F3F3;
    text-align: left;
}

.tab-content {
    border-left: 1px solid #ccc;
    border-right: 1px solid #ccc;
    border-bottom: 1px solid #ccc;
    padding:10px;
}

div.tablebg {
    margin:0px;
}

div.infobox{
    margin:0px;
    margin-bottom:10px;
}

td.FULL {
    color: #449d44;
    font-weight:bold;
}

td.LITE {
    color: #ec971f;
    font-weight:bold;
}

tr.PROCESSING td {
    background-color: #D6F7E6;
}

tr.FAILED td {
    background-color: #ffe5e5;
}

.toggle_users{
    cursor:pointer;
    font-weight:bold;
}

.usersarea {
    background-color:#EDEDED;
    padding:5px;
}

.badge-default {
  background-color: #777;
  color: #fff;
}

.badge-primary {
  background-color: #337ab7;
  color: #fff;
}

.badge-success {
  background-color: #5cb85c;
  color: #fff;
}

.badge-info {
  background-color: #5bc0de;
  color: #fff;
}

.badge-warning {
  background-color: #f0ad4e;
  color: #fff;
}

.badge-danger {
  background-color: #d9534f;
  color: #fff;
}

</style>
