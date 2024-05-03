/* global $, bootbox, getParameterByName, pdfjsViewer */
function initPage() {
  let pdfjsLib = window['pdfjs-dist/build/pdf'];
  if(pdfjsLib === undefined) {
    setTimeout(initPage, 100);
    return;
  }
  pdfjsLib.GlobalWorkerOptions.workerSrc = 'js/extern/pdf.worker.js';
  const container = document.getElementById('content');
  const eventBus = new pdfjsViewer.EventBus();
  const pdfLinkService = new pdfjsViewer.PDFLinkService({eventBus});
  const pdfViewer = new pdfjsViewer.PDFViewer({
    container,
    eventBus,
    linkService: pdfLinkService,
    onSignatureClick: signatureClicked,
  });
  let values = {
    'Ticket ID': '12345',
    'Date': (new Date()).toLocaleDateString()
  };
  pdfLinkService.setViewer(pdfViewer);
  eventBus.on('pagesinit', function () {
    // We can use pdfViewer now, e.g. let's change default scale.
    pdfViewer.currentScaleValue = 'page-width';
  });
  eventBus.on('annotationlayerrendered', () => {
    let pdf = pdfViewer.pdfDocument;
    pdf.getFieldObjects().then((fields) => {
      for(let fieldName in values) {
        if(fields[fieldName] !== undefined) {
          for(let fv of fields[fieldName]) {
            $('#pdfjs_internal_id_'+fv.id).val(values[fieldName]);
            pdf.annotationStorage.setValue(fv.id, {value: values[fieldName]});
            if(fieldName === 'Ticket ID' && getParameterByName('TicketID') !== null) {
              $('#pdfjs_internal_id_'+fv.id).prop('readonly', true);
            }
          }
        }
      }
      $('#savePDF').off('click').click(() => {
        let obj = {};
        console.log(fields);
        let requiredFields = document.getElementById('content').querySelectorAll('[required]');
        for(let requiredField of requiredFields) {
          if(requiredField.value.length === 0 && requiredField.dataset.signature === undefined) {
            console.log(requiredField);
            bootbox.alert('One or more required fields not complete!');
            requiredField.focus();
            return;
          }
        }
        obj.date = getValueByName('Date', fields);
        obj.ticketID = getValueByName('Ticket ID', fields);
        obj.name = getValueByName('Participants Printed Name', fields);
        obj.minorName = getValueByName('Minor Participants Printed Name', fields);
        pdf.saveDocument().then((data) => {
          let libTask = window.PDFLib.PDFDocument.load(data);
          libTask.then((pdfToSave) => {
            let form = pdfToSave.getForm();
            let promises = [];
            for(let field of form.getFields()) {
              promises.push(new Promise((resolve) => {
                if(!(field instanceof window.PDFLib.PDFSignature)) {
                  //The other library handled this already...
                  resolve(true);
                }
                let otherTag = field.ref.tag.split(' ');
                otherTag.splice(1,1);
                otherTag = otherTag.join('');
                let otherData = document.getElementById('pdfjs_internal_id_'+otherTag).dataset.signature;
                if(!(otherData.length > 0)) {
                  resolve(true);
                }
                let widgets = field.acroField.getWidgets();
                let pdfImageTask = pdfToSave.embedPng(otherData);
                pdfImageTask.then((pdfImage) => {
                  for(let widget of widgets) {
                    let page = pdfToSave.getPages().find(x => x.ref === widget.P());
                    let rect = widget.getRectangle();
                    pdfImage.scaleToFit(rect.width, rect.height);
                    page.drawImage(pdfImage, rect);
                  }
                  resolve(true);
                });
              }));
            }
            //Wait for all signatures to be added
            Promise.allSettled(promises);
            let pdfSaveTask = pdfToSave.save();
            pdfSaveTask.then((bytes) => {
              const blob = new Blob([bytes], { type: 'application/pdf' });
              let link = document.createElement('a');
              let url = URL.createObjectURL(blob);
              link.setAttribute('href', url);
              let name = '';
              console.log(obj);
              if(obj.ticketID !== undefined) {
                name += obj.ticketID;
              }
              if(obj.minorName !== undefined) {
                name += '_'+obj.minorName;
              } else if (obj.name !== undefined) {
                name += '_'+obj.name;
              }
              if(name.length === 0) {
                name = 'test';
              }
              name += '.pdf';
              link.setAttribute('download', name);
              link.style.visibility = 'hidden';
              let formData = new FormData();
	      let formFile = new File([blob], name, {type: 'application/pdf' });
	      formData.append('pdf', formFile);
	      fetch('../api/v1/tickets/Actions/submitWaiver', {method: 'POST', body: formData});
              document.body.appendChild(link);
              link.click();
              document.body.removeChild(link);
              if(inIframe()) {
                window.parent.postMessage('waiverDone', location.href);
              }
            });
          });
        });
      });
    });
  });
  let bundle = 'AdultBundle.pdf';
  if(getParameterByName('minor') !== null) {
    bundle = 'MinorBundle.pdf';
  }
  let ticketID = getParameterByName('TicketID');
  if(ticketID !== null) {
    values['Ticket ID'] = ticketID;
  }
  let loadingTask = pdfjsLib.getDocument({url: bundle});
  loadingTask.promise.then(function(pdf) {
    pdfViewer.setDocument(pdf);
    pdfLinkService.setDocument(pdf, null);
  });
  if(inIframe()) {
    $('#mainNav').hide();
  }
}

function getValueByName(name, fields) {
  if(fields[name] === undefined) {
    return undefined;
  }
  for(let fv of fields[name]) {
    let val = $('#pdfjs_internal_id_'+fv.id).val();
    if(val !== undefined) {
      return val;
    }
  }
  return undefined;
}

const dialogMessage = `
<div class="container" id="signOptions">
  <ul class="nav nav-tabs" id="signTabs" role="tablist">
    <li class="nav-item">
      <a class="nav-link active" id="drawTab" data-toggle="tab" href="#tab0" role="tab" aria-controls="tab0" aria-selected="true">Sign</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="typeTab" data-toggle="tab" href="#tab1" role="tab" aria-controls="tab1" aria-selected="false">Type</a>
    </li>
  </ul>
  <div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="tab0">
      <canvas id="sigCanvas" style="width: 100%; height: 300px; outline: 1.5px solid black;">
      </canvas>
    </div>
    <div role="tabpanel" class="tab-pane" id="tab1">
      <canvas id="sigCanvas2" style="width: 100%; height: 300px; outline: 1.5px solid black;">
      </canvas>
      <input id="sigType" autofocus="true"/>
    </div>
  </div>
</div>
`;

function renderSigText(e, final) {
  let currentText = $(e.currentTarget).val();
  let typeCanvas = document.getElementById('sigCanvas2');
  let context2 = typeCanvas.getContext('2d');  
  context2.clearRect(0, 0, typeCanvas.width, typeCanvas.height);
  context2.font = '50px signature';
  context2.textAlign = 'center';
  let size = 50;
  if(final) {
    let width = context2.measureText(currentText).width;
    //Need a fudge factor to handle edges of font
    while(width+50 < typeCanvas.width) {
      size += 5;
      context2.font = size+'px signature';
      width = context2.measureText(currentText).width;
    }
    while(width+50 > typeCanvas.width) {
      size -= 5;
      context2.font = size+'px signature';
      width = context2.measureText(currentText).width;
    }
  }
  context2.fillText(currentText, typeCanvas.width/2, typeCanvas.height/2);
}

function signatureClicked(ev) {
  bootbox.dialog({
    title: 'Sign Document',
    message: dialogMessage,
    size: 'xl',
    buttons: {
      ok: {
        label: 'OK',
        className: 'btn-primary',
        callback: () => {
          let data = canvas.toDataURL();
          if($('#signTabs .nav-item .active')[0].id === 'typeTab') {
            data = document.getElementById('sigCanvas2').toDataURL();
          }
          document.getElementById('pdfjs_internal_id_'+ev.pdfData.fieldId).dataset.signature = data;
          document.getElementById('pdfjs_internal_id_'+ev.pdfData.fieldId).innerHTML = '<img src="'+data+'" style="width:100%; height:100%;"/>';
        }
      },
      cancel: {
        label: 'Cancel',
        className: 'btn-danger',
        callback: () => {
          canvas.width = canvas.width;
          return;
        }
      }
    },
    onShown: (e) => {
      canvas.width = canvas.clientWidth;
      canvas.height = canvas.clientHeight;
      //Make sure the field is auto focused
      $(e.currentTarget).on('shown.bs.tab', () => {
        let activeTab = $('.tab-content .active');
        let focusedInp = $(activeTab).find('input[autofocus="true"]');
        setTimeout(function(){ 
          $(focusedInp).focus();
        }, 200);
      });
      let sigFont = new FontFace('signature', 'url(js/extern/signature.woff2)');
      sigFont.load().then((font) => {
        document.fonts.add(font);
      });
      $('#sigType').on('keydown', renderSigText);
      $('#sigType').on('change', (changeEvent) => {
        renderSigText(changeEvent, true);
      });
      document.body.style.touchAction = 'none';
    },
    onHidden: () => {
      document.body.style.touchAction = 'auto';
    },
  });
  let canvas = document.getElementById('sigCanvas');
  let ctx = canvas.getContext('2d');
  let drawing = false;
  let drawPosition = {x: 0, y: 0};
  let lastPosition = drawPosition;
  ctx.strokeStyle = '#222222';
  ctx.lineWith = 2;
  canvas.addEventListener('mousedown', (e) => {
    drawing = true;
    lastPosition = getMousePos(canvas, e);
  }, false);
  canvas.addEventListener('mouseup', () => {
    drawing = false;
  }, false);
  canvas.addEventListener('mouseout', () => {
    drawing = false;
  }, false);
  canvas.addEventListener('mousemove', (e) => {
    drawPosition = getMousePos(canvas, e);
  });
  canvas.addEventListener('touchstart', (e) => {
    drawPosition = getTouchPos(canvas, e);
    let touch = e.targetTouches[0];
    let mouseEvent = new MouseEvent('mousedown', {
      clientX: touch.clientX,
      clientY: touch.clientY
    });
    canvas.dispatchEvent(mouseEvent);
  }, false);
  canvas.addEventListener('touchend', () => {
    let mouseEvent = new MouseEvent('mouseup', {});
    canvas.dispatchEvent(mouseEvent);
  }, false);
  canvas.addEventListener('touchmove', (e) => {
    let touch = e.targetTouches[0];
    let mouseEvent = new MouseEvent('mousemove', {
      clientX: touch.clientX,
      clientY: touch.clientY
    });
    canvas.dispatchEvent(mouseEvent);
  }, false);

  // Draw to the canvas
  function renderCanvas() {
    if (drawing) {
      ctx.moveTo(lastPosition.x, lastPosition.y);
      ctx.lineTo(drawPosition.x, drawPosition.y);
      ctx.stroke();
      lastPosition = drawPosition;
    }
  }
  // Allow for animation
  (function drawLoop () {
    window.requestAnimFrame(drawLoop);
    renderCanvas();
  })();
}

function getMousePos(canvas, mouseEvent) {
  let rect = canvas.getBoundingClientRect();
  return {
    x: mouseEvent.clientX - rect.left,
    y: mouseEvent.clientY - rect.top,
  };
}

function getTouchPos(canvas, touchEvent) {
  var rect = canvas.getBoundingClientRect();
  return {
    x: touchEvent.touches[0].clientX - rect.left,
    y: touchEvent.touches[0].clientY - rect.top
  };
}

$(initPage);

// Get a regular interval for drawing to the screen
window.requestAnimFrame = (function (callback) {
  return window.requestAnimationFrame || 
     window.webkitRequestAnimationFrame ||
     window.mozRequestAnimationFrame ||
     window.oRequestAnimationFrame ||
     window.msRequestAnimaitonFrame ||
     function (callback) {
       window.setTimeout(callback, 1000/60);
     };
})();


function inIframe() {
  try {
    return window.self !== window.top;
  } catch (e) {
    return true;
  }
}
