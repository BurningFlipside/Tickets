/*global $, TicketRequest, TicketSystem*/
/*exported changeStatusFilter, editRequest, getCSV, getPDF, saveRequest*/
var ticketSystem = new TicketSystem('../api/v1');

function requeryTable() {
  var year = $('#year').val();
  var status = $('#statusFilter').val();
  var filter = '';
  if(year !== '*') {
    filter = 'year eq '+year;
  } else {
    filter = 'year ne 999999';
  }
  if(status !== '*') {
    filter+=' and private_status eq '+status;
  }
  var table = $('#requests').DataTable();
  table.ajax.url(ticketSystem.getRequestDataTableUri(filter)).load();
}

function changeYear() {
  requeryTable();
}

function changeStatusFilter() {
  requeryTable();
}

function drawDone() {
  $('td.details-control').html('<span class="fa fa-plus"></span>');
}

function saveRequestDone(data, err) {
  if(err !== null) {
    if(err.jsonResp !== undefined && err.jsonResp.message !== undefined) {
      alert(err.jsonResp.message);
    } else {
      console.log(err);
      alert('Unable to update request!');
    }
    return;
  }
  $('#modal').modal('hide');
  changeYear($('#year'));
}

function saveRequest() {
  var obj = {};
  var a = $('#request_edit_form').serializeArray();
  for(var i = 0; i < a.length; i++) {
    var name = a[i].name; // eslint-disable-line security/detect-object-injection
    var split = name.split('_');
    if(split[0] === 'ticket') {
      var childName = split[1];
      if(obj['tickets'] === undefined) {
        obj['tickets'] = [];
      }
      if(obj['tickets'].length === 0 || obj['tickets'][obj['tickets'].length-1][childName] !== undefined) { // eslint-disable-line security/detect-object-injection
        obj['tickets'][obj['tickets'].length] = {}; // eslint-disable-line security/detect-object-injection
      }
      obj['tickets'][obj['tickets'].length-1][childName] = a[i].value; // eslint-disable-line security/detect-object-injection
    } else if(split[0] === 'donation') {
      if(obj['donations'] === undefined) {
        obj['donations'] = {};
      }
      if(obj['donations'][split[2]] === undefined) {
        obj['donations'][split[2]] = {};
      }
      obj['donations'][split[2]][split[1]] = a[i].value; // eslint-disable-line security/detect-object-injection
    } else {
      if(a[i].value === 'on') { // eslint-disable-line security/detect-object-injection
        a[i].value = 1; // eslint-disable-line security/detect-object-injection
      }
      if(name === 'critvol') {
        name = 'crit_vol';
      }
      obj[name] = a[i].value; // eslint-disable-line security/detect-object-injection
    }
  }
  obj.minor_confirm = true; // eslint-disable-line camelcase
  ticketSystem.updateRequest(obj, saveRequestDone); 
}

function editRequest() {
  window.location = '../request.php?request_id='+$('#request_id').val();
}

function getPDF() {
  var request = $('#modal').data('request');
  window.location = request.getPdfUri();
}

function getCSV() {
  var uri = $('#requests').DataTable().ajax.url();
  uri = uri.replace('fmt=data-table', '$format=csv2');
  window.location = uri;
}

function gotAsyncRequest(data) {
  this.row.data(data);
  $('#modal').modal('hide');
  let myBind = rowClicked.bind(this.row.node());
  myBind(); 
}

function rowClicked() {
  var tr = $(this).closest('tr');
  var row = $('#requests').DataTable().row(tr);
  var data = new TicketRequest(row.data(), ticketSystem);
  $('#modal').modal();
  $('#modal_title').html('Request #'+data.request_id);
  $('#request_id').val(data.request_id);
  $('#givenName').val(data.givenName);
  $('#sn').val(data.sn);
  $('#mail').val(data.mail);
  if(data.c === undefined) {
    let myBind = gotAsyncRequest.bind({row: row});
    ticketSystem.getRequest(myBind, data.request_id, data.year);
    return;
  }
  $('#c').val(data.c);
  $('#mobile').val(data.mobile);
  $('#street').val(data.street);
  $('#zip').val(data.zip);
  $('#l').val(data.l);
  $('#st').val(data.st);
  $('#ticket_table tbody').empty();
  for(let i = 0; i < data.tickets.length; i++) {
    let newRow = $('<tr/>');
    let ticket = data.tickets[i]; // eslint-disable-line security/detect-object-injection
    $('<td/>').html('<input type="text" id="ticket_first_'+i+'" name="ticket_first_'+i+'" class="form-control" value="'+ticket.first+'"/>').appendTo(newRow);
    $('<td/>').html('<input type="text" id="ticket_last_'+i+'" name="ticket_last_'+i+'" class="form-control" value="'+ticket.last+'"/>').appendTo(newRow);
    $('<td/>').html('<input type="text" id="ticket_type_'+i+'" name="ticket_type_'+i+'" class="form-control" value="'+ticket.type+'"/>').appendTo(newRow);
    newRow.appendTo($('#ticket_table tbody'));
  }
  $('#donation_table tbody').empty();
  if(data.donations !== null) {
    for(let donationName in data.donations) {
      let newRow = $('<tr/>');
      let amount = data.donations[`${donationName}`].amount;
      $('<td/>').html(donationName).appendTo(newRow);
      $('<td/>').html('<input type="text" id="donation_amount_'+donationName+'" name="donation_amount_'+donationName+'" class="form-control" value="'+amount+'"/>').appendTo(newRow);
      newRow.appendTo($('#donation_table tbody'));
    }
  }
  $('#total_due').val('$'+data.total_due);
  $('#status').val(data.private_status);
  $('#total_received').val(data.total_received);
  $('#comments').val(data.comments);
  $('#bucket').val(data.bucket);
  if(data.envelopeArt) {
    $('#envelopeArt').prop('checked', true);
  } else {
    $('#envelopeArt').prop('checked', false);
  }

  if(data.crit_vol) {
    $('#critvol').prop('checked', true);
  } else {
    $('#critvol').prop('checked', false);
  }
  if(data.protected) {
    $('#protected').prop('checked', true);
  } else {
    $('#protected').prop('checked', false);
  }
  $('#modal').data('request', data);
}

function statusAjaxDone(jqXHR) {
  if(jqXHR.status !== 200) {
    return;
  }
  for(let status of jqXHR.responseJSON) {
    $('#status').append('<option value="'+status.status_id+'">'+status.name+'</option>');
    $('#statusFilter').append('<option value="'+status.status_id+'">'+status.name+'</option>');
  }
}

function gotTicketYears(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Unable to obtain valid ticket years!');
    console.log(jqXHR);
    return;
  }
  jqXHR.responseJSON.sort().reverse();
  for(var i = 0; i < jqXHR.responseJSON.length; i++) {
    let value = jqXHR.responseJSON[i]; // eslint-disable-line security/detect-object-injection
    if(i === 0) {
      $('#year').append($('<option/>').attr('value', value).text(value).attr('selected', true));
    } else {
      $('#year').append($('<option/>').attr('value', value).text(value));
    }
  }
  changeYear($('#year'));
}

function initPage() {
  $.ajax({
    url: '../api/v1/globals/years',
    type: 'get',
    dataType: 'json',
    complete: gotTicketYears});
  $('#requests').on('draw.dt', drawDone);
  $('#requests').dataTable({
    'columns': [ 
      {'data': 'request_id'},
      {'data': 'givenName'},
      {'data': 'sn'},
      {'data': 'mail'},
      {'data': 'total_due'}
    ]
  });
  $('#requests tbody').on('click', 'td:not(.details-control)', rowClicked);
  $.ajax({
    url: '../api/v1/globals/statuses',
    dataType: 'json',
    complete: statusAjaxDone});
}

$(initPage);
