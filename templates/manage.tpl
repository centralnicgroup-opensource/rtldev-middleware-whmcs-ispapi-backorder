<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" href="//cdn.datatables.net/1.10.7/css/jquery.dataTables.css">
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script src="//cdn.datatables.net/1.10.7/js/jquery.dataTables.min.js"></script>
<script src="modules/addons/ispapibackorder/templates/js/backorder.js"></script>
<link rel="stylesheet" href="modules/addons/ispapibackorder/templates/css/styles.css">

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
    <div id="dialogerror" title="{$LANG.createbackordererror}" style="display:none;">
        <p>{$LANG.createbackordererrortext}</p>
    </div>

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
            $("#" + field).val(value);
            if (redraw) oTable.fnDraw();
        }

        $.ajax({
            type: "POST",
            async: false,
            dataType: "json",
            url: "{/literal}{$modulepath}{literal}backend/call.php",
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
                      output += '<tr value="'+obj.status+'" class="setValue" field="status">';
                      output += '<td align="center" width="75%"><strong>'+obj.status+'</strong></td>';
                      output += '<td align="center" >'+obj.anzahl +'</td>';
                      output += '</tr>';
                      $("#overviewbackorderstatus").append(output);
                });

                $("#boxbackordertotal").text(data.total);
                $("#boxbackordersuccessful").text(data.PROPERTY['SUCCESSFUL']['anzahl']);
                $("#boxbackordermissed").text(data.PROPERTY['FAILED']['anzahl']);
                $("#boxbackorderrequest").text(data.PROPERTY['REQUESTED']['anzahl']);
            },
            error: function(data){
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
                async: false,
                dataType: "json",
                url: "{/literal}{$modulepath}{literal}backend/call.php",
                data: {
                    COMMAND: command,
                    DOMAIN: $(this).attr("value"),
                },
                success: function(data) {
                    if(data["CODE"]==200)
                        button.closest('tr').remove();
                    else{
                        $("#createnewbackorderdomainerrortext").html(data['DESCRIPTION'] );
                        $("#dialogerror").dialog({
                            modal: true,
                            width: "400px"
                        });
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
            "stateSave": true,
            "searching": false,
            "lengthChange": false,
            "iDisplayLength": 20,
            "order": [
                [1, "asc"]
            ],
            "aoColumnDefs": [
                  { "orderable": false, "aTargets": [ 0 ] }
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

    <!-- ########## CREDIT VOLUME ########## -->
    <div menuitemname="Client Details" class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-money"></i>&nbsp;{$LANG.creditvolume}</h3>
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
    <!-- ###################################### -->

    <!-- ########## BACKORDER STATUS ########## -->
    <div menuitemname="Client Details" class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-question-circle"></i>&nbsp;{$LANG.overviewbackorderstatus}</h3>
        </div>
        <div class="panel-body">
        	<table width="100%">
        		<tr>
        			<td align="center" width="75%"><b>{$LANG.tldstatus}</b></td>
        			<td align="center" ><b>{$LANG.tldnumber}</b></td>
        		</tr>
        	</table>
        	<hr style="margin:5px;">
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
    <div id="dialog" title="{$LANG.createbackordersuccess}" style="display:none;">
        <p>{$LANG.createbackordersuccesstext}</p>
    </div>
    <div id="dialogerror" title="{$LANG.createbackordererror}" style="display:none;">
        <p>{$LANG.createbackordererrortext}</p>
    </div>





</div>
<!--############################### / SIDEBAR #######################################-->
