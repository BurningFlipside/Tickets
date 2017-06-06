var ticketSystem = new TicketSystem('api/v1');

var ticket_constraints = null;
var table_row = 0;

function constraints_ajax_done(jqXHR)
{
    ticket_constraints = jqXHR.responseJSON;
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
    var costs = $('[name=ticket_cost]');
    for(i = 0; i < costs.length; i++)
    {
        total += float_value(costs[i].value);
    }
    $('#ticket_subtotal').html('$'+total);
}

function addRowToTable(tbody, first, last, type, row_id)
{
    var row = $('<tr/>');
    var cell = $('<td/>', {id: 'delete_cell'});
    if(row_id != 0)
    {
        var button = $('<button/>', {type: 'button', class: 'btn btn-link btn-sm', id: 'delete_'+row_id, onclick: 'delete_ticket()'});
        $('<span/>', {class: 'fa fa-times'}).appendTo(button);
        button.appendTo(cell);
    }
    cell.appendTo(row);
    cell = $('<td/>');
    var first = $('<input/>', {type: "text", id: 'ticket_first', name: 'ticket_first', required: true, value: first, class: 'form-control'});
    first.appendTo(cell);
    cell.appendTo(row);
    cell = $('<td/>');
    var last = $('<input/>', {type: "text", id: 'ticket_last', name: 'ticket_last', required: true, value: last, class: 'form-control'});
    last.appendTo(cell);
    cell.appendTo(row);
    cell = $('<td/>');
    var cell2 = $('<td/>');
    var age = $('<select/>', {id: 'ticket_type', name: 'ticket_type', class: 'form-control', required: true, onchange: 'ticket_type_changed(this)'});
    var cost = $('<input/>', {type: "text", id: 'ticket_cost', name: "ticket_cost", readonly: true, value: last, class: 'form-control'});
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
    $('#givenName').val(data.givenName);
    $('#sn').val(data.sn);
    $('#mail').val(data.mail);
    $('#mail').tooltip({content: 'This field is not editable. If you want to use a different email then please register a new account with that email.'});
    $('#street').val(data.postalAddress);
    $('#zip').val(data.postalCode);
    $('#l').val(data.l);
    $('#st').val(data.st);
    $('#mobile').val(data.mobile);
    if(data.c == undefined || data.c.length <= 0)
    {
        $('#c').val('US');
    }
    else
    {
        $('#c').val(data.c);
    }
    if(data.postalAddress == null || data.postalAddress.length == 0 || 
       data.postalCode == null || data.postalCode.length == 0 || 
       data.l == null || data.l.length == 0 || 
       data.st == null || data.st.length == 0 || 
       data.mobile == null || data.mobile.length == 0)
    {
        add_notification($('#request_set'), 'If you had filled out your profile this data would all be populated.');
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

function reeval_list()
{
    var list = $(this).data('list');
    if(shouldBeChecked(list.request_condition))
    {
        $(this).prop('checked', true);
    }
}

function reeval_lists()
{
    $('#email_lists :checkbox').each(reeval_list);
}

function ticket_type_changed(dropdown)
{
    var dropdown_value = $(dropdown).val();
    if(ticket_constraints !== null)
    {
        var count = 0;
        var types = $('[id^=ticket_type]');
        for(i = 0; i < types.length; i++)
        {
            var x = $(types[i]);
            if(x.val() == dropdown_value)
            {
                count++;
            }
        }
        var ticket_types = ticket_constraints.ticket_types;
        for(i = 0; i < ticket_types.length; i++)
        {
            if(ticket_types[i].typeCode == dropdown_value)
            {
                $(dropdown).parent().siblings().find('[name="ticket_cost"]').val('$'+ticket_types[i].cost);
                if(count > ticket_types[i].max_per_request)
                {
                    alert("You are only allowed to have "+ticket_types[i].max_per_request+" "+
                          ticket_types[i].description+" tickets per request");
                }
            }
        }
    }
    calculate_ticket_subtotal();
    reeval_lists();
}

function add_new_ticket()
{
    var tbody = $('#ticket_table tbody');
    if(table_row > 1)
    {
        var button = $('#delete_'+(table_row-1));
        button.attr('disabled', true);
        var cell = button.parent();
        cell.attr('data-toggle', 'tooltip');
        cell.attr('data-placement', 'left');
        cell.attr('data-container', 'body');
        cell.attr('title', 'You can only remove the last ticket in the list.');
        cell.tooltip();
    }
    addRowToTable(tbody, '', '', ' ', table_row++);
    if(ticket_constraints != null)
    {
        var rows = $('#ticket_table tbody tr');
        if(rows.length >= ticket_constraints.max_total_tickets)
        {
            $(this).prop('disabled', true);
            $('#new_ticket_tooltip').attr('data-toggle', 'tooltip');
            $('#new_ticket_tooltip').attr('data-placement', 'left');
            $('#new_ticket_tooltip').attr('title', 'You can have a maximum of '+ticket_constraints.max_total_tickets+' tickets per request');
            $('#new_ticket_tooltip').tooltip();
        }
    }
}

function delete_ticket()
{
    var button = $('#delete_'+(table_row-1));
    var cell = button.parent();
    cell.parent().remove();
    table_row--;
    var button = $('#delete_'+(table_row-1));
    button.removeAttr('disabled');
    var cell = button.parent();
    cell.tooltip('destroy');
    calculate_ticket_subtotal();
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
            var box = $('<input/>', {name: id, id: text_id, type: 'text', 'class': 'form-control', 'placeholder': 'Donation ($)', 'type': 'number'});
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

function show_donation_info_tooltip()
{
    var span = $(this);
    var donation = span.data('donation');
    var tooltip = '';
    tooltip += '<p align="left">';
    tooltip += 'Checking this box will provide '+donation.entityName+' with the following:<br/>';
    tooltip += '<address>';
    tooltip += $('#givenName').val()+' '+$('#sn').val()+'<br/>';
    tooltip += $('#street').val()+'<br/>';
    tooltip += $('#l').val()+', '+$('#st').val()+' '+$('#zip').val()+'<br/>';
    if($('#c').val() != 'US')
    {
        tooltip += $('#c option:selected').text()+'<br/>';
    }
    tooltip += '</address>';
    tooltip += 'Email: '+$('#mail').val()+'<br/>';
    var amount = $('#donation_amount_'+donation.entityName).val();
    if(amount == 'other')
    {
        amount = $('#donation_amount_'+donation.entityName+'_text').val();
    }
    tooltip += 'Donation Amount: $'+amount;
    tooltip += '</p>';
    span.attr('data-original-title', tooltip);
}

function add_disclose_checkbox_to_cell(cell, donation)
{
    var id = 'donation_disclose_'+donation.entityName;
    var span = $('<span/>', {'data-toggle': 'tooltip', 'data-placement': 'bottom', 'title': 'Filler...'});
    var checkbox = $('<input/>', {type: 'checkbox', id: id, name: id});
    var label = $('<label/>', {for: id}).html('&nbsp;Allow '+donation.entityName+' to see my contact details');
    checkbox.appendTo(span);
    label.appendTo(span);
    span.appendTo(cell);
    span.tooltip({html:true});
    span.data('donation', donation);
    span.on('show.bs.tooltip', show_donation_info_tooltip);
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
    cell = $('<td/>');
    if(donation.thirdParty && $(window).width() >= 768)
    {
        add_disclose_checkbox_to_cell(cell, donation);
    }
    cell.appendTo(row);
    cell = $('<td/>', {style: 'vertical-align:middle; horizontal-align:left'});
    var id = 'donation_amount_'+donation.entityName;
    var dropdown = $('<select />', {id: id, name: id, onchange: 'donation_amount_changed('+id+')', 'class':'form-control'});
    $('<option/>', {value: '0', text: '$0'}).appendTo(dropdown);
    $('<option/>', {value: '5', text: '$5'}).appendTo(dropdown);
    $('<option/>', {value: '10', text: '$10'}).appendTo(dropdown);
    $('<option/>', {value: '25', text: '$25'}).appendTo(dropdown);
    $('<option/>', {value: '50', text: '$50'}).appendTo(dropdown);
    $('<option/>', {value: 'other', text: 'Other...'}).appendTo(dropdown);
    dropdown.appendTo(cell); 
    cell.appendTo(row);
    row.appendTo(table);
    if(donation.thirdParty && $(window).width() < 768)
    {
        row = $('<tr/>');
        cell = $('<td/>', {colspan: '3'});
        add_disclose_checkbox_to_cell(cell, donation);
        cell.appendTo(row);
        row.appendTo(table);
    }
}

function donations_ajax_done(jqXHR)
{
    var data = jqXHR.responseJSON;
    var div = $('#donations');
    if(data.length > 0)
    {
        var table = $('<table/>', {width: '100%'});
        for(i = 0; i < data.length; i++)
        {
            add_donation_type_to_table(table, data[i]);
        }
        table.appendTo(div);
    }
    else
    {
       div.hide();
    }
}

function get_ticket_count(type)
{
    var values = $('[value="'+type+'"]').filter(':selected');
    return values.length;
}

function shouldBeChecked(condition)
{
    if(condition == '1')
    {
        return true;
    }
    var A = get_ticket_count('A');
    var T = get_ticket_count('T');
    var C = get_ticket_count('C');
    var K = get_ticket_count('K');
    var res = eval(condition);
    return res;
}

function addListToRow(list, row)
{
    var cell = $('<td/>');
    var checkbox = $('<input/>', {id: 'list_'+list.short_name, name: 'list_'+list.short_name, type: 'checkbox'});
    if(shouldBeChecked(list.request_condition))
    {
        checkbox.attr('checked', 'true');
    }
    checkbox.appendTo(cell);
    cell.appendTo(row);
    checkbox.data('list', list);

    cell = $('<td/>');
    cell.append(list.name+' ');
    if(list.description)
    {
        var img = $('<img/>', {src: '/images/info.svg', style: 'height: 1em; width: 1em;', title: list.description});
        img.appendTo(cell);
    }
    cell.appendTo(row);
}

function lists_ajax_done(jqXHR)
{
    var data = jqXHR.responseJSON;
    var table = $('#email_lists');
    if(data.length > 0)
    {
        for(i = 0; i < data.length; i+=2)
        {
            var row = $('<tr/>');
            addListToRow(data[i], row);
            if(i+1 < data.length)
            {
                addListToRow(data[i+1], row);
            }
            row.appendTo(table);
        }
    }
}

function requestSubmitDone(data, err) {
    if(err !== null) {
        if(err.jsonResp !== undefined && err.jsonResp.message !== undefined) {
            alert(err.jsonResp.message);
        }
        else {
            alert('Unable to submit request!');
            console.log(err);
        }
        return;
    }
    if(data.need_minor_confirm !== undefined && (data.need_minor_confirm === '1' || data.need_minor_confirm === true)) {
        $('#minor_dialog').modal({
                'backdrop': 'static',
                'keyboard': false
        });
        $('[title]').tooltip('hide');
    }
    else {
        window.location = 'index.php';
    }
}

function fixup_donation_form()
{
    if($(this).val() == 'other')
    {
        $(this).attr('disabled', 'true');
        var id = $(this).id;
        $(this).removeAttr('name');
        $('#'+id+'_text').attr('name', id);
    }
}

function revert_donation_form()
{
    if($(this).val() == 'other')
    {
        $(this).removeAttr('disabled');
        var id = $(this).id;
        $(this).attr('name', id);
        $('#'+id+'_text').attr('name', id+'_text');
    }
}

function request_data_submitted()
{
    $('[id^=donation_amount_]').each(fixup_donation_form);
    var obj = {};
    var a = $('#request').serializeArray();
    for(var i = 0; i < a.length; i++)
    {
        var name = a[i].name;
        var split = name.split('_');
        if(split[0] == 'list')
        {
            if(obj['lists'] === undefined)
            {
                obj['lists'] = {};
            }
            obj['lists'][name.substring(5)] = a[i].value;
        }
        else if(split[0] == 'ticket')
        {
            var child_name = name.substring(7);
            if(obj['tickets'] === undefined)
            {
                obj['tickets'] = [];
            }
            if(obj['tickets'].length === 0 || obj['tickets'][obj['tickets'].length-1][child_name] !== undefined)
            {
                 obj['tickets'][obj['tickets'].length] = {};
            }
            obj['tickets'][obj['tickets'].length-1][child_name] = a[i].value;
        }
        else if(split[0] == 'donation')
        {
            if(obj['donations'] === undefined)
            {
                obj['donations'] = {};
            }
            if(obj['donations'][split[2]] === undefined)
            {
                obj['donations'][split[2]] = {};
            }
            obj['donations'][split[2]][split[1]] = a[i].value;
        }
        else
        {
            obj[name] = a[i].value;
        }
    }
    if(obj.donations !== undefined)
    {
        for(var donationType in obj.donations)
        {
            if(obj.donations[donationType].amount == 0)
            {
                delete obj.donations[donationType];
            }
        }
        if($.isEmptyObject(obj.donations))
        {
            delete obj.donations;
        }
    }

    ticketSystem.createRequest(obj, requestSubmitDone);
    $('[id^=donation_amount_]').each(revert_donation_form);
    return false;
}

function resubmit_form()
{
    var form = $('#request');
    $('<input/>', {type: 'hidden', name: 'minor_confirm', value: '1'}).appendTo(form);
    request_data_submitted(form[0]); 
}

function minor_affirm_clicked()
{
   $('#minor_dialog_continue').removeAttr('disabled');
   $('#minor_dialog_continue').on('click', resubmit_form);
}

function currentRequestDone(request, err) {
    if(request === null) {
        ticketSystem.getTicketRequestIdForCurrentUser(requestIdDone);
    }
    else {
        var tbody = $('#ticket_table tbody');
        for(var propertyName in request) {
            switch(propertyName) {
                case 'tickets':
                    for(var i = 0; i < request[propertyName].length; i++) {
                        addRowToTable(tbody, request.tickets[i].first, request.tickets[i].last, request.tickets[i].type, table_row++);
                    }
                    break;
                case 'donations':
                    if(request[propertyName] === null) {
                        continue;
                    }
                    for(var i = 0; i < request[propertyName].length; i++) {
                        var id = 'donation_amount_'+request.donations[i].type;
                        var dropdown = $('#'+id);
                        dropdown.val(request.donations[i].amount);
                        if(dropdown.val() == null) {
                            dropdown.val('other');
                            var box = $('<input/>', {name: id, id: id+'_text', type: 'text', value: request.donations[i].amount});
                            box.appendTo(dropdown.parent());
                        }
                        if(request.donations[i].disclose !== undefined && request.donations[i].disclose == '1') {
                            $('#donation_disclose_'+request.donations[i].type).prop('checked', true);
                        }
                    }
                    break;
                default:
                    $('#'+propertyName).val(request[propertyName]);
                    break;
            }
        }
    }
}

function requestIdDone(data, err) {
    if(err !== null) {
        alert('Unable to obtain request ID!');
        return;
    }
    $('#request_id').val(data);
    if(browser_supports_cors()) {
        $.ajax({
            url: window.profilesUrl+'/api/v1/users/me',
            type: 'get',
            dataType: 'json',
            xhrFields: {withCredentials: true},
            success: request_ajax_done});
    }
    else {
        add_notification($('#request_set'), 'Your browser is out of date. Due to this some data may be missing from your request. Please make sure it is complete');
    }
}

function init_request()
{
    var request_id  = getParameterByName('request_id');
    var year        = getParameterByName('year');
    ticketSystem.getRequest(currentRequestDone, request_id, year);
    var request = $('#request').data('request');
    $('#add_new_ticket').on('click', add_new_ticket);
    if(request != undefined)
    {
        var tbody = $('#ticket_table tbody');
        for(var i = 0; i < request.tickets.length; i++)
        {
            addRowToTable(tbody, request.tickets[i].first, request.tickets[i].last, request.tickets[i].type.typeCode, table_row++);
        }
        for(var i = 0; i < request.donations.length; i++)
        {
            var id = 'donation_amount_'+request.donations[i].type;
            var dropdown = $('#'+id);
            dropdown.val(request.donations[i].amount);
            if(dropdown.val() == null)
            {
                dropdown.val('other');
                var box = $('<input/>', {name: id, id: id+'_text', type: 'text', value: request.donations[i].amount});
                box.appendTo(dropdown.parent());
            }
            if(request.donations[i].disclose !== undefined && request.donations[i].disclose == '1')
            {
                $('#donation_disclose_'+request.donations[i].type.entityName).prop('checked', true);
            }
        }
        reeval_lists();
    }
    $('#request').submit(request_data_submitted);
}

function start_populate_form()
{
    $.when(
        $.ajax({
            url: 'api/v1/globals/constraints',
            type: 'get',
            dataType: 'json',
            complete: constraints_ajax_done}),
        $.ajax({
            url: 'api/v1/globals/donation_types',
            type: 'get',
            dataType: 'json',
            complete: donations_ajax_done}),
        $.ajax({
            url: 'api/v1/globals/lists',
            type: 'get',
            dataType: 'json',
            complete: lists_ajax_done})
    ).done(init_request);
}

function init_in_thread()
{
    if($('#request_id').length > 0)
    {
        setTimeout(start_populate_form, 0);
        $('[title]').tooltip();
    }
}

$(init_in_thread);
