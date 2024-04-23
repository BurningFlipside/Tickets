/* global $ */
/* exported save, genPreview */
function genPreviewDone(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Unable to generate a preview!');
    console.log(jqXHR);
    return;
  }
  var pdfWin = window.open('data:application/pdf;base64, '+jqXHR.responseText); // eslint-disable-line security/detect-non-literal-fs-filename
  if(!pdfWin) {
    alert('Popup was blocked!');
  }
}

function saveDone(jqXHR) {
  if(jqXHR.status === 200) {
    location.reload();
    return;
  }
  alert('Unable to save data!');
  console.log(jqXHR);
}

function genPreview() {
  $.ajax({
    url: '../api/v1/globals/Actions/generatePreview/Tickets/TicketPDF',
    type: 'post',
    data: $('#pdf-source').val(),
    processData: false,
    dataType: 'json',
    complete: genPreviewDone});
}

function save() {
  $.ajax({
    url: '../api/v1/globals/long_text/ee_pdf_source',
    type: 'PATCH',
    contentType: 'application/json',
    data: $('#pdf-source').val(),
    processData: false,
    complete: saveDone});
}

function gotPDFSource(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Unable to obtain PDF source!');
    return;
  }
  $('#pdf-source').val(jqXHR.responseJSON.value);
}

function pageInit() {
  $.ajax({
    url: '../api/v1/globals/long_text/ee_pdf_source',
    type: 'get',
    complete: gotPDFSource});
  $('#pdf-source').ckeditor({
    'allowedContent': true
  });
}

$(pageInit);
