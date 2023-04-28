/*global $, create_modal, TicketSystem*/
/*exported autoCritvol*/
var ticketSystem = new TicketSystem('../api/v1');

function setCritDone(data, err) {
  if(err !== null) {
    if(err.jsonResp !== undefined && err.jsonResp.message !== undefined) {
      alert(err.jsonResp.message);
    } else {
      console.log(err);
      alert('Unable to save request!');
    }
    return;
  }
  console.log(data);
}

function patchRequestCritVol(requestId, value) {
  var obj = {};
  obj.request_id = requestId; // eslint-disable-line camelcase
  obj.year = 'current';
  obj.crit_vol = value; // eslint-disable-line camelcase
  ticketSystem.updateRequest(obj, setCritDone); 
}

function saveOneCritvol(index, element) {
  var elem = $(element);
  var name = elem.attr('name');
  name = name.substring(name.lastIndexOf('_')+1);
  patchRequestCritVol(name, true);
}

function unsaveOneCritvol(index, element) {
  var elem = $(element);
  var name = elem.attr('name');
  name = name.substring(name.lastIndexOf('_')+1);
  patchRequestCritVol(name, false);
}

function saveCritvol() {
  var inputs = $('[name^=crit_vol]:checked');
  inputs.each(saveOneCritvol);
  inputs = $('[name^=crit_vol]:not(:checked)');
  inputs.each(unsaveOneCritvol);
  $('.modal').modal('hide');
}

function searchDone(data, err) {
  if(err !== null) {
    if(err.jsonResp !== undefined && err.jsonResp.message !== undefined) {
      alert(err.jsonResp.message);
    } else {
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
  for(let request of data) {
    row = $('<tr/>');
    cell = $('<td/>');
    cell.html(request.request_id);
    cell.appendTo(row);
    cell = $('<td/>');
    cell.html(request.givenName+' '+request.sn);
    cell.appendTo(row);
    var checkbox = $('<input/>', {'type': 'checkbox', 'name': 'crit_vol_'+request.request_id});
    if(request.crit_vol) {
      checkbox.attr('checked', 'true');
    }
    cell = $('<td/>');
    checkbox.appendTo(cell);
    cell.appendTo(row);
    row.appendTo(thead);
  }
  thead.appendTo(table);
  var modal = create_modal('Requests', table, [{'text': 'Save', 'method': saveCritvol}]);
  modal.modal();
}

function search(event) {
  var type = $('#search_type').val();
  var value = $('#search').val();
  if(type === '*') {
    ticketSystem.searchRequest(value, searchDone);
  } else {
    ticketSystem.getRequests(searchDone, 'contains('+type+','+value+') and year eq current');
  }
  event.preventDefault();
  return false;
}

function uploadDone(json, err) {
  if(err !== null) {
    if(err.jsonResp !== undefined && err.jsonResp.message !== undefined) {
      alert(err.jsonResp.message);
    } else {
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
  for(let processed of json.processed) {
    $('#successes').append(JSON.stringify(processed)+'<br/>');
  }
  for(let unprocessed of json.unprocessed) {
    $('#failures').append(JSON.stringify(unprocessed)+'<br/>');
  }
  $('#result_dialog').modal();
}

function allCritvolsObtained(jqXHR) {
  if(jqXHR.status !== 200 || jqXHR.responseText === undefined) {
    alert('Unable to obtain leads list!');
    return;
  }
  ticketSystem.bulkSetCritvols(jqXHR.responseText, uploadDone);
}

function autoCritvol() {
  let uri = 'https://profiles.burningflipside.com/api/v1/leads?$select=mail';
  if(window.profilesUrl !== undefined) {
    uri =  window.profilesUrl+'/api/v1/leads?$select=mail';
  }
  $.ajax({
    url: uri,
    type: 'get',
    dataType: 'json',
    xhrFields: {withCredentials: true},
    complete: allCritvolsObtained});
}

function fileRead(e) {
  ticketSystem.bulkSetCritvols(e.target.result, uploadDone);
}

function handleFileUpload(files) {
  for(let file of files) {
    var reader = new FileReader();
    reader.onload = fileRead;
    reader.readAsText(file);
  }
}

function dragEnter(event) {
  event.stopPropagation();
  event.preventDefault();
  $(this).css('border', '2px solid #0B85A1');
}

function dragOver(event) {
  event.stopPropagation();
  event.preventDefault();
}

function dropIn(event) {
  $(this).css('border', '2px dotted #0B85A1');
  event.preventDefault();
  var files = event.originalEvent.dataTransfer.files;
  //We need to send dropped files to Server
  handleFileUpload(files, $('#filehandler'));
}

function docDragEnter(event) {
  event.stopPropagation();
  event.preventDefault();
}

function docDragOver(event) {
  event.stopPropagation();
  event.preventDefault();
  $('#filehandler').css('border', '2px dotted #0B85A1');
}

function docDropIn(event) {
  event.stopPropagation();
  event.preventDefault();
}

function initPage() {
  $('#search_btn').on('click', search);
  var drag = $('#filehandler');
  drag.on('dragenter', dragEnter);
  drag.on('dragover', dragOver);
  drag.on('drop', dropIn);
  $(document).on('dragenter', docDragEnter);
  $(document).on('dragover', docDragOver);
  $(document).on('drop', docDropIn);
}

$(initPage);
