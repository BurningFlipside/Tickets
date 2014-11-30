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
        success: change_name_done});
}

function init_page()
{
    $('[title]').tooltip();
}

$(init_page);
