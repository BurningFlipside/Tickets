/*global Tabulator*/
/*exported exportCSV*/

function initTable(table) {
  let tabulator = new Tabulator(table, {
    ajaxURL: '../api/v1/requests?$filter=year%20eq%20current%20and%20private_status%20eq%203',
    columns: [
      {title: 'Request ID', field: 'request_id'},
      {title: 'Private Status', field: 'private_status'},
      {title: 'Total Due', field: 'total_due', sorter: 'number'},
      {title: 'Total Received', field: 'total_received', sorter: 'number'},
      {title: 'Comments', field: 'comments'},
      {title: 'Bucket', field: 'bucket'},
      {title: 'Critical', field: 'crit_vol', formatter: 'tickCross'},
    ],
    printAsHtml: true,
    pagination: 'local',
    paginationSize: 10,
    paginationSizeSelector: [10, 25, 50, 100]
  });
  tabulator.on('tableBuilt', () => {
    tabulator.setData();
  });
}

function expandTable(table) {
  let tabulator = Tabulator.findTable('#'+table.id)[0];
  let footers = document.querySelectorAll('.tabulator-paginator');
  footers.forEach(footer => footer.style.display = 'none');
  tabulator.setPageSize(1000);
}

function beforePrint(tables) {
  tables.forEach(expandTable);
  let navs = document.getElementsByClassName('navbar-sidenav');
  for (let nav of navs) {
    nav.style.display = 'none';
  }
  let buttons = document.getElementsByClassName('fa');
  for (let button of buttons) {
    button.style.display = 'none';
  }
}

function afterPrint() {
  location.reload();
}

function exportCSV() {
  let tabulator = Tabulator.findTable('#invalid')[0];
  tabulator.download('csv', 'invalid.csv');
}

function initPage() {
  let tables = document.querySelectorAll('table');
  tables.forEach(initTable);
  window.addEventListener('beforeprint', () => {
    beforePrint(tables);
  });
  window.addEventListener('afterprint', () => {
    afterPrint(tables);
  });
}

window.onload = initPage;
