/*
 * Emoji button for text fields.
 * Add data-emoji to any <input type="text"> or <textarea>: a 😊 button appears inside it,
 * and picking an emoji inserts it at the cursor. The picker (emoji-picker-element,
 * with search + categories + skin tones) is only downloaded the first time it's opened.
 */
(function () {
  'use strict';

  var PICKER_URL = 'https://cdn.jsdelivr.net/npm/emoji-picker-element@1.21.3/index.js';
  var loading = null, popover = null, activeField = null, activeBtn = null;

  function loadPicker() {
    if (!loading) loading = import(PICKER_URL);
    return loading;
  }

  function buildPopover() {
    popover = document.createElement('div');
    popover.className = 'emoji-pop';
    popover.setAttribute('role', 'dialog');
    popover.setAttribute('aria-label', 'Choose an emoji');
    popover.hidden = true;
    popover.innerHTML = '<div class="emoji-pop-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading emoji…</div>';
    document.body.appendChild(popover);

    loadPicker().then(function () {
      var picker = document.createElement('emoji-picker');
      picker.className = 'light';
      popover.innerHTML = '';
      popover.appendChild(picker);
      picker.addEventListener('emoji-click', function (e) {
        insert(e.detail.unicode);
      });
      if (!popover.hidden) setTimeout(function () { focusSearch(picker); }, 50);
    }).catch(function () {
      popover.innerHTML = '<div class="emoji-pop-loading text-danger">Couldn’t load emoji. Check your internet connection.</div>';
    });
  }

  function focusSearch(picker) {
    try { var s = picker.shadowRoot && picker.shadowRoot.querySelector('input'); if (s) s.focus(); } catch (e) {}
  }

  function place() {
    var r = activeBtn.getBoundingClientRect(), w = 340, h = 400, gap = 8;
    var left = Math.min(Math.max(8, r.right - w), window.innerWidth - w - 8);
    var top = r.bottom + gap;
    if (top + h > window.innerHeight - 8 && r.top - gap - h > 8) top = r.top - gap - h; // open upwards if no room below
    popover.style.left = Math.max(8, left) + 'px';
    popover.style.top = Math.max(8, top) + 'px';
  }

  function open(field, btn) {
    if (!popover) buildPopover();
    // Inside a Bootstrap modal the picker must live in the modal, or the modal's focus trap
    // stops you typing in the emoji search box.
    var host = field.closest('.modal') || document.body;
    if (popover.parentNode !== host) host.appendChild(popover);
    activeField = field; activeBtn = btn;
    popover.hidden = false;
    btn.setAttribute('aria-expanded', 'true');
    place();
    var picker = popover.querySelector('emoji-picker');
    if (picker) setTimeout(function () { focusSearch(picker); }, 30);
  }

  function close(returnFocus) {
    if (!popover || popover.hidden) return;
    popover.hidden = true;
    if (activeBtn) activeBtn.setAttribute('aria-expanded', 'false');
    if (returnFocus && activeField) activeField.focus();
  }

  function insert(emoji) {
    var f = activeField;
    if (!f) return;
    var start = f.selectionStart != null ? f.selectionStart : f.value.length;
    var end = f.selectionEnd != null ? f.selectionEnd : f.value.length;
    var next = f.value.slice(0, start) + emoji + f.value.slice(end);
    var max = parseInt(f.getAttribute('maxlength'), 10);
    if (max && next.length > max) {
      if (window.lkToast) window.lkToast('Not enough space for another emoji here.', 'error');
      return;
    }
    f.value = next;
    var pos = start + emoji.length;
    f.focus();
    try { f.setSelectionRange(pos, pos); } catch (e) {}
    f.dispatchEvent(new Event('input', { bubbles: true }));   // updates counters / live previews
    f.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function enhance(field) {
    if (field.dataset.emojiReady) return;
    field.dataset.emojiReady = '1';

    var wrap = document.createElement('span');
    wrap.className = 'emoji-field' + (field.tagName === 'TEXTAREA' ? ' is-textarea' : '');
    field.parentNode.insertBefore(wrap, field);
    wrap.appendChild(field);

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'emoji-btn';
    btn.setAttribute('aria-label', 'Add emoji');
    btn.setAttribute('aria-haspopup', 'dialog');
    btn.setAttribute('aria-expanded', 'false');
    btn.title = 'Add emoji';
    btn.innerHTML = '<i class="fa-regular fa-face-smile"></i>';
    wrap.appendChild(btn);

    btn.addEventListener('mousedown', function (e) { e.preventDefault(); }); // keep the cursor position in the field
    btn.addEventListener('click', function () {
      if (popover && !popover.hidden && activeField === field) close(true);
      else open(field, btn);
    });
  }

  function init(root) {
    (root || document).querySelectorAll('input[data-emoji], textarea[data-emoji]').forEach(enhance);
  }

  document.addEventListener('mousedown', function (e) {
    if (popover && !popover.hidden && !popover.contains(e.target) && !(activeBtn && activeBtn.contains(e.target))) close(false);
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && popover && !popover.hidden) { e.stopPropagation(); close(true); } }, true);
  // Closing a modal (e.g. after saving a link) also closes the picker.
  document.addEventListener('hide.bs.modal', function () { close(false); });
  window.addEventListener('resize', function () { if (popover && !popover.hidden) place(); });
  window.addEventListener('scroll', function () { if (popover && !popover.hidden) place(); }, true);

  window.lkEmoji = { init: init };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { init(); });
  else init();
})();
