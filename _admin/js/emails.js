function save_done(data)
{
    if(data.success !== undefined)
    {
        location.reload();
    }
    else
    {
        alert(data.error);
    }
}

function save()
{
    $.ajax({
        url: '/tickets/_admin/ajax/emails.php',
        type: 'post',
        data: 'type='+$('#ticket_text_name').val()+'&save='+encodeURIComponent($('#pdf-source').val()),
        dataType: 'json',
        success: save_done});
}

function ticket_text_changed()
{
    window.location = '/tickets/_admin/emails.php?type='+$('#ticket_text_name').val();
}

function page_init()
{
    $('#pdf-source').ckeditor({
        'allowedContent': true
    });
    var type = getParameterByName('type');
    if(type !== null)
    {
        $('#ticket_text_name').val(type);
    }
}

$(page_init);
