async function GoogleIsLoggedIn() {
  return new Promise((resolve) => {
    fetch('../api/v1/google/loginStatus').then((response) => {
      if(!response.ok) {
        resolve(false);
        return;
      }
      response.json().then((data) => {
        if(data.loggedIn) {
          resolve(true);
        }
        resolve(false);
      });
    });
  });
}

async function AddExportToDriveToTables() {
  GoogleIsLoggedIn().then((loggedIn) => {
    if(!loggedIn) {
      return;
    }
    let tables = document.querySelectorAll('table');
    for(let table of tables) {
      let parent = table.parentElement;
      let button = document.createElement('button');
      button.setAttribute('type', 'button');
      button.setAttribute('class', 'btn btn-link');
      button.setAttribute('title', 'Export to Google Sheet');
      button.innerHTML = '<i class="fab fa-google-drive"></i>';
      button.addEventListener('click', doGoogleExport.bind(table));
      parent.appendChild(button);
    }
  });
}

async function AddExportToDriveToTabulatorInstance(tabulator, btnContainer) {
  GoogleIsLoggedIn().then((loggedIn) => {
    if(!loggedIn) {
      return;
    }
    if(btnContainer === undefined) {
      parent = tabulator.element.parentElement;
    }
    let button = document.createElement('button');
    button.setAttribute('type', 'button');
    button.setAttribute('class', 'btn btn-link');
    button.setAttribute('title', 'Export to Google Sheet');
    button.innerHTML = '<i class="fab fa-google-drive"></i>';
    button.addEventListener('click', doGoogleExportTabulator.bind(tabulator));
    btnContainer.appendChild(button);
  });

}

function tableToArray(table) {
  let result = [];
  let rows = table.rows;
  let cells, t;
  for(let i = 0; i < rows.length; i++) {
    cells = rows[i].cells;
    t = [];
    for(let j = 0; j < cells.length; j++) {
      t.push(cells[j].textContent);
    }
    result.push(t);
  }
  return result;
}

function doGoogleExport(data, name) {
  if(data === undefined) {
    data = tableToArray(this);
    name = this.id;
  }
  fetch('../api/v1/google/user/spreadsheets', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      data: data,
      name: name,
    }),
  }).then((response) => {
    if(!response.ok) {
      alert('Failed to export to Google Drive!');
    }
    return response.json();
  }).then((data) => {
    window.open('https://docs.google.com/spreadsheets/d/'+data.id, '_blank');
  });
}

function doGoogleExportTabulator() {
  let table = this;
  let data = table.getData();
  let tableData = getTableFromJson(data);
  doGoogleExport(tableData, table.element.id);
}

function exportFromBackendToGoogle() {
  fetch(this.url).then((response) => {
    if(!response.ok) {
      alert('Failed to get data!');
      return;
    }
    return response.json();
  }).then((data) => {
    // Reconstruct the data into a table style array...
    let tableData = getTableFromJson(data);
    doGoogleExport(tableData, this.name);
  });
}

function getTableFromJson(data) {
  if(data.length === 0) {
    return [];
  }
  let keys = Object.keys(data[0]);
  for(let obj of data) {
    // Merge the keys and get rid of duplicates...
    keys = [...new Set([...keys, ...Object.keys(obj)])];
  }
  let table = [];
  table.push(keys);
  for(let obj of data) {
    let row = [];
    for(let key of keys) {
      if(obj[key] === undefined || obj[key] === null) {
        row.push('');
        continue;
      }
      if(Array.isArray(obj[key])) {
        row.push(JSON.stringify(obj[key]));
        continue;
      }
      if(typeof obj[key] === 'object') {
        row.push(JSON.stringify(obj[key]));
        continue;
      }
      row.push(obj[key]);
    }
    table.push(row);
  }
  return table;
}

window.addEventListener('load', AddExportToDriveToTables);