<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" href="//cdn.datatables.net/1.10.7/css/jquery.dataTables.css">
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script src="//cdn.datatables.net/1.10.7/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="modules/addons/ispapibackorder/templates/js/jquery.noty.packaged.min.js"></script>
<script src="modules/addons/ispapibackorder/templates/js/backorder.js"></script>
<link rel="stylesheet" href="modules/addons/ispapibackorder/templates/css/styles.css">

<div class="container" style="text-align:right;padding-right:45px;margin-bottom:10px;"><a class="btn btn-default" href="index.php?m=ispapibackorder&p=manage">{$LANG.managebackorders} (<span style="font-weight:bold;" id="nb_backorders">0</span>)</a></div>

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

    <table id="DeletedDomainsList" class="table table-striped table-framed" cellspacing="0" width="100%">
        <thead><tr>{foreach $fields as $field}<th>{$field['fieldname']}</th>{/foreach}</tr></thead>
    </table>

    {LITERAL}
    <script>

        function setdate(tmpdate)
        {
            if(tmpdate=="last7"){
                var mydate = new Date();
                var mydate2 = new Date( new Date().getTime() + 24 * 60 * 60 * 1000 * 6);
                $("#dropdate_from").val( mydate.getFullYear()+"-"+( mydate.getMonth()+1) +"-"+mydate.getDate() );
                $("#dropdate_to").val( mydate2.getFullYear()+"-"+( mydate2.getMonth()+1) +"-"+mydate2.getDate() );
            }else if(tmpdate=="total"){
                $("#dropdate_from").val("");
                $("#dropdate_to").val("");
            }else{
                var mydate = new Date(tmpdate);
                mydate.setDate(mydate.getDate());
                $("#dropdate_from").val(tmpdate);
                $("#dropdate_to").val( mydate.getFullYear()+"-"+( mydate.getMonth()+1) +"-"+mydate.getDate() );
            }
            $("#searchbutton").trigger( "click" );
        }

        var oTable;
        $(document).ready(function() {

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
                                updateBackorderNumber();
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

            function updateBackorderNumber(){
                $.ajax({
                    type: "POST",
                    async: true,
                    dataType: "json",
                    url: "{/literal}{$modulepath}{literal}backend/call.php",
                    data: {COMMAND : "QueryBackorderOverviewStatus"},
                    success: function(data){
                        var total=0;
                        $.each(data.PROPERTY, function(i, obj) {
                              total = total + parseInt(obj.anzahl);
                        });
                        $("#nb_backorders").html(total);
                    },
                    error: function(data){
                        $("#nb_backorders").html("#");
                    }
                });
            }
            updateBackorderNumber();

            $.ajax({
                type: "POST",
                async: true,
                dataType: "json",
                url: "{/literal}{$modulepath}{literal}backend/call.php",
                data: {COMMAND : "QueryDeletedDomainsStats"},
                success: function(data){
                    $("#droppingdomains").html();
                    $.each(data.PROPERTY.DROPCOUNT, function(i, obj) {
                          if(i<3)
                          {
                              var output ='';
                              output +='<a onclick="setdate(\''+data.PROPERTY.DROPDAY[i]+'\');" class="list-group-item">';
                              output +='<div class="row"><div class="col-lg-8"> <i class="fa fa-circle-o"></i>&nbsp;';
                              output +='	<span>'+data.PROPERTY.DROPDAY[i]+'</span></div>';
                              output +='</div>';
                              output +='</a>';
                              $("#droppingdomains").append(output);
                          }
                    });
                    var output ='';
                    output +='<a onclick="setdate(\'total\');" class="list-group-item">';
                    output +='	<div class="row"><div class="col-lg-8"><i class="fa fa-circle-o"></i>&nbsp;';
                    output +='	<span>{/literal}{$LANG.all}{literal}</span></div>';
                    output +='</div>';
                    output +='</a>';
                    $("#droppingdomains").append(output);
                },
                error: function(data){
                }
            });

            $("#dropdate_to").datepicker();
            $("#dropdate_from").datepicker();
            $("#dropdate_to").datepicker("option", "dateFormat", "yy-mm-dd");
            $("#dropdate_from").datepicker("option", "dateFormat", "yy-mm-dd");

            $("#settings").submit(function(event) {
                //$("#searchbutton").trigger("click");
                return false;
            });

            $("#searchbutton").click(function() {
                var oSettings = oTable.fnSettings();
                oSettings._iDisplayLength = $("#results").val();
                oTable.fnDraw();
            });

            $("#settings input:checkbox").click(function() {
                $("#searchbutton").trigger("click");
            });

            $("#settings select").change(function() {
                $("#searchbutton").trigger("click");
            });

            $("#openfilter").click(function() {
                $("#settingsdiv").toggle("slideDown");
            });

            $(document).on('click', '.setbackorder', function (e) {
                var button = $(this);
                var command = "CreateBackorder";
                if ($(this).hasClass("active"))
                    command = "DeleteBackorder";

                $.ajax({
                    type: "POST",
                    async: true,
                    dataType: "json",
                    url: "{/literal}{$modulepath}{literal}backend/call.php",
                    data: {
                        COMMAND: command,
                        DOMAIN: $(this).attr("value"),
                        DROPDATE: $(this).attr("placeholder2"),
                        TYPE: "FULL"
                    },
                    success: function(data) {
                        if(command=="CreateBackorder" && data["CODE"]==200){
                            button.addClass("active btn-success");
                            updateBackorderNumber();
                            noty({text: "{/literal}{$LANG.notybackordersuccessfullycreated}{literal}"});
                        }
                        else if(command=="DeleteBackorder" && data["CODE"]==200){
                            button.removeClass("active btn-success");
                            updateBackorderNumber();
                            noty({text: "{/literal}{$LANG.notybackordersuccessfullydeleted}{literal}"});
                        }
                        else{
                            noty({text: data['DESCRIPTION'], type: "error"});
                        }
                    },
                    error: function(data) {
                        noty({text: "{/literal}{$LANG.notyerroroccured}{literal}", type: "error"});
                    }
                });
            });

            $.ajax({
                type: "POST",
                async: true,
                dataType: "json",
                url: "{/literal}{$modulepath}{literal}backend/call.php",
                data: {COMMAND : "QueryPriceList"},
                success: function(data){
                    $("#pricelist").html();
                    $.each(data.PROPERTY, function(i, obj) {
                          var output="";
                          output += '<tr>';
                          output += '<td align="center" width="33%" ><strong>.'+obj.TLD+'</strong></td>';
                          output += '<td align="center">'+obj.PRICEFULL_FORMATED+'</td>';
                          output += '</tr>';
                          $("#pricelist").append(output);
                    });
                },
                error: function(data){
                }
            });


			$.ajax({
			type: "POST",
			async: true,
			dataType: "json",
			url: "{/literal}{$modulepath}{literal}backend/call.php",
			data: {COMMAND : "QueryPriceList"},
			success: function(data){
                var output="";
				$.each(data.PROPERTY, function(i, obj) {
					  output += '<option value="'+obj.TLD+'">'+obj.TLD+'</option>';
				});
                $("#tld").append(output);
			},
			error: function(data){
			}
		});

            oTable = $('#DeletedDomainsList').dataTable({
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
                    [2, "asc"]
                ],
                "aoColumnDefs": [
                      { "orderable": false, "aTargets": [ 0 ] }
                ],
                "ajax": {
                    "url": "{/literal}{$modulepath}{literal}controller/dropdomains.php",
                    "type": "POST",
                    "data": function(d) {
                        d.COMMAND = "QueryDeletedDomainsList";
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

    <!--############################## REFINE SEARCH ##############################-->
    <div menuitemname="Client Details" class="panel panel-default">

            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-search"></i> {$LANG.refinesearch}</h3>
            </div>
            <form id="settings">
            <div class="panel-body">
                <div class="form-group" style="margin-bottom:5px;">
                    <label style="margin:0px;" for="inputFirstName" class="control-label">{$LANG.domaindoescontain}</label>
                    <input class="form-control" name="DOMAINREGEXP" id="DOMAINREGEXP" value="">
                </div>

                <div class="form-group" style="margin-bottom:5px;">
                    <label style="margin:0px;" for="" class="control-label">TLD</label>
                    <select class="form-control input-sm" name="tld" id="tld">
                        <option value="_all_">{$LANG.all}</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:5px;">
                    <label style="margin:0px;" for="inputFirstName" class="control-label">{$LANG.domainresults}</label>
                    <select class="form-control input-sm" name="results" id="results">
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div id="settingsdiv">
                    <hr style="margin:10px 0px 10px 0px;">
                    <div class="form-group" style="margin-bottom:10px;">
                        <label style="margin:0px;" for="inputFirstName" class="control-label">{$LANG.domainchars}</label>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="input-group m-bot15">
                                    <span class="input-group-addon">{$LANG.min}:</span>
                                    <input class="form-control input-sm" name="chars_count_min" id="chars_count_min" value="">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="input-group m-bot15">
                                    <span class="input-group-addon">{$LANG.max}:</span>
                                    <input class="form-control input-sm" name="chars_count_max" id="chars_count_max" value="">
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr style="margin:10px 0px 10px 0px;">
                    <div class="form-group" style="margin-bottom:10px;">
                        <label style="margin:0px;" for="inputFirstName" class="control-label">{$LANG.domainletters}</label>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="input-group m-bot15">
                                    <span class="input-group-addon">{$LANG.min}:</span>
                                    <input class="form-control input-sm" name="letters_count_min" id="letters_count_min" value="">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="input-group m-bot15">
                                    <span class="input-group-addon">{$LANG.max}:</span>
                                    <input class="form-control input-sm" name="letters_count_max" id="letters_count_max" value="">
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr style="margin:10px 0px 10px 0px;">
                    <div class="form-group" style="margin-bottom:10px;">
                        <label style="margin:0px;" for="inputFirstName" class="control-label">{$LANG.domaindigits}</label>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="input-group m-bot15">
                                    <span class="input-group-addon">{$LANG.min}:</span>
                                    <input class="form-control input-sm" name="digits_count_min" id="digits_count_min" value="">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="input-group m-bot15">
                                    <span class="input-group-addon">{$LANG.max}:</span>
                                    <input class="form-control input-sm" name="digits_count_max" id="digits_count_max" value="0">
                                </div>
                            </div>
                        </div>
                        <!--<div class="row">
                            <div class="col-lg-12">
                                <input name="digits_no" id="digits_no" type="checkbox" checked="" /> {$LANG.nodigits}
                                <input name="digits_only" id="digits_only" type="checkbox" /> {$LANG.digitsonly}
                            </div>
                        </div>-->
                    </div>

                    <hr style="margin:10px 0px 10px 0px;">
                    <div class="form-group" style="margin-bottom:5px;">
                        <label style="margin:0px;" for="inputFirstName" class="control-label">{$LANG.domainhyphens}</label>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="input-group m-bot15">
                                    <span class="input-group-addon">{$LANG.min}:</span>
                                    <input class="form-control input-sm" name="hyphens_count_min" id="hyphens_count_min" value="">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="input-group m-bot15">
                                    <span class="input-group-addon">{$LANG.max}:</span>
                                    <input class="form-control input-sm" name="hyphens_count_max" id="hyphens_count_max" value="">
                                </div>
                            </div>
                        </div>
                        <!--<div class="row">
                            <div class="col-lg-12">
                                <input name="hyphens_no" id="hyphens_no" type="checkbox" /> {$LANG.nohyphens}
                            </div>
                        </div>-->
                    </div>

                    <hr style="margin:10px 0px 10px 0px;">
                    <div class="form-group" style="margin-bottom:5px;">
                        <label style="margin:0px;" for="inputFirstName" class="control-label">{$LANG.domaindropdate}</label>
                        <div class="row" style="margin-bottom:5px;">
                            <div class="col-lg-12">
                                <div class="input-group m-bot15">
                                    <span class="input-group-addon" style="width:70px;">{$LANG.from}:</span>
                                    <input class="form-control input-sm" name="dropdate_from" id="dropdate_from" value="">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="input-group m-bot15">
                                    <span class="input-group-addon" style="width:70px;">{$LANG.to}:</span>
                                    <input class="form-control input-sm" name="dropdate_to" id="dropdate_to" value="">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top:10px; margin-bottom:5px;">
                    <input class="form-control input-sm btn-default" type="button" value="{$LANG.domainsearchsettings}" id="openfilter">
                </div>
            </div>
            <div class="panel-footer clearfix">
                <input type="submit" class="btn btn-block btn-success" value="{$LANG.domainsearch}" id="searchbutton">
            </div>
        </form>

    </div>
    <!--############################## END REFINE SEARCH ##############################-->

    <!-- ########## DROP DATES ########## -->
    <div menuitemname="Client Details" class="panel panel-default">
        <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-calendar"></i>&nbsp; {$LANG.upcomingdrops}</h3>
        </div>
        <div class="list-group" id="droppingdomains"></div>
    </div>
    <!-- ###################################### -->

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
    <!--############################### END CREATE BACKORDER #######################################-->

    <!--############################### BACKORDERS PRICING #######################################-->
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
    <!--############################### END BACKORDERS PRICING #######################################-->

</div>
<!--############################### END SIDEBAR #######################################-->
