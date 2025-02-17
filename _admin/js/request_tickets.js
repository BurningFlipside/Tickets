/*global Tabulator*/

function changeYear(control) {
  let year = control.value;
  let tables = Tabulator.findTable('#tickets');
  if(tables === false) {
    return;
  }
  let table = tables[0];
  table.setData('../api/v1/requests_w_tickets?$filter=year eq '+year+'&$select=request_id,first,last,type');
}

function initPage() {
  fetch('../api/v1/globals/years').then((response) => {
    if(!response.ok) {
      return;
    }
    response.json().then((years) => {
      years.sort().reverse();
      let yearSelect = document.getElementById('year');
      for(let year of years) {
        if(yearSelect.options.length === 0) {
          yearSelect.add(new Option(year, year, true, true));
        } else {
          yearSelect.add(new Option(year, year));
        }
      }
      changeYear(yearSelect);
    });
  });
  fetch('../api/v1/globals/ticket_types').then((response) => {
    if(!response.ok) {
      return;
    }
    response.json().then((types) => {
      new Tabulator('#tickets', {
        pagination: 'local',
        paginationSize: 10,
        columns: [
          {title: 'Request ID', field: 'request_id'},
          {title: 'First Name', field: 'first'},
          {title: 'Last Name', field: 'last'},
          {title: 'Type', field: 'type', width: 100, formatter: (cell) => {
            console.log(types);
            return types.find((type) => type.typeCode === cell.getValue()).description;
          }}
        ]
      });
    });
  
  });
}

window.onload = initPage;
