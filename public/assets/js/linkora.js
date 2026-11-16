/* Linkora — shared front-end behaviour (jQuery) */
(function ($) {
  'use strict';

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' } });

  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Navbar: a soft highlight slides to the hovered link (desktop only), resting on the current page */
  var navList = document.querySelector('.lk-nav .navbar-nav');
  if (navList && window.matchMedia('(hover: hover) and (min-width: 992px)').matches) {
    var ind = document.createElement('span');
    ind.className = 'lk-nav-indicator';
    navList.prepend(ind);
    navList.classList.add('has-indicator');
    var moveTo = function (link) {
      if (!link) { ind.style.opacity = '0'; return; }
      ind.style.width = link.offsetWidth + 'px';
      ind.style.transform = 'translateX(' + (link.offsetLeft) + 'px)';
      ind.style.top = link.offsetTop + 'px';
      ind.style.height = link.offsetHeight + 'px';
      ind.style.opacity = '1';
    };
    var active = navList.querySelector('.nav-link.active');
    navList.querySelectorAll('.nav-link').forEach(function (a) { a.addEventListener('mouseenter', function () { moveTo(a); }); });
    navList.addEventListener('mouseleave', function () { moveTo(active); });
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { moveTo(active); }); else moveTo(active);
    window.addEventListener('resize', function () { moveTo(active); });
  }

  /* Navbar shadow on scroll */
  var $nav = $('.lk-nav');
  if ($nav.length) {
    // Hide the floating header while scrolling down, bring it back as soon as the user scrolls up.
    // It always stays visible near the top, while the mobile menu is open, or when it has keyboard focus.
    var lastY = window.scrollY, navEl = $nav[0];
    var onScroll = function () {
      var y = Math.max(window.scrollY, 0), dy = y - lastY;
      $nav.toggleClass('scrolled', y > 8);
      if (Math.abs(dy) < 6) return;                       // ignore tiny jitter (trackpads, momentum)
      var keep = y < 120 || navEl.querySelector('.navbar-collapse.show') || navEl.contains(document.activeElement);
      $nav.toggleClass('nav-hidden', dy > 0 && !keep);
      lastY = y;
    };
    $(window).on('scroll', onScroll); onScroll();
    $nav.on('focusin', function () { $nav.removeClass('nav-hidden'); });
  }

  /* Reveal on scroll + counters */
  function count(el) {
    var n = +el.dataset.n, s = el.dataset.s || '', t0 = null;
    if (reducedMotion) { el.textContent = (n >= 1000 ? n.toLocaleString('en-IN') : n) + s; return; }
    function f(t) {
      t0 = t0 || t;
      var k = Math.min((t - t0) / 1600, 1), v = Math.round(n * (1 - Math.pow(1 - k, 3)));
      el.textContent = (n >= 1000 ? v.toLocaleString('en-IN') : v) + s;
      if (k < 1) requestAnimationFrame(f);
    }
    requestAnimationFrame(f);
  }
  function reveal(el) { el.classList.add('in'); el.querySelectorAll('[data-n]').forEach(count); }
  if ('IntersectionObserver' in window && !reducedMotion) {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) { reveal(e.target); io.unobserve(e.target); } });
    }, { threshold: .15 });
    document.querySelectorAll('.rv').forEach(function (el) { io.observe(el); });
  } else {
    document.querySelectorAll('.rv').forEach(reveal);
  }

  /* Home: animated QR tile */
  var qr = document.getElementById('qr');
  if (qr) {
    var p = '1110111100101101111101001001111011111111001101110', h = '';
    for (var i = 0; i < 49; i++) h += '<b style="animation-delay:' + ((i * 137) % 30) / 10 + 's;' + (p[i] === '0' ? 'opacity:.12' : '') + '"></b>';
    qr.innerHTML = h;
  }

  /* Home: phone parallax */
  var stage = document.getElementById('stage');
  if (stage && !reducedMotion && window.matchMedia('(hover: hover)').matches) {
    var ph = document.getElementById('phone'), chips = stage.querySelectorAll('.chip');
    stage.addEventListener('mousemove', function (e) {
      var r = stage.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
      ph.style.transform = 'rotateY(' + x * 14 + 'deg) rotateX(' + (-y * 10) + 'deg)';
      chips.forEach(function (c, i) { c.style.translate = (x * (24 + i * 10)) + 'px ' + (y * (18 + i * 8)) + 'px'; });
    });
    stage.addEventListener('mouseleave', function () { ph.style.transform = ''; chips.forEach(function (c) { c.style.translate = ''; }); });
  }

  /* Username input: lowercase + strip invalid characters as the user types */
  $(document).on('input', '[data-username-input]', function () {
    var v = this.value.toLowerCase().replace(/[^a-z0-9_.]/g, '');
    if (v !== this.value) this.value = v;
  });

  /* Show / hide password */
  $(document).on('click', '.btn-toggle-pass', function () {
    var $in = $(this).closest('.input-group').find('input');
    var show = $in.attr('type') === 'password';
    $in.attr('type', show ? 'text' : 'password');
    $(this).attr('aria-label', show ? 'Hide password' : 'Show password')
      .find('i').toggleClass('fa-eye', !show).toggleClass('fa-eye-slash', show);
  });

  /* Password strength meter */
  $(document).on('input', '[data-pw-meter]', function () {
    var v = this.value, score = 0;
    if (v.length >= 8) score++;
    if (v.length >= 12) score++;
    if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
    if (/\d/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    var colors = ['#dc3545', '#dc3545', '#f0ad4e', '#f0ad4e', '#2BB673', '#157347'];
    var labels = ['Too short', 'Weak', 'Fair', 'Good', 'Strong', 'Very strong'];
    var $m = $($(this).data('pw-meter'));
    $m.find('span').css({ width: (v ? Math.max(score, 1) * 20 : 0) + '%', backgroundColor: colors[score] });
    $m.next('.pw-label').text(v ? labels[score] : '');
  });

  /* Auth: email step — Continue is enabled only for a valid-looking email */
  var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
  $('[data-email-input]').each(function () {
    var $in = $(this), $btn = $in.closest('form').find('[data-email-submit]');
    var sync = function () { $btn.prop('disabled', !emailRe.test($.trim($in.val()))); };
    $in.on('input change', sync);
    setTimeout(sync, 300); // browser autofill
    sync();
  });
  $(document).on('submit', 'form.email-step, #otp-form', function () {
    var $b = $(this).find('[type=submit]');
    if ($b.data('busy')) return false;
    $b.data('busy', true).prop('disabled', true)
      .html('<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>' + ($(this).is('#otp-form') ? 'Checking…' : 'Sending code…'));
  });

  /* Auth: show / hide password inside a floating field */
  $(document).on('click', '[data-toggle-pass]', function () {
    var $in = $($(this).data('toggle-pass')), show = $in.attr('type') === 'password';
    $in.attr('type', show ? 'text' : 'password');
    $(this).attr('aria-label', show ? 'Hide password' : 'Show password').find('i').toggleClass('fa-eye', !show).toggleClass('fa-eye-slash', show);
  });

  /* Auth: 6-digit code boxes */
  var $otpForm = $('#otp-form');
  if ($otpForm.length) {
    var $boxes = $otpForm.find('.otp-box'), $code = $('#otp-code'), $submit = $('#otp-submit');
    var collect = function () {
      var v = $boxes.map(function () { return this.value; }).get().join('');
      $code.val(v);
      $boxes.each(function () { $(this).toggleClass('filled', this.value !== ''); });
      $submit.prop('disabled', v.length !== 6 || !!$submit.data('busy'));
      return v;
    };
    var fill = function (digits, from) {
      digits = digits.replace(/\D/g, '');
      for (var i = 0; i < digits.length && from + i < 6; i++) $boxes[from + i].value = digits[i];
      var next = Math.min(from + digits.length, 5);
      $boxes.eq(next).trigger('focus');
      if (collect().length === 6) $otpForm.trigger('submit');
    };
    $boxes.on('input', function () {
      var i = $boxes.index(this), v = this.value.replace(/\D/g, '');
      if (v.length > 1) { this.value = ''; fill(v, i); return; } // autofill / fast typing
      this.value = v;
      if (v && i < 5) $boxes.eq(i + 1).trigger('focus');
      $otpForm.find('.otp-boxes').removeClass('is-invalid');
      if (collect().length === 6) $otpForm.trigger('submit');
    });
    $boxes.on('keydown', function (e) {
      var i = $boxes.index(this);
      if (e.key === 'Backspace' && !this.value && i > 0) { $boxes.eq(i - 1).val('').trigger('focus'); collect(); e.preventDefault(); }
      else if (e.key === 'ArrowLeft' && i > 0) { $boxes.eq(i - 1).trigger('focus'); e.preventDefault(); }
      else if (e.key === 'ArrowRight' && i < 5) { $boxes.eq(i + 1).trigger('focus'); e.preventDefault(); }
    });
    $boxes.on('focus', function () { this.select(); });
    $boxes.on('paste', function (e) {
      var text = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
      e.preventDefault();
      fill(text, $boxes.index(this));
    });
    if ($otpForm.find('.otp-boxes').hasClass('is-invalid')) $otpForm.find('.otp-boxes').addClass('shake');
    collect();
  }

  /* Auth: resend countdown */
  var $resend = $('#resend-btn');
  if ($resend.length) {
    var wait = parseInt($resend.data('wait'), 10) || 0;
    var tick = function () {
      if (wait > 0) { $resend.prop('disabled', true).text('Resend code in ' + wait + 's'); wait--; setTimeout(tick, 1000); }
      else { $resend.prop('disabled', false).text('Resend code'); }
    };
    tick();
  }

  /* AJAX forms: <form data-ajax> → JSON submit with inline validation errors */
  $(document).on('submit', 'form[data-ajax]', function (e) {
    e.preventDefault();
    var $f = $(this), $btn = $f.find('[type=submit]'), $alert = $f.find('.form-alert');
    $f.find('.is-invalid').removeClass('is-invalid');
    $f.find('.invalid-feedback.ajax').remove();
    $alert.addClass('d-none').removeClass('alert-success alert-danger');
    $btn.prop('disabled', true).data('label', $btn.html()).html('<span class="spinner-border spinner-border-sm me-2"></span>Sending…');

    $.ajax({ url: $f.attr('action'), method: 'POST', data: $f.serialize() })
      .done(function (r) {
        $alert.removeClass('d-none').addClass('alert-success').html('<i class="fa-solid fa-circle-check me-2"></i>' + $('<div>').text(r.message).html());
        $f[0].reset();
        $f.find('[data-counter]').trigger('input');
      })
      .fail(function (x) {
        if (x.status === 422 && x.responseJSON && x.responseJSON.errors) {
          var first = null;
          $.each(x.responseJSON.errors, function (name, msgs) {
            var $in = $f.find('[name="' + name + '"]');
            $in.addClass('is-invalid').after($('<div class="invalid-feedback ajax">').text(msgs[0]));
            first = first || $in;
          });
          if (first) first.trigger('focus');
        } else {
          var msg = x.status === 429 ? 'Too many messages. Please wait a minute and try again.' : 'Something went wrong. Please try again.';
          $alert.removeClass('d-none').addClass('alert-danger').text(msg);
        }
      })
      .always(function () { $btn.prop('disabled', false).html($btn.data('label')); });
  });

  /* Character counter */
  $(document).on('input', '[data-counter]', function () {
    $($(this).data('counter')).text(this.value.length + ' / ' + this.maxLength);
  });

  /* Pricing: monthly / yearly toggle */
  $(document).on('click', '.billing-toggle button', function () {
    var cycle = $(this).data('cycle');
    $('.billing-toggle button').removeClass('active').attr('aria-pressed', 'false');
    $(this).addClass('active').attr('aria-pressed', 'true');
    $('[data-price-monthly]').each(function () {
      var $p = $(this);
      if ($p.is('[data-free]')) return;
      $p.find('.amt').text($p.data(cycle === 'yearly' ? 'price-yearly' : 'price-monthly'));
      $p.find('.per').text(cycle === 'yearly' ? '/year' : '/month');
      $p.closest('.price-card').find('.price-note').text($p.data(cycle === 'yearly' ? 'note-yearly' : 'note-monthly'));
    });
  });

  /* Panel: sidebar toggle (mobile) */
  $(document).on('click', '[data-sb-toggle]', function () { $('body').toggleClass('sb-open'); });
  $(document).on('keydown', function (e) { if (e.key === 'Escape') $('body').removeClass('sb-open'); });

  /* Copy to clipboard */
  $(document).on('click', '[data-copy]', function () {
    var $b = $(this), text = $b.data('copy'), old = $b.html();
    var done = function () { $b.html('<i class="fa-solid fa-check me-1"></i>Copied'); setTimeout(function () { $b.html(old); }, 1600); };
    if (navigator.clipboard) navigator.clipboard.writeText(text).then(done);
    else { var t = $('<textarea>').val(text).appendTo('body').select(); document.execCommand('copy'); t.remove(); done(); }
  });

  /* ------------------------------------------------------------------
   * lkConfirm — branded replacement for the browser's confirm() popup.
   *   lkConfirm({ title, message, confirmText, cancelText, altText, tone })
   *   tone: 'danger' | 'warning' | 'info'
   * Resolves to 'confirm', 'alt' (optional third button) or false (cancel / Esc / backdrop).
   * ------------------------------------------------------------------ */
  var $dlg = null, dlgModal = null, dlgResolve = null;
  var TONES = {
    danger:  { icon: 'fa-regular fa-trash-can', btn: 'btn-danger' },
    warning: { icon: 'fa-solid fa-triangle-exclamation', btn: 'btn-primary' },
    info:    { icon: 'fa-solid fa-circle-info', btn: 'btn-primary' }
  };

  function buildDialog() {
    $dlg = $(
      '<div class="modal fade lk-dialog" tabindex="-1" aria-hidden="true" aria-labelledby="lk-dialog-title" aria-describedby="lk-dialog-msg">' +
        '<div class="modal-dialog modal-dialog-centered">' +
          '<div class="modal-content">' +
            '<div class="lk-dialog-body">' +
              '<div class="lk-dialog-icon"><i></i></div>' +
              '<h2 class="lk-dialog-title" id="lk-dialog-title"></h2>' +
              '<p class="lk-dialog-msg" id="lk-dialog-msg"></p>' +
            '</div>' +
            '<div class="lk-dialog-actions">' +
              '<button type="button" class="btn btn-ghost" data-lk-act="cancel"></button>' +
              '<button type="button" class="btn btn-ghost lk-dialog-alt" data-lk-act="alt"></button>' +
              '<button type="button" class="btn" data-lk-act="confirm"></button>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>'
    ).appendTo('body');
    dlgModal = new bootstrap.Modal($dlg[0]);
    var result = false;
    $dlg.on('click', '[data-lk-act]', function () {
      var act = $(this).data('lk-act');
      result = act === 'cancel' ? false : act;
      dlgModal.hide();
    });
    $dlg.on('show.bs.modal', function () { result = false; });
    $dlg.on('shown.bs.modal', function () { $dlg.find('[data-lk-act="confirm"]').trigger('focus'); });
    $dlg.on('hidden.bs.modal', function () { if (dlgResolve) { var r = dlgResolve; dlgResolve = null; r(result); } });
  }

  window.lkConfirm = function (opts) {
    opts = opts || {};
    if (!window.bootstrap) return Promise.resolve('confirm');
    if (!$dlg) buildDialog();
    var tone = TONES[opts.tone] ? opts.tone : 'warning';
    $dlg.attr('data-tone', tone).toggleClass('has-alt', !!opts.altText);
    $dlg.find('.lk-dialog-icon i').attr('class', TONES[tone].icon);
    $dlg.find('.lk-dialog-title').text(opts.title || 'Are you sure?');
    $dlg.find('.lk-dialog-msg').text(opts.message || '').toggle(!!opts.message);
    $dlg.find('[data-lk-act="cancel"]').text(opts.cancelText || 'Cancel');
    $dlg.find('[data-lk-act="alt"]').text(opts.altText || '').toggle(!!opts.altText);
    $dlg.find('[data-lk-act="confirm"]').text(opts.confirmText || 'Confirm')
      .removeClass('btn-primary btn-danger').addClass(TONES[tone].btn);
    return new Promise(function (resolve) {
      dlgResolve = resolve;
      dlgModal.show();
    });
  };

  /* <form data-confirm="Message"> — asks with lkConfirm before submitting.
     Optional: data-confirm-title, data-confirm-ok, data-confirm-tone. DELETE forms default to the danger style. */
  $(document).on('submit', 'form[data-confirm]', function (e) {
    var form = this;
    if (form.dataset.lkConfirmed === '1') { form.dataset.lkConfirmed = ''; return; }
    e.preventDefault();
    var submitter = e.originalEvent && e.originalEvent.submitter;
    var isDelete = $(form).find('input[name="_method"]').val() === 'DELETE';
    lkConfirm({
      title: form.dataset.confirmTitle || (isDelete ? 'Delete this?' : 'Are you sure?'),
      message: form.dataset.confirm,
      confirmText: form.dataset.confirmOk || (isDelete ? 'Delete' : 'Yes, continue'),
      tone: form.dataset.confirmTone || (isDelete ? 'danger' : 'warning')
    }).then(function (ok) {
      if (ok !== 'confirm') return;
      form.dataset.lkConfirmed = '1';
      if (form.requestSubmit) form.requestSubmit(submitter || undefined); else form.submit();
    });
  });
})(jQuery);
