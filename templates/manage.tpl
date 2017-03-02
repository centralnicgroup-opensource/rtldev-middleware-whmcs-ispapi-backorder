<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" href="//cdn.datatables.net/1.10.7/css/jquery.dataTables.css">
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script src="//cdn.datatables.net/1.10.7/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="modules/addons/ispapibackorder/templates/js/jquery.noty.packaged.min.js"></script>
<script src="modules/addons/ispapibackorder/templates/js/backorder.js"></script>
<link rel="stylesheet" href="modules/addons/ispapibackorder/templates/css/styles.css">


<div class="container" style="text-align:right;padding-right:45px;margin-bottom:10px;"><a class="btn btn-default" href="index.php?m=ispapibackorder&p=dropdomains">{$LANG.domainheader}</a></div>

<!--############################### MAIN AREA #######################################-->
<div class="col-md-9 pull-md-right">
    {if $error}
        <div class="alert alert-error">
            <p>{$error}</p>
        </div>
    {/if}
    {if $info}
        <div class="alert alert-info">
            <p>{$info}</p>
        </div>
    {/if}
    <form id="settings">
        <input type="hidden" name="status" id="status" value="" />
        <input type="hidden" name="tld" id="tld" value="" />
        <input type="hidden" name="type" id="type" value="" />
        <input type="hidden" name="dropdate" id="dropdate" value="" />
    </form>
    <div id="top"></div>
    <table id="BackorderList" class="table table-striped table-framed" cellspacing="0" width="100%">
        <thead>
            <tr>
                {foreach $fields as $field}
                <th>{$field['fieldname']}</th>
                {/foreach}
            </tr>
        </thead>
    </table>
    <br>

    {LITERAL}
    <script>
    var oTable;
    $(document).ready(function() {

        function setvalue(field, value, thiselement, redraw, resetform) {
            if (redraw != true && redraw != false) redraw = true;
            if (resetform != true && resetform != false) resetform = true;
            if (resetform) {
                $(".selectioactive").removeClass("selectioactive");
                thiselement.addClass("selectioactive");
                $("#settings").find(":input").each(function() {
                    $(this).val("")
                });
            }
            if(field=="status" && value=="ALL"){
                value = "";
            }
            $("#" + field).val(value);
            if (redraw) oTable.fnDraw();
        }

        $.ajax({
            type: "POST",
            async: true,
            dataType: "json",
            url: "modules/addons/ispapibackorder/backend/call.php",
            data: {COMMAND : "GetAvailableFunds"},
            success: function(data){
                //console.log(data);
                $("#creditbalance").html(data.PROPERTY.CREDITBALANCE.VALUE_FORMATED);
                $("#reservedamount").html(data.PROPERTY.RESERVEDAMOUNT.VALUE_FORMATED);
                $("#unpaidinvoices").html(data.PROPERTY.UNPAIDINVOICES.VALUE_FORMATED);
                $("#amountavailable").html(data.PROPERTY.AMOUNT.VALUE_FORMATED);
            },
            error: function(data){
            }
        });

        $(document).on('click', '#createnewbackorderbutton', function (e) {
            var type = "FULL";
            $.ajax({
                    type: "POST",
                    async: true,
                    dataType: "json",
                    url: "modules/addons/ispapibackorder/backend/call.php",
                    data: {
                        COMMAND: "CreateBackorder",
                        DOMAIN: $("#createnewbackorder").val(),
                        TYPE: type
                    },
                    success: function(data) {
                        if (data['CODE']=="200") {
                            reloadBackorderOverview();
                            noty({text: "{/literal}{$LANG.notybackordersuccessfullycreated}{literal}"});
                            oTable.fnDraw();
                        } else {
                            noty({text: data['DESCRIPTION'], type: "error"});
                        }
                    },
                    error: function(data) {
                        noty({text: "{/literal}{$LANG.notyerroroccured}{literal}", type: "error"});
                    }
            });
        });

        function reloadBackorderOverview(){
            $.ajax({
                type: "POST",
                async: true,
                dataType: "json",
                url: "{/literal}{$modulepath}{literal}backend/call.php",
                data: {COMMAND : "QueryBackorderOverviewStatus"},
                success: function(data){
                    $("#overviewbackorderstatus").html("");
                    var total=0;
                    var output="";
                    $.each(data.PROPERTY, function(i, obj) {
                          total = total + parseInt(obj.anzahl);
                          //Only display status with anzahl > 0
                          if(obj.anzahl > 0){
                              output  = '';
                              output += '<tr value="'+obj.status+'" class="setValue" field="status">';
                              output += '<td class="" style="text-align:center;">'+obj.status+' ('+obj.anzahl+')</td>';
                              output += '</tr>';
                              $("#overviewbackorderstatus").append(output);
                          }
                    });
                    if(total > 0){
                        output  = '<tr><td><hr style="margin:5px;"></td></tr>';
                        output += '<tr value="ALL" class="setValue" field="status">';
                        output += '<td class="bold" style="text-align:center;">{/literal}{$LANG.showall}{literal} ('+total+')</td>';
                        output += '</tr>';
                        $("#overviewbackorderstatus").append(output);
                        $("#overviewbackorderstatus_box").show();
                    }else{
                        $("#overviewbackorderstatus_box").hide();
                    }
                }
            });
        }
        reloadBackorderOverview();

        $.ajax({
            type: "POST",
            async: true,
            dataType: "json",
            url: "{/literal}{$modulepath}{literal}backend/call.php",
            data: {COMMAND : "QueryPriceList"},
            success: function(data){
                $("#pricelist").html();
                var count = 0;
                $.each(data.PROPERTY, function(i, obj) {
                    count++;
                    if(count <= 5){
                        var output="";
                        output += '<tr>';
                        output += '<td align="center" width="33%" ><strong>.'+obj.TLD+'</strong></td>';
                        output += '<td align="center">'+obj.PRICEFULL_FORMATED+'</td>';
                        output += '</tr>';
                        $("#pricelist").append(output);
                    }else{
                        var output="";
                        output += '<tr class="pricing_row hide">';
                        output += '<td align="center" width="33%" ><strong>.'+obj.TLD+'</strong></td>';
                        output += '<td align="center">'+obj.PRICEFULL_FORMATED+'</td>';
                        output += '</tr>';
                        $("#pricelist").append(output);
                    }
                });
                var output="";
                output += '<tr>';
                output += '<td colspan="2" align="center"><button action="show" style="margin-top:8px;" class="form-control input-sm btn-default" id="morepricing"><i class="fa fa-caret-down" aria-hidden="true"></i> {/literal}{$LANG.showmore}{literal}</button></td>';
                output += '</tr>';
                $("#pricelist").append(output);
            },
            error: function(data){
            }
        });

        //show/hide pricing logic
        $(document).on('click', '#morepricing', function (e) {
            if($(this).attr("action") == "show") {
                $(this).html("<i class='fa fa-caret-up' aria-hidden='true'></i> {/literal}{$LANG.hidemore}{literal}");
                $.each( $(".pricing_row"), function(obj) {
                    $(this).removeClass("hide");
                });
                $(this).attr("action", "hide")
            }else{
                $(this).html("<i class='fa fa-caret-down' aria-hidden='true'></i> {/literal}{$LANG.showmore}{literal}");
                $.each( $(".pricing_row"), function(obj) {
                    $(this).addClass("hide");
                });
                $(this).attr("action", "show")
            }
        });


        $(document).on('click', '.setValue', function (e) {
            setvalue($(this).attr("field"), $(this).attr("value"), $(this));
        });

        $(document).on('click', '.setbackorder', function (e) {
            var button = $(this);
            var command = "DeleteBackorder";

            $.ajax({
                type: "POST",
                async: true,
                dataType: "json",
                url: "{/literal}{$modulepath}{literal}backend/call.php",
                data: {
                    COMMAND: command,
                    DOMAIN: $(this).attr("value"),
                },
                success: function(data) {
                    if(data["CODE"]==200){
                        button.closest('tr').remove();
                        reloadBackorderOverview();
                        noty({text: "{/literal}{$LANG.notybackordersuccessfullydeleted}{literal}"});
                    }
                    else{
                        noty({text: data['DESCRIPTION'], type: "error"});
                        /*$("#createnewbackorderdomainerrortext").html(data['DESCRIPTION'] );
                        $("#dialogerror").dialog({
                            modal: true,
                            width: "400px"
                        });*/
                    }
                },
                error: function(data) {
                }
            });
        });

        oTable = $('#BackorderList').dataTable({
            scrollX: true,
            "dom": '<"clear">ilfrtpC',
            "oLanguage": {
                sProcessing: "<img src='modules/addons/ispapibackorder/templates/img/loading.gif'>"
            },
            "processing": true,
            "serverSide": true,
            "stateSave": false,
            "searching": false,
            "lengthChange": false,
            "iDisplayLength": 20,
            "order": [
                [1, "desc"]
            ],
            "aoColumnDefs": [
                  { "orderable": true, "aTargets": [ 0 ] }
            ],
            "ajax": {
                "url": "{/literal}{$modulepath}{literal}controller/manage.php",
                "type": "POST",
                "data": function(d) {
                    d.COMMAND = "QueryBackorderList";
                    $("#settings").find(':input').each(function() {
                        if ($(this).attr("type") == "checkbox") d[$(this).attr("name")] = $(this).prop('checked');
                        else d[$(this).attr("name")] = $(this).val();
                    });
                }
            },
            "fnDrawCallback": function(oSettings) {
                var oSettings = oTable.fnSettings();
                $("#results").val(oSettings._iDisplayLength);
            }
        });

    });
    </script>
    {/LITERAL}
</div>
<!--###############################################################################-->

<!--############################### SIDEBAR #######################################-->
<div class="col-md-3 pull-md-left sidebar">

    <!-- ########################### MY ACCOUNT ######################### -->
    <div menuitemname="Client Details" class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-user-circle-o"></i>&nbsp;{$LANG.creditvolume}</h3>
        </div>
        <div class="panel-body">
            <div id="creditvolume" class="row" style="padding:0px 10px;">
                <div style="float:left;">{$LANG.creditbalance}:</div>
                <div style="float:right;"><span id="creditbalance"></span></div>
                <div style="clear:both"></div>
                <div style="float:left;">{$LANG.reservedamount}:</div>
                <div style="float:right;">- <span id="reservedamount"></span></div>
                <div style="clear:both"></div>
                <div style="float:left;">{$LANG.unpaidinvoices}:</div>
                <div style="float:right;">- <span id="unpaidinvoices"></span></div>
                <div style="clear:both"></div>
                <div style="border-bottom:1px solid grey;"></div>
                <div style="float:left;font-weight:bold;">{$LANG.amountavailable}:</div>
                <div style="float:right;font-weight:bold;"><span id="amountavailable"></span></div>
            </div>
        </div>
    </div>
    <!-- ################################################################ -->

    <!-- ########## BACKORDER STATUS ########## -->
    <div menuitemname="Client Details" class="panel panel-default" id="overviewbackorderstatus_box" style="display:none;">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-filter"></i>&nbsp;{$LANG.overviewbackorderstatus}</h3>
        </div>
        <div class="panel-body">
        	<table width="100%" id="overviewbackorderstatus"></table>
        </div>
    </div>
    <!-- ###################################### -->

    <!-- ########## CREATE BACKORDER ########## -->
    <div menuitemname="Client Details" class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-shopping-cart"></i> {$LANG.createbackorder}</h3>
        </div>
        <div class="panel-body">
            <input style="width:100%;" type="text" name="domain" class="form-control" id="createnewbackorder" placeholder="domain.tld">
        </div>
        <div class="panel-footer clearfix">
            <input type="submit" value="{$LANG.createbackorder}" id="createnewbackorderbutton" class="btn btn-block btn-success">
        </div>
    </div>
    <!-- ###################################### -->

    <!--###################################### PRICING ##########################################-->
    <div menuitemname="Client Details" class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-usd"></i> {$LANG.pricelist}</h3>
        </div>
        <div class="panel-body">
            <table width="100%">
                <tr>
                    <td align="center" width="33%"><b>TLD</b></td>
                    <td align="center"><b>{$LANG.tldprice}</b></td>
                </tr>
            </table>
            <hr style="margin:5px;">
            <table width="100%" id="pricelist">
            </table>
        </div>
    </div>
    <!--#########################################################################################-->


</div>
<!--############################### / SIDEBAR #######################################-->
