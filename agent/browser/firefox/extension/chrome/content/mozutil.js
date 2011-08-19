/**
 * Copyright 2011 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * This file defines a namespace wpt.moz, which holds mozilla-specific
 * menthods used by webpagetest.
 */

// Namespace wpt.moz:
window['wpt'] = window['wpt'] || {};
window.wpt['moz'] = window.wpt['moz'] || {};

(function() {  // Begin closure

var CI = Components.interfaces;
var CC = Components.classes;
var CU = Components.utils;

wpt.moz.createInst = function(mozClass, mozInstance) {
  return CC[mozClass].createInstance(CI[mozInstance]);
};

wpt.moz.getService = function(mozClass, mozInterface) {
  return CC[mozClass].getService().QueryInterface(CI[mozInterface]);
};

/**
 * Take an object with parameters that specify a cookie, and set it.
 * The format of the object is the same as the object passed to
 * chrome.cookies.set() in a chrome extension.
 * @param {Object} cookieObj
 */
wpt.moz.setCookie = function(cookieObj) {
  var uri = wpt.moz.createInst('@mozilla.org/network/standard-url;1',
                               'nsIURI');
  uri.spec = cookieObj.url;

  var cookieString = (cookieObj.name + '=' + cookieObj.value + ';');

  // If there is an expiration date, append it to the cookie string.
  // Example: 'name=value; expires=Wed, 10 Aug 2011 18:33:05 GMT'.
  if (cookieObj['expirationDate']) {
    cookieString = [
        cookieString,
        ' expires = ',
        new Date(cookieObj['expirationDate']).toUTCString()
        ].join('');
  }

  var cookieService = wpt.moz.getService('@mozilla.org/cookieService;1',
                                         'nsICookieService');
  cookieService.setCookieString(
      uri,  // The URI of the document for which cookies are being queried.
      null,  // The prompt to use for all user-level cookie notifications.
      cookieString,  // The cookie string to set.
      null);  // The channel used to load the document.
};

wpt.moz.execScriptInSelectedTab = function(scriptText, exportedFunctions) {
  // Mozilla's Components.utils.sandbox object allows javascript to be run
  // with limited privlages.  Any javascript we run directly can do anything
  // extension javascript can do, including reading and writing to the
  // filesystem.  The sandbox imposes a the same limits on javascript that
  // the page has.  Docs are here:
  // https://developer.mozilla.org/en/Components.utils.evalInSandbox .

  // Get the window object of the foremosst tab.
  var wrappedWindow = gBrowser.contentWindow;
  var sandbox = new CU.Sandbox(
      wrappedWindow,  // Same limitations as the javascript in the window.
      {sandboxPrototype: wrappedWindow});  // Window is the prototype of the global
                                           // object.  A global reference that is
                                           // not defined on the sandbox will refer
                                           // to the item on the window.

  for (var fnName in exportedFunctions) {
    sandbox[fnName] = exportedFunctions[fnName];
  }

  // If the script we are running throws, we need some way to see the exception.
  // Wrap the script in a try block, and dump any exceptions we catch.
  var scriptWithExceptionDumping = [
      'try {',
      '  (function() {',
      scriptText,
      '  })();',
      '} catch (ex) {',
      '  dump("\\n\\nUncaught exception in exec script: " + ex + "\\n\\n");',
      '}'].join('\n');

  CU.evalInSandbox(scriptWithExceptionDumping, sandbox);
};

wpt.moz.clearAllBookmarks = function() {
  var bookmarksService = wpt.moz.getService(
      '@mozilla.org/browser/nav-bookmarks-service;1',
      'nsINavBookmarksService');

  bookmarksService.removeFolderChildren(bookmarksService.toolbarFolder);
  bookmarksService.removeFolderChildren(bookmarksService.bookmarksMenuFolder);
  bookmarksService.removeFolderChildren(bookmarksService.tagsFolder);
  bookmarksService.removeFolderChildren(bookmarksService.unfiledBookmarksFolder);
};

})();  // End closure
