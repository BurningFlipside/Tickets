function add_row_to_table(tbody, name, value)
{
    var row = $('<tr/>');
    var cell = $('<td/>');
    if(name == '_blank')
    {
    }
    else
    {
        cell.html('<button type="button" class="btn btn-link btn-sm" id="delete_'+name+'"><span class="fa fa-times"></span></button>');
    }
    cell.appendTo(row);
    cell = $('<td/>');
    if(name == '_blank')
    {
        cell.html('<input type="text" id="name__blank" value="" placeholder="Variable Name"/>');
    }
    else
    {
        cell.html(name);
    }
    cell.appendTo(row);
    cell = $('<td/>');
    cell.html('<div class="input-group"><input class="form-control" type="text" id="text_'+name+'" value="'+value+'"/><span class="input-group-btn"><button type="button" class="btn btn-default" id="change_'+name+'"><span class="fa fa-check"></span></button></span></div>');
    cell.appendTo(row);
    row.appendTo(tbody);
}

function variable_set_done(jqXHR)
{
    if(jqXHR.status === 200)
    {
        $('#raw tbody').empty();
        init_vars();
    }
    else
    {
        console.log(jqXHR);
        alert("Unable to set variable");
    }
}

function delete_var()
{
    var button = $(this);
    var var_name = button.attr('id').substr(7);
    $.ajax({
            url: '../api/v1/globals/vars/'+var_name,
            type: 'delete',
            dataType: 'json',
            complete: variable_set_done});
}

function change_var()
{
    var button = $(this);
    var var_name = button.attr('id').substr(7);
    var var_value = $('#text_'+var_name).val();
    var method = 'patch';
    if(var_name == '_blank')
    {
        var_name = $('#name__blank').val();
        if(var_name.length < 1)
        {
            alert('Variable name must be at least one character long');
            return;
        }
        method = 'post';
    }
    $.ajax({
            url: '../api/v1/globals/vars/'+var_name,
            contentType: 'application/json',
            data: JSON.stringify(var_value),
            processData: false,
            type: method,
            dataType: 'json',
            complete: variable_set_done});
}

function unset_test_mode()
{
    $.ajax({
            url: '../api/v1/globals/vars/test_mode',
            contentType: 'application/json',
            data: JSON.stringify(0),
            processData: false,
            type: 'patch',
            dataType: 'json',
            complete: variable_set_done});
}

function known_change(control)
{
    var jq = $(control);
    var var_value = '';
    var var_name = '';
    if(jq.is("button"))
    {
        var_name = jq.attr("for");
        var_value = $('#'+var_name).val(); 
    }
    else
    {
        var_name = jq.attr("name");
        var_value = jq.val();
    }
    if(var_name == 'test_mode' && var_value == '0')
    {
        var html = '<strong>Warning!</strong> Unsetting Test mode will delete all test entries are you sure you want to continue?';
        var modal = create_modal('Test Mode', html, [{text:'Yes', method: unset_test_mode, close: true}, {text:'No', close: true}]);
        modal.modal();
        return;
    }
    $.ajax({
            url: '../api/v1/globals/vars/'+var_name,
            contentType: 'application/json',
            data: JSON.stringify(var_value),
            processData: false,
            type: 'patch',
            dataType: 'json',
            complete: variable_set_done});
}

function populate_raw_table(vars)
{
    var tbody = $('#raw tbody');
    for(var i = 0; i < vars.length; i++)
    {
        add_row_to_table(tbody, vars[i].name, vars[i].value);
    }
    //Add empty row for adding
    add_row_to_table(tbody, '_blank', '');
    $('[id^=delete_]').on('click', delete_var);
    $('[id^=change_]').on('click', change_var);
}

function populate_known_form(vars)
{
   for(var i = 0; i < vars.length; i++)
   {
       var control = $('#'+vars[i].name);
       if(control.length > 0)
       {
           control.val(vars[i].value);
       }
   }
}

function variables_done(jqXHR)
{
    if(jqXHR.status !== 200)
    {
        alert('Error obtaining variables!');
        return;
    }
    else
    {
        populate_raw_table(jqXHR.responseJSON);
        populate_known_form(jqXHR.responseJSON);
    }
}

function init_vars()
{
    $('#tabs a:first').tab('show');
    $.ajax({
            url: '../api/v1/globals/vars',
            type: 'get',
            dataType: 'json',
            complete: variables_done});
}

$(init_vars)
