function set_crit_done(data)
{
    console.log(data);
}

function save_one_critvol(index, element)
{
    var elem = $(element);
    var name = elem.attr('name');
    name = name.substring(name.lastIndexOf('_')+1);
    $.ajax({
        url: '/tickets/ajax/request.php',
        data: 'set_crit='+encodeURIComponent(name),
        type: 'post',
        dataType: 'json',
        success: set_crit_done});
}

function unsave_one_critvol(index, element)
{
    var elem = $(element);
    var name = elem.attr('name');
    name = name.substring(name.lastIndexOf('_')+1);
    $.ajax({
        url: '/tickets/ajax/request.php',
        data: 'unset_crit='+encodeURIComponent(name),
        type: 'post',
        dataType: 'json',
        success: set_crit_done});
}

function save_critvol(event)
{
    var inputs = $('[name^=crit_vol]:checked');
    inputs.each(save_one_critvol);
    inputs = $('[name^=crit_vol]:not(:checked)');
    inputs.each(unsave_one_critvol);
    $('.modal').modal('hide');
}

function search_done(data)
{
    if(data.requests === undefined)
    {
        alert("No requests found!");
    }
    else
    {
            var table = $('<table/>', {'class': 'table'});
            var thead = $('<thead/>');
            var row = $('<tr/>');
            var cell = $('<th/>');
            cell.html('Request ID');
            cell.appendTo(row);
            cell = $('<th/>');
            cell.html('Name');
            cell.appendTo(row);
            cell = $('<th/>');
            cell.html('Crit');
            cell.appendTo(row);
            row.appendTo(thead);
            thead.appendTo(table);
            thead = $('<tbody/>');
            for(i = 0; i < data.requests.length; i++)
            {
                row = $('<tr/>');
                cell = $('<td/>');
                cell.html(data.requests[i].request_id);
                cell.appendTo(row);
                cell = $('<td/>');
                cell.html(data.requests[i].givenName+' '+data.requests[i].sn);
                cell.appendTo(row);
                var checkbox = $('<input/>', {'type': 'checkbox', 'name': 'crit_vol_'+data.requests[i].request_id});
                if(data.requests[i].crit_vol)
                {
                    checkbox.attr('checked', 'true');
                }
                cell = $('<td/>');
                checkbox.appendTo(cell);
                cell.appendTo(row);
                row.appendTo(thead);
            }
            thead.appendTo(table);
            var modal = create_modal('Requests', table, [{'text': 'Save', 'method': save_critvol}]);
            modal.modal();
    }
}

function search(event)
{
    var type = $('#search_type').val();
    var value = $('#search').val();
    $.ajax({
        url: '/tickets/ajax/request.php',
        data: 'type='+encodeURIComponent(type)+'&value='+encodeURIComponent(value),
        type: 'get',
        dataType: 'json',
        success: search_done});
    event.preventDefault();
    return false;
}

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
            url:  "critvol_upload.php",
            type: "POST",
            contentType: false,
            processData: false,
            cache: false,
            data: formData,
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

function init_page()
{
    $('#search_btn').on('click', search);
    var drag = $('#filehandler');
    drag.on('dragenter', drag_enter);
    drag.on('dragover', drag_over);
    drag.on('drop', drop_in);
    $(document).on('dragenter', doc_drag_enter);
    $(document).on('dragover', doc_drag_over);
    $(document).on('drop', doc_drop_in);
}

$(init_page);
