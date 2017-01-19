<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" href="//cdn.datatables.net/1.10.7/css/jquery.dataTables.css">
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script src="//cdn.datatables.net/1.10.7/js/jquery.dataTables.min.js"></script>

<button type="button" class="reload btn btn-info btn-sm">Reload</button>
<br><br>

<script>
    $(document).ready(function() {

        $(".reload").click(function() {
            $('#backorderlist').DataTable().ajax.reload();
        });

        var modulelink = "addonmodules.php?module=ispapibackorder&tab=0";

        var table = $('#backorderlist').DataTable({
            "aLengthMenu": [50, 100, 200, 500],
            "ajax": {
                "url": "../modules/addons/ispapibackorder/backend/call.php",
                "type": "POST",
                "data": {COMMAND : "QueryCompleteBackorderList"},
                "dataSrc": "PROPERTY",
            },
            //"serverSide":true,
            "responsive": true,
            "processing":true,
            "iDisplayLength": 50,
            "destroy": true,
            "columns": [
                { "data": "id" },
                { "data": "domain", "searchable" : true },
                { "data": "userid", "searchable" : true },
                { "data": "dropdate", "searchable" : true },
                { "data": "status", "searchable" : true},
                { "data": "reference", "searchable" : true},
            ],
            "order": [[ 0, "desc" ]],
            "columnDefs": [
                {
                    "targets": 1,
                    "data": null,
                    "render": function ( data, type, row ) {
                        return row["domain"] + "." + row["tld"];
                    },
                },
                {
                    "targets": 2,
                    "data": null,
                    "render": function ( data, type, row ) {
                        return '<a target="blank_" href="clientssummary.php?userid=' + row["userid"] + '">' + row["firstname"] + " " + row["lastname"] + "</a>" + " [" + row["userid"] + "]";
                    },
                },
                {
                    "targets": 6,
                    "data": null,
                    "render": function ( data, type, row ) {
                        var links="";
                        if( row["status"] == "REQUESTED" || row["status"] == "ACTIVE" || (row["status"] == "PROCESSING" && row["reference"] == "" )){
                            links = links + '<button type="button" class="deleteBackorder" domain="' + row["domain"] + "." + row["tld"] + '" userid="' + row["userid"] + '">Delete</button>';
                        }

                        /*if( row["status"] == "REQUESTED" ){
                            links = links + '<button type="button" class="activateBackorder" domain="' + row["domain"] + "." + row["tld"] + '" userid="' + row["userid"] + '">Activate</button>';
                        }*/

                        return links;
                    },
                }
            ],
            'fixedColumns': true,
        });



       $(document).on('click', '.activateBackorder', function (e) {

           var domain = $(this).attr("domain");
           var userid = $(this).attr("userid")
           var button = $(this);
           $.ajax({
               type: "POST",
               async: false,
               dataType: "json",
               url: "../modules/addons/ispapibackorder/backend/call.php",
               data: {COMMAND : 'ActivateBackorder', DOMAIN: domain, USERID: userid},
               success: function(data){
                   if(data["CODE"]==200){
                       $('#backorderlist').DataTable().ajax.reload();
                   }else{
                       $("#dialogerror_text").html(data['DESCRIPTION'] );
                       $("#dialogerror").dialog({
                           modal: true,
                       });
                   }
               },
               error: function(data){
                   $("#dialogerror_text").html(data['DESCRIPTION'] );
                   $("#dialogerror").dialog({
                       modal: true,
                   });
               }
           });

       });


       $(document).on('click', '.deleteBackorder', function (e) {

           var domain = $(this).attr("domain");
           var userid = $(this).attr("userid")
           var button = $(this);
           $.ajax({
               type: "POST",
               async: false,
               dataType: "json",
               url: "../modules/addons/ispapibackorder/backend/call.php",
               data: {COMMAND : 'DeleteBackorder', DOMAIN: domain, USERID: userid},
               success: function(data){
                   if(data["CODE"]==200){
                       //button.parent().parent().remove()
                       button.closest('tr').remove();
                   }else{
                       $("#dialogerror_text").html(data['DESCRIPTION'] );
                       $("#dialogerror").dialog({
                           modal: true,
                       });
                   }
               },
               error: function(data){
                   $("#dialogerror_text").html(data['DESCRIPTION'] );
                   $("#dialogerror").dialog({
                       modal: true,
                   });
               }
           });

       });


    })
</script>

<div id="dialogerror" title="Error" style="display:none;">
    <p id="dialogerror_text"></p>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-hover table-condensed dt-bootstrap" id="backorderlist" style="width:99%;">
        <thead>
            <tr>
                <th>ID</th>
                <th>DOMAIN</th>
                <th>CLIENT</th>
                <th>DROPDATE</th>
                <th>STATUS</th>
                <th>REFERENCE</th>
                <th></th>
            </tr>
        </thead>
    </table>
</div>
