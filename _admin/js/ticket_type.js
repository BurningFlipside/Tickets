/* global $, create_modal */
function addTicketType(ticketType) {
  var nav = $('#ticket_type_nav');
  var navItem = $('<li/>', {'class': 'nav-item'});
  var link = $('<a/>', {'class': 'nav-link', href: '#'+ticketType.typeCode, role: 'tab', 'data-toggle':'tab'}).html(ticketType.description).appendTo(navItem);
  if(ticketType.typeCode !== 'NEW') {
    let button = $('<button/>', {type: 'button', 'class': 'btn btn-link', id: 'delete_'+ticketType.typeCode, 'title': 'Delete Ticket Type', 'data-toggle': 'tooltip', 'data-placement': 'top'});
    $('<span/>', {'aria-hidden': 'true'}).html('&times;').appendTo(button);
    $('<span/>', {'class': 'sr-only'}).html('Delete').appendTo(button);
    link.append('&nbsp;');
    button.appendTo(link);
  }
  navItem.appendTo(nav);

  var content = $('#ticket_type_content');
  var contentItem = $('<div/>', {'class':'tab-pane', id: ticketType.typeCode});
  var form = $('<form/>', {'class':'form-horizontal', 'role':'form'});
  var div = $('<div/>', {'class':'form-group'});
  var label = $('<label/>', {'for': 'type_code_'+ticketType.typeCode, 'class': 'col-sm-2 control-label'}).html('Type Code');
  var input = $('<input/>', {'type': 'text', 'class': 'form-control', 'id': 'type_code_'+ticketType.typeCode, 'required': 'true'});
  if(ticketType.typeCode !== 'NEW') {
    input.attr('value', ticketType.typeCode);
  }
  label.appendTo(div);
  var innerDiv = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
  input.appendTo(innerDiv);
  innerDiv.appendTo(div);

  label = $('<label/>', {'for': 'desc_'+ticketType.typeCode, 'class': 'col-sm-2 control-label'}).html('Description');
  input = $('<input/>', {'type': 'text', 'class': 'form-control', 'id': 'desc_'+ticketType.typeCode, 'required': 'true'});
  if(ticketType.typeCode !== 'NEW') {
    input.attr('value', ticketType.description);
  }
  label.appendTo(div);
  innerDiv = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
  input.appendTo(innerDiv);
  innerDiv.appendTo(div);

  label = $('<label/>', {'for': 'cost_'+ticketType.typeCode, 'class': 'col-sm-2 control-label'}).html('Cost ($)');
  input = $('<input/>', {'type': 'text', 'class': 'form-control', 'id': 'cost_'+ticketType.typeCode, 'value': ticketType.cost, 'required': 'true'});
  label.appendTo(div);
  innerDiv = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
  input.appendTo(innerDiv);
  innerDiv.appendTo(div);
   
  label = $('<label/>', {'for': 'max_per_'+ticketType.typeCode, 'class': 'col-sm-2 control-label'}).html('Max of this type per request');
  input = $('<input/>', {'type': 'number', 'class': 'form-control', 'id': 'max_per_'+ticketType.typeCode, 'value': ticketType.max_per_request, 'required': 'true'});
  label.appendTo(div);
  innerDiv = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
  input.appendTo(innerDiv);
  innerDiv.appendTo(div);

  div.append('<div class="clearfix visible-sm visible-md visible-lg"></div>');

  label = $('<label/>', {'for': 'minor_'+ticketType.typeCode, 'class': 'col-sm-2 control-label'}).html('Is this request type a minor?');
  input = $('<input/>', {'type': 'checkbox', 'class': 'form-control', 'id': 'minor_'+ticketType.typeCode, 'data-on-text': 'Yes', 'data-off-text': 'No'});
  if(ticketType.is_minor === '1') {
    input.attr('checked', true);
  }
  label.appendTo(div);
  innerDiv = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
  input.appendTo(innerDiv);
  innerDiv.appendTo(div); 

  div.appendTo(form);

  div = $('<div/>', {'class':'row'});
  let button = $('<button/>', {'class': 'btn btn-default', id: 'commit_'+ticketType.typeCode, 'title': 'Change Ticket Type', 'data-toggle': 'tooltip', 'data-placement': 'top'});
  button.html('Commit Changes');
  button.appendTo(div);
  div.appendTo(form);

  form.appendTo(contentItem);
  contentItem.appendTo(content);
}

function tabShown(e) {
  $('[id^=delete_]').hide();
  var tab;
  if(e === undefined) {
    tab = $('#ticket_type_nav .active');
  } else {
    tab = $(e.currentTarget);
  }
  tab.find('[id^=delete_]').show();
}

function actionFailed(jqXHR) {
  console.log(jqXHR);
}

function reallyDelete(e) {
  var button = $(e.currentTarget);
  var id = button.data('data');
  $.ajax({
    url: '../api/v1/globals/ticket_types/'+id,
    type: 'DELETE',
    dataType: 'json',
    success: initTicketType,
    error: actionFailed});
}

function deleteTicketType(e) {
  var button = $(e.currentTarget);
  var id = button.attr('id').split('_');
  var type = id[1];
  var desc = $('#desc_'+type).val();

  var yes = {'close': true, 'text': 'Yes', 'method': reallyDelete, 'data': type};
  var no = {'close': true, 'text': 'No'};
  var buttons = [yes, no];
  var modal = create_modal('Are you sure?', 'Are you sure you want to delete the '+desc+' ticket type? This operation cannot be undone.', buttons);
  modal.appendTo(document.body);
  modal.modal();
  e.preventDefault();
}

function commitTicketType(e) {
  var button = $(e.currentTarget);
  var id = button.attr('id').split('_');
  var type = id[1];
  var controls = $('[id$=_'+type+'].form-control');
  var data = {};
  for(let _control of controls) {
    var control = $(_control);
    var controlName = control.attr('id');
    controlName = controlName.substring(0, controlName.lastIndexOf('_'));
    if(control[0].type === 'checkbox') {
      data[`${controlName}`] = control[0].checked;
    } else {
      data[`${controlName}`] = control.val();
    }
  }
  e.preventDefault();
  $.ajax({
    url: '../api/v1/globals/ticket_types',
    contentType: 'application/json',
    data: JSON.stringify(data),
    type: 'POST',
    processData: false,
    dataType: 'json',
    success: initTicketType,
    error: actionFailed});
}

function ticketTypesDone(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Error reading ticket types!');
    console.log(jqXHR);
    return;
  }
  var data = jqXHR.responseJSON;
  for(let type of data) {
    addTicketType(type);
  }
  var newType = new Object();
  newType.typeCode = 'NEW';
  newType.description = 'New Type';
  newType.cost = '';
  newType.max_per_request = ''; // eslint-disable-line camelcase
  newType.is_minor = ''; // eslint-disable-line camelcase
  addTicketType(newType);
  $('#ticket_type_nav a:first').tab('show');
  $('#ticket_type_nav a').on('shown.bs.tab', tabShown);
  $('[title]').tooltip();
  tabShown();
  $('[id^=delete_]').on('click', deleteTicketType);
  $('[id^=commit_]').on('click', commitTicketType);
}

function initTicketType() {
  $('#ticket_type_nav').empty();
  $.ajax({
    url: '../api/v1/globals/ticket_types',
    type: 'get',
    dataType: 'json',
    complete: ticketTypesDone});
}

$(initTicketType);
