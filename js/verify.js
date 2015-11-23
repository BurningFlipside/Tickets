function verifyCodeDone(jqXHR)
{
    if(jqXHR.status !== 200)
    {
        $('#verified').html('?');
        $('#verified').css('background-color', 'gray');
        $('#verified').css('color', 'black');
        $('#verified').attr('title', '');
        return;
    }
    var data = jqXHR.responseJSON;
    if(data.verified)
    {
            $('#verified').html('<span class="glyphicon glyphicon-ok"></span>');
            $('#verified').css('background-color', 'GreenYellow');
            $('#verified').css('color', 'green');
            $('#verified').attr('title', 'Ticket Code is valid!');
    }
    else
    {
            $('#verified').html('<span class="glyphicon glyphicon-remove"></span>');
            $('#verified').css('background-color', 'red');
            $('#verified').css('color', 'DarkRed');
            $('#verified').attr('title', 'Ticket Code is not valid.');
    }
    $('#verified').tooltip({'placement': 'bottom'});
    console.log(data);
}

function verify_code()
{
   var code = $('#short_code').val();
   if(code.length < 8 || code.length > 10) return;
   $.ajax({
        url: 'api/v1/tickets/Actions/VerifyShortCode/'+encodeURIComponent(code),
        type: 'post',
        dataType: 'json',
        complete: verifyCodeDone});
}

function init_page()
{
    var id = getParameterByName('id');
    if(id !== null)
    {
        $('#short_code').val(id);
        verify_code();
    }
}

$(init_page);
