/* global $, getParameterByName */
/* exported nextTicket, prevTicket, resendTicketEmail, saveTicket, spinHash */
var ticketData = null;
var ticketTypes = null;
var earlyEntry = null;

function renderShortHash(data) {
  var shortHash = data.substring(0,8);
  return '<a style="cursor: pointer;" onclick="viewTicket(\''+data+'\');">'+shortHash+'</a>';
}

function renderTicketType(data) {
  if(ticketTypes === null || ticketTypes[`${data}`] === undefined) {
    return data;
  }
  return ticketTypes[`${data}`];
}

function getTicketBySelected() {
  if(ticketData.selected === -1) {
    return ticketData.current;
  }
  return ticketData.history[ticketData.selected];
}

function showTicketFromData(data) {
  var readOnly = true;
  let ticket = null;
  if(data.selected === -1) {
    ticket = data.current;
    $('#right_arrow').hide();
    if(data.history !== undefined && data.history.length > 0) {
      $('#left_arrow').show();
    } else {
      $('#left_arrow').hide();
    }
    readOnly = false;
    $('#saveticket').removeAttr('disabled');
  } else {
    ticket = data.history[data.selected];
    if(data.selected === (data.history.length - 1)) {
      $('#left_arrow').hide();
    } else {
      $('#left_arrow').show();
    }
    $('#right_arrow').show();
    $('#saveticket').attr('disabled', 'true');
  }
  $('#hash').val(ticket.hash);
  $('#year').val(ticket.year);
  $('#firstName').val(ticket.firstName);
  $('#lastName').val(ticket.lastName);
  $('#email').val(ticket.email);
  $('#request_id').val(ticket.request_id);
  $('#type').val(ticket.type);
  $('#guardian_first').val(ticket.guardian_first);
  $('#guardian_last').val(ticket.guardian_last);
  $('#earlyEntryWindow').val(ticket.earlyEntryWindow);
  if(ticket.sold === 1 || ticket.sold === '1') {
    $('#sold').prop('checked', true);
  } else {
    $('#sold').prop('checked', false);
  }
  if(ticket.used === 1 || ticket.used === '1') {
    $('#used').prop('checked', true);
  } else {
    $('#used').prop('checked', false);
  }
  if(ticket.void === 1 || ticket.void === '1') {
    $('#void').prop('checked', true);
  } else {
    $('#void').prop('checked', false);
  }
  $('#comments').val(ticket.comments);
  if(readOnly) {
    $('#firstName').prop('disabled', true);
    $('#lastName').prop('disabled', true);
    $('#email').prop('disabled', true);
    $('#request_id').prop('disabled', true);
    $('#type').prop('disabled', true);
    $('#guardian_first').prop('disabled', true);
    $('#guardian_last').prop('disabled', true);
    $('#sold').prop('disabled', true);
    $('#used').prop('disabled', true);
    $('#void').prop('disabled', true);
    $('#comments').prop('disabled', true);
  } else {
    $('#firstName').prop('disabled', false);
    $('#lastName').prop('disabled', false);
    $('#email').prop('disabled', false);
    $('#request_id').prop('disabled', false);
    $('#type').prop('disabled', false);
    $('#guardian_first').prop('disabled', false);
    $('#guardian_last').prop('disabled', false);
    $('#sold').prop('disabled', false);
    $('#used').prop('disabled', false);
    $('#void').prop('disabled', false);
    $('#comments').prop('disabled', false);
  }
  $('#ticket_modal').modal('show');
}

function ticketDataDone(data) {
  if(data.selected === undefined) {
    alert('Unable to retrieve ticket history data');
    console.log(data);
    return;
  }
  ticketData = data;
  showTicketFromData(data);
}

function viewTicket(hash) {
  $.ajax({
    url: '../api/v1/tickets/'+hash+'?with_history=1',
    type: 'get',
    dataType: 'json',
    success: ticketDataDone}); 
}

function prevTicket() {
  ticketData.selected++;
  showTicketFromData(ticketData);
}

function nextTicket() {
  ticketData.selected--;
  showTicketFromData(ticketData);
}

function setIfValueDifferent(ticket, obj, inputName, fieldName) {
  if(fieldName === undefined) {
    fieldName = inputName;
  }
  var input = $('#'+inputName);
  if(input.attr('type') === 'checkbox') {
    if(input.is(':checked')) {
      if(ticket[`${fieldName}`] === 0 || ticket[`${fieldName}`] === '0') {
        obj[`${fieldName}`] = 1;
      }
    } else if(ticket[`${fieldName}`] === 1 || ticket[`${fieldName}`] === '1') {
      obj[`${fieldName}`] = 0;
    }
  } else {
    var val = $('#'+inputName).val();
    if(val !== ticket[`${fieldName}`]) {
      obj[`${fieldName}`] = val;
    }
  }
}

function saveTicketDone(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Unable to save ticket!');
    return;
  }
  $('#ticket_modal').modal('hide');
  yearChanged();
}

function noChangeDone(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Error!');
    return;
  }
  $('#ticket_modal').modal('hide');
}

function saveTicket() {
  var ticket = getTicketBySelected();
  var obj = {};
  setIfValueDifferent(ticket, obj, 'email');
  setIfValueDifferent(ticket, obj, 'firstName');
  setIfValueDifferent(ticket, obj, 'lastName');
  setIfValueDifferent(ticket, obj, 'request_id');
  setIfValueDifferent(ticket, obj, 'type');
  setIfValueDifferent(ticket, obj, 'guardian_first');
  setIfValueDifferent(ticket, obj, 'guardian_last');
  setIfValueDifferent(ticket, obj, 'sold');
  setIfValueDifferent(ticket, obj, 'used');
  setIfValueDifferent(ticket, obj, 'void');
  setIfValueDifferent(ticket, obj, 'earlyEntryWindow');
  setIfValueDifferent(ticket, obj, 'comments');
  if(Object.keys(obj).length > 0) {
    $.ajax({
      url: '../api/v1/tickets/'+ticket.hash,
      contentType: 'application/json',
      data: JSON.stringify(obj),
      type: 'patch',
      dataType: 'json',
      complete: saveTicketDone});
  } else {
    $('#ticket_modal').modal('hide');
  }
}

function resendTicketEmail() {
  var ticket = getTicketBySelected();
  $.ajax({
    url: '../api/v1/tickets/'+ticket.hash+'/Actions/Ticket.SendEmail',
    contentType: 'application/json',
    type: 'post',
    dataType: 'json',
    complete: noChangeDone}); 
}

function spinHash() {
  var ticket = getTicketBySelected();
  $.ajax({
    url: '../api/v1/tickets/'+ticket.hash+'/Actions/Ticket.SpinHash',
    contentType: 'application/json',
    type: 'post',
    dataType: 'json',
    complete: saveTicketDone});
}

function backendSearchDone(data) {
  var tickets = data;
  if(data.old_tickets !== undefined) {
    tickets = data.old_tickets;
  }
  viewTicket(tickets[0].hash);
}

function tableSearched() {
  var dtApi = $('#tickets').DataTable();
  if(dtApi.search() === '') {
    return;
  }
  if(dtApi.rows({'search':'applied'})[0].length === 0) {
    $.ajax({
      url: '../api/v1/tickets/search/'+dtApi.search(),
      type: 'get',
      dataType: 'json',
      success: backendSearchDone
    });
  }
}

function requeryTable() {
  var year = $('#ticket_year').val();
  var sold = $('#ticketSold').val();
  var assigned = $('#ticketAssigned').val();
  var used = $('#ticketUsed').val();
  var voidVal = $('#ticketVoid').val();
  var disc = $('#discretionaryUser').val();
  var ee = $('#earlyEntry').val();
  var pool = $('#ticketPool').val();
  var filter = 'year eq '+year;
  if(year === '*') {
    filter = 'year ne 999999';
  }
  if(sold !== '*') {
    filter+=' and sold eq '+sold;
  }
  if(assigned !== '*') {
    filter+=' and assigned eq '+assigned;
  }
  if(used !== '*') {
    filter+=' and used eq '+used;
  }
  if(disc !== '') {
    filter+=' and discretionaryOrig eq \''+disc+'\'';
  }
  if(voidVal !== '*') {
    filter+=' and void eq '+voidVal;
  }
  if(ee !== '*') {
    filter+=' and earlyEntryWindow eq '+ee;
  }
  if(pool !== '*') {
    filter+=' and pool_id eq '+pool;
  }
  $('#tickets').DataTable().ajax.url('../api/v1/tickets?filter='+filter+'&fmt=data-table').load();
}

function soldChanged() {
  requeryTable();
}

function assignedChanged() {
  requeryTable();
}

function usedChanged() {
  requeryTable();
}

function yearChanged() {
  requeryTable();
}

function discretionaryChanged() {
  requeryTable();
}

function gotTicketYears(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Unable to obtain valid ticket years!');
    console.log(jqXHR);
    return;
  }
  jqXHR.responseJSON.sort().reverse();
  let selected = false;
  for(let year of jqXHR.responseJSON) {
    var opt = $('<option/>').attr('value', year).text(year);
    if(!selected) {
      opt.attr('selected', true);
      selected = true;
    }
    $('#ticket_year').append(opt);
  }
  $('#ticket_year').on('change', yearChanged);
  var e = {};
  e.currentTarget = {};
  e.currentTarget.value = $('#ticket_year').val();
  yearChanged(e);
}

function gotTicketTypes(jqXHR) {
  if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined) {
    console.log(jqXHR);
    return;
  }
  var data = jqXHR.responseJSON;
  var options = '';
  ticketTypes = {};
  for(let type of data) {
    options+='<option value="'+type.typeCode+'">'+type.description+'</option>';
    ticketTypes[type.typeCode] = type.description;
  }
  $('#type').replaceWith('<select id="type" name="type" class="form-control">'+options+'</select>');
}

function gotEarlyEntry(jqXHR) {
  if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined) {
    console.log(jqXHR);
    return;
  }
  var data = jqXHR.responseJSON;
  var options = '';
  earlyEntry = {};
  for(let ee of data) {
    options+='<option value="'+ee.earlyEntrySetting+'">'+ee.earlyEntryDescription+'</option>';
    earlyEntry[ee.earlyEntrySetting] = ee.earlyEntryDescription;
  }
  $('#earlyEntryWindow').replaceWith('<select id="earlyEntryWindow" name="earlyEntryWindow" class="form-control">'+options+'</select>');
  $('#earlyEntry :first-child').after(options);
}

function gotPools(jqXHR) {
  if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined) {
    console.log(jqXHR);
    return;
  }
  var data = jqXHR.responseJSON;
  var options = '';
  for(let pool of data) {
    options+='<option value="'+pool.pool_id+'">'+pool.pool_name+'</option>';
  }
  $('#ticketPool :first-child').after(options);
}

function initPage() {
  var sold = getParameterByName('sold');
  if(sold !== null) {
    $('#ticketSold').val(sold);
  }
  var used = getParameterByName('used');
  if(used !== null) {
    $('#ticketUsed').val(used);
  }
  var discretionaryUser = getParameterByName('discretionaryUser');
  if(discretionaryUser !== null) {
    $('#discretionaryUser').val(discretionaryUser);
  }

  $('#tickets').dataTable({
    columns: [
      {'data': 'hash', 'render': renderShortHash},
      {'data': 'firstName'},
      {'data': 'lastName'},
      {'data': 'email'},
      {'data': 'type', 'render': renderTicketType}
    ]
  });
  $.ajax({
    url: '../api/v1/globals/years',
    type: 'get',
    dataType: 'json',
    complete: gotTicketYears});
  $.ajax({
    url: '../api/v1/globals/ticket_types',
    type: 'get',
    dataType: 'json',
    complete: gotTicketTypes});
  $.ajax({
    url: '../api/v1/earlyEntry',
    type: 'get',
    dataType: 'json',
    complete: gotEarlyEntry});
  $.ajax({
    url: '../api/v1/pools',
    method: 'get',
    complete: gotPools
  });

  $('#tickets').on('search.dt', tableSearched);
  $('#ticketSold').on('change', soldChanged);
  $('#ticketAssigned').on('change', assignedChanged);
  $('#ticketUsed').on('change', usedChanged);
  $('#discretionaryUser').on('change', discretionaryChanged);
  $('#ticketVoid').on('change', usedChanged);
  $('#earlyEntry').on('change', usedChanged);
  $('#ticketPool').on('change', usedChanged);

  let hash = getParameterByName('hash');
  if(hash !== null) {
    viewTicket(hash);
  }
}

$(initPage);
