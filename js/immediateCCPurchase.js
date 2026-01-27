function initPage() {
  fetch('api/v1/tickets/Actions/CanPurchase').then(response => {
    if (response.ok) {
      response.json().then(data => {
        canBuyTypes = [];
        for(const key in data) {
          if (data[key] !== 0) {
            canBuyTypes.push(key);
          }
        }
        if (canBuyTypes.length === 0) {
          alert('You have already purchased the maximum allowed tickets.');
          window.location.href = 'index.php';
        }
        let container = document.getElementById('content');
        if(!canBuyTypes.includes('A')) {
          addNotification(container, 'You have already purchased the maximum amount of adult tickets you can for this account.', NOTIFICATION_WARNING);
        }
        for (const type of canBuyTypes) {
          addTypeToTable(type, canBuyTypes[type]);
        }
      });
    }
  });
}

function addTypeToTable(type, max) {
  let tableElem = document.getElementById('ticketTable');
  let row = document.createElement('tr');
  let typeElem = document.createElement('td');
  let cost = "$197";
  switch(type) {
    case 'A':
      typeElem.textContent = 'Adult';
      break;
    case 'C':
      typeElem.textContent = 'Child (0-6)';
      cost = "$0";
      break;
    case 'K':
      typeElem.textContent = 'Kid (7-13)';
      cost = "$47";
      break;
    case 'T':
      typeElem.textContent = 'Teen (14-17)';
      break;
  }
  tableElem.appendChild(row);
}

window.addEventListener('load', initPage);