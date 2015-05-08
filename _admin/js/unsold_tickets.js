function init_page()
{
    $('#tickets').dataTable({
        "ajax": '../api/v1/tickets?filter=sold eq 0&fmt=data-table',
        columns: [
            {'data': 'hash'},
            {'data': 'type'}
        ]
    });
}

$(init_page)
