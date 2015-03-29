function change_year(control)
{
    var data = 'filter=year eq '+$(control).val()+'&select=request_id,first,last,type&fmt=data-table';
    var table = $('#tickets').DataTable();
    table.ajax.url('../api/v1/requests_w_tickets?'+data).load();
}

function init_page()
{
    $('#tickets').dataTable({
        columns: [
            {'data': 'request_id'},
            {'data': 'first'},
            {'data': 'last'},
            {'data': 'type'}
        ]
    });
    change_year($('#year'));
}

$(init_page);
