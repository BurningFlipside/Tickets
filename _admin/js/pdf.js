/*global $*/
/*exported genPreview, save*/
function genPreviewDone(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Unable to generate a preview!');
    console.log(jqXHR);
    return;
  }
  var pdfWin = window.open('data:application/pdf;base64, '+jqXHR.responseText);  // eslint-disable-line security/detect-non-literal-fs-filename
  if(!pdfWin) {
    alert('Popup was blocked!');
  }
}

function saveDone(jqXHR) {
  if(jqXHR.status === 200) {
    location.reload();
  } else {
    alert('Unable to save data!');
    console.log(jqXHR);
  }
}

function genPreview() {
  $.ajax({
    url: '../api/v1/globals/Actions/generatePreview/Tickets/Flipside/RequestPDF',
    type: 'post',
    data: $('#pdf-source').val(),
    processData: false,
    dataType: 'json',
    complete: genPreviewDone});
}

function save() {
  $.ajax({
    url: '../api/v1/globals/long_text/pdf_source',
    type: 'PATCH',
    data: $('#pdf-source').val(),
    processData: false,
    contentType: 'text/html',
    complete: saveDone});
}

function gotPDFSource(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Unable to obtain PDF source!');
  }
  $('#pdf-source').val(jqXHR.responseJSON);
}

function pageInit() {
  $.ajax({
    url: '../api/v1/globals/long_text/pdf_source',
    type: 'get',
    complete: gotPDFSource});
  $('#pdf-source').ckeditor({
    'allowedContent': true
  });
}

$(pageInit);
