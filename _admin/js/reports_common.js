function initPage() {
  GoogleIsLoggedIn().then((loggedIn) => {
    if(!loggedIn) {
      return;
    }
    let listItems = document.querySelectorAll('.container-fluid li');
    for(let listItem of listItems) {
      let button = document.createElement('button');
      button.setAttribute('type', 'button');
      button.setAttribute('class', 'btn btn-link');
      button.setAttribute('title', 'Export to Google Sheet');
      button.innerHTML = 'Google Sheet';
      let name = listItem.innerText;
      name = name.substring(0, name.indexOf(':'));
      let href = listItem.querySelector('a').href;
      let url = new URL(href);
      url.searchParams.delete('fmt');
      url.searchParams.delete('$format');
      button.addEventListener('click', exportFromBackendToGoogle.bind({url: url, name: name}));
      let textItem = document.createTextNode(' | ');
      listItem.appendChild(textItem);
      listItem.appendChild(button);
    }
  });
}

window.addEventListener('load', initPage);