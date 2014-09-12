var ticket_constraints = null;
var table_row = 0;

function constraints_ajax_done(data)
{
    ticket_constraints = data.constraints;
    if(table_row > 0)
    {
        var types = $('[id^=ticket_type_]');
        for(i = 0; i < types.length; i++)
        {
            populateDropdown($(types[i]), null, types.data('temp'))
        }
        for(i = 0; i < table_row; i++)
        {
            ticket_type_changed(i);
        }
    }
}

function populateDropdown(dropdown, cost, value)
{
    if(cost != null)
    {
        cost.val(' ');
    }
    $('<option/>', {value: ' ', text: ' '}).appendTo(dropdown);
    for(i = 0; i < ticket_constraints.ticket_types.length; i++)
    {
        var props = {value: ticket_constraints.ticket_types[i].typeCode, text: ticket_constraints.ticket_types[i].description};
        if(ticket_constraints.ticket_types[i].typeCode == value)
        {
            if(cost != null)
            {
                cost.val('$'+ticket_constraints.ticket_types[i].cost);
            }
            props.selected = true;
        }
        $('<option/>', props).appendTo(dropdown);
    }
}

function float_value(i)
{
    if(typeof i === 'string')
    {
        return i.replace(/[\$,]/g, '')*1;
    }
    else
    {
        return i;
    }
}

function calculate_ticket_subtotal()
{
    var total = 0;
    var costs = $('[name^=ticket_cost_]');
    for(i = 0; i < costs.length; i++)
    {
        total += float_value(costs[i].value);
    }
    $('#ticket_subtotal').html('$'+total);
}

function addRowToTable(tbody, first, last, type, row_id)
{
    var row = $('<tr/>');
    var cell = $('<td/>');
    var first = $('<input/>', {type: "text", id: 'ticket_first_'+row_id, name: 'ticket_first_'+row_id, required: true, value: first, class: 'form-control'});
    first.appendTo(cell);
    cell.appendTo(row);
    cell = $('<td/>');
    var last = $('<input/>', {type: "text", id: 'ticket_last_'+row_id, name: 'ticket_last_'+row_id, required: true, value: last, class: 'form-control'});
    last.appendTo(cell);
    cell.appendTo(row);
    cell = $('<td/>');
    var cell2 = $('<td/>');
    var age = $('<select/>', {id: 'ticket_type_'+row_id, name: 'ticket_type_'+row_id, class: 'form-control', required: true, onchange: 'ticket_type_changed('+row_id+')'});
    var cost = $('<input/>', {type: "text", id: 'ticket_cost', name: "ticket_cost_"+row_id, readonly: true, value: last, class: 'form-control'});
    if(ticket_constraints != null)
    {
        populateDropdown(age, cost, type);
    }
    else
    {
        age.data('temp', type);
    }
    age.appendTo(cell);
    cell.appendTo(row);
    cost.appendTo(cell2);
    cell2.appendTo(row);
    row.appendTo(tbody);
    calculate_ticket_subtotal();
}

function request_ajax_done(data)
{
    $('#first').val(data.givenName);
    $('#last').val(data.sn);
    $('#email').val(data.mail);
    $('#email').tooltip({content: 'This field is not editable. If you want to use a different email then please register a new account with that email.'});
    $('#address').val(data.postalAddress);
    $('#zip').val(data.postalCode);
    $('#city').val(data.l);
    $('#state').val(data.st);
    $('#mobile').val(data.mobile);
    if(data.c == undefined || data.c.length <= 0)
    {
        $('#c').val('US');
    }
    else
    {
        $('#c').val(data.c);
    }

    var tbody = $('#ticket_table tbody');
    addRowToTable(tbody, data.givenName, data.sn, 'A', table_row++);
}

function float_value(i)
{
    if(typeof i === 'string')
    {
        return i.replace(/[\$,]/g, '')*1;
    }
    else
    {
        return i;
    }
}

function ticket_type_changed(row)
{
    var dropdown_value = $('#ticket_type_'+row).val();
    if(ticket_constraints != null)
    {
        var count = 0;
        var types = $('[id^=ticket_type_]');
        for(i = 0; i < types.length; i++)
        {
            if(types[i] == dropdown_value)
            {
                count++;
            }
        }
        var ticket_types = ticket_constraints.ticket_types;
        for(i = 0; i < ticket_types.length; i++)
        {
            if(ticket_types[i].typeCode == dropdown_value)
            {
                $('[name=ticket_cost_'+row+']').val('$'+ticket_types[i].cost);
                if(count > ticket_types[i].max_per_request)
                {
                    alert("You are only allowed to have "+ticket_types[i].max_per_request+" "+
                          ticket_types[i].description+" tickets per request");
                }
            }
        }
    }
    calculate_ticket_subtotal();
}

function add_new_ticket()
{
    var tbody = $('#ticket_table tbody');
    addRowToTable(tbody, '', '', ' ', table_row++);
    if(ticket_constraints != null)
    {
        var rows = $('#ticket_table tbody tr');
        if(rows.length >= ticket_constraints.max_total_tickets)
        {
            $(this).prop('disabled', true);
            $(this).attr('title', 'You can have a maximum of '+ticket_constraints.max_total_tickets+' tickets per request');
            $(this).tooltip();
        }
    }
}

function donation_amount_changed(elem)
{
    var jq = $(elem);
    var id = jq.attr('id');
    var text_id = id+"_text";
    if(jq.val() == 'other')
    {
        if($('#'+text_id).length < 1)
        {
            var box = $('<input/>', {name: text_id, id: text_id, type: 'text'});
            box.appendTo(jq.parent());
        }
    }
    else
    {
        var boxes = $('#'+text_id);
        if(boxes.length >= 1)
        {
            boxes.hide();
        }
    }
}

function add_donation_type_to_table(table, donation)
{
    var row = $('<tr/>');
    var cell = $('<td/>');
    cell.append(donation.entityName);
    if(donation.thirdParty || donation.url)
    {
        cell.append('<br/>');
        if(donation.thirdParty)
        {
            cell.append('<I>Not Affliated with AAR, LLC</I> ');
        }
        if(donation.url)
        {
            cell.append('<a href="'+donation.url+'" target="_new">More Info</a>');
        }
    }
    cell.appendTo(row);
    cell = $('<td/>', {style: 'vertical-align:middle; horizontal-align:left'});
    var id = 'donation_'+donation.entityName;
    var dropdown = $('<select />', {id: id, name: id, onchange: 'donation_amount_changed('+id+')'});
    $('<option/>', {value: '0', text: '$0'}).appendTo(dropdown);
    $('<option/>', {value: '5', text: '$5'}).appendTo(dropdown);
    $('<option/>', {value: '10', text: '$10'}).appendTo(dropdown);
    $('<option/>', {value: '25', text: '$25'}).appendTo(dropdown);
    $('<option/>', {value: '50', text: '$50'}).appendTo(dropdown);
    $('<option/>', {value: 'other', text: 'Other...'}).appendTo(dropdown);
    dropdown.appendTo(cell); 
    cell.appendTo(row);
    row.appendTo(table);
}

function donations_ajax_done(data)
{
    var div = $('#donations');
    var table = $('<table/>', {width: '100%'});
    for(i = 0; i < data.donations.length; i++)
    {
        add_donation_type_to_table(table, data.donations[i]);
    }
    table.appendTo(div);
}

function shouldBeChecked(condition)
{
    if(condition == '1')
    {
        return true;
    }
    //TODO - Add more condition checking
    return false;
}

function addListToRow(list, row)
{
    var cell = $('<td/>');
    var checkbox = $('<input/>', {id: list.short_name, name: list.short_name, type: 'checkbox'});
    if(shouldBeChecked(list.request_condition))
    {
        checkbox.attr('checked', 'true');
    }
    checkbox.appendTo(cell);
    cell.appendTo(row);

    cell = $('<td/>');
    cell.append(list.name+' ');
    if(list.description)
    {
        var img = $('<img/>', {src: '/images/info.svg', style: 'height: 1em; width: 1em;', title: list.description});
        img.appendTo(cell);
    }
    cell.appendTo(row);
}

function lists_ajax_done(data)
{
    var table = $('#email_lists');
    if(data.lists != undefined)
    {
        var lists = data.lists;
        for(i = 0; i < lists.length; i+=2)
        {
            var row = $('<tr/>');
            addListToRow(lists[i], row);
            if(i+1 < lists.length)
            {
                addListToRow(lists[i+1], row);
            }
            row.appendTo(table);
        }
    }
}

function request_submit_done(data)
{
    console.log(data);
}

function request_data_submitted(form)
{
    $.ajax({
        url: '/tickets/ajax/request.php',
        data: $(form).serialize(),
        type: 'post',
        dataType: 'json',
        success: request_submit_done});
}

function init_request()
{
    $.ajax({
        url: 'https://profiles.burningflipside.com/ajax/user.php',
        type: 'get',
        dataType: 'json',
        xhrFields: {withCredentials: true},
        success: request_ajax_done});
   $('#add_new_ticket').on('click', add_new_ticket);
   $.ajax({
        url: '/tickets/ajax/donations.php',
        type: 'get',
        dataType: 'json',
        success: donations_ajax_done});
   $.ajax({
        url: '/tickets/ajax/lists.php',
        type: 'get',
        dataType: 'json',
        success: lists_ajax_done});
   $('#request').validate({
        debug: true,
        submitHandler: request_data_submitted
    });
}

function populate_countries(data)
{
    var countries = data.countries;
    var dropdown = $('#c');
    for(var propertyName in countries)
    {
        $('<option\>', {value: propertyName, text: countries[propertyName]}).appendTo(dropdown);
    }
    dropdown.on('change', country_value_changed);
}

function populate_states(data)
{
    if(data.states == undefined)
    {
        //We don't know how to handle this country. Just let the user input the state freeform
        $('#state').replaceWith($('<input/>', {id: 'state', name: 'state', type: 'text'}));
    }
    else
    {
        var states = data.states;
        $('[for=state]').html(states.states_label+':');
        $('#state').replaceWith($('<select/>', {id: 'state', name: 'state'}));
        var dropdown = $('#state');
        for(var state in states.states)
        {
            $('<option/>', {value: state, text: states.states[state]}).appendTo(dropdown);
        }
    }
}

function start_populate_form()
{
    $.when(
        $.ajax({
            url: 'https://profiles.burningflipside.com/ajax/countries.php',
            type: 'get',
            dataType: 'json',
            success: populate_countries}),
        $.ajax({
            url: 'https://profiles.burningflipside.com/ajax/states.php?c=US',
            type: 'get',
            dataType: 'json',
            success: populate_states}),
        $.ajax({
            url: '/tickets/ajax/constraints.php',
            type: 'get',
            dataType: 'json',
            success: constraints_ajax_done})
    ).done(init_request);
}

function country_value_changed()
{
    var country = $(this).val();
    $.ajax({
            url: 'https://profiles.burningflipside.com/ajax/states.php?c='+country,
            type: 'get',
            dataType: 'json',
            success: populate_states});
}

function init_in_thread()
{
    setTimeout(start_populate_form, 0);
}

$(init_in_thread);
