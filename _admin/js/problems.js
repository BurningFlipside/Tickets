/*global Tabulator*/
/*exported exportCSV*/

function initTable(table) {
  let view = '../api/v1/requests/problems/'+table.id;
  let tabulator = new Tabulator(table, {
    ajaxURL: view,
    columns: [
      {title: 'Request ID', field: 'request_id', formatter: 'link', formatterParams: {urlPrefix: 'requests.php?id=', labelField: 'request_id'}},
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
  let buttons = document.getElementsByClassName('fas');
  for (let button of buttons) {
    button.style.display = 'none';
  }
}

function exportCSV(view) {
  let tabulator = Tabulator.findTable('#'+view)[0];
  tabulator.download('csv', view+'.csv');
}

function initPage() {
  let tables = document.querySelectorAll('table');
  tables.forEach(initTable);
  window.addEventListener('beforeprint', () => {
    beforePrint(tables);
  });
  window.addEventListener('afterprint', () => {
    location.reload();
  });
}

window.onload = initPage;
