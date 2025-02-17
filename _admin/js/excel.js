/*global $, TableToExcel*/
function makeTablesExportable() {
  let tables = document.getElementsByTagName('table');
  for(let table of tables) {
    let parent = table.parentElement;
    let button = document.createElement('button');
    button.setAttribute('type', 'button');
    button.setAttribute('class', 'btn btn-link');
    button.innerHTML = '<i class="fas fa-file-excel"></i>';
    button.addEventListener('click', doExport.bind(table));
    parent.appendChild(button);
  }
}

function doExport() {
  TableToExcel.convert(this, {
    name: this.id+'.xlsx',
    sheet: {
      name: 'Sheet 1'
    }
  });
}

$(makeTablesExportable);
