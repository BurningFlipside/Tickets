/* global $ */
/* exported processBulk */
function uploadDone(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Upload failed!');
    return;
  }
  let json = jqXHR.responseJSON;
  $('#success_count').html(json.success.length);
  $('#fail_count').html(json.fails.length);
  $('#successes').empty();
  $('#failures').empty();
  for(let success of json.success) {
    $('#successes').append(success+'<br/>');
  }
  for(let fail of json.fails) {
    $('#failures').append(fail+'<br/>');
  }
  $('#result_dialog').modal();
}

function sendFileToServer(formData) {
  $.ajax({
    url:  'users_upload.php',
    type: 'POST',
    contentType: false,
    processData: false,
    cache: false,
    data: formData,
    success: uploadDone}); 
}

function processBulk() {
  $.ajax({
    url:  '../api/v1/globals/users',
    type: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({text: $('#bulk_text').val()}),
    complete: uploadDone});
}

function handleFileUpload(files) {
  for(let file of files) {
    let fd = new FormData();
    fd.append('file', file);
    sendFileToServer(fd);
  }
}

function dragEnter(event) {
  event.stopPropagation();
  event.preventDefault();
  $(this).css('border', '2px solid #0B85A1');
}

function dragOver(event) {
  event.stopPropagation();
  event.preventDefault();
}

function dropIn(event) {
  $(this).css('border', '2px dotted #0B85A1');
  event.preventDefault();
  var files = event.originalEvent.dataTransfer.files;
  //We need to send dropped files to Server
  handleFileUpload(files, $('#filehandler'));
}

function docDragEnter(event) {
  event.stopPropagation();
  event.preventDefault();
}

function docDragOver(event) {
  event.stopPropagation();
  event.preventDefault();
  $('#filehandler').css('border', '2px dotted #0B85A1');
}

function docDropIn(event) {
  event.stopPropagation();
  event.preventDefault();
}

function renderName(data, type, row) {
  return row['givenName']+' '+row['sn'];
}

function initPage() {
  $('#users').dataTable({
    'ajax': '../api/v1/globals/users?fmt=data-table',
    'columns': [
      {'render': renderName},
      {'data': 'mail'},
      {'data': 'uid'},
      {'data': 'admin'}
    ]
  });
  var drag = $('#filehandler');
  drag.on('dragenter', dragEnter);
  drag.on('dragover', dragOver);
  drag.on('drop', dropIn);
  $(document).on('dragenter', docDragEnter);
  $(document).on('dragover', docDragOver);
  $(document).on('drop', docDropIn);
}

$(initPage);