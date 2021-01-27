<link rel="stylesheet" href="modules/addons/ispapibackorder/templates/lib/jquery-ui-1.12.1/jquery-ui.min.css">
<script src="modules/addons/ispapibackorder/templates/lib/jquery-ui-1.12.1/jquery-ui.min.js"></script>
<link rel="stylesheet" href="modules/addons/ispapibackorder/templates/lib/DataTables/datatables.css">
<script src="modules/addons/ispapibackorder/templates/lib/DataTables/datatables.min.js"></script>
<script src="modules/addons/ispapibackorder/templates/lib/noty-2.4.1/jquery.noty.packaged.min.js"></script>
<script src="modules/addons/ispapibackorder/templates/js/backorder.js"></script>
<link rel="stylesheet" href="modules/addons/ispapibackorder/templates/css/styles.css">

<div class="container" style="text-align:right;padding-right:45px;margin-bottom:10px;"><a class="btn btn-default" href="index.php?m=ispapibackorder&p=manage">{$_lang.managebackorders} (<span style="font-weight:bold;" id="nb_backorders">0</span>)</a></div>

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
    <br><br><br><br>

    {literal}
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
                //var selecteddate = new Date(tmpdate);
                //var selecteddate_formated = $.datepicker.formatDate('yy-mm-dd', selecteddate);
                //var selecteddatenextday = new Date(selecteddate.setTime( selecteddate.getTime() + 1 * 86400000 ));
                //var selecteddatenextday_formated = $.datepicker.formatDate('yy-mm-dd', selecteddate);
                $("#dropdate_from").val(tmpdate);
                $("#dropdate_to").val(tmpdate);
            }
            $("#searchbutton").trigger( "click" );
        }

        var oTable;
        $(document).ready(function() {

            $.ajax({
                type: "POST",
                async: true,
                dataType: "json",
                url: "{/literal}{$modulepath}{literal}backend/call.php",
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
                        url: "{/literal}{$modulepath}{literal}backend/call.php",
                        data: {
                            COMMAND: "CreateBackorder",
                            DOMAIN: $("#createnewbackorder").val(),
                            TYPE: type
                        },
                        success: function(data) {
                            if (data['CODE']=="200") {
                                updateBackorderNumber();
                                noty({text: "{/literal}{$_lang.notybackordersuccessfullycreated}{literal}"});
                                oTable.fnDraw();
                            } else {
                                noty({text: data['DESCRIPTION'], type: "error"});
                            }
                        },
                        error: function(data) {
                            noty({text: "{/literal}{$_lang.notyerroroccured}{literal}", type: "error"});
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
                              output +='<span>'+data.PROPERTY.DROPDAY[i]+'</span></div>';
                              output +='</div>';
                              output +='</a>';
                              $("#droppingdomains").append(output);
                          }
                    });
                    var output ='';
                    output +='<a onclick="setdate(\'total\');" class="list-group-item">';
                    output +='	<div class="row"><div class="col-lg-8"><i class="fa fa-circle-o"></i>&nbsp;';
                    output +='	<span><strong>{/literal}{$_lang.all}{literal}</strong></span></div>';
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
                            noty({text: "{/literal}{$_lang.notybackordersuccessfullycreated}{literal}"});
                        }
                        else if(command=="DeleteBackorder" && data["CODE"]==200){
                            button.removeClass("active btn-success");
                            updateBackorderNumber();
                            noty({text: "{/literal}{$_lang.notybackordersuccessfullydeleted}{literal}"});
                        }
                        else{
                            noty({text: data['DESCRIPTION'], type: "error"});
                        }
                    },
                    error: function(data) {
                        noty({text: "{/literal}{$_lang.notyerroroccured}{literal}", type: "error"});
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
                    output += '<td colspan="2" align="center"><button action="show" style="margin-top:8px;" class="form-control input-sm btn-default" id="morepricing"><i class="fa fa-caret-down" aria-hidden="true"></i> {/literal}{$_lang.showmore}{literal}</button></td>';
                    output += '</tr>';
                    $("#pricelist").append(output);
                },
                error: function(data){
                }
            });

            //show/hide pricing logic
            $(document).on('click', '#morepricing', function (e) {
                if($(this).attr("action") == "show") {
                    $(this).html("<i class='fa fa-caret-up' aria-hidden='true'></i> {/literal}{$_lang.hidemore}{literal}");
                    $.each( $(".pricing_row"), function(obj) {
                        $(this).removeClass("hide");
                    });
                    $(this).attr("action", "hide")
                }else{
                    $(this).html("<i class='fa fa-caret-down' aria-hidden='true'></i> {/literal}{$_lang.showmore}{literal}");
                    $.each( $(".pricing_row"), function(obj) {
                        $(this).addClass("hide");
                    });
                    $(this).attr("action", "show")
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

            $( document ).ready(function() {
                $("#DeletedDomainsList_processing").removeClass("panel");
                $("#DeletedDomainsList_processing").removeClass("panel-default");
                $("#DeletedDomainsList_processing").css("z-index", "1000");
            });

        });
    </script>
    {/literal}

</div>
<!--###############################################################################-->


<!--############################### SIDEBAR #######################################-->
<div class="col-md-3 pull-md-left sidebar">

    <!-- ##################### MY ACCOUNT ###################### -->
    <div menuitemname="Client Details" class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-user-circle-o"></i>&nbsp;{$_lang.creditvolume}</h3>
        </div>
        <div class="panel-body">
            <div id="creditvolume" class="row" style="padding:0px 10px;">
                <div style="float:left;">{$_lang.creditbalance}:</div>
                <div style="float:right;"><span id="creditbalance"></span></div>
                <div style="clear:both"></div>
                <div style="float:left;">{$_lang.reservedamount}:</div>
                <div style="float:right;">- <span id="reservedamount"></span></div>
                <div style="clear:both"></div>
                <div style="float:left;">{$_lang.unpaidinvoices}:</div>
                <div style="float:right;">- <span id="unpaidinvoices"></span></div>
                <div style="clear:both"></div>
                <div style="border-bottom:1px solid grey;"></div>
                <div style="float:left;font-weight:bold;">{$_lang.amountavailable}:</div>
                <div style="float:right;font-weight:bold;"><span id="amountavailable"></span></div>
            </div>
        </div>
    </div>
    <!-- ####################################################### -->

    <!--############################## SEARCH ##############################-->
    <div menuitemname="Client Details" class="panel panel-default">

            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-search"></i> {$_lang.refinesearch}</h3>
            </div>
            <form id="settings">
            <div class="panel-body">
                <div class="form-group" style="margin-bottom:5px;">
                    <label style="margin:0px;" for="inputFirstName" class="control-label">{$_lang.domaindoescontain}</label>
                    <input class="form-control" name="DOMAINREGEXP" id="DOMAINREGEXP" value="">
                    <span style="font-weight:normal;font-size:12px;">* {$_lang.regexsupported}</span>
                </div>

                <div class="form-group" style="margin-bottom:5px;">
                    <label style="margin:0px;" for="" class="control-label">{$_lang.domainsearchtld}</label>
                    <select class="form-control input-sm" name="tld" id="tld">
                        <option value="_all_">{$_lang.all}</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:5px;">
                    <label style="margin:0px;" for="inputFirstName" class="control-label">{$_lang.domainresults}</label>
                    <select class="form-control input-sm" name="results" id="results">
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div id="settingsdiv">
                    <hr style="margin:10px 0px 10px 0px;">
                    <div class="form-group" style="margin-bottom:10px;">
                        <label style="margin:0px;" for="inputFirstName" class="control-label">{$_lang.domainchars}</label>
                        <div class="row" style="margin-bottom:5px;">
                            <div class="col-lg-6" style="margin-bottom:5px;">
                                <div class="input-group m-bot15">
                                    <span class="input-group-addon">{$_lang.min}:</span>
                                    <input class="form-control input-sm" name="chars_count_min" id="chars_count_min" value="">
                                </div>
                            </div>
                            <div class="col-lg-6" style="margin-bottom:5px;">
                                <div class="input-group m-bot15">
                                    <span class="input-group-addon">{$_lang.max}:</span>
                                    <input class="form-control input-sm" name="chars_count_max" id="chars_count_max" value="">
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr style="margin:10px 0px 10px 0px;">
                    <div class="form-group" style="margin-bottom:10px;">
                        <label style="margin:0px;" for="inputFirstName" class="control-label">{$_lang.domainletters}</label>
                        <div class="row" style="margin-bottom:5px;">
                            <div class="col-lg-6" style="margin-bottom:5px;">
                                <div class="input-group m-bot15">
                                    <span class="input-group-addon">{$_lang.min}:</span>
                                    <input class="form-control input-sm" name="letters_count_min" id="letters_count_min" value="">
                                </div>
                            </div>
                            <div class="col-lg-6" style="margin-bottom:5px;">
                                <div class="input-group m-bot15">
                                    <span class="input-group-addon">{$_lang.max}:</span>
                                    <input class="form-control input-sm" name="letters_count_max" id="letters_count_max" value="">
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr style="margin:10px 0px 10px 0px;">
                    <div class="form-group" style="margin-bottom:10px;">
                        <label style="margin:0px;" for="inputFirstName" class="control-label">{$_lang.domaindigits}</label>
                        <div class="row" style="margin-bottom:5px;">
                            <div class="col-lg-6" style="margin-bottom:5px;">
                                <div class="input-group m-bot15">
                                    <span class="input-group-addon">{$_lang.min}:</span>
                                    <input class="form-control input-sm" name="digits_count_min" id="digits_count_min" value="">
                                </div>
                            </div>
                            <div class="col-lg-6" style="margin-bottom:5px;">
                                <div class="input-group m-bot15">
                                    <span class="input-group-addon">{$_lang.max}:</span>
                                    <input class="form-control input-sm" name="digits_count_max" id="digits_count_max" value="">
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr style="margin:10px 0px 10px 0px;">
                    <div class="form-group" style="margin-bottom:5px;">
                        <label style="margin:0px;" for="inputFirstName" class="control-label">{$_lang.domainhyphens}</label>
                        <div class="row" style="margin-bottom:5px;">
                            <div class="col-lg-6" style="margin-bottom:5px;">
                                <div class="input-group m-bot15">
                                    <span class="input-group-addon">{$_lang.min}:</span>
                                    <input class="form-control input-sm" name="hyphens_count_min" id="hyphens_count_min" value="">
                                </div>
                            </div>
                            <div class="col-lg-6" style="margin-bottom:5px;">
                                <div class="input-group m-bot15">
                                    <span class="input-group-addon">{$_lang.max}:</span>
                                    <input class="form-control input-sm" name="hyphens_count_max" id="hyphens_count_max" value="">
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr style="margin:10px 0px 10px 0px;">
                    <div class="form-group" style="margin-bottom:5px;">
                        <label style="margin:0px;" for="inputFirstName" class="control-label">{$_lang.domaindropdate}</label>
                        <div class="row" style="margin-bottom:5px;">
                            <div class="col-lg-12">
                                <div class="input-group m-bot15">
                                    <span class="input-group-addon" style="width:70px;">{$_lang.from}:</span>
                                    <input class="form-control input-sm" name="dropdate_from" id="dropdate_from" value="">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="input-group m-bot15">
                                    <span class="input-group-addon" style="width:70px;">{$_lang.to}:</span>
                                    <input class="form-control input-sm" name="dropdate_to" id="dropdate_to" value="">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top:10px; margin-bottom:5px;">
                    <input class="form-control input-sm btn-default" type="button" value="{$_lang.domainsearchsettings}" id="openfilter">
                </div>
            </div>
            <div class="panel-footer clearfix">
                <input type="submit" class="btn btn-block btn-success" value="{$_lang.domainsearch}" id="searchbutton">
            </div>
        </form>

    </div>
    <!--####################################################################-->

    <!-- ########################## UPCOMING DROPS ######################### -->
    <div menuitemname="Client Details" class="panel panel-default">
        <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-calendar"></i>&nbsp; {$_lang.upcomingdrops}</h3>
        </div>
        <div class="list-group" id="droppingdomains"></div>
    </div>
    <!--####################################################################-->

    <!--############################### CREATE BACKORDER #######################################-->
    <div menuitemname="Client Details" class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-shopping-cart"></i> {$_lang.createbackorder}</h3>
        </div>
        <div class="panel-body">
            <input style="width:100%;" type="text" name="domain" class="form-control" id="createnewbackorder" placeholder="domain.tld">
        </div>
        <div class="panel-footer clearfix">
            <input type="submit" value="{$_lang.createbackorder}" id="createnewbackorderbutton" class="btn btn-block btn-success">
        </div>
    </div>
    <!--#######################################################################################-->

    <!--############################### PRICING #######################################-->
    <div menuitemname="Client Details" class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-usd"></i> {$_lang.pricelist}</h3>
        </div>
        <div class="panel-body">
            <table width="100%">
                <tr>
                    <td align="center" width="33%"><b>TLD</b></td>
                    <td align="center"><b>{$_lang.tldprice}</b></td>
                </tr>
            </table>
            <hr style="margin:5px;">
            <table width="100%" id="pricelist">
            </table>
        </div>
    </div>
    <!--###############################################################################-->

</div>
<!--############################### END SIDEBAR #######################################-->
