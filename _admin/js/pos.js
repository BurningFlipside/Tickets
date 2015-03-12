function show_tab(e)
{
    e.preventDefault();
    if($(e.target).parent().filter('.disabled').length === 0)
    {
        $(this).tab('show');
    }
}

function tab_changed(e)
{
    var tab_index = $(e.target).parent().index();
    if(tab_index == 0)
    {
        $('.previous').attr('class', 'previous disabled');
    }
    else
    {
        $('.previous').attr('class', 'previous');
    }
    var last_index = $(e.target).parent().siblings().last().index();
    if(tab_index >= last_index)
    {
        $('.next').html('<a onclick="final_post(event)">Submit</a>');
    }
    else
    {
        $('.next').html('<a href="#" onclick="next_tab(event)">Next <span aria-hidden="true">&rarr;</span></a>');
    }
}

function validate_current()
{
    var tab = $('div.tab-pane.active');
    switch(tab.attr('id'))
    {
        case 'tab0':
            var qty_controls = $('[name^=Qty]');
            var cost = 0;
            var found = false;
            for(i = 0; i < qty_controls.length; i++)
            {
                var control = $(qty_controls[i])
                var qty = control.val();
                if(qty.length > 0)
                {
                    qty = parseInt(qty, 10);
                    found = true;
                    cost += qty*parseInt(control.data('cost'),10);
                    if(qty > parseInt(control.data('max'),10))
                    {
                        alert('Not enough tickets to fullfill request!');
                        return false;
                    }
                }
            }
            if(!found)
            {
                alert('No tickets ordered!');
            }
            $('#total').val('$'+cost);
            return found;
        case 'tab1':
            var email = $('#email').val();
            if(email.length == 0)
            {
                alert('Email is required!');
                return false;
            }
            $('#confirm_email').val(email);
        default:
            return true;
    }
}

function final_post_done(data)
{
    if(data === true)
    {
        location.reload();
    }
    console.log(data);
}

function final_post(e)
{
    if(validate_current())
    {
        var obj = {};
        obj.email = $('#confirm_email').val();
        obj.tickets = {};
        var qtys = $('[name^=Qty]');
        for(i = 0; i < qtys.length; i++)
        {
            var control = $(qtys[i]);
            var name = control.attr('name').substr(3);
            obj.tickets[name] = control.val();
        }
        var message = $('#message').val();
        if(message !== undefined && message.trim().length > 0)
        {
            obj.message = $('#message').val();
        }
        var data_str = JSON.stringify(obj);
        $.ajax({
           url: '/tickets/api/v1/ticket/pos/sell',
           type: 'POST',
           dataType: 'json',
           processData: false,
           data: data_str,
           success: final_post_done
        })
    }
}

function prev_tab(e)
{
    $('li.active').prevAll(":not('.disabled')").first().contents().tab('show');
}

function next_tab(e)
{
    if(validate_current())
    {
        $('li.active').nextAll(":not('.disabled')").first().contents().tab('show');
    }
}

function get_ticket_types_done(data)
{
    var tbody = $('#ticket_select tbody');
    var id = getParameterByName('id');
    if(tbody.length == 0) return;
    for(i = 0; i < data.ticket_types.length; i++)
    {
        tbody.append('<tr><td><input class="form-control" name="Qty'+data.ticket_types[i].typeCode+'" data-cost="'+data.ticket_types[i].cost+'" data-max="0" disabled/></td><td>'+data.ticket_types[i].description+'</td></tr>');
    }
    if(id === null)
    {
        $.ajax({
            url: '/tickets/api/v1/ticket?with_pool=true&sold=0',
            type: 'GET',
            dataType: 'json',
            success: get_tickets_done
        });
    }
    else
    {
        $.ajax({
            url: '/tickets/api/v1/ticket/'+id,
            type: 'GET',
            dataType: 'json',
            success: get_ticket_done
        });
    }
    if(browser_supports_input_type('number'))
    {
        $('[name^=Qty]').attr('type', 'number');
    }
}

function get_tickets_done(data)
{
    if(data.length == 0)
    {
        var control = $('#poswizard');
        control.empty();
        add_notification(control, 'You have no more tickets to sell!');
        return;
    }
    for(i = 0; i < data.length; i++)
    {
        var control = $('[name=Qty'+data[i].type+']');
        if(control.length > 0)
        {
            control.removeAttr('disabled');
            var max = parseInt(control.data('max'),10)+1;
            control.data('max', max);
            control.attr('data-max', max);
        }
    }
}

function get_ticket_done(data)
{
    if(data === false)
    {
        var control = $('#poswizard');
        control.empty();
        add_notification(control, 'Ticket does not exist!');
        return;
    }
    var control = $('[name=Qty'+data.type+']');
    if(control.length > 0)
    {
        control.removeAttr('disabled');
        var max = parseInt(control.data('max'),10)+1;
        control.data('max', max);
        control.attr('data-max', max);
    }
}

function get_available_tickets()
{
    $.ajax({
        url: '/tickets/api/v1/ticket/types',
        type: 'GET',
        dataType: 'json',
        success: get_ticket_types_done
    });
    $('.navbar-nav').click(show_tab);
    $('.previous').attr('class', 'previous disabled');
    $('a[data-toggle="tab"]').on('shown.bs.tab', tab_changed);
}

function init_pos_page()
{
    get_available_tickets();
    if(browser_supports_input_type('email'))
    {
        $('#email').attr('type', 'email');
    }
}

$(init_pos_page)
