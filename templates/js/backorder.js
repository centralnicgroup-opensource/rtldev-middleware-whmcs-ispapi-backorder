$( document ).ready(function() {

    $.ajax({
        type: "POST",
        async: false,
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
                async: false,
                dataType: "json",
                url: "modules/addons/ispapibackorder/backend/call.php",
                data: {
                    COMMAND: "CreateBackorder",
                    DOMAIN: $("#createnewbackorder").val(),
                    TYPE: type
                },
                success: function(data) {
                    $(".createnewbackorderdomain").html($("#createnewbackorder").val());
                        if (data['CODE']=="200") {
                            $("#dialog").dialog({modal: true, width: "400px"});
                            oTable.fnDraw();
                        } else {
                            $("#createnewbackorderdomainerrortext").html(data['DESCRIPTION'] );
                            $("#dialogerror").dialog({modal: true, width: "400px"});
                        }
                },
                error: function(data) {
                    $(".createnewbackorderdomain").html($("#createnewbackorder").val());
                    $("#createnewbackorderdomainerrortext").html(data['DESCRIPTION'] );
                    $("#dialogerror").dialog({modal: true, width: "400px"});
                }
        });
    });

});
