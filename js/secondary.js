/* global $ */
/* exported getPDF */
function postAnswersDone(response) {
  if(response.status !== 200) {
    alert('Failed verifying answers. Try again later.');
    return;
  }
  $('#questions').hide();
  $('#request').show();
}

function postRequestDone(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Failed updating request. Try again later.');
    return;
  }
  if(jqXHR.responseJSON.err !== undefined) {
    alert(jqXHR.responseJSON.err);
    return;
  }
  if(jqXHR.responseJSON.uri !== undefined) {
    window.location = jqXHR.responseJSON.uri;
  }
}

function postAnswers() {
  fetch('api/v1/secondary/createRequestId', {
    method: 'POST'})
    .then(response => postAnswersDone(response));
}

function postRequest() {
  var error = false;
  var hasTickets = false;
  var obj = $('#request').serializeObject();
  var enables = $('[id*="enable_"]');
  for(let _enable of enables) {
    let enable = $(_enable);
    if(enable.prop('checked') === false) {
      let id = getIDFromTarget(enable);
      delete obj['ticket_first_'+id];
      delete obj['ticket_last_'+id];
    }
  }
  for(var prop in obj) {
    if(obj[`${prop}`].length === 0) {
      $('[name='+prop+']').parent().addClass('has-error');
      error = 'One or more required fields are empty';
    }
    if(prop.startsWith('ticket_')) {
      hasTickets = true;
    }
  }
  if(hasTickets === false && error === false) {
    error = 'You must select at least one ticket!';
  }
  if(error !== false) {
    alert(error);
    return false;
  }
  $.ajax({
    url: 'api/v1/secondary/requests',
    data: JSON.stringify(obj),
    type: 'POST',
    dataType: 'json',
    contentType: 'application/json',
    processData: false,
    complete: postRequestDone});
  return false;
}

function getIDFromTarget(target) {
  var id = target.attr('id');
  var index = id.lastIndexOf('_');
  index = id.lastIndexOf('_', index-1);
  return id.substring(index+1);
}

function ticketFieldChanged(eventData) {
  var target = $(eventData.target);
  var id = getIDFromTarget(target);
  $('#enable_'+id).prop('checked', true);
  enablesChanged(eventData);
}

function enablesChanged() {
  var totalCost = 0;
  var count = $('#request').data('ticketcount')*1;
  var enables = $('[id*="enable_"]');
  for(let _enable of enables) {
    let enable = $(_enable);
    if(enable.prop('checked') === true) {
      let id = getIDFromTarget(enable);
      var costElem = $('#cost_'+id);
      var cost = costElem.html().substring(1);
      totalCost += cost*1;
      count++;
    }
  }
  if(count >= 6) {
    for(let _enable of enables) {
      let enable = $(_enable);
      if(enable.prop('checked') === false) {
        let id = getIDFromTarget(enable);
        let elems = $('[id$='+id+']');
        elems.prop('disabled', true);
      }
    }
  } else {
    for(let _enable of enables) {
      let enable = $(_enable);
      if(enable.prop('checked') === false) {
        let id = getIDFromTarget(enable);
        let elems = $('[id$='+id+']');
        elems.prop('disabled', false);
      }
    }
  }
  $('#ticket_subtotal').html('$'+totalCost);
}

function getPDF() {
  location = 'api/v1/secondary/me/current/pdf';
}

function changeToPersonalChecks() {
  var checked = $('#noPersonalChecks').prop('checked');
  if(checked === true) {
    $('#submitAnswer').removeAttr('disabled');
  } else {
    $('#submitAnswer').attr('disabled', true);
  }
}

function initPage() {
  $('#submitAnswer').click(postAnswers);
  $('#submitRequest').click(postRequest);
  $('#noPersonalChecks').change(changeToPersonalChecks);

  let ticketNameFields = $('[name*="ticket_"]');
  ticketNameFields.change(ticketFieldChanged);

  let ticketEnables = $('[id*="enable_"]');
  ticketEnables.change(enablesChanged);
}

$(initPage);
