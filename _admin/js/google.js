function writePageForLoggedInUser(data) {
  let googleLink = document.getElementById('googleLink');
  googleLink.innerHTML = 'You are logged into Google on the Ticket System.';
  googleLink.innerHTML += '<br/><img src="'+data.user.picture+'" style="border-radius: 50%;"/>'+data.user.name+' ('+data.user.email+')';
  document.getElementById('actions').style.visibility = 'visible';
}

function initPage() {
  fetch('../api/v1/google/loginStatus').then((response) => {
    let googleLink = document.getElementById('googleLink');
    if(response.status === 401) {
      googleLink.innerHTML = 'Log in to the volunteer system before attempting to use this page.';
      return;
    }
    response.json().then((data) => {
      if(data.loggedIn) {
        writePageForLoggedInUser(data);
        return;
      }
      fetch('../api/v1/google/loginLink').then((linkResponse) => {
        linkResponse.json().then((linkData) => {
          googleLink.innerHTML = `<a href="${linkData.uri}">Log in to Google</a>`;
        });
      });
    });
  });
}

window.addEventListener('load', initPage);