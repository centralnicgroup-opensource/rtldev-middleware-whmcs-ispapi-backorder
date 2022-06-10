<table>
    <tbody></tbody>
    <tfoot><tr><td colspan="100%">{$total} items</td></tr></tfoot>
</table>
<pre>{debug}</pre>


<script type="text/javascript">
const total = {$TOTAL};
{literal}
(async function() {
    try {
        const url = `${xr}/modules/addons/ispapibackorder/data.php`;
        const xhr = await $.ajax(url, {
            data : JSON.stringify({
                type: "xhr",
                action: "list",
                first: 0,
                limit: 50
            }),
            contentType : 'application/json',
            type : 'POST',
            dataType: 'json',
        });
        console.log(xhr)
    } catch(err) {
        console.log(err);
    }
}());
{/literal}
</script>
