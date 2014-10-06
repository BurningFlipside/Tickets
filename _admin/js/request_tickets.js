function change_year(control)
{
    var data = 'tickets='+$(control).val();
    var table = $('#tickets').DataTable();
    table.ajax.url('/tickets/ajax/request.php?'+data).load();
}

function init_page()
{
    $('#tickets').dataTable({
        columns: [
            {'data': 'request_id'},
            {'data': 'first'},
            {'data': 'last'},
            {'data': 'type.description'}
        ]
    });
    change_year($('#year'));
}

$(init_page);
