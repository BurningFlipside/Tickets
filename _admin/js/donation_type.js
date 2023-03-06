/*global $, create_modal*/
function addDonationType(donationType) {
  var nav = $('#donation_type_nav');
  var navItem = $('<li/>', {'class': 'nav-item'});
  var link = $('<a/>', {'class': 'nav-link', href: '#'+donationType.entityName, role: 'tab', 'data-toggle':'tab'}).html(donationType.entityName).appendTo(navItem);
  if(donationType.entityName !== 'NEW') {
    let button = $('<button/>', {type: 'button', 'class': 'btn btn-link', id: 'delete_'+donationType.entityName, 'title': 'Delete Donation Type', 'data-toggle': 'tooltip', 'data-placement': 'top'});
    $('<span/>', {'aria-hidden': 'true'}).html('&times;').appendTo(button);
    $('<span/>', {'class': 'sr-only'}).html('Delete').appendTo(button);
    link.append('&nbsp;');
    button.appendTo(link);
  }
  navItem.appendTo(nav);

  var content = $('#donation_type_content');
  var contentItem = $('<div/>', {'class':'tab-pane', id: donationType.entityName});
  var form = $('<form/>', {'class':'form-horizontal', 'role':'form'});
  var div = $('<div/>', {'class':'form-group'});
  var label = $('<label/>', {'for': 'entityName_'+donationType.entityName, 'class': 'col-sm-2 control-label'}).html('Entity Name');
  var input = $('<input/>', {'type': 'text', 'class': 'form-control', 'id': 'entityName_'+donationType.entityName, 'required': 'true'});
  if(donationType.entityName !== 'NEW') {
    input.attr('value', donationType.entityName);
  }
  label.appendTo(div);
  var innerDiv = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
  input.appendTo(innerDiv);
  innerDiv.appendTo(div);

  label = $('<label/>', {'for': 'url_'+donationType.entityName, 'class': 'col-sm-2 control-label'}).html('Website URL');
  input = $('<input/>', {'type': 'url', 'class': 'form-control', 'id': 'url_'+donationType.entityName, 'required': 'true'});
  if(donationType.entityName !== 'NEW') {
    input.attr('value', donationType.url);
  }
  label.appendTo(div);
  innerDiv = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
  input.appendTo(innerDiv);
  innerDiv.appendTo(div);

  label = $('<label/>', {'for': 'thirdParty_'+donationType.entityName, 'class': 'col-sm-2 control-label'}).html('Is this entity a third party?');
  input = $('<input/>', {'type': 'checkbox', 'class': 'form-control', 'id': 'thirdParty_'+donationType.entityName, 'data-on-text': 'Yes', 'data-off-text': 'No'});
  if(donationType.thirdParty === '1') {
    input.attr('checked', true);
  }
  label.appendTo(div);
  innerDiv = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
  input.appendTo(innerDiv);
  innerDiv.appendTo(div); 

  div.appendTo(form);

  div = $('<div/>', {'class':'row'});
  let button = $('<button/>', {'class': 'btn btn-default', id: 'commit_'+donationType.entityName, 'title': 'Change Donation Type', 'data-toggle': 'tooltip', 'data-placement': 'top'});
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
    tab = $('#donation_type_nav .active');
  } else {
    tab = $(e.currentTarget);
  }
  tab.find('[id^=delete_]').show();
}

function reallyDelete(e) {
  var button = $(e.currentTarget);
  var id = button.data('data');
  $.ajax({
    url: '../api/v1/globals/donation_types/'+id,
    type: 'DELETE',
    dataType: 'json',
    success: initDonationType,
    error: actionFailed});
}

function actionFailed(jqXHR) {
  console.log(jqXHR);
}

function deleteDonationType(e) {
  var button = $(e.currentTarget);
  var id = button.attr('id').split('_');
  var type = id[1];
  var desc = $('#entityName_'+type).val();

  var yes = {'close': true, 'text': 'Yes', 'method': reallyDelete, 'data': type};
  var no = {'close': true, 'text': 'No'};
  var buttons = [yes, no];
  var modal = create_modal('Are you sure?', 'Are you sure you want to delete the '+desc+' donation type? This operation cannot be undone.', buttons);
  modal.appendTo(document.body);
  modal.modal();
  e.preventDefault();
}

function commitDonationType(e) {
  var button = $(e.currentTarget);
  var id = button.attr('id').split('_');
  var type = id[1];
  var controls = $('[id$=_'+type+'].form-control');
  var data = {};
  for(let donationControl of controls) {
    var control = $(donationControl);
    if(control[0].type === 'checkbox') {
      data[control.attr('id').split('_')[0]] = control[0].checked;
    } else {
      data[control.attr('id').split('_')[0]] = control.val();
    }
  }
  e.preventDefault();
  $.ajax({
    url: '../api/v1/globals/donation_types',
    contentType: 'application/json',
    data: JSON.stringify(data),
    type: 'POST',
    processData: false,
    dataType: 'json',
    success: initDonationType,
    error: actionFailed});
}

function donationsDone(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Failed to obtain donation type data!');
    console.log(jqXHR);
  } else {
    var data = jqXHR.responseJSON;
    for(let type of data) {
      addDonationType(type);
    }
  }
  var newType = new Object();
  newType.entityName = 'NEW';
  newType.thirdParty = '';
  newType.url = '';
  addDonationType(newType);
  $('#donation_type_nav a:first').tab('show');
  $('#donation_type_nav a').on('shown.bs.tab', tabShown);
  $('[title]').tooltip();
  tabShown();
  $('[id^=delete_]').on('click', deleteDonationType);
  $('[id^=commit_]').on('click', commitDonationType);
}

function initDonationType() {
  $('#donation_type_nav').empty();
  $('#donation_type_content').empty();
  $.ajax({
    url: '../api/v1/globals/donation_types',
    type: 'get',
    dataType: 'json',
    complete: donationsDone});
}

$(initDonationType);
