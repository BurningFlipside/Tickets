function AddSearchToTable(tabulatorElem) {
  let parent = tabulatorElem.parentElement;
  let tabTable = Tabulator.findTable(tabulatorElem)[0];
  let node = document.createElement('div');
  node.style.float = 'right';
  node.style.textAlign = 'right';
  let search = document.createElement('input');
  search.setAttribute('type', 'text');
  search.setAttribute('placeholder', 'Search');
  search.style.border = '1px solid #aaa';
  search.style.borderRadius = '5px';
  search.style.padding = '2px';
  node.appendChild(search);
  parent.insertBefore(node, tabulatorElem);
  search.addEventListener('input', () => {
    doSearch(tabTable, search.value.toLowerCase());
  }, {passive: true});
}

async function doSearch(tabTable, searchValue) {
  tabTable.clearFilter();
  if(searchValue === '') {
    return;
  }
  tabTable.setFilter((data) => {
    for(let key in data) {
      if(data[`${key}`] === null || data[`${key}`] === undefined) {
        continue;
      }
      if(data[`${key}`].toString().toLowerCase().includes(searchValue)) {
        return true;
      }
    }
    return false;
  });
}