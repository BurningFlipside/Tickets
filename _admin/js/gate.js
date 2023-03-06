/*global $, add_notification, getParameterByName, NOTIFICATION_FAILED, PDFLib*/
/*exported nextTicket, prevTicket, processHistoryTicket, processTicket, submitClick*/
var historyData = null;
var earlyEntry;
var waiverMode = null;

function submitClick(pdfInstance) {
  let promises = [];
  promises.push(pdfInstance.getFormFields());
  promises.push(pdfInstance.exportInstantJSON());
  Promise.allSettled(promises).then((results) => {
    let formFields = results.shift().value;
    let pdfJson = results.shift().value;
    let sigCount = 0;
    for(let field of formFields.toJS()) {
      if(field.required) {
        if(field.value !== undefined) {
          if(field.value.trim().length === 0) {
            alert('Waivers Incomplete! Missing required field '+field.label);
            return;
          }
          continue;
        }
        sigCount++;
      }
    }
    if(pdfJson.annotations === undefined || sigCount > pdfJson.annotations.length) {
      alert('Waivers Incomplete! Missing at least one signature!');
      return;
    }
    //If we got here all the fields are populated.
    pdfInstance.exportPDF({flatten: true}).then((buffer) => {
      console.log(buffer);
      $('#waiverModal').modal('hide');
    });
  });
}

function displayPDF(location, pdfSrc) {
  let pdfjsLib = window['pdfjs-dist/build/pdf'];
  pdfjsLib.GlobalWorkerOptions.workerSrc = '//cdn.jsdelivr.net/npm/pdfjs-dist@2.13.216/build/pdf.worker.js';
  let loadingTask = pdfjsLib.getDocument({data: atob(pdfSrc)});
  let canvas = document.createElement('canvas');
  let overlay = document.createElement('div');
  canvas.style = 'position:absolute; left:0; top:0; z-index:1';
  $(location).append(canvas);
  $(location).append(overlay);
  loadingTask.promise.then((pdf) => {
    pdf.getPage(1).then((page) => {
      let viewport = page.getViewport({scale: 1.0});
      let context = canvas.getContext('2d');
      let renderContext = {canvasContext: context, viewport: viewport, annotationMode: pdfjsLib.AnnotationMode.ENABLE_STORAGE};
      canvas.width = viewport.width;
      canvas.height = viewport.height;
      overlay.style = 'position:absolute; left:0; top:0; width: '+viewport.width+'px; height: '+viewport.height+'px; z-index:2; overflow: hidden;';
      page.render(renderContext);
      page.getAnnotations().then((annotations) => {
        let unfilledAnnotations = [];
        for(let annotation of annotations) {
          if(annotation.fieldValue !== null && annotation.fieldValue.trim().length > 0) {
            //This is an already populated field...
            continue;
          }
          annotation.contentsObj.str=' ';
          unfilledAnnotations.push(annotation);
          console.log(annotation);
        }
        pdfjsLib.AnnotationLayer.render({
          viewport: viewport.clone({ dontFlip: true }),
          div: overlay,
          annotations: unfilledAnnotations,
          page: page
        });
        $(overlay).children('section').css('position','absolute').css('overflow', 'hidden');
      });
    });
  });
}

function startLocalWaivers(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Cannot determine ticket type! Reprocess ticket for waiver!');
    return;
  }
  $('#waiverPDF').empty();
  $('#waiverModal').modal('show');
  let data = jqXHR.responseJSON;
  let bundle = 'MinorBundle.pdf';
  if(data.type === 'A') {
    //Display adult waivers
    bundle = 'AdultBundle.pdf';
  }
  let today = new Date();
  let ticketID = data.physical_ticket_id;
  if(ticketID.trim().length === 0) {
    //Use the ticket short code...
    ticketID = data.hash.substring(0, 8);
  }
  fetch(bundle).then((res) => {
    res.arrayBuffer().then((buff) => {
      PDFLib.PDFDocument.load(buff).then((pdfDoc) => {
        let form = pdfDoc.getForm();
        form.getTextField('TicketID').setText(ticketID);
        form.getTextField('SigDate').setText((today.getMonth()+1)+'/'+(today.getDate())+'/'+today.getFullYear());
        console.log(form);
        pdfDoc.saveAsBase64().then((pdfDataUri) => {
          displayPDF('#waiverPDF', pdfDataUri);
        });
      });
    });
  });
/*
    
    PSPDFKit.unload('#waiverPDF');
    
    PSPDFKit.load({
      document: bundle,
      container: '#waiverPDF'
    }).then((instance) => {
      let toolbar = [{type: 'pager'}, {type: 'pan'}, {type: 'zoom-out'}, {type: 'zoom-in'}, {type: 'custom', title: 'Submit', onPress: () => {submitClick(instance)}}];
      instance.setToolbarItems(toolbar);
      let today = new Date();
      let ticketID = data.physical_ticket_id;
      if(ticketID.trim().length === 0) {
          //Use the ticket short code...
          ticketID = data.hash.substring(0, 8);
      }
      instance.setFormFieldValues({
        "TicketID": ticketID,
        "SigDate": (today.getMonth()+1)+'/'+(today.getDate())+'/'+today.getFullYear()
      });
    });*/
}

function startJotFormWaivers(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Cannot determine ticket type! Reprocess ticket for waiver!');
    return;
  }
  $('#waiverModal').modal('show');
  let data = jqXHR.responseJSON;
  let urlBase = 'TODO';
  let ticketID = data.physical_ticket_id;
  if(ticketID.trim().length === 0) {
    //Use the ticket short code...
    ticketID = data.hash.substring(0, 8);
  }
  if(data.type === 'A') {
    //Display adult waivers
    urlBase = 'https://form.jotform.com/221136124870044?ticket_number_1=';
  }
  $('#waiverPDF').append('<iframe src="'+urlBase+ticketID+'" style="width: 100%; height: 100vh;"/>');
}

function finishProcessingTicket(data) {
  $('#process_ticket_modal').modal('hide');
  switch(waiverMode) {
    case 'local':
      $.ajax({
        url: '../api/v1/tickets/'+data.hash,
        complete: startLocalWaivers});
      break;
    case 'jotform':
      $.ajax({
        url: '../api/v1/tickets/'+data.hash,
        complete: startJotFormWaivers});
      break;
    default:
      console.log('waiverMode', waiverMode);
      break;
  }
  console.log(data);
}

function errorProcessingTicket(jqXHR) {
  console.log(jqXHR);
}

function processTicket() {
  var hash = $('#hash').val();
  var data = {};
  data.firstName = $('#firstName').val();
  data.lastName  = $('#lastName').val();
  if($('#void:checked').length === 0) {
    data['void'] = 0;
  } else {
    data['void'] = 1;
  }
  if($('#used:checked').length === 0) {
    data.used = 0;
  } else {
    data.used = 1;
    var date = new Date();
    data.used_dt = date.toISOString().slice(0,19).replace('T', ' '); // eslint-disable-line camelcase
  }
  if($('#guardian_first').val().length > 0) {
    data.guardian_first = $('#guardian_first').val(); // eslint-disable-line camelcase
  }
  if($('#guardian_last').val().length > 0) {
    data.guardian_last = $('#guardian_last').val(); // eslint-disable-line camelcase
  }
  data.physical_ticket_id = $('#physical_ticket_id').val(); // eslint-disable-line camelcase
  data.comments = $('#comments').val();
  data = JSON.stringify(data);
  $.ajax({
    url:  '../api/v1/tickets/'+hash,
    contentType: 'application/json',
    type: 'patch',
    dataType: 'json',
    data: data,
    processData: false,
    success: finishProcessingTicket,
    error: errorProcessingTicket
  });
}

function foundTicket(data) {
  if(data[0] !== undefined) {
    data = data[0];
  }
  $('#ticket_history_modal').modal('hide');
  $('#search_ticket_modal').modal('hide');
  console.log(data);
  $('#process_ticket_modal .modal-body .alert').remove();
  if(data.used !== '0') {
    add_notification($('#process_ticket_modal .modal-body'), 'Ticket is already used!', NOTIFICATION_FAILED, false);
  }
  if(data['void'] !== '0') {
    add_notification($('#process_ticket_modal .modal-body'), 'Ticket is void!', NOTIFICATION_FAILED, false);
    $('#void').attr('checked', true);
  } else {
    $('#void').removeAttr('checked');
  }
  if(data['earlyEntryWindow']*1 < earlyEntry) {
    add_notification($('#process_ticket_modal .modal-body'), 'Ticket is not valid for current early entry status!', NOTIFICATION_FAILED, false);
  }
  $('#used').attr('checked', true);
  $('#hash').val(data.hash);
  $('#type').val(data.type);
  $('#firstName').val(data.firstName);
  $('#lastName').val(data.lastName);
  if((data.guardian_first === null && data.guardian_last === null) ||
     (data.guardian_first === '' && data.guardian_last === '')) {
    $('#guardian_first').val('');
    $('#guardian_last').val('');
    $('#minor_block').attr('hidden', 'true');
  } else {
    $('#guardian_first').val(data.guardian_first);
    $('#guardian_last').val(data.guardian_last);
    $('#minor_block').removeAttr('hidden');
  }
  $('#physical_ticket_id').val(data.physical_ticket_id);
  $('#comments').val(data.comments);
  $('#process_ticket_modal').modal('show');
}

function processHistoryTicket() {
  if(historyData.selected === -1) {
    foundTicket(historyData.current);
  } else {
    alert('Cannot process an old ticket');
  }
}

function showHistoryFromData(data) {
  var readOnly = true;
  var ticket;
  if(data.selected === -1) {
    ticket = data.current;
    $('#right_arrow').hide();
    if(data.history !== undefined && data.history.length > 0) {
      $('#left_arrow').show();
    } else {
      $('#left_arrow').hide();
    }
    readOnly = false;
  } else {
    ticket = data.history[data.selected];
    if(data.selected === (data.history.length - 1)) {
      $('#left_arrow').hide();
    } else {
      $('#left_arrow').show();
    }
    $('#right_arrow').show();
  }
  $('#history_hash').val(ticket.hash);
  $('#history_firstName').val(ticket.firstName);
  $('#history_lastName').val(ticket.lastName);
  $('#history_email').val(ticket.email);
  $('#history_request_id').val(ticket.request_id);
  $('#history_type').val(ticket.type);
  $('#history_guardian_first').val(ticket.guardian_first);
  $('#history_guardian_last').val(ticket.guardian_last);
  $('#history_sold').val(ticket.sold);
  $('#history_used').val(ticket.used);
  $('#history_void').val(ticket['void']);
  $('#history_physical_ticket_id').val(ticket.physical_ticket_id);
  $('#history_comments').val(ticket.comments);
  if(readOnly) {
    $('#history_firstName').prop('disabled', true);
    $('#history_lastName').prop('disabled', true);
    $('#history_email').prop('disabled', true);
    $('#history_request_id').prop('disabled', true);
    $('#history_type').prop('disabled', true);
    $('#history_guardian_first').prop('disabled', true);
    $('#history_guardian_last').prop('disabled', true);
    $('#history_sold').prop('disabled', true);
    $('#history_used').prop('disabled', true);
    $('#history_void').prop('disabled', true);
    $('#history_physical_ticket_id').prop('disabled', true);
    $('#history_comments').prop('disabled', true);
    $('#process_history').prop('disabled', true);
  } else {
    $('#history_firstName').prop('disabled', false);
    $('#history_lastName').prop('disabled', false);
    $('#history_email').prop('disabled', false);
    $('#history_request_id').prop('disabled', false);
    $('#history_type').prop('disabled', false);
    $('#history_guardian_first').prop('disabled', false);
    $('#history_guardian_last').prop('disabled', false);
    $('#history_sold').prop('disabled', false);
    $('#history_used').prop('disabled', false);
    $('#history_void').prop('disabled', false);
    $('#history_physical_ticket_id').prop('disabled', false);
    $('#history_comments').prop('disabled', false);
    $('#process_history').prop('disabled', false);
  }
  $('#ticket_history_modal').modal('show');
}

function foundHistory(data) {
  historyData = data;
  showHistoryFromData(data);
}

function prevTicket() {
  historyData.selected++;
  showHistoryFromData(historyData);
}

function nextTicket() {
  historyData.selected--;
  showHistoryFromData(historyData);
}

function searchDone(data) {
  if(data.length === undefined || data.length === 0) {
    searchFailed();
    return;
  }
  var table = $('#search_ticket_table').DataTable();
  table.clear();
  for(let ticket of data) {
    table.row.add(ticket);
  }
  table.draw();
  $('#search_ticket_modal').modal('show');
}

function historySearchDone(data) {
  if(data.length === undefined || data.length === 0) {
    searchFailed();
    return;
  }
  var table = $('#history_ticket_table').DataTable();
  table.clear();
  for(let history of data) {
    table.row.add(history);
  }
  table.draw();
  $('#history_ticket_modal').modal('show');
}

function searchFailed() {
  alert('Unable to locate ticket!');
}

function processMagStripe(stripeValue) {
  var card = {};
  if(stripeValue[0] !== '%') {
    return false;
  }
  if(stripeValue[1] === 'B' || stripeValue[1] === 'b') {
    //This appears to be a credit card
    stripeValue = stripeValue.replace('%B', '');
    stripeValue = stripeValue.replace('%b', '');
    var arr = stripeValue.split('^');
        
    card.type          = 'cc';
    card.ccNumber      = arr[0];
    card.expires       = {};
    card.expires.month = arr[2].substring(2,4);
    card.expires.year  = arr[2].substring(0,2);

    var nameArr = arr[1].split('/');
    card.first  = nameArr[1];
    card.last   = nameArr[0];

    var first = card.first.split(' ');
    if(card.length > 1) {
      card.first = first[0];
      card.initial = first[1];
    }
  } else {
    //This appears to be a drivers license
    var parts = stripeValue.split('^');
    card.type  = 'dl';
    card.state = parts[0].substring(1,3);
    card.city  = parts[0].substring(3);
    if(parts.length >= 2) {
      var names = parts[1].split('$');
      card.first = names[1];
      card.last  = names[0];
      if(parts.length >= 3) {
        card.address = parts[2];
        if(parts.length >= 4) {
          var subparts = parts[3].split('=');
          card.iin = subparts[0].substring(2, 5);
          card.dlNum = subparts[0].substring(8);
          if(subparts.length >= 2) {
            card.expires       = {};
            card.expires.month = subparts[1].substring(2,4);
            card.expires.year  = subparts[1].substring(0,2);
            card.birth         = {};
            card.birth.year    = subparts[1].substring(4,8);
            card.birth.month   = subparts[1].substring(8,10);
            card.birth.day     = subparts[1].substring(10,12);
            console.log(subparts);
          }
        }
      }
    }
  }
  console.log(card);
  return card;
}

function filterFromMagStripe(stripeValue) {
  if(stripeValue[0] !== '%') {
    return false;
  }
  var card = processMagStripe(stripeValue);
  if(card.first !== undefined && card.last !== undefined) {
    return 'filter=year eq current and '+
           'substringof(firstName,\''+card.first+'\') and '+
           'substringof(lastName,\''+card.last+'\')';
  } else if(stripeValue.indexOf('%TX') === 0) {
    //This appears to be a TX drivers license
    var parts = stripeValue.split('^');
    if(parts.length > 2) {
      var names = parts[1].split('$');
      return 'filter=year eq current and '+
             'substringof(firstName,\''+names[1]+'\') and '+
             'substringof(lastName,\''+names[0]+'\')';
    }
  }
  return false;
}

function reallySearch(jqXHR) {
  var filter = false;
  if(jqXHR.status === 401) {
    location.reload();
  }
  if(this.indexOf('%') === 0) {
    filter = filterFromMagStripe(this);
  } else if(this.indexOf(' ') > -1) {
    var names = this.split(' ');
    filter = 'filter=year eq current and '+
             'substringof(firstName,\''+names[0]+'\') and '+
             'substringof(lastName,\''+names[1]+'\')';
  } else {
    filter = 'filter=year eq current and '+
             '(substringof(firstName,\''+this+'\') or '+
             'substringof(lastName,\''+this+'\') or '+
             'substringof(hash,\''+this+'\') or '+
             'substringof(email,\''+this+'\') or '+
             'substringof(request_id,\''+this+'\'))';
  }
  $.ajax({
    url:  '../api/v1/tickets',
    data: filter,
    type: 'get',
    dataType: 'json',
    success: searchDone,
    error: searchFailed
  });
}

function reallySearchHistory() {
  var filter = false;
  if(this.indexOf('%') === 0) {
    filter = filterFromMagStripe(this);
  } else if(this.indexOf(' ') > -1) {
    var names = this.split(' ');
    filter = 'filter=year eq current and '+
             'substringof(firstName,\''+names[0]+'\') and '+
             'substringof(lastName,\''+names[1]+'\')';
  } else {
    filter = 'filter=year eq current and '+
             '(substringof(firstName,\''+this+'\') or '+
             'substringof(lastName,\''+this+'\') or '+
             'substringof(hash,\''+this+'\') or '+
             'substringof(email,\''+this+'\') or '+
             'substringof(request_id,\''+this+'\'))';
  }
  $.ajax({
    url:  '../api/v1/tickets_history',
    data: filter,
    type: 'get',
    dataType: 'json',
    success: historySearchDone,
    error: searchFailed
  });
}

function getTicket(hash) {
  if(hash.indexOf('%') === 0) {
    reallySearch.call(hash);
    return;
  }
  var pos = hash.indexOf('transfer.php?id=');
  if(pos !== -1) {
    pos+=16;
    hash = hash.substring(pos);
  }
  if(hash.length === 16) {
    hash = hash.substring(0, 8)+'%25'+hash.substring(8);
    $.ajax({
      url:  '../api/v1/tickets/?$filter=contains(hash,\''+hash+'\')',
      type: 'get',
      dataType: 'json',
      context: hash,
      success: foundTicket,
      error: reallySearch
    });    
  } else {
    $.ajax({
      url:  '../api/v1/tickets/'+hash,
      type: 'get',
      dataType: 'json',
      context: hash,
      success: foundTicket,
      error: reallySearch
    });
  }
}

function getHistory(hash) {
  $('#history_ticket_modal').modal('hide');
  if(hash.indexOf('%') === 0) {
    reallySearchHistory.call(hash);
    return;
  }
  $.ajax({
    url:  '../api/v1/tickets/'+hash+'?with_history=1',
    type: 'get',
    dataType: 'json',
    context: hash,
    success: foundHistory,
    error: reallySearchHistory
  });
}

function ticketClicked() {
  var table = $('#search_ticket_table').DataTable();
  var tr = $(this).closest('tr');
  var row = table.row(tr);
  if(tr.children('th').length > 0) {
    return;
  }
  foundTicket(row.data());
}

function historyClicked() {
  var table = $('#history_ticket_table').DataTable();
  var tr = $(this).closest('tr');
  var row = table.row(tr);
  if(tr.children('th').length > 0) {
    return;
  }
  getHistory(row.data().hash);
}

function ticketSearch(evt) {
  if(evt.which !== 13) {
    return;
  }
  var value = $(this).val();
  //Try this as a ticket
  getTicket(value);
}

function historySearch(evt) {
  if(evt.which !== 13) {
    return;
  } 
  var value = $(this).val();
  //Try this as a ticket
  getHistory(value);
}

function focusOnTicketId() {
  $('#physical_ticket_id').focus();
}

function focusOnSearch() {
  $('#ticket_search').val('');
  $('#ticket_search').focus();
}

function revertScreen() {
  $('.navbar').show();
  $('#page-wrapper').css('margin', '0 0 0 250px').css('width', '').css('height', '');
  $('#screen').html('<span class="fa fa-arrows-alt"></span>').attr('title', 'fullscreen').unbind('click', revertScreen).click(fullscreen);
}

function fullscreen() {
  $('.navbar').hide();
  $('#page-wrapper').css('width', '100%').css('height', '100%').css('margin', '0');
  $('#screen').html('<span class="fa fa-compress"></span>').attr('title', 'revert').unbind('click', fullscreen).click(revertScreen);
}

function gotEarlyEntry(jqXHR) {
  if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined) {
    alert('Unable to obtain ticket current EE status!');
    return;
  }
  earlyEntry = jqXHR.responseJSON*1;
}

function enumError(err) {
  $('#ticketCodeScan').hide();
  console.log(err);
}

function initGatePage() {
  waiverMode = getParameterByName('waiverMode');
  $.ajax({
    url: '../api/v1/globals/vars/currentEarlyEntry',
    type: 'get',
    dataType: 'json',
    complete: gotEarlyEntry});
  $('#ticket_search').keypress(ticketSearch);
  $('#history_search').keypress(historySearch);
  $('#process_ticket_modal').on('shown.bs.modal', focusOnTicketId);
  $('#process_ticket_modal').on('hidden.bs.modal', focusOnSearch);
  $('#search_ticket_table').dataTable({
    'columns': [
      {'data': 'hash'},
      {'data': 'firstName'},
      {'data': 'lastName'},
      {'data': 'type'}
    ]
  });
  $('#history_ticket_table').dataTable({
    'columns': [
      {'data': 'hash'},
      {'data': 'firstName'},
      {'data': 'lastName'},
      {'data': 'type'}
    ]
  });
  $('#search_ticket_table').on('click', 'tr', ticketClicked);
  $('#history_ticket_table').on('click', 'tr', historyClicked);
  enumError(null);
}

$(initGatePage);


