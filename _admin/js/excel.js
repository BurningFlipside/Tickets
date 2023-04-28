/*global $, TableToExcel*/
function makeTablesExportable() {
  var tables = $('table');
  for(let table of tables) {
    var btn = $('<button/>', {type: 'button', 'class': 'btn btn-link'});
    btn.append('<i class="fas fa-file-excel"></i>');
    btn.click(doExport.bind(table));
    btn.appendTo(table);
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
