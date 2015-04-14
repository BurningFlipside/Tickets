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
    $.ajax({
        url: '/tickets/ajax/tickets.php',
        type: 'post',
        data: 'hash='+$('#hash').val()+'&first='+$('#firstName').val()+'&last='+$('#lastName').val(),
        dataType: 'json',
        success: change_name_done});
}

function transfer()
{
    $.ajax({
        url: '/tickets/ajax/tickets.php',
        type: 'post',
        data: 'transfer=1&hash='+$('#hash').val()+'&email='+encodeURIComponent($('#email').val()),
        dataType: 'json',
        success: transfer_done});
}

function claim_ticket()
{
    $.ajax({
        url: '/tickets/ajax/tickets.php',
        type: 'post',
        data: 'claim=1&hash='+$('#hash').val()+'&first='+$('#firstName').val()+'&last='+$('#lastName').val(),
        dataType: 'json',
        success: change_name_done});
}

function init_page()
{
    $('[title]').tooltip();
}

$(init_page);
