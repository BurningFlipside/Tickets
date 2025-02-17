/*exported genTickets*/
function genTickets() {
  let totalCount = 0;
  let elements = document.querySelectorAll('#additional [type="number"]');
  let genForm = document.getElementById('gen_form');
  let obj = Object.fromEntries(new FormData(genForm));
  obj.types = {};
  for(let element of elements) {
    totalCount += 1*element.value;
    obj.types[element.id] = 1*element.value;
  }
  if(totalCount === 0) {
    alert('No additional tickets created!');
    return false;
  }
  fetch('../api/v1/tickets/Actions/GenerateTickets', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(obj)
  }).then(response => {
    if(!response.ok) {
      alert('Failed to generate tickets');
      return;
    }
    response.json().then(data => {
      let str = 'Created '+data.passed+' tickets\n';
      if(data.failed > 0) {
        str += 'Failed to create '+data.failed+' tickets';
      }
      alert(str);
      location.reload();
    });
  });
  return false;
}

function initPage() {
  fetch('../api/v1/tickets/types').then(response => {
    if(!response.ok) {
      return;
    }
    response.json().then(data => {
      let currentTable = document.getElementById('current');
      let additionalTable = document.getElementById('additional');
      currentTable = currentTable.tBodies[0];
      additionalTable = additionalTable.tBodies[0];
      for(let type of data) {
        let row = currentTable.insertRow();
        let row1 = additionalTable.insertRow();
        let cell = row.insertCell();
        let cell1 = row1.insertCell();
        cell.textContent = type.description;
        cell1.textContent = type.description;
        cell = row.insertCell();
        cell1 = row1.insertCell();
        cell.id = type.typeCode+'Current';
        let input = document.createElement('input');
        input.type = 'number';
        input.id = type.typeCode;
        input.value = 0;
        input.className = 'form-control';
        cell1.appendChild(input);
        fetch('../api/v1/tickets?$filter=year eq current and type eq \''+type.typeCode+'\'&$count=true&$select=@odata.count').then(typeResponse => {
          if(!typeResponse.ok) {
            return;
          }
          typeResponse.json().then(typeData => {
            document.getElementById(type.typeCode+'Current').textContent = typeData['@odata.count'];
          });
        });
      }
    });
  });
}

window.onload = initPage;
