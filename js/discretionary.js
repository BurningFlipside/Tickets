/* global $, browser_supports_font_face, getTicketDataByHash */
/* exported cancelTransfer, sellTicket */
function sellTicket(control) {
  var jq = $(control);
  var id = jq.attr('for');
  var ticket = getTicketDataByHash(id);
  if(ticket === null) {
    alert('Cannot find ticket');
    return;
  }
  window.location = '_admin/pos.php?id='+ticket.hash;
}

function makeDiscretionaryAction(data) {
  var res = '';
  var viewOptions = {class: 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'View Ticket Code', for: data, onclick: 'view_ticket(this)'};
  var pdfOptions =  {class: 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'Download PDF', for: data, onclick: 'download_ticket(this)'};
  var sellOptions = {class: 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'Sell Ticket<br/>Use this option to sell<br/>the ticket to someone else', 'data-html': true, for: data, onclick: 'sellTicket(this)'};
  if(browser_supports_font_face()) {
    if($(window).width() < 768) {
      viewOptions.type = 'button';
      var button = $('<button/>', viewOptions);
      var glyph = $('<span/>', {class: 'fa fa-search'});
      glyph.appendTo(button);
      res += button.prop('outerHTML');
    }
    pdfOptions.type = 'button';
    button = $('<button/>', pdfOptions);
    glyph = $('<span/>', {class: 'fa fa-download'});
    glyph.appendTo(button);
    if(button.prop('outerHTML') === undefined) {
      res += new XMLSerializer().serializeToString(button[0]);
    } else {
      res += button.prop('outerHTML');
    }

    var rand = Math.floor(Math.random() * 7);

    sellOptions.type = 'button';
    button = $('<button/>', sellOptions);
    switch(rand) {
      case 0:
        glyph = $('<span/>', {class: 'fa fa-dollar-sign'});
        break;
      case 1:
        glyph = $('<span/>', {class: 'fa fa-euro-sign'});
        break;
      case 2:
        glyph = $('<span/>', {class: 'fa fa-yen-sign'});
        break;
      case 3:
        glyph = $('<span/>', {class: 'fa fa-pound-sign'});
        break;
      case 4:
        glyph = $('<span/>', {class: 'fab fa-bitcoin'});
        break;
      case 5:
        glyph = $('<span/>', {class: 'fa fa-lira-sign'});
        break;
      case 6:
        glyph = $('<span/>', {class: 'fa fa-ruble-sign'});
        break;
    }
    glyph.appendTo(button);
    if(button.prop('outerHTML') === undefined) {
      res += new XMLSerializer().serializeToString(button[0]);
    } else {
      res += button.prop('outerHTML');
    }
  } else {
    if($(window).width() < 768) {
      var link = $('<a/>', viewOptions);
      link.append('View');
      res += link.prop('outerHTML');
      res += '|';
    }
    link = $('<a/>', pdfOptions);
    link.append('Download');
    res += link.prop('outerHTML');
    res += '|';

    link = $('<a/>', sellOptions);
    link.append('Sell');
    res += link.prop('outerHTML');
  }
  return res;
}

function cancelDone(jqXHR) {
  if(jqXHR.status !== 200) {
    console.log(jqXHR);
    alert('Failed to cancel transfer!');
    return;
  }
  location.reload();
}

function cancelTransfer(longId) {
  $.ajax({
    url: 'api/v1/ticket/'+longId+'/Actions/Ticket.SpinHash',
    method: 'POST',
    contentType: 'application/json',
    complete: cancelDone
  });
}

function createOverlay(index, value) {
  var row = $(value);
  var div = $('<div>');
  let linkText = row[0].cells[3].children[0].attributes.onclick.nodeValue;
  let longId = linkText.substring(linkText.indexOf("'")+1, linkText.lastIndexOf("'"));
  div.append('<a href="#" onclick="cancelTransfer(\''+longId+'\')">Cancel Transfer</a>');
  div.css({
    position: 'absolute',
    'background-color': '#C0C0C0',
    'top': row[0].offsetTop,
    'left': row[0].offsetLeft,
    width: row.width(),
    height: row.height(),
    opacity: 0.8,
    'text-align': 'center'
  });
  $('#discretionary_wrapper').append(div);
}

function dTableDrawComplete() {
  if($('#discretionary').DataTable().data().length !== 0) {
    $('#discretionary_set').show();
  }
  if($(window).width() < 768) {
    $('#discretionary th:nth-child(1)').hide();
    $('#discretionary td:nth-child(1)').hide();
    $('#discretionary th:nth-child(2)').hide();
    $('#discretionary td:nth-child(2)').hide();
  }
  $.each($('.transferInProgress'), createOverlay);
}

function rowCreated(row, data) {
  if(data.transferInProgress === '1') {
    $(row).addClass('transferInProgress');
  }
}

function shortHashDisc(data) {
  return '<a href="#" onclick="showLongId(\''+data+'\')">'+data.substring(0,8)+'</a>';
}

function initDiscretionaryTable() {
  $('#discretionary').dataTable({
    'ajax': 'api/v1/ticket/discretionary?fmt=data-table',
    'createdRow': rowCreated,
    columns: [
      {'data': 'firstName'},
      {'data': 'lastName'},
      {'data': 'type'},
      {'data': 'hash', 'render': shortHashDisc},
      {'data': 'hash', 'render': makeDiscretionaryAction, 'class': 'action-buttons', 'orderable': false}
    ],
    paging: false,
    info: false,
    searching: false
  });

  $('#discretionary').on('draw.dt', dTableDrawComplete);
}

function initDiscretionary() {
  if($('#discretionary').dataTable === undefined) {
    setTimeout(initDiscretionary, 100);
    return;
  }
  initDiscretionaryTable();
}

$(initDiscretionary);
