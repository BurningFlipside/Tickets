/*global $, getParameterByName*/
/*exported save*/
function saveDone(jqXHR) {
  if(jqXHR.status === 200) {
    location.reload();
  } else {
    alert('Unable to save data!');
    console.log(jqXHR);
  }
}

function save() {
  $.ajax({
    url: '../api/v1/globals/long_text/'+$('#ticket_text_name').val(),
    type: 'PATCH',
    contentType: 'text/html',
    data: $('#pdf-source').val(),
    processData: false,
    complete: saveDone});
}

function gotEmailSource(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Unable to obtain PDF source!');
  }
  $('#pdf-source').val(jqXHR.responseJSON);
}

function ticketTextChanged() {
  $.ajax({
    url: '../api/v1/globals/long_text/'+$('#ticket_text_name').val(),
    type: 'get',
    complete: gotEmailSource});
}

function pageInit() {
  $('#pdf-source').ckeditor({
    'allowedContent': true
  });
  var type = getParameterByName('type');
  if(type !== null) {
    $('#ticket_text_name').val(type);
  }
  ticketTextChanged();
}

$(pageInit);
