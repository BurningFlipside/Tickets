function init_page()
{
    $('#tickets').dataTable({
        "ajax": '../api/v1/tickets?filter=sold eq 0 and used eq 0 and year eq 2017&fmt=data-table',
        columns: [
            {'data': 'hash'},
            {'data': 'type'}
        ]
    });
}

$(init_page)
