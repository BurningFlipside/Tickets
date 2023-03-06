/*global $, TicketSystem*/
/*exported changeMenu, lookupRequestByValue, saveRequest*/
var ticketSystem = new TicketSystem('../api/v1');

function requestAjaxDone(data, err) {
  if(err !== null) {
    if(err.httpStatus === 401) {
      location.reload();
    }
    if(err.jsonResp !== undefined && err.jsonResp.message !== undefined) {
      alert(err.jsonResp.message);
    } else {
      console.log(err);
      alert('Unable to assign bucket to request!');
    }
    return;
  }
  $('#modal_title').html('Request #'+data.request_id);
  $('#given_name').val(data.givenName);
  $('#last_name').val(data.sn);
  $('#total_due').val('$'+data.total_due);
  $('#comments').val(data.comments);
  $('#status').val(data.private_status);
  if(data.total_received === 0 || data.total_received === '0') {
    $('#total_received').val('');
  } else {
    $('#total_received').val(data.total_received);
  }
  setTimeout(function() {
    $('#total_received').focus();
  }, 300);
  $('#bucket').val(data.bucket);
  $('#request_id_hidden').val(data.request_id);
  $('#save_btn').data('id', data.id);
}

function lookupRequest(control) {
  var id = $(control).val();
  lookupRequestById(id);
  $(control).val('');
}

function lookupRequestById(id) {
  $('#request_select').modal('hide');
  $('#modal').modal('show');
  ticketSystem.getRequestAndAssignBucket(requestAjaxDone, id);
}

function lookupAjaxDone(data, err) {
  if(err !== null) {
    if(err.jsonResp !== undefined && err.jsonResp.message !== undefined) {
      alert(err.jsonResp.message);
    } else {
      console.log(err);
      alert('Unable to lookup request!');
    }
    return;
  }
  if(data.length === 1) {
    $('#request_id').val(data[0].request_id);
    lookupRequest($('#request_id'));
  } else {
    $('#request_select').modal('show');
    var table = $('#request_table').DataTable();
    table.clear();
    for(let request of data) {
      var row = [
        '<a href="#" onclick="lookupRequestById(\''+request.request_id+'\');">'+request.request_id+'</a>',
        request.givenName+' '+request.sn,
        request.mail];
      table.row.add(row);
    }
    table.draw();
  }
}

function lookupRequestByValue(control) {
  var type  = $('#type').data('type');
  var value = $(control).val();
  if(type === '*') {
    ticketSystem.searchRequest(value, lookupAjaxDone);
  } else {
    ticketSystem.getRequests(lookupAjaxDone, 'contains('+type+','+value+') and year eq current');
  }
  $(control).val('');
}

function changeMenu(value, text) {
  $('#type').data('type', value);
  $('#type').html(text+'  <span class="caret"></span>');
}

function restoreFocus() {
  $('#request_id').focus();
}

function statusAjaxDone(jqXHR) {
  if(jqXHR.responseJSON === undefined) {
    alert('Unable to obtain status data!');
    return;
  }
  var data = jqXHR.responseJSON;
  for(let status of data) {
    $('#status').append('<option value="'+status.status_id+'">'+status.name+'</option>');
  }
}

function saveRequestDone(data, err) {
  if(err !== null) {
    if(err.jsonResp !== undefined && err.jsonResp.message !== undefined) {
      alert(err.jsonResp.message);
    } else {
      console.log(err);
      alert('Unable to save request!');
    }
    return;
  }
  $('#modal').modal('hide');
  $('#request_id').focus();
}

function saveRequest() {
  if($('#total_received').val() === '') {
    alert('Need Total Received!');
    return;
  }
  var obj = $('#req_form').serializeObject();
  if(obj.total_due !== undefined && obj.total_due[0] === '$') {
    obj.total_due = obj.total_due.substring(1); // eslint-disable-line camelcase
  }
  if(obj.total_received !== undefined && obj.total_received[0] === '$') {
    obj.total_received = obj.total_received.substring(1); // eslint-disable-line camelcase
  }
  obj.request_id = obj.id; // eslint-disable-line camelcase
  delete obj.id;
  obj.year = 'current';
  ticketSystem.updateRequest(obj, saveRequestDone);
}

function initPage() {
  $('#modal').modal({show: false});
  $('#modal').on('hidden.bs.modal', restoreFocus);
  $.ajax({
    url: '../api/v1/globals/statuses',
    dataType: 'json',
    complete: statusAjaxDone});
  $('#request_table').dataTable();
}

$(initPage);
