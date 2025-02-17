/*global $, getParameterByName, CKEDITOR*/
function saveDone(jqXHR) {
  if(jqXHR.status === 200) {
    location.reload();
  } else {
    alert('Unable to save data!');
    console.log(jqXHR);
  }
}

function save(editor) {
  // The default removes line breaks which messes with our ability to do things like use these emails as plain text...
  // so work around it...
  let text = null;
  let documentView = document.getElementsByClassName('ck-source-editing-area');
  if(documentView.length !== 0) {
    text = documentView[0].dataset.value;
  }
  if(text === null) {
    // This kinda sucks and makes it unreadable but it should work...
    text = editor.getData();
  }
  let ticketTextType = document.getElementById('ticketTextName');
  fetch('../api/v1/globals/long_text/'+ticketTextType.value, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'text/html'
    },
    body: text
  }).then((response) => {
    if(response.ok) {
      return;
    }
    alert('Unable to save data!');
    console.log(response);
  });
}

function ticketTextChanged(elem, editor) {
  fetch('../api/v1/globals/long_text/'+elem.value).then((response) => {
    if(!response.ok) {
      return;
    }
    response.json().then((data) => {
      editor.setData(data.value);
    });
  });
}

function pageInit() {
  const {
    ClassicEditor,
    Essentials,
    Bold,
    Italic,
    Font,
    Paragraph,
    SourceEditing,
    Style,
    GeneralHtmlSupport,
    Link,
    Plugin,
    ButtonView
  } = CKEDITOR;
  class mySavePlugin extends Plugin {
    init() {
      const editor = this.editor;
      editor.ui.componentFactory.add('save', () => {
        const view = new ButtonView();
        view.set({
          label: 'Save',
          icon: '<svg fill="none" height="20" viewBox="0 0 20 20" width="20" xmlns="http://www.w3.org/2000/svg"><path d="m3 5c0-1.10457.89543-2 2-2h8.3787c.5304 0 1.0391.21071 1.4142.58579l1.6213 1.62132c.3751.37507.5858.88378.5858 1.41421v8.37868c0 1.1046-.8954 2-2 2h-10c-1.10457 0-2-.8954-2-2zm2-1c-.55228 0-1 .44772-1 1v10c0 .5523.44772 1 1 1v-4.5c0-.8284.67157-1.5 1.5-1.5h7c.8284 0 1.5.6716 1.5 1.5v4.5c.5523 0 1-.4477 1-1v-8.37868c0-.26522-.1054-.51957-.2929-.70711l-1.6213-1.62132c-.1875-.18753-.4419-.29289-.7071-.29289h-.3787v2.5c0 .82843-.6716 1.5-1.5 1.5h-4c-.82843 0-1.5-.67157-1.5-1.5v-2.5zm2 0v2.5c0 .27614.22386.5.5.5h4c.2761 0 .5-.22386.5-.5v-2.5zm7 12v-4.5c0-.2761-.2239-.5-.5-.5h-7c-.27614 0-.5.2239-.5.5v4.5z" fill="#212121"/></svg>'
        });
        view.on('execute', () => {
          save(editor);
        });
        return view;
      });
    }
  }
  ClassicEditor.create(document.querySelector('#pdf-source'), {
    licenseKey: 'GPL',
    plugins: [Essentials, Bold, Italic, Font, Paragraph, SourceEditing, GeneralHtmlSupport, Style, Link, mySavePlugin],
    toolbar: ['save', 'undo', 'redo', '|', 'bold', 'italic', '|', 'sourceEditing', '|', 'style', 'fontColor', 'fontSize', 'fontFamily', '|', 'link'],
    htmlSupport: {
      allow: [
        {
          name: /.*/,
          attributes: true,
          classes: true,
          styles: true
        }
      ]
    }
  }).then((editor) => {
    ticketTextType.addEventListener('change', () => {
      ticketTextChanged(ticketTextType, editor);
    });
    ticketTextChanged(ticketTextType, editor);
  });
  let type = getParameterByName('type');
  let ticketTextType = document.getElementById('ticketTextName');
  if(type !== null) {
    ticketTextType.value = type;
  }
}

window.addEventListener('load', pageInit);
