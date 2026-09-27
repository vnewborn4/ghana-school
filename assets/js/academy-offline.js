/*
 * Offline support for the student academy.
 *
 * Three jobs, in order of how much they matter to a child in Accra:
 *
 *   1. Never lose typing. The editor keeps a local copy of what is being
 *      written, so a dropped connection or a closed tab costs nothing.
 *   2. Never lose a submission. A save that fails because the line went down
 *      is queued on the device and sent by itself when the line returns.
 *   3. Say what is happening, in words a ten-year-old can act on.
 *
 * On shared machines the rules are strict. Everything stored here is tagged
 * with the learner who was signed in, and queued work is only ever sent for
 * that same learner -- so one child's homework can never be submitted under
 * another child's name.
 *
 * Everything degrades. With no service worker, no IndexedDB, or no JavaScript
 * at all, the forms post normally and the academy works as it always did.
 */
(function () {
  'use strict';

  var DB_NAME = 'academy-offline';
  var DB_VERSION = 1;
  var OUTBOX = 'outbox';
  var DRAFT_PREFIX = 'academy-draft:';
  var MAX_AGE_MS = 7 * 24 * 60 * 60 * 1000;   // a week; then it is stale anyway

  var learner = (document.body && document.body.dataset.learner) || '';
  var db = null;

  /* ------------------------------------------------------------ helpers -- */

  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  /**
   * The site's install path. Taken from the page when it says so, and
   * otherwise worked out from the URL, because offline.html is a static file
   * with no PHP to tell it.
   */
  function basePath() {
    var body = document.body;
    if (body && typeof body.dataset.basePath === 'string') return body.dataset.basePath;
    var marker = window.location.pathname.indexOf('/academy/');
    return marker > 0 ? window.location.pathname.slice(0, marker) : '';
  }

  function banner(message, kind) {
    var bar = document.getElementById('offline-banner');
    if (!bar) {
      bar = document.createElement('div');
      bar.id = 'offline-banner';
      bar.setAttribute('role', 'status');
      bar.setAttribute('aria-live', 'polite');
      document.body.appendChild(bar);
    }
    bar.className = 'offline-banner offline-banner-' + (kind || 'info');
    bar.textContent = message;
    bar.hidden = false;
  }

  function hideBanner() {
    var bar = document.getElementById('offline-banner');
    if (bar) bar.hidden = true;
  }

  /* ----------------------------------------------------------- outbox ---- */

  function openDb() {
    return new Promise(function (resolve) {
      if (!('indexedDB' in window)) { resolve(null); return; }
      var request;
      try {
        request = indexedDB.open(DB_NAME, DB_VERSION);
      } catch (error) {
        resolve(null);   // private mode, or storage blocked
        return;
      }
      request.onupgradeneeded = function () {
        var database = request.result;
        if (!database.objectStoreNames.contains(OUTBOX)) {
          database.createObjectStore(OUTBOX, { keyPath: 'id', autoIncrement: true });
        }
      };
      request.onsuccess = function () { resolve(request.result); };
      request.onerror = function () { resolve(null); };
    });
  }

  function withStore(mode, work) {
    return new Promise(function (resolve, reject) {
      if (!db) { reject(new Error('no storage')); return; }
      var tx = db.transaction(OUTBOX, mode);
      var result = work(tx.objectStore(OUTBOX));
      tx.oncomplete = function () { resolve(result && result.result); };
      tx.onerror = function () { reject(tx.error); };
      tx.onabort = function () { reject(tx.error); };
    });
  }

  function allItems() {
    return new Promise(function (resolve) {
      if (!db) { resolve([]); return; }
      var tx = db.transaction(OUTBOX, 'readonly');
      var request = tx.objectStore(OUTBOX).getAll();
      request.onsuccess = function () { resolve(request.result || []); };
      request.onerror = function () { resolve([]); };
    });
  }

  /**
   * Queue a failed submission. The CSRF token is deliberately dropped: it
   * belongs to the session, not to the action, and the one held now will have
   * expired by the time this is sent. A fresh one is added on the way out.
   */
  function queueSubmission(action, formData, label) {
    var fields = [];
    formData.forEach(function (value, name) {
      if (name !== 'csrf') fields.push([name, value]);
    });
    return withStore('readwrite', function (store) {
      return store.add({
        learner: learner,
        action: action,
        fields: fields,
        label: label || 'Your work',
        created: Date.now()
      });
    });
  }

  function dropItem(id) {
    return withStore('readwrite', function (store) { return store.delete(id); });
  }

  /** Anything older than a week is dropped rather than left on the device. */
  function expireOldItems(items) {
    var now = Date.now();
    return Promise.all(items
      .filter(function (item) { return now - item.created > MAX_AGE_MS; })
      .map(function (item) { return dropItem(item.id); }));
  }

  /**
   * Send everything queued for the learner who is signed in now.
   *
   * Work queued by a different learner on this device is left alone, not
   * sent: submitting one child's homework under another child's name would
   * be worse than a delay.
   */
  function flushOutbox() {
    if (!db || !learner || !navigator.onLine) return Promise.resolve();

    return allItems().then(function (items) {
      return expireOldItems(items).then(function () { return items; });
    }).then(function (items) {
      var mine = items.filter(function (item) {
        return item.learner === learner && Date.now() - item.created <= MAX_AGE_MS;
      });
      var theirs = items.length - mine.length;
      if (!mine.length) {
        if (theirs > 0) {
          banner('There is unsent work on this computer from another sign-in. '
               + 'That learner should sign in here to send it.', 'warn');
        } else {
          hideBanner();
        }
        return;
      }

      banner('Sending ' + mine.length + ' saved ' + (mine.length === 1 ? 'item' : 'items') + '…', 'info');

      // One at a time and in order, so a save and the submit that follows it
      // cannot arrive the wrong way round.
      return mine.reduce(function (chain, item) {
        return chain.then(function (stop) {
          if (stop) return stop;
          return sendItem(item);
        });
      }, Promise.resolve(false)).then(function (stopped) {
        if (stopped === 'auth') {
          banner('Your saved work could not be sent because you were signed out. '
               + 'Sign in again on this computer and it will go.', 'warn');
        } else if (stopped === 'offline') {
          banner('Still no internet. Your work stays saved on this computer.', 'warn');
        } else {
          banner('Your saved work has been sent.', 'ok');
          window.setTimeout(hideBanner, 6000);
        }
      });
    }).catch(function () { /* storage gave up; the forms still work */ });
  }

  function sendItem(item) {
    var body = new FormData();
    item.fields.forEach(function (pair) { body.append(pair[0], pair[1]); });
    body.append('csrf', csrfToken());

    return fetch(item.action, {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      redirect: 'follow'
    }).then(function (response) {
      if (response.ok) return dropItem(item.id).then(function () { return false; });
      // 400 is what an expired session looks like here. Keep the work.
      if (response.status === 400 || response.status === 401 || response.status === 403) {
        return 'auth';
      }
      // Anything else is the server refusing this submission on its merits.
      // Dropping it silently would be worse than keeping it for a person.
      return 'auth';
    }).catch(function () { return 'offline'; });
  }

  /* --------------------------------------------------- form interception - */

  function offlineForms() {
    return Array.prototype.slice.call(document.querySelectorAll('[data-offline-form]'));
  }

  function wireForms() {
    offlineForms().forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!db) return;              // no queue available: submit normally
        event.preventDefault();

        var data;
        try {
          data = new FormData(form, event.submitter);
        } catch (error) {
          data = new FormData(form);   // older browsers ignore the submitter
        }
        // Belt and braces: some browsers still leave the submitter out.
        if (event.submitter && event.submitter.name && !data.has(event.submitter.name)) {
          data.append(event.submitter.name, event.submitter.value);
        }

        var action = form.getAttribute('action') || window.location.href;
        var label = form.getAttribute('data-offline-label') || 'Your work';
        setBusy(form, true);

        fetch(action, { method: 'POST', body: data, credentials: 'same-origin', redirect: 'follow' })
          .then(function (response) {
            if (!response.ok) throw new Error('rejected');
            clearLocalDraft();
            try { sessionStorage.setItem('academy-flash', 'saved'); } catch (e) { /* ignore */ }
            window.location.reload();
          })
          .catch(function () {
            queueSubmission(action, data, label).then(function () {
              setBusy(form, false);
              clearLocalDraft();
              banner('No internet. ' + label + ' is saved on this computer and will be '
                   + 'sent on its own when the internet comes back.', 'warn');
            }).catch(function () {
              setBusy(form, false);
              banner('This computer could not save your work. Please tell your teacher.', 'bad');
            });
          });
      });
    });
  }

  function setBusy(form, busy) {
    Array.prototype.forEach.call(form.querySelectorAll('button[type=submit]'), function (button) {
      button.disabled = busy;
    });
  }

  /* -------------------------------------------- local draft for My page -- */
  /*
   * Only the page editor keeps a local copy, and only of the page's own code.
   * That is the work most painful to lose and the least sensitive to leave on
   * a shared machine -- it is written to be published. Reflections and
   * assignment answers are not stored locally; they reach the outbox only if
   * a real submission fails.
   */

  var editorFields = null;

  function draftKey() {
    var slug = (document.body && document.body.dataset.siteSlug) || '';
    return DRAFT_PREFIX + learner + ':' + slug;
  }

  function readLocalDraft() {
    try {
      var raw = window.localStorage.getItem(draftKey());
      if (!raw) return null;
      var draft = JSON.parse(raw);
      if (!draft || Date.now() - draft.at > MAX_AGE_MS) { clearLocalDraft(); return null; }
      return draft;
    } catch (error) { return null; }
  }

  function writeLocalDraft() {
    if (!editorFields) return;
    try {
      window.localStorage.setItem(draftKey(), JSON.stringify({
        html: editorFields.html.value,
        css: editorFields.css.value,
        js: editorFields.js.value,
        at: Date.now()
      }));
    } catch (error) { /* full or blocked: the outbox still covers a failed save */ }
  }

  function clearLocalDraft() {
    try { window.localStorage.removeItem(draftKey()); } catch (error) { /* ignore */ }
  }

  function hasUnsavedDraft() {
    var draft = readLocalDraft();
    if (!draft || !editorFields) return false;
    return draft.html !== editorFields.html.value
        || draft.css !== editorFields.css.value
        || draft.js !== editorFields.js.value;
  }

  function wireEditorDraft() {
    var html = document.getElementById('html');
    var css = document.getElementById('css');
    var js = document.getElementById('js');
    if (!html || !css || !js || !learner) return;
    editorFields = { html: html, css: css, js: js };

    var draft = readLocalDraft();
    if (draft && (draft.html !== html.value || draft.css !== css.value || draft.js !== js.value)) {
      offerRestore(draft);
    }

    var timer = null;
    [html, css, js].forEach(function (field) {
      field.addEventListener('input', function () {
        window.clearTimeout(timer);
        timer = window.setTimeout(writeLocalDraft, 800);
      });
    });
  }

  function offerRestore(draft) {
    var box = document.createElement('div');
    box.className = 'notice notice-warn';
    var when = new Date(draft.at);
    box.innerHTML = '<strong>You have unsaved work on this computer</strong>'
      + '<p style="margin:6px 0">From ' + when.toLocaleString() + '. '
      + 'Do you want to bring it back?</p>';

    var restore = document.createElement('button');
    restore.type = 'button';
    restore.className = 'button-big';
    restore.textContent = 'Bring back my work';
    restore.addEventListener('click', function () {
      editorFields.html.value = draft.html;
      editorFields.css.value = draft.css;
      editorFields.js.value = draft.js;
      box.remove();
      banner('Your work is back. Press Save to keep it.', 'ok');
    });

    var discard = document.createElement('button');
    discard.type = 'button';
    discard.className = 'button-big secondary';
    discard.style.marginLeft = '10px';
    discard.textContent = 'No, use the saved version';
    discard.addEventListener('click', function () { clearLocalDraft(); box.remove(); });

    box.appendChild(restore);
    box.appendChild(discard);

    var main = document.getElementById('academy-main');
    var head = main && main.querySelector('.academy-head');
    if (head && head.parentNode) head.parentNode.insertBefore(box, head.nextSibling);
    else if (main) main.insertBefore(box, main.firstChild);
  }

  /* ------------------------------------------------------- signing out --- */

  function wireSignOut() {
    var form = document.querySelector('form[action$="logout.php"]');
    if (!form) return;
    form.addEventListener('submit', function (event) {
      if (hasUnsavedDraft()) {
        if (!window.confirm('You have unsaved changes to your page. '
                          + 'If you sign out now they will be lost. Sign out anyway?')) {
          event.preventDefault();
          return;
        }
      }
      clearLocalDraft();

      // Warn, but never destroy: queued work is only ever sent for the
      // learner who made it, so it is safe to leave until they return.
      allItems().then(function (items) {
        var mine = items.filter(function (item) { return item.learner === learner; });
        if (mine.length) {
          banner('You still have work waiting to be sent. Sign in again on this '
               + 'computer when the internet is back.', 'warn');
        }
      });
    });
  }

  /* ---------------------------------------------------------- start up --- */

  function registerWorker() {
    if (!('serviceWorker' in navigator)) return;
    var path = basePath() + '/academy/sw.js';
    navigator.serviceWorker.register(path, { scope: basePath() + '/academy/' })
      .catch(function () { /* unsupported or blocked: the site still works */ });
  }

  function showSavedFlash() {
    try {
      if (sessionStorage.getItem('academy-flash') === 'saved') {
        sessionStorage.removeItem('academy-flash');
        banner('Saved.', 'ok');
        window.setTimeout(hideBanner, 4000);
      }
    } catch (error) { /* ignore */ }
  }

  function start() {
    registerWorker();
    showSavedFlash();
    wireEditorDraft();

    var retry = document.getElementById('retry');
    if (retry) retry.addEventListener('click', function () { window.location.reload(); });

    openDb().then(function (opened) {
      db = opened;
      wireForms();
      wireSignOut();
      if (!navigator.onLine) {
        banner('No internet. You can keep working — everything you save stays on '
             + 'this computer until the internet comes back.', 'warn');
      } else {
        flushOutbox();
      }
    });

    window.addEventListener('online', function () {
      banner('The internet is back.', 'ok');
      flushOutbox();
    });
    window.addEventListener('offline', function () {
      banner('No internet. You can keep working — everything you save stays on '
           + 'this computer until the internet comes back.', 'warn');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
