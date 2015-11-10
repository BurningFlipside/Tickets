function upload_done(data)
{
    var json = eval('('+data+')');
    console.log(json);
    $('#success_count').html(json.success.length);
    $('#fail_count').html(json.fails.length);
    $('#successes').empty();
    $('#failures').empty();
    for(i = 0; i < json.success.length; i++)
    {
        $('#successes').append(json.success[i].token+' => '+json.success[i].name+'<br/>');
        for(j = 0; j < json.success[i].tickets.length; j++)
        {
            $('#successes').append('&hellip;'+json.success[i].tickets[j].first+' '+json.success[i].tickets[j].last+'<br/>');
        }
    }
    for(i = 0; i < json.fails.length; i++)
    {
        $('#failures').append(json.fails[i]+'<br/>');
    }
    $('#result_dialog').modal();
}

function sendFileToServer(formData)
{
    var jqXHR=$.ajax({
            url:  "users_upload.php",
            type: "POST",
            contentType: false,
            processData: false,
            cache: false,
            data: formData,
            success: upload_done}); 
}

function process_bulk()
{
    var jqXHR=$.ajax({
            url:  "users_upload.php?data="+$('#bulk_text').val(),
            type: "POST",
            contentType: false,
            processData: false,
            cache: false,
            success: upload_done});
}

function handleFileUpload(files, obj)
{
   for (var i = 0; i < files.length; i++) 
   {
        var fd = new FormData();
        fd.append('file', files[i]);
        sendFileToServer(fd);
   }
}

function drag_enter(event)
{
    event.stopPropagation();
    event.preventDefault();
    $(this).css('border', '2px solid #0B85A1');
}

function drag_over(event)
{
    event.stopPropagation();
    event.preventDefault();
}

function drop_in(event)
{
    $(this).css('border', '2px dotted #0B85A1');
    event.preventDefault();
    var files = event.originalEvent.dataTransfer.files;
    //We need to send dropped files to Server
    handleFileUpload(files, $('#filehandler'));
}

function doc_drag_enter(event)
{
    event.stopPropagation();
    event.preventDefault();
}

function doc_drag_over(event)
{
    event.stopPropagation();
    event.preventDefault();
    $('#filehandler').css('border', '2px dotted #0B85A1');
}

function doc_drop_in(event)
{
    event.stopPropagation();
    event.preventDefault();
}

function renderName(data, type, row)
{
    return row['givenName']+' '+row['sn'];
}

function init_page()
{
    $('#users').dataTable({
        "ajax": '../api/v1/globals/users?fmt=data-table',
        'columns': [
            {'render': renderName},
            {'data': 'mail'},
            {'data': 'uid'},
            {'data': 'admin'}
        ]
    });
    var drag = $('#filehandler');
    drag.on('dragenter', drag_enter);
    drag.on('dragover', drag_over);
    drag.on('drop', drop_in);
    $(document).on('dragenter', doc_drag_enter);
    $(document).on('dragover', doc_drag_over);
    $(document).on('drop', doc_drop_in);
}

$(init_page);
