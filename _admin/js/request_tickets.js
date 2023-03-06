/*global $, TicketSystem*/
var ticketSystem = new TicketSystem('../api/v1');

function changeYear(control) {
  var table = $('#tickets').DataTable();
  table.ajax.url(ticketSystem.getRequestedTicketsDataTableUri('year eq '+$(control).val(), 'request_id,first,last,type')).load();
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
  $('#tickets').dataTable({
    columns: [
      {'data': 'request_id'},
      {'data': 'first'},
      {'data': 'last'},
      {'data': 'type'}
    ]
  });
}

$(initPage);
