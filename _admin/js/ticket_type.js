function addTicketType(ticket_type)
{
    var nav = $('#ticket_type_nav');
    var nav_item = $('<li/>');
    var link = $('<a/>', {href: '#'+ticket_type.typeCode, role: 'tab', 'data-toggle':'tab'}).html(ticket_type.description).appendTo(nav_item);
    if(ticket_type.typeCode != 'NEW')
    {
        var button = $('<button/>', {type: 'button', 'class': 'btn btn-link', id: 'delete_'+ticket_type.typeCode, 'title': 'Delete Ticket Type', 'data-toggle': "tooltip", 'data-placement': "top"});
        $('<span/>', {'aria-hidden': 'true'}).html('&times;').appendTo(button);
        $('<span/>', {'class': 'sr-only'}).html('Delete').appendTo(button);
        link.append('&nbsp;');
        button.appendTo(link);
    }
    nav_item.appendTo(nav);

    var content = $('#ticket_type_content');
    var content_item = $('<div/>', {'class':'tab-pane', id: ticket_type.typeCode});
    var form = $('<form/>', {'class':'form-horizontal', 'role':'form'});
    var div = $('<div/>', {'class':'form-group'});
    var label = $('<label/>', {'for': 'type_code_'+ticket_type.typeCode, 'class': 'col-sm-2 control-label'}).html('Type Code');
    var input = $('<input/>', {'type': 'text', 'class': 'form-control', 'id': 'type_code_'+ticket_type.typeCode, 'required': 'true'});
    if(ticket_type.typeCode != 'NEW')
    {
        input.attr('value', ticket_type.typeCode);
    }
    label.appendTo(div);
    var inner_div = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
    input.appendTo(inner_div);
    inner_div.appendTo(div);

    label = $('<label/>', {'for': 'desc_'+ticket_type.typeCode, 'class': 'col-sm-2 control-label'}).html('Description');
    input = $('<input/>', {'type': 'text', 'class': 'form-control', 'id': 'desc_'+ticket_type.typeCode, 'required': 'true'});
    if(ticket_type.typeCode != 'NEW')
    {
        input.attr('value', ticket_type.description);
    }
    label.appendTo(div);
    inner_div = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
    input.appendTo(inner_div);
    inner_div.appendTo(div);

    label = $('<label/>', {'for': 'cost_'+ticket_type.typeCode, 'class': 'col-sm-2 control-label'}).html('Cost ($)');
    input = $('<input/>', {'type': 'text', 'class': 'form-control', 'id': 'cost_'+ticket_type.typeCode, 'value': ticket_type.cost, 'required': 'true'});
    label.appendTo(div);
    inner_div = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
    input.appendTo(inner_div);
    inner_div.appendTo(div);
   
    label = $('<label/>', {'for': 'max_per_'+ticket_type.typeCode, 'class': 'col-sm-2 control-label'}).html('Max of this type per request');
    input = $('<input/>', {'type': 'number', 'class': 'form-control', 'id': 'max_per_'+ticket_type.typeCode, 'value': ticket_type.max_per_request, 'required': 'true'});
    label.appendTo(div);
    inner_div = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
    input.appendTo(inner_div);
    inner_div.appendTo(div);

    div.append('<div class="clearfix visible-sm visible-md visible-lg"></div>');

    label = $('<label/>', {'for': 'minor_'+ticket_type.typeCode, 'class': 'col-sm-2 control-label'}).html('Is this request type a minor?');
    input = $('<input/>', {'type': 'checkbox', 'class': 'form-control', 'id': 'minor_'+ticket_type.typeCode, 'data-on-text': 'Yes', 'data-off-text': 'No'});
    if(ticket_type.is_minor == '1')
    {
        input.attr('checked', true);
    }
    label.appendTo(div);
    inner_div = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
    input.appendTo(inner_div);
    inner_div.appendTo(div); 

    div.appendTo(form);

    div = $('<div/>', {'class':'row'});
    var button = $('<button/>', {'class': 'btn btn-default', id: 'commit_'+ticket_type.typeCode, 'title': 'Change Ticket Type', 'data-toggle': "tooltip", 'data-placement': "top"});
    button.html('Commit Changes');
    button.appendTo(div);
    div.appendTo(form);

    form.appendTo(content_item);
    content_item.appendTo(content);
}

function tab_shown(e)
{
    $('[id^=delete_]').hide();
    var tab;
    if(e === undefined)
    {
        tab = $('#ticket_type_nav .active');
    }
    else
    {
        tab = $(e.currentTarget);
    }
    tab.find('[id^=delete_]').show();
}

function actionFailed(jqXHR)
{
    console.log(jqXHR);
}

function reallyDelete(e)
{
    var button = $(e.currentTarget);
    var id = button.data('data');
    $.ajax({
        url: '../api/v1/globals/ticket_types/'+id,
        type: 'DELETE',
        dataType: 'json',
        success: initTicketType,
        error: actionFailed});
}

function deleteTicketType(e)
{
    var button = $(e.currentTarget);
    var id = button.attr('id').split('_');
    var type = id[1];
    var desc = $('#desc_'+type).val();

    var yes = {'close': true, 'text': 'Yes', 'method': reallyDelete, 'data': type};
    var no = {'close': true, 'text': 'No'};
    var buttons = [yes, no];
    var modal = create_modal('Are you sure?', 'Are you sure you want to delete the '+desc+' ticket type? This operation cannot be undone.', buttons);
    modal.appendTo(document.body);
    modal.modal();
    e.preventDefault();
}

function commitTicketType(e)
{
    var button = $(e.currentTarget);
    var id = button.attr('id').split('_');
    var type = id[1];
    var controls = $('[id$=_'+type+'].form-control');
    var data = {};
    for(i = 0; i < controls.length; i++)
    {
        var control = $(controls[i]);
        var control_name = control.attr('id');
        control_name = control_name.substring(0, control_name.lastIndexOf('_'));
        if(control[0].type === 'checkbox')
        {
            data[control_name] = control[0].checked;
        }
        else
        {
            data[control_name] = control.val();
        }
    }
    e.preventDefault();
    $.ajax({
        url: '../api/v1/globals/ticket_types',
        data: JSON.stringify(data),
        type: 'POST',
        processData: false,
        dataType: 'json',
        success: initTicketType,
        error: actionFailed});
}

function ticketTypesDone(jqXHR)
{
    if(jqXHR.status !== 200)
    {
        alert('Error reading ticket types!');
        console.log(jqXHR);
        return;
    }
    var data = jqXHR.responseJSON;
    for(var i = 0; i < data.length; i++)
    {
        addTicketType(data[i]);
    }
    var new_type = new Object();
    new_type.typeCode = 'NEW';
    new_type.description = 'New Type';
    new_type.cost = '';
    new_type.max_per_request = '';
    new_type.is_minor = '';
    addTicketType(new_type);
    $('#ticket_type_nav a:first').tab('show');
    $('#ticket_type_nav a').on('shown.bs.tab', tab_shown);
    $('[title]').tooltip();
    tab_shown();
    $('[id^=delete_]').on('click', deleteTicketType);
    $('[id^=commit_]').on('click', commitTicketType);
}

function initTicketType()
{
    $('#ticket_type_nav').empty();
    $.ajax({
        url: '../api/v1/globals/ticket_types',
        type: 'get',
        dataType: 'json',
        complete: ticketTypesDone});
}

$(initTicketType);
