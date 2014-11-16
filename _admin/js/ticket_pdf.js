function gen_preview_done(data)
{
    if(data.pdf != undefined)
    {
        var win = window.open(data.pdf, '_blank');
        if(win == undefined)
        {
            alert('Popups are blocked! Please enable popups.');
        }
    }
}

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

function gen_preview()
{
    $.ajax({
        url: '/tickets/_admin/ajax/ticket_pdf.php',
        type: 'post',
        data: 'preview='+encodeURIComponent($('#pdf-source').val()),
        dataType: 'json',
        success: gen_preview_done});
}

function save()
{
    $.ajax({
        url: '/tickets/_admin/ajax/ticket_pdf.php',
        type: 'post',
        data: 'save='+encodeURIComponent($('#pdf-source').val()),
        dataType: 'json',
        success: save_done});
}

function page_init()
{
    $('#pdf-source').ckeditor({
        'allowedContent': true
    });
}

$(page_init);
