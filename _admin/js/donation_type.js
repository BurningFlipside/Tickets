function addDonationType(donation_type)
{
    var nav = $('#donation_type_nav');
    var nav_item = $('<li/>');
    var link = $('<a/>', {href: '#'+donation_type.entityName, role: 'tab', 'data-toggle':'tab'}).html(donation_type.entityName).appendTo(nav_item);
    if(donation_type.entityName != 'NEW')
    {
        var button = $('<button/>', {type: 'button', 'class': 'btn btn-link', id: 'delete_'+donation_type.entityName, 'title': 'Delete Donation Type', 'data-toggle': "tooltip", 'data-placement': "top"});
        $('<span/>', {'aria-hidden': 'true'}).html('&times;').appendTo(button);
        $('<span/>', {'class': 'sr-only'}).html('Delete').appendTo(button);
        link.append('&nbsp;');
        button.appendTo(link);
    }
    nav_item.appendTo(nav);

    var content = $('#donation_type_content');
    var content_item = $('<div/>', {'class':'tab-pane', id: donation_type.entityName});
    var form = $('<form/>', {'class':'form-horizontal', 'role':'form'});
    var div = $('<div/>', {'class':'form-group'});
    var label = $('<label/>', {'for': 'entityName_'+donation_type.entityName, 'class': 'col-sm-2 control-label'}).html('Entity Name');
    var input = $('<input/>', {'type': 'text', 'class': 'form-control', 'id': 'entityName_'+donation_type.entityName, 'required': 'true'});
    if(donation_type.entityName != 'NEW')
    {
        input.attr('value', donation_type.entityName);
    }
    label.appendTo(div);
    var inner_div = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
    input.appendTo(inner_div);
    inner_div.appendTo(div);

    label = $('<label/>', {'for': 'url_'+donation_type.entityName, 'class': 'col-sm-2 control-label'}).html('Website URL');
    input = $('<input/>', {'type': 'url', 'class': 'form-control', 'id': 'url_'+donation_type.entityName, 'required': 'true'});
    if(donation_type.entityName != 'NEW')
    {
        input.attr('value', donation_type.url);
    }
    label.appendTo(div);
    inner_div = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
    input.appendTo(inner_div);
    inner_div.appendTo(div);

    label = $('<label/>', {'for': 'thirdParty_'+donation_type.entityName, 'class': 'col-sm-2 control-label'}).html('Is this entity a third party?');
    input = $('<input/>', {'type': 'checkbox', 'class': 'form-control', 'id': 'thirdParty_'+donation_type.entityName, 'data-on-text': 'Yes', 'data-off-text': 'No'});
    if(donation_type.thirdParty == '1')
    {
        input.attr('checked', true);
    }
    label.appendTo(div);
    inner_div = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
    input.appendTo(inner_div);
    inner_div.appendTo(div); 

    div.appendTo(form);

    div = $('<div/>', {'class':'row'});
    var button = $('<button/>', {'class': 'btn btn-default', id: 'commit_'+donation_type.entityName, 'title': 'Change Donation Type', 'data-toggle': "tooltip", 'data-placement': "top"});
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
        tab = $('#donation_type_nav .active');
    }
    else
    {
        tab = $(e.currentTarget);
    }
    tab.find('[id^=delete_]').show();
}

function reallyDelete(e)
{
    var button = $(e.currentTarget);
    var id = button.data('data');
    $.ajax({
        url: '../api/v1/globals/donation_types/'+id,
        type: 'DELETE',
        dataType: 'json',
        success: initDonationType,
        error: actionFailed});
}

function actionFailed(jqXHR)
{
    console.log(jqXHR);
}

function deleteDonationType(e)
{
    var button = $(e.currentTarget);
    var id = button.attr('id').split('_');
    var type = id[1];
    var desc = $('#entityName_'+type).val();

    var yes = {'close': true, 'text': 'Yes', 'method': reallyDelete, 'data': type};
    var no = {'close': true, 'text': 'No'};
    var buttons = [yes, no];
    var modal = create_modal('Are you sure?', 'Are you sure you want to delete the '+desc+' donation type? This operation cannot be undone.', buttons);
    modal.appendTo(document.body);
    modal.modal();
    e.preventDefault();
}

function commitDonationType(e)
{
    var button = $(e.currentTarget);
    var id = button.attr('id').split('_');
    var type = id[1];
    var controls = $('[id$=_'+type+'].form-control');
    var data = {};
    for(i = 0; i < controls.length; i++)
    {
        var control = $(controls[i]);
        if(control[0].type === 'checkbox')
        {
            data[control.attr('id').split('_')[0]] = control[0].checked;
        }
        else
        {
            data[control.attr('id').split('_')[0]] = control.val();
        }
    }
    e.preventDefault();
    $.ajax({
        url: '../api/v1/globals/donation_types',
        data: JSON.stringify(data),
        type: 'POST',
        processData: false,
        dataType: 'json',
        success: initDonationType,
        error: actionFailed});
}

function donationsDone(jqXHR)
{
    if(jqXHR.status !== 200)
    {
        alert('Failed to obtain donation type data!');
        console.log(jqXHR);
    }
    else
    {
        var data = jqXHR.responseJSON;
        for(var i = 0; i < data.length; i++)
        {
            addDonationType(data[i]);
        }
    }
    var new_type = new Object();
    new_type.entityName = 'NEW';
    new_type.thirdParty = '';
    new_type.url = '';
    addDonationType(new_type);
    $('#donation_type_nav a:first').tab('show');
    $('#donation_type_nav a').on('shown.bs.tab', tab_shown);
    $('[title]').tooltip();
    tab_shown();
    $('[id^=delete_]').on('click', deleteDonationType);
    $('[id^=commit_]').on('click', commitDonationType);
}

function initDonationType()
{
    $('#donation_type_nav').empty();
    $('#donation_type_content').empty();
    $.ajax({
        url: '../api/v1/globals/donation_types',
        type: 'get',
        dataType: 'json',
        complete: donationsDone});
}

$(initDonationType);
