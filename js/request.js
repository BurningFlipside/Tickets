/* global $, TicketSystem, add_notification, browser_supports_cors, getParameterByName */
/* exported deleteTicket, minorAffirmClicked */
var ticketSystem = new TicketSystem('api/v1');

var ticketConstraints = null;
var tableRow = 0;

function constraintsAjaxDone(jqXHR) {
  ticketConstraints = jqXHR.responseJSON;
  if(tableRow > 0) {
    var types = $('[id^=ticket_type_]');
    for(let type of types) {
      populateDropdown($(type), null, types.data('temp'));
    }
    for(let i = 0; i < tableRow; i++) {
      ticketTypeChanged(i);
    }
  }
}

function populateDropdown(dropdown, cost, value) {
  if(cost !== null) {
    cost.val(' ');
  }
  $('<option/>', {value: ' ', text: ' '}).appendTo(dropdown);
  for(let ticketType of ticketConstraints.ticket_types) {
    var props = {value: ticketType.typeCode, text: ticketType.description};
    if(ticketType.typeCode === value) {
      if(cost !== null) {
        cost.val('$'+ticketType.cost);
      }
      props.selected = true;
    }
    $('<option/>', props).appendTo(dropdown);
  }
}

function floatValue(i) {
  if(typeof i === 'string') {
    return i.replace(/[\$,]/g, '')*1;
  }
  return i;
}

function calculateTicketSubtotal() {
  var total = 0;
  var costs = $('[name=ticket_cost]');
  for(let cost of costs) {
    total += floatValue(cost.value);
  }
  $('#ticket_subtotal').html('$'+total);
}

function addRowToTable(tbody, firstName, lastName, type, rowId) {
  var row = $('<tr/>');
  var cell = $('<td/>', {id: 'delete_cell'});
  if(rowId !== 0) {
    var button = $('<button/>', {type: 'button', class: 'btn btn-link btn-sm', id: 'delete_'+rowId, onclick: 'deleteTicket()'});
    $('<span/>', {class: 'fa fa-times'}).appendTo(button);
    button.appendTo(cell);
  }
  cell.appendTo(row);
  cell = $('<td/>');
  var first = $('<input/>', {type: 'text', id: 'ticket_first', name: 'ticket_first', required: true, value: firstName, class: 'form-control'});
  first.appendTo(cell);
  cell.appendTo(row);
  cell = $('<td/>');
  var last = $('<input/>', {type: 'text', id: 'ticket_last', name: 'ticket_last', required: true, value: lastName, class: 'form-control'});
  last.appendTo(cell);
  cell.appendTo(row);
  cell = $('<td/>');
  var cell2 = $('<td/>');
  var age = $('<select/>', {id: 'ticket_type', name: 'ticket_type', class: 'form-control', required: true, onchange: 'ticketTypeChanged(this)'});
  var cost = $('<input/>', {type: 'text', id: 'ticket_cost', name: 'ticket_cost', readonly: true, class: 'form-control'});
  if(ticketConstraints !== null) {
    populateDropdown(age, cost, type);
  } else {
    age.data('temp', type);
  }
  age.appendTo(cell);
  cell.appendTo(row);
  cost.appendTo(cell2);
  cell2.appendTo(row);
  row.appendTo(tbody);
  calculateTicketSubtotal();
}

function requestAjaxDone(data) {
  $('#givenName').val(data.givenName);
  $('#sn').val(data.sn);
  $('#mail').val(data.mail);
  $('#mail').tooltip({content: 'This field is not editable. If you want to use a different email then please register a new account with that email.'});
  $('#street').val(data.postalAddress);
  $('#zip').val(data.postalCode);
  $('#l').val(data.l);
  $('#st').val(data.st);
  $('#mobile').val(data.mobile);
  if(data.c === undefined || data.c.length <= 0) {
    $('#c').val('US');
  } else {
    $('#c').val(data.c);
  }
  if(data.postalAddress === null || data.postalAddress.length === 0 || 
     data.postalCode === null || data.postalCode.length === 0 || 
     data.l === null || data.l.length === 0 || 
     data.st === null || data.st.length === 0 || 
     data.mobile === null || data.mobile.length === 0) {
    add_notification($('#request_set'), 'If you had filled out your profile this data would all be populated.');
  }

  var tbody = $('#ticket_table tbody');
  addRowToTable(tbody, data.givenName, data.sn, 'A', tableRow++);
}

function reEvalList() {
  var list = $(this).data('list');
  if(shouldBeChecked(list.request_condition)) {
    $(this).prop('checked', true);
  }
}

function reEvalLists() {
  $('#email_lists :checkbox').each(reEvalList);
}

function ticketTypeChanged(dropdown) {
  var dropdownValue = $(dropdown).val();
  if(ticketConstraints !== null) {
    var count = 0;
    var types = $('[id^=ticket_type]');
    for(let type of types) {
      var x = $(type);
      if(x.val() === dropdownValue) {
        count++;
      }
    }
    var ticketTypes = ticketConstraints.ticket_types;
    for(let ticketType of ticketTypes) {
      if(ticketType.typeCode === dropdownValue) {
        $(dropdown).parent().siblings().find('[name="ticket_cost"]').val('$'+ticketType.cost);
        if(count > ticketType.max_per_request) {
          alert('You are only allowed to have '+ticketType.max_per_request+' '+
                ticketType.description+' tickets per request');
        }
      }
    }
  }
  calculateTicketSubtotal();
  reEvalLists();
}

function addNewTicket() {
  var tbody = $('#ticket_table tbody');
  if(tableRow > 1) {
    var button = $('#delete_'+(tableRow-1));
    button.attr('disabled', true);
    var cell = button.parent();
    cell.attr('data-toggle', 'tooltip');
    cell.attr('data-placement', 'left');
    cell.attr('data-container', 'body');
    cell.attr('title', 'You can only remove the last ticket in the list.');
    cell.tooltip();
  }
  addRowToTable(tbody, '', '', ' ', tableRow++);
  if(ticketConstraints !== null) {
    var rows = $('#ticket_table tbody tr');
    if(rows.length >= ticketConstraints.max_total_tickets) {
      $(this).prop('disabled', true);
      $('#new_ticket_tooltip').attr('data-toggle', 'tooltip');
      $('#new_ticket_tooltip').attr('data-placement', 'left');
      $('#new_ticket_tooltip').attr('title', 'You can have a maximum of '+ticketConstraints.max_total_tickets+' tickets per request');
      $('#new_ticket_tooltip').tooltip();
    }
  }
}

function deleteTicket() {
  let button = $('#delete_'+(tableRow-1));
  let cell = button.parent();
  cell.parent().remove();
  tableRow--;
  button = $('#delete_'+(tableRow-1));
  button.removeAttr('disabled');
  cell = button.parent();
  cell.tooltip('destroy');
  calculateTicketSubtotal();
}

function donationAmountChanged(elem) {
  var jq = null;
  if(elem.target !== undefined) {
    jq = $(elem.target);
  } else {
    jq = $(elem);
  }
  var id = jq.attr('id');
  var textId = id+'_text';
  if(jq.val() === 'other') {
    if($('#'+textId).length < 1) {
      var box = $('<input/>', {name: id, id: textId, 'class': 'form-control', 'placeholder': 'Donation ($)', 'type': 'number'});
      box.appendTo(jq.parent());
    }
  } else {
    var boxes = $('#'+textId);
    if(boxes.length >= 1) {
      boxes.hide();
    }
  }
}

function showDonationInfoTooltip() {
  var span = $(this);
  var donation = span.data('donation');
  var tooltip = '';
  tooltip += '<p align="left">';
  tooltip += 'Checking this box will provide '+donation.entityName+' with the following:<br/>';
  tooltip += '<address>';
  tooltip += $('#givenName').val()+' '+$('#sn').val()+'<br/>';
  tooltip += $('#street').val()+'<br/>';
  tooltip += $('#l').val()+', '+$('#st').val()+' '+$('#zip').val()+'<br/>';
  if($('#c').val() !== 'US') {
    tooltip += $('#c option:selected').text()+'<br/>';
  }
  tooltip += '</address>';
  tooltip += 'Email: '+$('#mail').val()+'<br/>';
  var amount = $('#donation_amount_'+donation.entityName).val();
  if(amount === 'other') {
    amount = $('#donation_amount_'+donation.entityName+'_text').val();
  }
  tooltip += 'Donation Amount: $'+amount;
  tooltip += '</p>';
  span.attr('data-original-title', tooltip);
}

function addDiscloseCheckboxToCell(cell, donation) {
  var id = 'donation_disclose_'+donation.entityName;
  var span = $('<span/>', {'data-toggle': 'tooltip', 'data-placement': 'bottom', 'title': 'Filler...'});
  var checkbox = $('<input/>', {type: 'checkbox', id: id, name: id});
  var label = $('<label/>', {for: id}).html('&nbsp;Allow '+donation.entityName+' to see my contact details');
  checkbox.appendTo(span);
  label.appendTo(span);
  span.appendTo(cell);
  span.tooltip({html:true});
  span.data('donation', donation);
  span.on('show.bs.tooltip', showDonationInfoTooltip);
}

function addDonationTypeToTable(table, donation) {
  var row = $('<tr/>');
  var cell = $('<td/>');
  cell.append(donation.entityName);
  if(donation.thirdParty || donation.url) {
    cell.append('<br/>');
    if(donation.thirdParty) {
      cell.append('<I>Not Affliated with AAR, LLC</I> ');
    }
    if(donation.url) {
      cell.append('<a href="'+donation.url+'" target="_new">More Info</a>');
    }
  }
  cell.appendTo(row);
  cell = $('<td/>');
  if(donation.thirdParty && $(window).width() >= 768) {
    addDiscloseCheckboxToCell(cell, donation);
  }
  cell.appendTo(row);
  cell = $('<td/>', {style: 'vertical-align:middle; horizontal-align:left'});
  var id = 'donation_amount_'+donation.entityName;
  var dropdown = $('<select />', {id: id, name: id, 'class':'form-control'});
  $('<option/>', {value: '0', text: '$0'}).appendTo(dropdown);
  $('<option/>', {value: '5', text: '$5'}).appendTo(dropdown);
  $('<option/>', {value: '10', text: '$10'}).appendTo(dropdown);
  $('<option/>', {value: '25', text: '$25'}).appendTo(dropdown);
  $('<option/>', {value: '50', text: '$50'}).appendTo(dropdown);
  $('<option/>', {value: 'other', text: 'Other...'}).appendTo(dropdown);
  dropdown.appendTo(cell); 
  cell.appendTo(row);
  row.appendTo(table);
  dropdown.on('change', donationAmountChanged);
  if(donation.thirdParty && $(window).width() < 768) {
    row = $('<tr/>');
    cell = $('<td/>', {colspan: '3'});
    addDiscloseCheckboxToCell(cell, donation);
    cell.appendTo(row);
    row.appendTo(table);
  }
}

function donationsAjaxDone(jqXHR) {
  var data = jqXHR.responseJSON;
  var div = $('#donations');
  if(data.length > 0) {
    var table = $('<table/>', {width: '100%'});
    for(let donation of data) {
      addDonationTypeToTable(table, donation);
    }
    table.appendTo(div);
  } else {
    div.hide();
  }
}
/*
function getTicketCount(type) {
  var values = $('[value="'+type+'"]').filter(':selected');
  return values.length;
}*/

function shouldBeChecked(condition) {
  if(condition === '1') {
    return true;
  }
  //var A = getTicketCount('A');
  //var T = getTicketCount('T');
  //var C = getTicketCount('C');
  //var K = getTicketCount('K');
  var res = eval(condition);
  return res;
}

function addListToRow(list, row) {
  var cell = $('<td/>');
  var checkbox = $('<input/>', {id: 'list_'+list.short_name, name: 'list_'+list.short_name, type: 'checkbox'});
  if(shouldBeChecked(list.request_condition)) {
    checkbox.attr('checked', 'true');
  }
  checkbox.appendTo(cell);
  cell.appendTo(row);
  checkbox.data('list', list);

  cell = $('<td/>');
  cell.append(list.name+' ');
  if(list.description) {
    var img = $('<img/>', {src: '/images/info.svg', style: 'height: 1em; width: 1em;', title: list.description});
    img.appendTo(cell);
  }
  cell.appendTo(row);
}

function listsAjaxDone(jqXHR) {
  var data = jqXHR.responseJSON;
  var table = $('#email_lists');
  if(data.length > 0) {
    for(let i = 0; i < data.length; i+=2) {
      var row = $('<tr/>');
      addListToRow(data[i], row); // eslint-disable-line security/detect-object-injection
      if(i+1 < data.length) {
        addListToRow(data[i+1], row);
      }
      row.appendTo(table);
    }
  }
}

function requestSubmitDone(data, err) {
  if(err !== null) {
    if(err.jsonResp !== undefined && err.jsonResp.message !== undefined) {
      alert(err.jsonResp.message);
    } else {
      alert('Unable to submit request!');
      console.log(err);
    }
    return;
  }
  if(data.need_minor_confirm !== undefined && (data.need_minor_confirm === '1' || data.need_minor_confirm === true)) {
    $('#minor_dialog').modal({
      'backdrop': 'static',
      'keyboard': false});
    $('[title]').tooltip('hide');
  } else {
    window.location = 'index.php';
  }
}

function fixupDonationForm() {
  if($(this).val() === 'other') {
    $(this).attr('disabled', 'true');
    var id = $(this).id;
    $(this).removeAttr('name');
    $('#'+id+'_text').attr('name', id);
  }
}

function revertDonationForm() {
  if($(this).val() === 'other') {
    $(this).removeAttr('disabled');
    var id = $(this).id;
    $(this).attr('name', id);
    $('#'+id+'_text').attr('name', id+'_text');
  }
}

function requestDataSubmitted() {
  $('[id^=donation_amount_]').each(fixupDonationForm);
  var obj = {};
  var a = $('#request').serializeArray();
  for(let item of a) {
    var name = item.name;
    var split = name.split('_');
    if(split[0] === 'list') {
      if(obj['lists'] === undefined) {
        obj['lists'] = {};
      }
      obj['lists'][name.substring(5)] = item.value;
    } else if(split[0] === 'ticket') {
      let childName = name.substring(7);
      if(obj['tickets'] === undefined) {
        obj['tickets'] = [];
      }
      if(obj['tickets'].length === 0 || obj['tickets'][obj['tickets'].length-1][`${childName}`] !== undefined) {
        obj['tickets'][obj['tickets'].length] = {};
      }
      obj['tickets'][obj['tickets'].length-1][`${childName}`] = item.value;
    } else if(split[0] === 'donation') {
      if(obj['donations'] === undefined) {
        obj['donations'] = {};
      }
      if(obj['donations'][split[2]] === undefined) {
        obj['donations'][split[2]] = {};
      }
      obj['donations'][split[2]][split[1]] = item.value;
    } else {
      obj[`${name}`] = item.value;
    }
  }
  if(obj.donations !== undefined) {
    for(let donationType in obj.donations) {
      if(obj.donations[`${donationType}`].amount === 0) {
        delete obj.donations[`${donationType}`];
      }
    }
    if($.isEmptyObject(obj.donations)) {
      delete obj.donations;
    }
  }

  ticketSystem.createRequest(obj, requestSubmitDone);
  $('[id^=donation_amount_]').each(revertDonationForm);
  return false;
}

function resubmitForm() {
  var form = $('#request');
  $('<input/>', {type: 'hidden', name: 'minor_confirm', value: '1'}).appendTo(form);
  requestDataSubmitted(form[0]); 
}

function minorAffirmClicked() {
  $('#minor_dialog_continue').removeAttr('disabled');
  $('#minor_dialog_continue').on('click', resubmitForm);
}

function currentRequestDone(request) {
  if(request === null) {
    ticketSystem.getTicketRequestIdForCurrentUser(requestIdDone);
  } else {
    var tbody = $('#ticket_table tbody');
    for(var propertyName in request) {
      switch(propertyName) {
        case 'tickets':
          for(let ticket of request.tickets) {
            addRowToTable(tbody, ticket.first, ticket.last, ticket.type, tableRow++);
          }
          break;
        case 'donations':
          if(request[`${propertyName}`] === null) {
            continue;
          }
          for(let donation of request.donations) {
            var id = 'donation_amount_'+donation.type;
            var dropdown = $('#'+id);
            dropdown.val(donation.amount);
            if(dropdown.val() === null) {
              dropdown.val('other');
              var box = $('<input/>', {name: id, id: id+'_text', type: 'text', value: donation.amount});
              box.appendTo(dropdown.parent());
            }
            if(donation.disclose !== undefined && donation.disclose === '1') {
              $('#donation_disclose_'+donation.type).prop('checked', true);
            }
          }
          break;
        default:
          $('#'+propertyName).val(request[`${propertyName}`]);
          break;
      }
    }
  }
}

function requestIdDone(data, err) {
  if(err !== null) {
    alert('Unable to obtain request ID!');
    return; 
  }
  $('#request_id').val(data);
  if(browser_supports_cors()) {
    $.ajax({
      url: window.profilesUrl+'/api/v1/users/me',
      type: 'get',
      dataType: 'json',
      xhrFields: {withCredentials: true},
      success: requestAjaxDone});
  } else {
    add_notification($('#request_set'), 'Your browser is out of date. Due to this some data may be missing from your request. Please make sure it is complete');
  }
}

function initRequest() {
  var requestId  = getParameterByName('request_id');
  var year       = getParameterByName('year');
  ticketSystem.getRequest(currentRequestDone, requestId, year);
  var request = $('#request').data('request');
  $('#add_new_ticket').on('click', addNewTicket);
  if(request !== undefined) {
    var tbody = $('#ticket_table tbody');
    for(let ticket of request.tickets) {
      addRowToTable(tbody, ticket.first, ticket.last, ticket.type.typeCode, tableRow++);
    }
    for(let donation of request.donations) {
      var id = 'donation_amount_'+donation.type;
      var dropdown = $('#'+id);
      dropdown.val(donation.amount);
      if(dropdown.val() === null) {
        dropdown.val('other');
        var box = $('<input/>', {name: id, id: id+'_text', type: 'text', value: donation.amount});
        box.appendTo(dropdown.parent());
      }
      if(donation.disclose !== undefined && donation.disclose === '1') {
        $('#donation_disclose_'+donation.type.entityName).prop('checked', true);
      }
    }
    reEvalLists();
  }
  $('#request').submit(requestDataSubmitted);
}

function startPopulateForm() {
  $.when(
    $.ajax({
      url: 'api/v1/globals/constraints',
      type: 'get',
      dataType: 'json',
      complete: constraintsAjaxDone}),
    $.ajax({
      url: 'api/v1/globals/donation_types',
      type: 'get',
      dataType: 'json',
      complete: donationsAjaxDone}),
    $.ajax({
      url: 'api/v1/globals/lists',
      type: 'get',
      dataType: 'json',
      complete: listsAjaxDone})
  ).done(initRequest);
}

function initInThread() {
  if($('#request_id').length > 0) {
    setTimeout(startPopulateForm, 0);
    $('[title]').tooltip();
  }
}

$(initInThread);
