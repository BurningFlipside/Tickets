/*global $, bootbox*/
/*exported addEEFromGoogleSheet*/
let passTypes = {
  'Theme Camp': {Title: 'CPAF', Email: ''},
  'Art Project': {Title: 'ArtAF', Email: ''}
};

function sendCreateToServer(type, count) {
  fetch('/tickets/api/v1/earlyEntry/passes', {
    method: 'post',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      type: type,
      count: count,
      owner: passTypes[type].Email
    })
  });
}

function createPasses(type) {
  bootbox.prompt({
    size: 'small',
    title: 'Enter the number of passes to create',
    inputType: 'number',
    min: 1,
    max: 100,
    callback: function(result) {
      if(result === null) {
        return;
      }
      sendCreateToServer(type, result);
    }
  });
}

function bulkAssign(type) {
  bootbox.dialog({
    title: 'Bulk Assign Early Entry Passes for '+type,
    message: '<form id="bulkForm"><div class="form-group"><label for="bulkEmail">Email Address</label><input type="email" class="form-control" id="bulkEmail" required></div><div class="form-group"><label for="bulkCount">Number of Passes</label><input type="number" class="form-control" id="bulkCount" required></div><div class="form-group"><label for="bulkNote">Notes</label><input type="text" class="form-control" id="bulkNote"></div></form>',
    buttons: {
      cancel: {
        label: 'Cancel',
        className: 'btn btn-secondary'
      },
      confirm: {
        label: 'Assign',
        className: 'btn btn-primary',
        callback: function() {
          let email = $('#bulkEmail').val();
          let count = $('#bulkCount').val();
          let note = $('#bulkNote').val();
          if(email === '' || count === '') {
            return false;
          }
          fetch('/tickets/api/v1/earlyEntry/passes/Actions/BulkAssign', {
            method: 'post',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              type: type,
              count: count,
              owner: email,
              notes: note
            })
          }).then(function(response) {
            if(response.ok) {
              alert('Passes Assigned');
              location.reload();
            } else {
              bootbox.alert('Failed to assign passes');
            }
          });
        }
      }
    }
  });
}

function listPasses(type) {
  fetch('/tickets/api/v1/earlyEntry/passes?$filter=type eq \''+type+'\' and year eq current').then(function(response) {
    response.json().then(function(data) {
      let table = $('<table>').addClass('table table-striped');
      let thead = $('<thead>');
      let theadRow = $('<tr>').append($('<th>'));
      theadRow.append($('<th>').text('Pass ID'));
      theadRow.append($('<th>').text('Assigned To'));
      theadRow.append($('<th>').text('Used'));
      theadRow.append($('<th>').text('Notes'));
      thead.append(theadRow);
      let tbody = $('<tbody>');
      for(let i = 0; i < data.length; i++) {
        let buttonCell = $('<td>');
        if(data[i].used) {
          buttonCell.append($('<button>').addClass('btn btn-link').attr('title', 'Mark as Unused').html('<i class="fa fa-history"></i>').click(function() {
            fetch('/tickets/api/v1/earlyEntry/passes/'+data[i].id, {
              method: 'patch',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                used: false,
                usedBy: '',
                usedDT: '0000-00-00 00:00:00'
              })
            }).then(function(patchResponse) {
              if (patchResponse.ok) {
                bootbox.hideAll();
                listPasses(type);
              } else {
                bootbox.alert('Failed to mark pass as unused');
              }
            });
          }));
        } else {
          buttonCell.append($('<button>').addClass('btn btn-link').attr('title', 'Edit Notes').html('<i class="fa fa-sticky-note"></i>').click(function() {
            bootbox.prompt({
              size: 'small',
              title: 'Enter notes',
              value: data[i].notes,
              callback: function(result) {
                if(result === null) {
                  return;
                }
                fetch('/tickets/api/v1/earlyEntry/passes/'+data[i].id, {
                  method: 'patch',
                  headers: {
                    'Content-Type': 'application/json'
                  },
                  body: JSON.stringify({
                    notes: result
                  })
                }).then(function(patchResponse) {
                  if (patchResponse.ok) {
                    bootbox.hideAll();
                    listPasses(type);
                  } else {
                    bootbox.alert('Failed to update notes');
                  }
                });
              }
            });
          }));
          buttonCell.append($('<button>').addClass('btn btn-link').attr('title', 'Reassign').html('<i class="fa fa-paper-plane"></i>').click(function() {
            bootbox.prompt({
              size: 'small',
              title: 'Enter email address',
              callback: function(result) {
                if(result === null) {
                  return;
                }
                fetch('/tickets/api/v1/earlyEntry/passes/'+data[i].id+'/Actions/Reassign', {
                  method: 'post',
                  headers: {
                    'Content-Type': 'application/json'
                  },
                  body: JSON.stringify({
                    assignedTo: result
                  })
                }).then(function(postResponse) {
                  if (postResponse.ok) {
                    bootbox.hideAll();
                    listPasses(type);
                  } else {
                    bootbox.alert('Failed to reassign pass');
                  }
                });
              }
            });
          }));
          buttonCell.append($('<button>').addClass('btn btn-link').attr('title', 'Download PDF').html('<i class="fa fa-file-pdf"></i>').click(function() {
            fetch('/tickets/api/v1/earlyEntry/passes/'+data[i].id+'/pdf').then(function(pdfResponse) {
              if (pdfResponse.ok) {
                pdfResponse.blob().then(function(blob) {
                  let url = URL.createObjectURL(blob);
                  let a = document.createElement('a');
                  a.href = url;
                  a.download = type+'_'+data[i].id+'.pdf';
                  a.click();
                  a.parentNode.removeChild(a);
                });
              } else {
                bootbox.alert('Failed to generate PDF');
              }
            });
          }));
        }
        let row = $('<tr>').append(buttonCell);
        row.append($('<td>').text(data[i].id));
        row.append($('<td>').text(data[i].assignedTo));
        row.append($('<td>').text(data[i].used));
        row.append($('<td>').text(data[i].notes));
        tbody.append(row);
      }
      table.append(thead).append(tbody);
      bootbox.dialog({
        title: 'Early Entry Passes for '+type,
        size: 'large',
        message: table
      });
    });
  });

}

function doTableInit() {
  let tbody = $('#passTypes tbody');
  for(let type in passTypes) {
    let row = $('<tr>');
    let ctrlCell = $('<td>');
    let addButton = $('<button>').addClass('btn btn-link').attr('title', 'Create Early Entry Passes').html('<i class="fa fa-plus-square"></i>');
    let listButton = $('<button>').addClass('btn btn-link').attr('title', 'List Early Entry Passes').html('<i class="fa fa-list"></i>');
    let bulkButton = $('<button>').addClass('btn btn-link').attr('title', 'Bulk Assign Early Entry Passes').html('<i class="fa fa-mail-bulk"></i>');
    addButton.click(function() {
      createPasses(type);
    });
    listButton.click(function() {
      listPasses(type);
    });
    bulkButton.click(function() {
      bulkAssign(type);
    });
    let typeCell = $('<td>').text(type);
    ctrlCell.append(addButton);
    ctrlCell.append(listButton);
    ctrlCell.append(bulkButton);
    row.append(ctrlCell).append(typeCell);
    tbody.append(row);
  }
  fetch('/tickets/api/v1/earlyEntry/passes?$filter=year eq current').then(function(response) {
    response.json().then(function(data) {
      let props = Object.getOwnPropertyNames(passTypes);
      let counts = {};
      for(let i = 0; i < data.length; i++) {
        if(counts[data[i].type] === undefined) {
          counts[data[i].type] = {count: 0, used: 0};
        }
        if (data[i].used) {
          counts[data[i].type].used++;
        }
        counts[data[i].type].count++;
      }
      let rows = document.getElementById('passTypes').getElementsByTagName('tr');
      for(let i = 0; i < props.length; i++) {
        let row = rows[i+1];
        let countCell = row.insertCell();
        let usedCell = row.insertCell();
        if(counts[props[i]] === undefined) {
          countCell.textContent = 0;
          usedCell.textContent = 0;
          continue;
        }
        countCell.textContent = counts[props[i]].count;
        usedCell.textContent = counts[props[i]].used;
      }
    });
  });
}

function addEEFromGoogleSheet() {
  bootbox.prompt({
    size: 'small',
    title: 'Enter the URL of the Google Sheet',
    callback: function(result) {
      if(result === null) {
        return;
      }
      let parts = result.split('/');
      if(parts.length < 6) {
        bootbox.alert('Invalid URL');
        return;
      }
      let sheetId = parts[5];
      fetch('/tickets/api/v1/earlyEntry/Actions/CheckEESpreadSheet', {
        method: 'post',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          spreadSheetId: sheetId
        })
      }).then(function(response) {
        if(response.ok) {
          response.json().then(function(data) {
            console.log(data);
            if(data.errorCount > 0) {
              bootbox.alert(data.errorCount+' entries in spreadsheet could not be updated in the database');
              return;
            }
            bootbox.alert('Early Entry statuses added successfully');
          });
        } else {
          bootbox.alert('Failed to add Early Entry Passes');
        }
      });
    }
  });

}

function pageInit() {
  if(window.profilesUrl === undefined) {
    window.profilesUrl = 'https://profiles.burningflipside.com';
  }
  let promises = [];
  for(let type in passTypes) {
    promises.push(fetch(window.profilesUrl+'/api/v1/users?$filter=Title eq \''+passTypes[type].Title+'\'', {
      mode: 'cors',
      credentials: 'include'
    }));
  }
  Promise.all(promises).then(function(responses) {
    let props = Object.getOwnPropertyNames(passTypes);
    for(let i = 0; i < responses.length; i++) {
      responses[i].json().then(function(data) {
        passTypes[props[i]].Email = data[0].mail;
      });
    }
    doTableInit();
  });
}

$(pageInit);