<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" href="//cdn.datatables.net/1.10.7/css/jquery.dataTables.css">
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script src="//cdn.datatables.net/1.10.7/js/jquery.dataTables.min.js"></script>

<button type="button" class="reloadlogs btn btn-info btn-sm">Reload</button>
<br><br>

<script>
    $(document).ready(function() {
        $(".reloadlogs").click(function() {
            $('#backorderlogs').DataTable().ajax.reload();
        });

        var table = $('#backorderlogs').DataTable({
            "aLengthMenu": [50, 100, 200, 500],
            "ajax": {
                "url": "../modules/addons/ispapibackorder/backend/call.php",
                "type": "POST",
                "data": {COMMAND : "QueryLogList"},
                "dataSrc": "PROPERTY",
            },
            "iDisplayLength": 50,
            "destroy": true,
            "columns": [
                { "data": "id" },
                { "data": "cron" },
                { "data": "date" },
                { "data": "status" },
                { "data": "message", "searchable": true },
            ],
            "order": [[ 0, "desc" ]],
            'fixedColumns': true
        });
    })
</script>

<div class="table-responsive">
    <table class="table table-bordered table-hover table-condensed dt-bootstrap" id="backorderlogs" style="width:99%;">
        <thead>
            <tr>
                <th>ID</th>
                <th>CRON</th>
                <th>DATE</th>
                <th>STATUS</th>
                <th>MESSAGE</th>
            </tr>
        </thead>
    </table>
</div>
