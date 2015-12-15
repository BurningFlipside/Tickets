function change_name_done(data)
{
    if(data.error !== undefined)
    {
        alert(data.error);
        return;
    }
    else
    {
        window.location = '/tickets/index.php';
    }
}

function transfer_done(data)
{
    if(data.error !== undefined)
    {
        alert(data.error);
        return;
    }
    else
    {
        window.location = '/tickets/index.php?show_transfer_info=1';
    }
}

function change_name()
{
    var obj = {};
    obj.firstName = $('#firstName').val();
    obj.lastName  = $('#lastName').val();
    $.ajax({
        url: 'api/v1/tickets/'+encodeURIComponent($('#hash').val()),
        type: 'patch',
        data: JSON.stringify(obj),
        processData: false,
        dataType: 'json',
        success: change_name_done});
}

function transfer()
{
    var obj = {};
    obj.email = $('#email').val();
    $.ajax({
        url: 'api/v1/tickets/'+encodeURIComponent($('#hash').val())+'/Actions/Transfer',
        type: 'post',
        data: JSON.stringify(obj),
        processData: false,
        dataType: 'json',
        success: transfer_done});
}

function claim_ticket()
{
    var obj = {};
    obj.firstName = $('#firstName').val();
    obj.lastName  = $('#lastName').val();
    $.ajax({
        url: 'api/v1/tickets/'+encodeURIComponent($('#hash').val())+'/Actions/Claim',
        type: 'post',
        data: JSON.stringify(obj),
        processData: false,
        dataType: 'json',
        success: change_name_done});
}

function init_page()
{
    $('[title]').tooltip();
}

$(init_page);
