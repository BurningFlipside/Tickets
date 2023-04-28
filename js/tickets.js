/* global $ */
/* exported getDaysLeftInMailWindow, getTicketYear, initTicketPage, isTestMode, isWindowOpen */
var cache = {};

function getSessionCachedVar(varName) {
  if(typeof(Storage) !== 'undefined') {
    return JSON.parse(sessionStorage.getItem(varName));
  }
  if(cache[`${varName}`] !== undefined) {
    return cache[`${varName}`];
  }
  return null;
}

function setSessionCachedVar(varName, value) {
  if(typeof(Storage) !== 'undefined') {
    sessionStorage.setItem(varName, JSON.stringify(value));
  } else {
    cache[`${varName}`] = value;
  }
}

function processDate(dateStr) {
  var dateType = new Date(dateStr+' GMT-0600');
  if(isNaN(dateType)) {
    dateType = new Date(dateStr+'T06:00:00.000Z');
  }
  return dateType; 
}

function cacheWindowResults(jqXHR) {
  if(jqXHR.status === 200) {
    var data = jqXHR.responseJSON;
    data.test_mode          = (data.test_mode==='1'); // eslint-disable-line camelcase
    data.request_start_date = processDate(data.request_start_date); // eslint-disable-line camelcase
    data.request_stop_date  = processDate(data.request_stop_date); // eslint-disable-line camelcase
    data.request_stop_date.setHours(23);
    data.request_stop_date.setMinutes(59);
    data.mail_start_date    = processDate(data.mail_start_date); // eslint-disable-line camelcase
    data.current            = processDate(data.current);
    data.year               = parseInt(data.year);
    setSessionCachedVar('window', data);
  } else {
    console.log(jqXHR);
  }
  this.callback(data, this.reallyDone);
}

function processWindowData(windowData, callback) {
  var now   = new Date(Date.now());
  var start = new Date(windowData.request_start_date);
  var end   = new Date(windowData.request_end_date);
  if(new Date(windowData.current) < now) {
    now = windowData.current;
  }
  if(windowData.test_mode || (now > start && now < end)) {
    setSessionCachedVar('windowOpen', true);
    if(now > new Date(windowData.mail_start_date)) {
      setSessionCachedVar('windowMailOpen', true);
      setSessionCachedVar('windowMailDaysLeft', Math.floor(end/(1000*60*60*24) - now/(1000*60*60*24)));
    } else {
      setSessionCachedVar('windowMailOpen', false);
    }
  } else {
    setSessionCachedVar('windowOpen', false);
    setSessionCachedVar('windowMailOpen', false);
  }
  if(callback !== undefined) {
    callback();
  }
}

function getAndCacheWindowData(callback, fullyCompleteCallback) {
  var obj = {};
  obj.callback   = callback;
  obj.reallyDone = fullyCompleteCallback;
  $.ajax({
    url: 'api/v1/globals/window',
    type: 'GET',
    dataType: 'json',
    context: obj,
    complete: cacheWindowResults});  
}

function getWindowData(callback, initIsDoneCallback) {
  var data = getSessionCachedVar('window');
  if(data === null) {
    getAndCacheWindowData(callback, initIsDoneCallback);
  }
  callback(data, initIsDoneCallback);
}

function initTicketPage(initIsDoneCallback) {
  getWindowData(processWindowData, initIsDoneCallback);
}

function isWindowOpen() {
  return getSessionCachedVar('windowOpen');
}

function isTestMode() {
  var windowData = getSessionCachedVar('window');
  if(windowData === null || windowData.test_mode === undefined) {
    return false;
  }
  return windowData.test_mode;
}

function isMailWindowOpen() {
  return getSessionCachedVar('windowMailOpen');
}

function getDaysLeftInMailWindow() {
  if(!isMailWindowOpen()) {
    return 0;
  }
  return getSessionCachedVar('windowMailDaysLeft');
}

function getTicketYear() {
  var windowData = getSessionCachedVar('window');
  if(windowData === null || windowData.test_mode === undefined) {
    return undefined;
  }
  return windowData.year;
}
