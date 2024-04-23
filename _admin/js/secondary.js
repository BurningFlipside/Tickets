/*global $, add_notification*/
/*exported getCSV, getPDF, saveRequest*/
function changeYear(control) {
  var data = 'filter=year eq '+$(control).val()+'&fmt=data-table';
  var table = $('#requests').DataTable();
  table.ajax.url('../api/v1/secondary/requests?'+data).load(gotAllRequests);
}

function gotAllRequests(data) {
  let requests = data.data;
  let totalTickets = 0;
  for(let request of requests) {
    let tickets = JSON.parse(request.valid_tickets);
    totalTickets += tickets.length;
  }
  add_notification($('#requests_wrapper'), 'There are currently requests for '+totalTickets+' tickets.');
}

function ticketRequestDone(data) {
  if(data.error !== undefined) {
    alert(data.error);
    console.log(data);
  } else {
    changeYear($('#year'));
  }
}

function saveRequestDone() {
  $('#modal').modal('hide');
  $.ajax({
    url: '../api/v1/secondary/requests/'+$('#request_id').val()+'/current/Actions/Ticket',
    processData: false,
    dataType: 'json',
    type: 'post',
    success: ticketRequestDone});
}

function saveRequest() {
  var obj = $('#request_edit_form').serializeObject();
  obj.total_due = obj.total_due.substring(1); // eslint-disable-line camelcase
  $.ajax({
    url: '../api/v1/secondary/requests/'+$('#request_id').val()+'/current',
    contentType: 'application/json',
    data: JSON.stringify(obj),
    processData: false,
    dataType: 'json',
    type: 'patch',
    success: saveRequestDone});
}

function getPDF() {
  var year = $('#year').val();
  window.location = '../api/v1/secondary/'+$('#request_id').val()+'/'+year+'/pdf';
}

function getCSV() {
  var year = $('#year').val();
  window.location = '../api/v1/secondary/requests?$format=csv&$filter=year eq '+year;
}

function rowClicked() {
  var tr = $(this).closest('tr');
  var row = $('#requests').DataTable().row(tr);
  var data = row.data();
  $('#ticketButton').prop('disabled', true);
  $('#modal').modal();
  $('#modal_title').html('Request #'+data.request_id);
  $('#request_id').val(data.request_id);
  $('#givenName').val(data.givenName);
  $('#sn').val(data.sn);
  $('#mail').val(data.mail);
  $('#c').val(data.c);
  $('#street').val(data.street);
  $('#zip').val(data.zip);
  $('#l').val(data.l);
  $('#st').val(data.st);
  $('#ticket_table tbody').empty();
  if(typeof(data.valid_tickets) === 'string') {
    data.valid_tickets = JSON.parse(data.valid_tickets); // eslint-disable-line camelcase
  }
  for(let ticket of data.valid_tickets) {
    let newRow = $('<tr/>');
    var type = ticket.substring(0, 1);
    var id = ticket;
    $('<td/>').html('<input type="text" id="ticket_first_'+id+'" name="ticket_first_'+id+'" class="form-control" value="'+data['ticket_first_'+id]+'"/>').appendTo(newRow);
    $('<td/>').html('<input type="text" id="ticket_last_'+id+'" name="ticket_last_'+id+'" class="form-control" value="'+data['ticket_last_'+id]+'"/>').appendTo(newRow);
    $('<td/>').html(type).appendTo(newRow);
    newRow.appendTo($('#ticket_table tbody'));
  }
  /*
  let i = 0;
  for(let ticket of data.tickets) {
    let newRow = $('<tr/>');
    $('<td/>').html('<input type="text" id="ticket_first_'+i+'" name="ticket_first_'+i+'" class="form-control" value="'+ticket.first+'"/>').appendTo(newRow);
    $('<td/>').html('<input type="text" id="ticket_last_'+i+'" name="ticket_last_'+i+'" class="form-control" value="'+ticket.last+'"/>').appendTo(newRow);
    $('<td/>').html('<input type="text" id="ticket_type_'+i+'" name="ticket_type_'+i+'" class="form-control" value="'+ticket.type+'"/>').appendTo(newRow);
    newRow.appendTo($('#ticket_table tbody'));
    i++;
  }*/
  $('#total_due').val('$'+data.total_due);
  $('#total_received').val(data.total_received);
  if(data.total_due === data.total_received) {
    $('#ticketButton').prop('disabled', false);
  }
}

function totalChanged() {
  var due = $('#total_due').val().substring(1);
  var received = $('#total_received').val();
  if(due === received || (received[0] === '$' && due === received.substring(1))) {
    $('#ticketButton').prop('disabled', false);
  }
}

function gotTicketYears(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Unable to obtain valid ticket years!');
    console.log(jqXHR);
    return;
  }
  jqXHR.responseJSON.sort().reverse();
  for(let year of jqXHR.responseJSON) {
    $('#year').append($('<option/>').attr('value', year).text(year));
  }
  changeYear($('#year'));
}

function initPage() {
  $.ajax({
    url: '../api/v1/globals/years',
    type: 'get',
    dataType: 'json',
    complete: gotTicketYears});
  $('#requests').dataTable({
    'columns': [ 
      {'data': 'request_id'},
      {'data': 'mail'},
      {'data': 'givenName'},
      {'data': 'sn'},
      {'data': 'total_due'}
    ]
  });
  $('#requests tbody').on('click', 'td', rowClicked);
  $('#total_received').on('change', totalChanged);
}

$(initPage);
