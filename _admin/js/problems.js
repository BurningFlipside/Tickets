function init_table()
{
    $(this).dataTable({
        'ajax': 'ajax/problems.php?v='+$(this).attr('id'),
        'columns': [
            {'data': 'request_id'},
            {'data': 'private_status'},
            {'data': 'total_due'},
            {'data': 'total_received'},
            {'data': 'comments'},
            {'data': 'crit_vol'}
        ]
    });
}

function init_page()
{
    $('table').each(init_table);
}

$(init_page);
