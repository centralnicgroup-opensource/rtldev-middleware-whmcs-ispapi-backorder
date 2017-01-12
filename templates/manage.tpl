<!--############################### MAIN AREA #######################################-->

<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" href="//cdn.datatables.net/1.10.7/css/jquery.dataTables.css">
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script src="//cdn.datatables.net/1.10.7/js/jquery.dataTables.min.js"></script>
<script src="modules/addons/backorder/templates/js/backorder.js"></script>
<link rel="stylesheet" href="modules/addons/backorder/templates/css/styles.css">

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

    {literal}
    <script>
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

        var oTable;
        $(document).ready(function() {

            oTable = $('#BackorderList').dataTable({
                scrollX: true,
                "dom": '<"clear">ilfrtpC',
                "oLanguage": {
                    sProcessing: "<img src='modules/addons/backorder/templates/img/loading.gif'>"
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

                    $(".setbackorder").click(function() {
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
                }
            });



        });

    </script>
    {/literal}

</div>
<!--############################### / MAIN AREA #######################################-->

<!--############################### SIDEBAR #######################################-->
<div class="col-md-3 pull-md-left sidebar">

{$BackorderSidebar}

<!--############################### CREATE BACKORDER #######################################-->
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

{literal}
<script>
    $("#createnewbackorderbutton").click(function() {
        var type = "FULL";

        $.ajax({
                type: "POST",
                async: false,
                dataType: "json",
                url: "{/literal}{$modulepath}{literal}backend/call.php",
                data: {
                    COMMAND: "CreateBackorder",
                    DOMAIN: $("#createnewbackorder").val(),
                    TYPE: type
                },
                success: function(data) {
                    $(".createnewbackorderdomain").html($("#createnewbackorder").val());
                        if (data['CODE']=="200") {
                            $("#dialog").dialog({
                                modal: true,
                                width: "400px"
                            });
                            oTable.fnDraw();
                        } else {
                            $("#createnewbackorderdomainerrortext").html(data['DESCRIPTION'] );
                            $("#dialogerror").dialog({
                                modal: true,
                                width: "400px"
                            });
                        }
                },
                error: function(data) {
                    $(".createnewbackorderdomain").html($("#createnewbackorder").val());
                    $("#createnewbackorderdomainerrortext").html(data['DESCRIPTION'] );
                    $("#dialogerror").dialog({
                        modal: true,
                        width: "400px"
                    });
                }
        });
    });
</script>
{/literal}
<!--############################### / CREATE BACKORDER #######################################-->



</div>
<!--############################### / SIDEBAR #######################################-->
