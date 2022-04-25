/* global $ */
/* exported getPDF */
function postAnswersDone(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Failed verifying answers. Try again later.');
    return;
  }
  $('[id*="answer"]').parent().removeClass('has-error');
  if(jqXHR.responseJSON.wrong !== undefined) {
    $('#answer\\['+jqXHR.responseJSON.wrong+'\\]').parent().addClass('has-error');
  }
  if(jqXHR.responseJSON.err !== undefined) {
    alert(jqXHR.responseJSON.err);
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
  var array = $('#questions').serializeArray();
  var obj = {};
  $.each(array, function() {
    var parts = this.name.split('[');
    if(obj[parts[0]] === undefined && parts.length === 1) {
      obj[parts[0]] = this.value;
    } else {
      if(obj[parts[0]] === undefined) {
        obj[parts[0]] = [];
      }
      var part2 = (parts[1].substring(0, parts[1].indexOf(']')))*1;
      if(obj[parts[0]][`${part2}`] === undefined && parts.length === 3) {
        obj[parts[0]][`${part2}`] = [];
      }
      if(parts.length === 3) {
        var part3 = (parts[2].substring(0, parts[2].indexOf(']')))*1;
        obj[parts[0]][`${part2}`][`${part3}`] = this.value;
      } else {
        obj[parts[0]][`${part2}`] = this.value;
      }
    }
  });
  $.ajax({
    url: 'api/v1/secondary/questions/answers',
    data: JSON.stringify(obj),
    type: 'POST',
    dataType: 'json',
    processData: false,
    complete: postAnswersDone});
  return false;
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
    processData: false,
    complete: postRequestDone});
  return false;
}

function questionsDone(jqXHR) {
  $('#questionContent').prepend(jqXHR.responseText);
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

function initPage() {
  $('#submitAnswer').click(postAnswers);
  $('#submitRequest').click(postRequest);
  $.ajax({
    url: 'api/v1/secondary/questions?fmt=html',
    type: 'get',
    complete: questionsDone});
  var country = $('#c').data('country');
  var state = $('#st').data('state');
  $('#c').bfhcountries({'country': country});
  $('#st').bfhstates({'country': 'c', 'state': state});

  var ticketNameFields = $('[name*="ticket_"]');
  ticketNameFields.change(ticketFieldChanged);

  var ticketEnables = $('[id*="enable_"]');
  ticketEnables.change(enablesChanged);
}

$(initPage);
