var selectedPool = 0;
var tickets = null;

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
        $('.next').html('<a class="page-link" href="#" onclick="final_post(event)">Sell</a>');
    }
    else
    {
        $('.next').html('<a class="page-link" href="#" onclick="next_tab(event)">Next <span aria-hidden="true">&rarr;</span></a>');
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

function final_post_done(jqXHR)
{
    $('.next').attr('disabled', false);
    if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined)
    {
        alert('Unable to get ticket pools!');
        return;
    }
    data = jqXHR.responseJSON;
    if(data === true)
    {
        location.reload();
    }
    else
    {
        alert(data);
        console.log(data);
    }
}

function final_post(e)
{
    if(validate_current())
    {
        $('.next').attr('disabled', true);
        var id = getParameterByName('id');
        var obj = {};
        obj.pool = selectedPool;
        obj.email = $('#email').val();
        obj.tickets = {};
        var qtys = $('[name^=Qty]');
        for(i = 0; i < qtys.length; i++)
        {
            var control = $(qtys[i]);
            var name = control.attr('name').substr(3);
            obj.tickets[name] = control.val()*1;
        }
        var message = $('#message').val();
        if(message !== undefined && message.trim().length > 0)
        {
            obj.message = $('#message').val();
        }
        var firstName = $('#firstName').val();
        if(firstName !== undefined && firstName.trim().length > 0)
        {
            obj.firstName = firstName;
        }
        var lastName = $('#lastName').val();
        if(lastName !== undefined && lastName.trim().length > 0)
        {
            obj.lastName = lastName;
        }
        var data_str = JSON.stringify(obj);
        if(id !== null)
        {
            var obj = {};
            obj.email = $('#email').val();
            $.ajax({
                 url: '/tickets/api/v1/tickets/'+id+'/Actions/Ticket.Sell',
                 contentType: 'application/json',
                 type: 'POST',
                 dataType: 'json',
                 processData: false,
                 data: data_str,
                 complete: final_post_done});
            return;
        }
        $.ajax({
           url: '/tickets/api/v1/ticket/pos/sell',
           contentType: 'application/json',
           type: 'POST',
           dataType: 'json',
           processData: false,
           data: data_str,
           complete: final_post_done
        })
    }
}

function prev_tab(e)
{
    $('li.nav-item .active').parent().prevAll(":not('.disabled')").first().find('a').tab('show');
}

function next_tab(e)
{
    if(validate_current())
    {
        $('li.nav-item .active').parent().next().find('a').tab('show');
    }
}

function getTicketTypesDone(jqXHR)
{
    if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined)
    {
        alert('Unable to get ticket pools!');
        return;
    }
    var data = jqXHR.responseJSON;
    var tbody = $('#ticket_select tbody');
    var id = getParameterByName('id');
    if(tbody.length == 0) return;
    for(i = 0; i < data.length; i++)
    {
        tbody.append('<tr><td><input class="form-control" name="Qty'+data[i].typeCode+'" data-type="'+data[i].typeCode+'" data-cost="'+data[i].cost+'" data-max="0" disabled/></td><td>'+data[i].description+'</td></tr>');
    }
    if(id === null)
    {
        $.ajax({
            url: '/tickets/api/v1/tickets/pos?$filter=sold eq 0',
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

function updateControl(index, element)
{
    var control = $(element);
    var type = control.data('type');
    if(tickets[selectedPool] === undefined || tickets[selectedPool][type] === undefined)
    {
        control.attr('disabled', true);
        control.attr('max', 0);
        control.attr('min', 0);
    }
    else
    {
        control.removeAttr('disabled');
        control.attr('max', tickets[selectedPool][type].length);
        control.attr('min', 0);
        control.data('max', tickets[selectedPool][type].length);
        control.attr('data-max', tickets[selectedPool][type].length);
    }
}

function poolChanged(control)
{
    selectedPool = $(control).val()*1;
    var inputs = $('[name^=Qty]');
    inputs.each(updateControl);
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
        if(tickets === null)
        {
            tickets = [];
        }
        var pool_id = data[i].pool_id*1;
        if(tickets[pool_id] === undefined)
        {
            tickets[pool_id] = {};
        }
        if(tickets[pool_id][data[i].type] === undefined)
        {
            tickets[pool_id][data[i].type] = [];
        }
        tickets[pool_id][data[i].type].push(data[i]);
    }
    if(tickets !== null)
    {
    	var options = $('#pool option');
	for(i = 0; i < options.length; i++)
    	{
            if(tickets[options[i].value*1] === undefined)
            {
                $(options[i]).prop('disabled', true);
            }
        }
        poolChanged($('#pool')[0]);
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
        //control.removeAttr('disabled');
        var max = parseInt(control.data('max'),10)+1;
        control.data('max', max);
        control.attr('data-max', max);
        control.attr('max', 1);
        control.attr('min', 1);
        control.val(1);
    }
    selectedPool = data.pool_id;
    $('#pool').val(data.pool_id);
    $('#pool').prop('disabled', true);
}

function getTicketTypes()
{
    $.ajax({
        url: '/tickets/api/v1/ticket/types',
        type: 'GET',
        dataType: 'json',
        complete: getTicketTypesDone
    });
    $('.previous').attr('class', 'previous disabled');
    $('a[data-toggle="tab"]').on('shown.bs.tab', tab_changed);
}

function getPoolsDone(jqXHR)
{
    if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined)
    {
        alert('Unable to get ticket pools!');
        return;
    }
    var poolSelect = $('#pool');
    for(var i = 0; i < jqXHR.responseJSON.length; i++)
    {
        poolSelect.append('<option value="'+jqXHR.responseJSON[i].pool_id+'">'+jqXHR.responseJSON[i].pool_name+'</option>');
    }
}

function getPools()
{
    $.ajax({
        url: '../api/v1/pools/me',
        type: 'GET',
        dataType: 'json',
        complete: getPoolsDone
    });
}

function init_pos_page()
{
    getPools();
    getTicketTypes();
    if(browser_supports_input_type('email'))
    {
        $('#email').attr('type', 'email');
    }
}

$(init_pos_page)
