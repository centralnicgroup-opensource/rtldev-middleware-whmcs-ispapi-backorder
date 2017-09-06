<button type="button" class="reloadlogs btn btn-secondary btn-sm">Reload</button>
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
    <table class="table table-bordered table-hover table-condensed dt-bootstrap datatable" id="backorderlogs" style="width:100%;">
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
