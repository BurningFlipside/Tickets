/* global $, add_notification, getParameterByName, NOTIFICATION_SUCCESS, NOTIFICATION_WARNING, TicketSystem,  */
/* exported copy_request, downloadTicket, editTicket, showLongId, transferTicket, viewTicket, saveTicket */
var ticketSystem = null;

var outOfWindow = false;
var testMode = false;
var ticketYear = false;
var basicButtonOptions = {'class': 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', 'data-html': true};

function tableDrawComplete() {
  $('#ticket_set').show();
  if ($('#ticketList').DataTable().data().length !== 0) {
    //Table contains nothing, just return
    return;
  }
  if($(window).width() < 768) {
    $('#ticketList th:nth-child(3)').hide();
    $('#ticketList td:nth-child(3)').hide();
    $('#ticketList th:nth-child(4)').hide();
    $('#ticketList td:nth-child(4)').hide();
  }
}

function getWordsDone(data) {
  $('#long_id_words').html(data.hash_words);
}

function showLongId(hash) {
  $('#long_id').html(hash);
  $('#long_id_words').html('');
  $.ajax({
    url: 'api/v1/tickets/'+hash+'?select=hash_words',
    type: 'get',
    dataType: 'json',
    success: getWordsDone});
  $('#ticket_view_modal').modal('hide');
  $('#ticket_id_modal').modal('show');
}

function findTicketInTableByHash(table, hash) {
  var json = table.DataTable().ajax.json();
  for(let ticket of json.data) {
    if(ticket.hash === hash) { // eslint-disable-line security/detect-possible-timing-attacks
      return ticket;
    }
  }
  return null;
}

function getTicketDataByHash(hash) {
  var ticket = findTicketInTableByHash($('#ticketList'), hash);
  if(ticket === null) {
    ticket = findTicketInTableByHash($('#discretionary'), hash);
  }
  return ticket;
}

function viewTicket(control) {
  var jq = $(control);
  var id = jq.attr('for');
  var ticket = getTicketDataByHash(id);
  if(ticket === null) {
    alert('Cannot find ticket');
    return;
  }
  $('[title]').tooltip('hide');
  $('#view_first_name').html(ticket.firstName);
  $('#view_last_name').html(ticket.lastName);
  $('#view_type').html(ticket.type);
  $('#view_short_code').html(ticket.hash.substring(0,7)).attr('onclick', 'showLongId(\''+ticket.hash+'\')');
  $('#ticket_view_modal').modal('show');
}

function saveTicketDone(data) {
  if(data.error !== undefined) {
    alert(data.error);
    return;
  }
  location.reload();
}

function saveTicket() {
  $.ajax({
    url: 'api/v1/tickets/'+$('#show_short_code').data('hash'),
    type: 'patch',
    data: '{"firstName":"'+$('#edit_first_name').val()+'","lastName":"'+$('#edit_last_name').val()+'"}',
    contentType: 'application/json',
    processData: false,
    dataType: 'json',
    success: saveTicketDone});
  $('#ticket_edit_modal').modal('hide');
}

function editTicket(control) {
  var jq = $(control);
  var id = jq.attr('for');
  var ticket = getTicketDataByHash(id);
  if(ticket === null) {
    alert('Cannot find ticket');
    return;
  }
  $('[title]').tooltip('hide');
  $('#edit_first_name').val(ticket.firstName);
  $('#edit_last_name').val(ticket.lastName);
  $('#show_short_code').val(ticket.hash.substring(0,8)).data('hash', id);
  $('#ticket_edit_modal').modal('show');
}

function downloadTicket(control) {
  var jq = $(control);
  var id = jq.attr('for');
  window.open('api/v1/tickets/'+id+'/pdf', '_blank');  // eslint-disable-line security/detect-non-literal-fs-filename
}

function transferTicket(control) {
  var jq = $(control);
  var id = jq.attr('for');
  var ticket = getTicketDataByHash(id);
  if(ticket === null) {
    alert('Cannot find ticket');
    return;
  }
  window.location.assign('transfer.php?id='+ticket.hash);
}

function shortHash(data) {
  return '<a href="#" onclick="showLongId(\''+data+'\')">'+data.substring(0,8)+'</a>';
}

function getOuterHTML(button) {
  if(button.prop('outerHTML') === undefined) {
    return new XMLSerializer().serializeToString(button[0]);
  }
  return button.prop('outerHTML');
}

function makeGlyphButton(options, glyphClass, onClick) {
  options.type = 'button';
  var button = $('<button/>', options);
  var glyph = $('<span/>', {'class': glyphClass});
  button.on('click', onClick);
  glyph.appendTo(button);
  return button;
}

function makeGlyphLink(options, glyphClass, ref) {
  var link = $('<a/>', options);
  var glyph = $('<span/>', {'class': glyphClass});
  if(ref !== undefined) {
    link.attr('href', ref);
  }
  glyph.appendTo(link);
  return getOuterHTML(link);
}

function createButtonOptions(title, onClick, forData) {
  var ret = JSON.parse(JSON.stringify(basicButtonOptions));
  ret.title   = title;
  if(forData !== undefined) {
    ret['for']  = forData;
  }
  if(onClick !== undefined) {
    ret.onclick = onClick;
  }
  return ret;
}

function createLinkOptions(title, forData, href, target) {
  var ret = basicButtonOptions;
  ret.title   = title;
  ret['for']  = forData;
  ret.href    = href;
  if(target !== undefined) {
    ret.target = target;
  }
  return ret;
}

function getViewButton(data) {
  var viewOptions = createButtonOptions('View Ticket Code', 'viewTicket(this)', data);
  return makeGlyphButton(viewOptions, 'fa fa-search');
}

function getEditButton(data) {
  var editOptions = createButtonOptions('Edit Ticket<br/>Use this option to keep the ticket<br/>on your account but<br/>change the legal name.', 'editTicket(this)', data);
  return makeGlyphButton(editOptions, 'fa fa-pencil-alt');
}

function getPDFButton(data) {
  var pdfOptions = createLinkOptions('Download PDF', data, 'api/v1/tickets/'+data+'/pdf', '_blank');
  return makeGlyphLink(pdfOptions, 'fa fa-download');
}

function getTransferButton(data) {
  var transferOptions = createButtonOptions('Transfer Ticket<br/>Use this option to send<br/>the ticket to someone else', 'transferTicket(this)', data);
  return makeGlyphButton(transferOptions, 'fa fa-envelope');
}

function makeActions(data) {
  var res = '';
  if($(window).width() < 768) {
    res += getOuterHTML(getViewButton(data));
  }
  res += getOuterHTML(getEditButton(data));
  res += getPDFButton(data);
  res += getOuterHTML(getTransferButton(data));
  return res;
}

function initTable() {
  // prevent duplicate initialization error
  if ($.fn.dataTable === undefined) {
    //datatable isn't loaded yet retry...
    setTimeout(initTable, 100);
    return;
  }
  if ($.fn.dataTable.isDataTable('#ticketList')) {
    $('#ticketList').DataTable();
  } else {
    $('#ticketList').dataTable({
      'ajax': 'api/v1/ticket?fmt=data-table',
      columns: [
        { 'data': 'firstName' },
        { 'data': 'lastName' },
        { 'data': 'type' },
        {
          'data': 'hash',
          'render': shortHash
        },
        {
          'data': 'hash',
          'render': makeActions,
          'class': 'action-buttons',
          'orderable': false
        }
      ],
      paging: false,
      info: false,
      searching: false
    });
  }
  $('#ticketList').on('draw.dt', tableDrawComplete);
  $('[data-toggle="tooltip"]').tooltip();
}

function addButtonsToRow(row, request) {
  var cell = $('<td/>', {style: 'white-space: nowrap;'});
  var editOptions = createButtonOptions('Edit Request');
  var mailOptions = createButtonOptions('Resend Request Email');
  var pdfOptions = createButtonOptions('Download Request PDF');
  var html = makeGlyphLink(editOptions, 'fa fa-pencil-alt', 'request.php?request_id='+request.request_id+'&year='+request.year);
  cell.append(html);

  html = makeGlyphButton(mailOptions, 'fa fa-envelope', request.sendEmail());
  cell.append(html);

  html = makeGlyphLink(pdfOptions, 'fa fa-download', request.getPdfUri());
  cell.append(html);
  cell.appendTo(row);
}

function toggleHiddenRequests() {
  var rows = $('tr.old_request');
  if(rows.is(':visible')) {
    rows.hide();
  } else {
    rows.show();
  }
}

function addOldRequestToTable(tbody, request) {
  var container = tbody.find('tr#old_requests');
  if(container.length === 0) {
    tbody.prepend('<tr id="old_requests" style="cursor: pointer;"><td colspan="5"><span class="fa fa-chevron-right"></span> Old Requests</td></tr>');
    container = tbody.find('tr#old_requests');
    container.on('click', toggleHiddenRequests);
  }
  var row = $('<tr class="old_request" style="display: none;">');
  row.append('<td/>');
  row.append('<td>'+request.year+'</td>');
  if(request.tickets === null) {
    row.append('<td>0</td>');
  } else {
    row.append('<td>'+request.tickets.length+'</td>');
  }
  row.append('<td>$'+request.total_due+'</td>');
  container.after(row);
}

function addRequestToTable(tbody, request, oldRequestOnly) {
  if(request.year !== ticketYear) {
    addOldRequestToTable(tbody, request);
    return;
  }
  oldRequestOnly.value = false;
  var row = $('<tr/>');
  row.append('<td>'+request.request_id+'</td>');
  row.append('<td>'+request.year+'</td>');
  if(request.tickets === null) {
    request.tickets = [];
  }
  row.append('<td>'+request.tickets.length+'</td>');
  if(!outOfWindow || testMode) {
    row.append('<td>$'+request.total_due+'</td>');
    addButtonsToRow(row, request);
  } else {
    var cell = $('<td/>');
    cell.attr('data-original-title', request.status.description);
    cell.attr('data-container', 'body');
    cell.attr('data-toggle', 'tooltip');
    cell.attr('data-placement', 'top');
    cell.html(request.status.name);
    cell.appendTo(row);
    row.append('<td></td>');
  }
  row.appendTo(tbody);
  $('[data-original-title]').tooltip();
}

function processRequests(requests) {
  var tbody = $('#requestList tbody');
  var oldRequestOnly = {};
  oldRequestOnly.value = true;
  for(let request of requests) {
    addRequestToTable(tbody, request, oldRequestOnly);
  }
  if(outOfWindow === false) {
    tbody.append('<tr><td></td><td colspan="4" style="text-align: center;"><a href="request.php"><span class="fa fa-plus-square"></span> Create a new request</a></td></tr>');
    $('#fallback').hide();
  } else {
    tbody.append('<tr><td colspan="5" style="text-align: center;"></td></tr>');
  }
  if($('[title]').length > 0) {
    $('[title]').tooltip();
  }
  if($(window).width() < 768) {
    $('#requestList th:nth-child(1)').hide();
    $('#requestList td:nth-child(1)').hide();
  }
}

function getRequestsDone(requests, err) {
  if(err !== null) {
    alert('Error obtaining request!');
    return;
  }
  if(requests === undefined || requests.length === 0) {
    if(outOfWindow) {
      $('#requestList').empty();
    } else {
      $('#request_set').empty();
      $('#request_set').append('You do not currently have a current or previous ticket request.<br/>');
      $('#request_set').append('<a href="/tickets/request.php">Create a Ticket Request</a>');
    }
  } else {
    processRequests(requests);
  }
  if($('#request_set').length > 0) {
    $('#request_set').show();
  }
}

function processOutOfWindow(now, start, end, myWindow) {
  if(now < start) {
    let message = 'The request window is not open yet. It starts on '+start.toDateString();
    if(myWindow.test_mode !== '1') {
      $('[href="request.php"]').hide();
    }
    add_notification($('#content'), message);
    outOfWindow = true;
    return;
  }
  if(now < start || now > end) {
    var message = 'The request window is currently closed. No new ticket requests are accepted at this time.';
    if(myWindow.test_mode === '1') {
      message += ' But test mode is enabled. Any requests created will be deleted before ticketing starts!';
      testMode = true;
    } else {
      $('[href="request.php"]').hide();
    }
    let div = add_notification($('#request_set'), message);
    div.after('<div class="w-100"></div>');
    div.before('<div class="col-sm-1"></div>');
    outOfWindow = true;
    if(!testMode) {
      $('#requestList th:nth-child(4)').html('Request Status');
    }
    $('#request').collapse('hide');
  }
}

function processMailInWindow(now, mailStart, end) {
  if(now > mailStart && now < end) {
    var days = Math.floor(end/(1000*60*60*24) - now/(1000*60*60*24));
    var message = 'The mail in window is currently open! ';
    if(days === 1) {
      message += 'You have 1 day left to mail your request!';
    } else if(days === 0) {
      message += 'Today is the last day to mail your request!';
    } else {
      message += 'You have '+days+' days left to mail your request!';
    }
    add_notification($('#request_set'), message, NOTIFICATION_WARNING);
  }
}

function getWindowDone(data, err) {
  if(ticketYear !== false) {
    return;
  }
  if(err !== null) {
    if(err.jsonResp !== undefined && err.jsonResp.code !== undefined) {
      switch(err.jsonResp.code) {
        case 5:
          //Not logged in... just silently fail the whole script right here
          return;
        default:
          alert(err.jsonResp.message);
          break;
      }
    }
    return;
  }
  var now = new Date(Date.now());
  if(data.current < now) {
    now = data.current;
  }
  ticketYear = data.year;
  processOutOfWindow(now, data.request_start_date, data.request_stop_date, data);
  processMailInWindow(now, data.mail_start_date, data.request_stop_date);
  ticketSystem.getRequests(getRequestsDone);
  initTable();
}

function collapseCard(e) {
  var x = $(e.target).siblings();
  x.find('.fa-chevron-up').removeClass('fa-chevron-up').addClass('fa-chevron-down');
}

function expandCard(e) {
  var x = $(e.target).siblings();
  x.find('.fa-chevron-down').removeClass('fa-chevron-down').addClass('fa-chevron-up');
}

function initIndex() {
  if(window.TicketSystem === undefined || window.getParameterByName === undefined) {
    setTimeout(initIndex, 100);
    return;
  }
  ticketSystem = new TicketSystem('api/v1');
  ticketSystem.getWindow(getWindowDone);
  $('.card .collapse').on('hidden.bs.collapse', collapseCard);
  $('.card .collapse').on('shown.bs.collapse', expandCard);
  if(getParameterByName('show_transfer_info') === '1') {
    var body = $('#content');
    add_notification(body, 'You have successfully sent an email with the ticket information. The ticket will be fully transfered when the receipient logs in and claims the ticket', NOTIFICATION_SUCCESS);
  }
}

$(initIndex);
