function sell_ticket(control)
{
    var jq = $(control);
    var id = jq.attr('for');
    var ticket = get_ticket_data_by_hash(id);
    if(ticket == null)
    {
        alert('Cannot find ticket');
        return;
    }
    window.location = '_admin/pos.php?id='+ticket.hash;
}

function make_d_action(data, type, row, meta)
{
    var res = '';
    var view_options = {class: 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'View Ticket Code', for: data, onclick: 'view_ticket(this)'};
    var pdf_options =  {class: 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'Download PDF', for: data, onclick: 'download_ticket(this)'};
    var sell_options = {class: 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'Sell Ticket<br/>Use this option to sell<br/>the ticket to someone else', 'data-html': true, for: data, onclick: 'sell_ticket(this)'};
    if(browser_supports_font_face())
    {
        if($(window).width() < 768)
        {
            view_options.type = 'button';
            var button = $('<button/>', view_options);
            var glyph = $('<span/>', {class: 'fa fa-search'});
            glyph.appendTo(button);
            res += button.prop('outerHTML');
        }
        pdf_options.type = 'button';
        button = $('<button/>', pdf_options);
        glyph = $('<span/>', {class: 'fa fa-download'});
        glyph.appendTo(button);
        if(button.prop('outerHTML') === undefined)
        {
            res += new XMLSerializer().serializeToString(button[0]);
        }
        else
        {
            res += button.prop('outerHTML');
        }

        var rand = Math.floor(Math.random() * 7);

        sell_options.type = 'button';
        button = $('<button/>', sell_options);
        switch(rand)
        {
            case 0:
                glyph = $('<span/>', {class: 'fa fa-dollar-sign'});
                break;
            case 1:
                glyph = $('<span/>', {class: 'fa fa-euro-sign'});
                break;
            case 2:
                glyph = $('<span/>', {class: 'fa fa-yen-sign'});
                break;
            case 3:
                glyph = $('<span/>', {class: 'fa fa-pound-sign'});
                break;
            case 4:
                glyph = $('<span/>', {class: 'fab fa-bitcoin'});
                break;
            case 5:
                glyph = $('<span/>', {class: 'fa fa-lira-sign'});
                break;
            case 6:
                glyph = $('<span/>', {class: 'fa fa-ruble-sign'});
                break;
        }
        glyph.appendTo(button);
        if(button.prop('outerHTML') === undefined)
        {
            res += new XMLSerializer().serializeToString(button[0]);
        }
        else
        {
            res += button.prop('outerHTML');
        }
    }
    else
    {
        if($(window).width() < 768)
        {
            var link = $('<a/>', view_options);
            link.append("View");
            res += link.prop('outerHTML');
            res += '|';
        }
        link = $('<a/>', pdf_options);
        link.append("Download");
        res += link.prop('outerHTML');
        res += '|';

        link = $('<a/>', transfer_options);
        link.append("Sell");
        res += link.prop('outerHTML');
    }
    return res;
}

function createOverlay(index, value)
{
    var row = $(value);
    var div = $('<div>');
    div.append('<a href="#" onclick="cancelTransfer()">Cancel Transfer</a>');
    div.css({
        position: 'absolute',
        'background-color': '#C0C0C0',
        'top': row[0].offsetTop,
        'left': row[0].offsetLeft,
        width: row.width(),
        height: row.height(),
        opacity: 0.8,
        'text-align': 'center'
    });
    $('#discretionary_wrapper').append(div);
}

function dTableDrawComplete()
{
    if($("#discretionary").DataTable().data().length !== 0)
    {
        $("#discretionary_set").show();
    }
    if($(window).width() < 768)
    {
        $('#discretionary th:nth-child(1)').hide();
        $('#discretionary td:nth-child(1)').hide();
        $('#discretionary th:nth-child(2)').hide();
        $('#discretionary td:nth-child(2)').hide();
    }
    $.each($('.transferInProgress'), createOverlay);
}

function rowCreated(row, data, index)
{
    if(data.transferInProgress === '1')
    {
        $(row).addClass('transferInProgress');
    }
}

function init_d_table()
{
    $('#discretionary').dataTable({
        "ajax": 'api/v1/ticket/discretionary?fmt=data-table',
        'createdRow': rowCreated,
        columns: [
            {'data': 'firstName'},
            {'data': 'lastName'},
            {'data': 'type'},
            {'data': 'hash', 'render': short_hash},
            {'data': 'hash', 'render': make_d_action, 'class': 'action-buttons', 'orderable': false}
        ],
        paging: false,
        info: false,
        searching: false
    });

    $("#discretionary").on('draw.dt', dTableDrawComplete);
}

function init_discretionary()
{
    if($('#discretionary').dataTable === undefined) {
      setTimeout(init_discretionary, 100);
      return;
    }
    init_d_table();
}

$(init_discretionary);
