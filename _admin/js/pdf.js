function gen_preview_done(jqXHR)
{
    if(jqXHR.status !== 200)
    {
        alert('Unable to generate a preview!');
        console.log(jqXHR);
        return;
    }
    var pdfWin = window.open("data:application/pdf;base64, "+jqXHR.responseText);
    if(!pdfWin)
    {
        alert('Popup was blocked!');
    }
}

function saveDone(jqXHR)
{
    if(jqXHR.status === 200)
    {
        location.reload();
    }
    else
    {
        alert('Unable to save data!');
        console.log(jqXHR);
    }
}

function gen_preview()
{
    $.ajax({
        url: '../api/v1/globals/Actions/generatePreview/Tickets/Flipside/RequestPDF',
        type: 'post',
        data: $('#pdf-source').val(),
        processData: false,
        dataType: 'json',
        complete: gen_preview_done});
}

function save()
{
    $.ajax({
        url: '../api/v1/globals/long_text/pdf_source',
        type: 'PATCH',
        data: $('#pdf-source').val(),
        processData: false,
        complete: saveDone});
}

function gotPDFSource(jqXHR)
{
    if(jqXHR.status !== 200)
    {
         alert('Unable to obtain PDF source!');
    }
    $('#pdf-source').val(jqXHR.responseText);
}

function page_init()
{
    $.ajax({
        url: '../api/v1/globals/long_text/pdf_source',
        type: 'get',
        complete: gotPDFSource});
    $('#pdf-source').ckeditor({
        'allowedContent': true
    });
}

$(page_init);
