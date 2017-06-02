function change_name_done(jqXHR)
{
    var data = jqXHR.responseJSON;
    if(data !== undefined && data.error !== undefined)
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

function transfer()
{
    var obj = {};
    obj.email = $('#email').val();
    $.ajax({
        url: 'api/v1/tickets/'+encodeURIComponent($('#hash').val())+'/Actions/Ticket.Transfer',
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
        url: 'api/v1/tickets/'+encodeURIComponent($('#hash').val())+'/Actions/Ticket.Claim',
        type: 'post',
        data: JSON.stringify(obj),
        processData: false,
        dataType: 'json',
        complete: change_name_done});
}

function init_page()
{
    $('[title]').tooltip();
    if(browser_supports_input_type('email'))
    {
        $('#email').attr({type:"email"});
    }
}

$(init_page);
