/* Student academy: editor tabs, live preview, and confirm prompts.
   No inline handlers anywhere -- the site's CSP allows scripts from 'self'
   only, so everything is wired up here. */
(function () {
  'use strict';

  /* ---- tabs ---- */
  var tabs = Array.prototype.slice.call(document.querySelectorAll('.editor-tab'));
  function showTab(tab) {
    tabs.forEach(function (other) {
      var pane = document.getElementById(other.getAttribute('aria-controls'));
      var on = other === tab;
      other.setAttribute('aria-selected', on ? 'true' : 'false');
      if (pane) { pane.hidden = !on; }
    });
    var active = document.getElementById(tab.getAttribute('aria-controls'));
    var field = active && active.querySelector('textarea');
    if (field) { field.focus(); }
  }
  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () { showTab(tab); });
  });

  /* ---- Tab key inserts two spaces instead of leaving the editor ---- */
  document.querySelectorAll('.editor-pane textarea').forEach(function (area) {
    area.addEventListener('keydown', function (event) {
      if (event.key !== 'Tab' || event.shiftKey) { return; }
      event.preventDefault();
      var start = area.selectionStart;
      var end = area.selectionEnd;
      area.value = area.value.slice(0, start) + '  ' + area.value.slice(end);
      area.selectionStart = area.selectionEnd = start + 2;
    });
  });

  /* ---- preview ---- */
  var preview = document.getElementById('site-preview');
  var refresh = document.getElementById('refresh-preview');
  if (preview && refresh) {
    refresh.addEventListener('click', function () {
      var base = preview.getAttribute('data-base') || preview.getAttribute('src');
      preview.setAttribute('src', base + (base.indexOf('?') === -1 ? '?' : '&') + 't=' + Date.now());
    });
  }

  /* ---- warn before leaving with unsaved work ---- */
  var form = document.getElementById('site-editor-form');
  if (form) {
    var dirty = false;
    form.addEventListener('input', function () { dirty = true; });
    form.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (event) {
      if (!dirty) { return; }
      event.preventDefault();
      event.returnValue = '';
    });
  }

  /* ---- print ---- */
  var printButton = document.getElementById('print-cards');
  if (printButton) {
    printButton.addEventListener('click', function () { window.print(); });
  }

  /* ---- confirm prompts, declared with data-confirm ---- */
  document.querySelectorAll('[data-confirm]').forEach(function (node) {
    node.addEventListener('submit', function (event) {
      if (!window.confirm(node.getAttribute('data-confirm'))) { event.preventDefault(); }
    });
  });

  /* ---- confirm on one button of a form that has several ---- */
  document.querySelectorAll('[data-confirm-button]').forEach(function (button) {
    button.addEventListener('click', function (event) {
      if (!window.confirm(button.getAttribute('data-confirm-button'))) { event.preventDefault(); }
    });
  });
})();
