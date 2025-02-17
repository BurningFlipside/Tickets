/* global getTicketDataByHash, Tabulator */
/* exported cancelTransfer, sellTicket */
function sellTicket(control) {
  let id = control.getAttribute('for');
  var ticket = getTicketDataByHash(id);
  if(ticket === null) {
    alert('Cannot find ticket');
    return;
  }
  window.location = '_admin/pos.php?id='+ticket.hash;
}

function makeDiscretionaryAction(cell, type, fullTicket) {
  let data = cell.getValue();
  let res = '';
  let sellOptions = {title: 'Sell Ticket<br/>Use this option to sell<br/>the ticket to someone else', 'data-bs-html': true, for: data, onclick: 'sellTicket(this)'};
  sellOptions['data-bs-title'] = sellOptions.title;
  if(fullTicket.transferInProgress === 1) {
    return 'Ticket Sale in Progress';
  }
  if(window.innerWidth < 768) {
    let button = document.createElement('button');
    button.className = 'btn btn-link btn-sm';
    button.setAttribute('type', 'button');
    button.setAttribute('data-bs-toggle', 'tooltip');
    button.setAttribute('data-bs-placement', 'top');
    button.setAttribute('title', 'View Ticket Code');
    button.setAttribute('for', data);
    button.setAttribute('onclick', 'viewTicket(this)');
    let glyph = document.createElement('span');
    glyph.className = 'fa fa-search';
    button.appendChild(glyph);
    res += button.outerHTML;
  }
  let button = document.createElement('button');
  button.className = 'btn btn-link btn-sm';
  button.setAttribute('type', 'button');
  button.setAttribute('data-bs-toggle', 'tooltip');
  button.setAttribute('data-bs-placement', 'top');
  button.setAttribute('title', 'Download PDF');
  button.setAttribute('for', data);
  button.setAttribute('onclick', 'downloadTicket(this)');
  let glyph = document.createElement('span');
  glyph.className = 'fa fa-download';
  button.appendChild(glyph);
  res += button.outerHTML;
  
  let rand = Math.floor(Math.random() * 7);
  button = document.createElement('button');
  button.className = 'btn btn-link btn-sm';
  button.setAttribute('type', 'button');
  button.setAttribute('data-bs-toggle', 'tooltip');
  button.setAttribute('data-bs-placement', 'top');
  button.setAttribute('title', 'Sell Ticket<br/>Use this option to sell<br/>the ticket to someone else');
  button.setAttribute('data-bs-html', 'true');
  button.setAttribute('for', data);
  //button.setAttribute('onclick', 'sellTicket(this)');
  button.setAttribute('onclick', 'editTicket(this)');
  glyph = document.createElement('span');
  switch(rand) {
    case 0:
      glyph.className = 'fa fa-dollar-sign';
      break;
    case 1:
      glyph.className = 'fa fa-euro-sign';
      break;
    case 2:
      glyph.className = 'fa fa-yen-sign';
      break;
    case 3:
      glyph.className = 'fa fa-pound-sign';
      break;
    case 4:
      glyph.className = 'fab fa-bitcoin';
      break;
    case 5:
      glyph.className = 'fa fa-lira-sign';
      break;
    case 6:
      glyph.className = 'fa fa-ruble-sign';
      break;
  }
  button.appendChild(glyph);
  res += button.outerHTML;
  return res;
}

function cancelTransfer(longId) {
  fetch('api/v1/ticket/'+longId+'/Actions/Ticket.SpinHash', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    }
  }).then((response) => {
    if(!response.ok) {
      console.error(response);
      alert('Failed to cancel transfer!');
      return;
    }
    location.reload();
  });
}

function createOverlay(row) {
  let div = document.createElement('div');
  let data = row.getData();
  let longId = data.hash;
  div.id = 'overlay_'+longId;
  let existingDiv = document.getElementById(div.id);
  if(existingDiv !== null) {
    existingDiv.remove();
  }
  let link = document.createElement('a');
  link.href = '#';
  link.setAttribute('onclick', 'cancelTransfer(\''+longId+'\')');
  link.innerText = 'Cancel Transfer';
  div.append(link);
  let rowElement = row.getElement();
  div.style.position = 'absolute';
  div.style.backgroundColor = '#C0C0C0';
  div.style.top = rowElement.offsetTop+'px';
  div.style.left = rowElement.offsetLeft+'px';
  div.style.width = '100%';
  div.style.height = rowElement.offsetHeight+'px';
  div.style.opacity = 0.8;
  div.style.textAlign = 'center';
  div.style.verticalAlign = 'middle';
  let wrapper = document.getElementById('discretionary').getElementsByClassName('tabulator-tableholder');
  wrapper[0].appendChild(div);
}

function dTableDrawComplete(data) {
  if(data.length === 0) {
    return;
  }
  document.getElementById('discretionary_set').style.removeProperty('display');
  if(window.innerWidth < 768) {
    let tables = Tabulator.findTable('#discretionary');
    if(tables === false) {
      return;
    }
    tables[0].hideColumn('firstName');
    tables[0].hideColumn('lastName');
  }
}

function shortHashDisc(cell) {
  let data = cell.getValue();
  return '<a href="#" onclick="showLongId(\''+data+'\')">'+data.substring(0,8)+'</a>';
}

function initDiscretionary() {
  let table = new Tabulator('#discretionary', {
    ajaxURL: 'api/v1/ticket/discretionary',
    layout: 'fitColumns',
    columns: [
      {title: 'First Name', field: 'firstName'},
      {title: 'Last Name', field: 'lastName'},
      {title: 'Type', field: 'type', width: 70},
      {title: 'Short Ticket Code', field: 'hash', formatter: shortHashDisc, width: 160},
      {title: '', field: 'hash', formatter: makeDiscretionaryAction, width: 80},
    ],
    rowFormatter: function(row) {
      let data = row.getData();
      if(data.transferInProgress === 1) {
        row.getElement().classList.add('transferInProgress');
        createOverlay(row);
      }
    }
  });
  table.on('tableBuilt', () => {
    table.setData();
  });
  table.on('dataProcessed', dTableDrawComplete);
}

window.addEventListener('load', initDiscretionary);
