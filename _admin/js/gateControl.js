function earlyEntryChanged(ev) {
  let value = ev.target.value;
  fetch('../api/v1/globals/vars/currentEarlyEntry', {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(value)
  }).then(function(response) {
    if(response.status !== 200) {
      console.log(response);
      alert('Failed to set Early Entry status!');
      return;
    }
    location.reload();
  });
}

function getCurrent() {
  fetch('../api/v1/globals/vars/currentEarlyEntry').then(function(response) {
    response.json().then(function(data) {
      let select = document.getElementById('currentEarlyEntry');
      select.addEventListener('change', earlyEntryChanged);
      if(data.value !== undefined) {
        select.value = data.value;
        return;
      }
      select.value = data;
    });
  });
}

function initPage() {
  fetch('../api/v1/earlyEntry').then(function(response) {
    if(response.httpStatus === 401) {
      return;
    }
    response.json().then(function(data) {
      let select = document.getElementById('currentEarlyEntry');
      for(let ee of data) {
        let option = new Option(ee.earlyEntryDescription, ee.earlyEntrySetting);
        select.add(option);
      }
      getCurrent();
    });
  });
}

window.onload = initPage;