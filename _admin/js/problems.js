/*global $, TicketSystem*/
/*exported exportCSV*/
var ticketSystem = new TicketSystem('../api/v1');

function initTable() {
  $(this).dataTable({
    'ajax': ticketSystem.getProblemRequestDataTableUri($(this).attr('id')),
    'columns': [
      {'data': 'request_id'},
      {'data': 'private_status'},
      {'data': 'total_due'},
      {'data': 'total_received'},
      {'data': 'comments'},
      {'data': 'bucket'},
      {'data': 'crit_vol'}
    ]
  });
}

function expandTable() {
  $(this).DataTable().page.len(-1);
  $(this).DataTable().draw();
}

function beforePrint() {
  $('table').each(expandTable);
}

function afterPrint() {
}

function onPrintChange(mql) {
  if(mql.matches) {
    beforePrint();
  } else {
    afterPrint();
  }
}

function exportCSV(view) {
  var uri = ticketSystem.getProblemRequestDataTableUri(view);
  uri = uri.replace('fmt=data-table', '$format=csv');
  window.location = uri; 
}

function initPage() {
  $('table').each(initTable);
  if(window.matchMedia !== undefined) {
    //WebKit implementation
    var mediaQueryList = window.matchMedia('print');
    mediaQueryList.addListener(onPrintChange);
  }
  //IE & Firefox implementation
  window.onbeforeprint = beforePrint;
  window.onafterprint = afterPrint;
}

$(initPage);
