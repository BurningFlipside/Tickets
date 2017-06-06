var ticketSystem = new TicketSystem('../api/v1');

function setCritDone(data, err) {
    if(err !== null) {
        if(err.jsonResp !== undefined && err.jsonResp.message !== undefined) {
            alert(err.jsonResp.message);
        }
        else {
            console.log(err);
            alert('Unable to save request!');
        }
        return;
    }
    console.log(data);
}

function patchRequestCritVol(request_id, value) {
    var obj = {};
    obj.request_id = request_id;
    obj.year = 'current';
    obj.crit_vol = value;
    ticketSystem.updateRequest(obj, setCritDone); 
}

function save_one_critvol(index, element)
{
    var elem = $(element);
    var name = elem.attr('name');
    name = name.substring(name.lastIndexOf('_')+1);
    patchRequestCritVol(name, true);
}

function unsave_one_critvol(index, element)
{
    var elem = $(element);
    var name = elem.attr('name');
    name = name.substring(name.lastIndexOf('_')+1);
    patchRequestCritVol(name, false);
}

function save_critvol(event)
{
    var inputs = $('[name^=crit_vol]:checked');
    inputs.each(save_one_critvol);
    inputs = $('[name^=crit_vol]:not(:checked)');
    inputs.each(unsave_one_critvol);
    $('.modal').modal('hide');
}

function searchDone(data, err) {
    if(err !== null) {
        if(err.jsonResp !== undefined && err.jsonResp.message !== undefined) {
            alert(err.jsonResp.message);
        }
        else {
            console.log(err);
            alert('No requests found!');
        }
        return;
    }
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
    for(i = 0; i < data.length; i++) {
        row = $('<tr/>');
        cell = $('<td/>');
        cell.html(data[i].request_id);
        cell.appendTo(row);
        cell = $('<td/>');
        cell.html(data[i].givenName+' '+data[i].sn);
        cell.appendTo(row);
        var checkbox = $('<input/>', {'type': 'checkbox', 'name': 'crit_vol_'+data[i].request_id});
        if(data[i].crit_vol) {
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

function search(event)
{
    var type = $('#search_type').val();
    var value = $('#search').val();
    if(type === '*')
    {
        ticketSystem.searchRequest(value, searchDone);
    }
    else
    {
        ticketSystem.getRequests(searchDone, 'contains('+type+','+value+') and year eq current');
    }
    event.preventDefault();
    return false;
}

function uploadDone(json, err)
{
    if(err !== null) {
        if(err.jsonResp !== undefined && err.jsonResp.message !== undefined) {
            alert(err.jsonResp.message);
        }
        else {
            console.log(err);
            alert('Unable to import bulk crivols!');
        }
        return;
    }
    console.log(json);
    $('#success_count').html(json.processed.length);
    $('#fail_count').html(json.unprocessed.length);
    $('#successes').empty();
    $('#failures').empty();
    for(i = 0; i < json.processed.length; i++)
    {
        $('#successes').append(JSON.stringify(json.processed[i])+'<br/>');
    }
    for(i = 0; i < json.unprocessed.length; i++)
    {
        $('#failures').append(JSON.stringify(json.unprocessed[i])+'<br/>');
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
            complete: uploadDone}); 
}

function allCritvolsObtained(jqXHR)
{
    if(jqXHR.status !== 200 || jqXHR.responseText === undefined)
    {
        alert('Unable to obtain leads list!');
        return;
    }
    ticketSystem.bulkSetCritvols(jqXHR.responseText, uploadDone);
}

function auto_critvol()
{
    $.ajax({
        url: window.profilesUrl+'/api/v1/leads?$select=mail',
        type: 'get',
        dataType: 'json',
        xhrFields: {withCredentials: true},
        complete: allCritvolsObtained});
}

function fileRead(e)
{
    ticketSystem.bulkSetCritvols(e.target.result, uploadDone);
}

function handleFileUpload(files, obj)
{
   for (var i = 0; i < files.length; i++) 
   {
       var reader = new FileReader();
       reader.onload = fileRead;
       reader.readAsText(files[i]);
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
