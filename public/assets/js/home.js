/*
 * Linkora homepage motion (GSAP + ScrollTrigger, loaded only on "/").
 * - Content is fully visible without JS; html.h-anim only hides the hero intro until it plays.
 * - Respects prefers-reduced-motion: no entrance/scroll/parallax/typing animation.
 */
(function () {
  'use strict';

  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  var root = document.documentElement;

  /* ---------- Loader: first visit in this tab only, never longer than ~0.9s ---------- */
  function hideLoader() {
    var l = document.getElementById('h-loader');
    if (!l || !root.classList.contains('h-loader-on')) return;
    l.classList.add('is-done');
    setTimeout(function () { root.classList.remove('h-loader-on'); }, 500);
    try { sessionStorage.setItem('lk_home_seen', '1'); } catch (e) {}
  }

  /* ---------- Typing effect on the last hero word ---------- */
  function typing() {
    var el = document.querySelector('.h-typed');
    if (!el || reduce) return;
    var words; try { words = JSON.parse(el.getAttribute('data-words')); } catch (e) { return; }
    var w = 0, i = words[0].length, deleting = true;
    function tick() {
      var word = words[w];
      if (deleting) {
        i--; el.textContent = word.slice(0, Math.max(i, 0));
        if (i <= 0) { deleting = false; w = (w + 1) % words.length; return setTimeout(tick, 260); }
        return setTimeout(tick, 45);
      }
      i++; el.textContent = words[w].slice(0, i);
      if (i >= words[w].length) { deleting = true; return setTimeout(tick, 2100); }
      setTimeout(tick, 85);
    }
    setTimeout(tick, 2600);
  }

  /* ---------- Cursor spotlight on cards (CSS reads --mx/--my) ---------- */
  function spotlight() {
    if (!finePointer) return;
    document.querySelectorAll('.h-spot').forEach(function (card) {
      card.addEventListener('pointermove', function (e) {
        var r = card.getBoundingClientRect();
        card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
        card.style.setProperty('--my', (e.clientY - r.top) + 'px');
      });
    });
  }

  /* ---------- 3D tilt + layered parallax of the hero stage ---------- */
  function stageTilt(gsap) {
    var stage = document.getElementById('h-stage');
    if (!stage || reduce || !finePointer) return;
    var layers = Array.prototype.slice.call(stage.querySelectorAll('[data-depth]'));
    var phone = stage.querySelector('.h-phone');
    // Let GSAP own the phone's 3D transform (it can't read a 3D rotation back from CSS)
    if (gsap && phone) gsap.set(phone, { rotateY: -10, rotateX: 6, transformPerspective: 1400 });
    var setters = layers.map(function (el) {
      return { el: el, d: parseFloat(el.getAttribute('data-depth')) || 1,
        x: gsap ? gsap.quickTo(el, 'x', { duration: .8, ease: 'power3' }) : null,
        y: gsap ? gsap.quickTo(el, 'y', { duration: .8, ease: 'power3' }) : null };
    });
    stage.addEventListener('pointermove', function (e) {
      var r = stage.getBoundingClientRect(), px = (e.clientX - r.left) / r.width - .5, py = (e.clientY - r.top) / r.height - .5;
      setters.forEach(function (s) {
        if (s.x) { s.x(px * 28 * s.d); s.y(py * 22 * s.d); }
      });
      if (gsap && phone) gsap.to(phone, { rotateY: -10 + px * 16, rotateX: 6 - py * 12, duration: .8, ease: 'power3', overwrite: 'auto' });
    });
    stage.addEventListener('pointerleave', function () {
      setters.forEach(function (s) { if (s.x) { s.x(0); s.y(0); } });
      if (gsap && phone) gsap.to(phone, { rotateY: -10, rotateX: 6, duration: 1, ease: 'power3' });
    });
  }

  /* ---------- Count-up numbers ---------- */
  function countUp(el, gsap) {
    var target = parseFloat(el.getAttribute('data-count')), suffix = el.getAttribute('data-suffix') || '';
    if (!gsap || reduce || isNaN(target)) return;
    var obj = { v: 0 };
    gsap.to(obj, { v: target, duration: 1.6, ease: 'power2.out', onUpdate: function () { el.textContent = Math.round(obj.v) + suffix; } });
  }

  /* ---------- "How it works": the line grows from the first step to the last as you scroll,
     and each step lights up once the line reaches it (horizontal on desktop, vertical on phones) ---------- */
  function stepsLine(gsap) {
    var wrap = document.querySelector('.h-steps');
    var bar = wrap && wrap.querySelector('.h-steps-line span');
    if (!bar) return;
    var steps = Array.prototype.slice.call(wrap.querySelectorAll('.h-step'));
    var mm = gsap.matchMedia();
    mm.add({ wide: '(min-width: 768px)', narrow: '(max-width: 767.98px)' }, function (ctx) {
      var wide = ctx.conditions.wide, prop = wide ? 'width' : 'height';
      // Where each step sits along the line (0..1), so it lights up exactly when the line arrives
      function marks() {
        var r = wrap.getBoundingClientRect();
        return steps.map(function (s) {
          var n = s.querySelector('.h-step-num').getBoundingClientRect();
          return wide ? (n.left + n.width / 2 - r.left) / r.width : (n.top + n.height / 2 - r.top) / r.height;
        });
      }
      var at = marks();
      function paint(p) { steps.forEach(function (s, i) { s.classList.toggle('is-pending', p < at[i] - 0.02); }); }
      gsap.set(bar, { width: wide ? '0%' : '100%', height: wide ? '100%' : '0%' });
      paint(0);
      var tween = gsap.fromTo(bar, (function () { var o = {}; o[prop] = '0%'; return o; })(), (function () {
        var o = { ease: 'none' }; o[prop] = '100%';
        o.scrollTrigger = { trigger: wrap, start: wide ? 'top 75%' : 'top 70%', end: wide ? 'bottom 45%' : 'bottom 55%', scrub: 0.6,
          onRefresh: function () { at = marks(); },
          onUpdate: function (self) { paint(self.progress); } };
        return o;
      })());
      return function () { steps.forEach(function (s) { s.classList.remove('is-pending'); }); gsap.set(bar, { clearProps: 'width,height' }); tween.kill(); };
    });
  }

  /* ---------- "Why Linkora": live illustrations, staggered 3D reveal, tilt, parallax, swatch preview ---------- */
  function why(gsap, ST) {
    var sec = document.querySelector('[data-why]');
    if (!sec) return;
    var cards = Array.prototype.slice.call(sec.querySelectorAll('.h-why-card'));

    // Illustrations animate only while the section is on screen (saves battery/CPU)
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (es) {
        es.forEach(function (e) { sec.classList.toggle('is-live', e.isIntersecting); });
      }, { rootMargin: '120px 0px' }).observe(sec);
    } else {
      sec.classList.add('is-live');
    }

    // Hover (or tap) a colour swatch → the mini phone previews that theme
    var custom = sec.querySelector('.h-why-custom');
    if (custom) {
      var sw = custom.querySelector('.h-swatches');
      sw.querySelectorAll('i').forEach(function (s) {
        function pick() {
          custom.style.setProperty('--pick-bg', s.getAttribute('data-bg'));
          custom.style.setProperty('--pick-btn', s.getAttribute('data-btn'));
          custom.classList.add('is-picked');
        }
        s.addEventListener('mouseenter', pick);
        s.addEventListener('click', pick);
      });
      sw.addEventListener('mouseleave', function () {
        custom.classList.remove('is-picked');
        custom.style.removeProperty('--pick-bg');
        custom.style.removeProperty('--pick-btn');
      });
    }

    if (reduce || !gsap || !ST) { sec.classList.add('is-in'); return; }

    // Heading underline + chart line draw in when the heading comes into view
    ST.create({ trigger: sec.querySelector('.h-why-head'), start: 'top 78%', once: true, onEnter: function () { sec.classList.add('is-in'); } });

    // Cards rise in one after another with a slight 3D flip; inline styles are cleared afterwards so the CSS tilt takes over
    gsap.from(cards, {
      y: 70, rotateX: -14, opacity: 0, transformPerspective: 1100, transformOrigin: '50% 100%',
      duration: 1, ease: 'power3.out', stagger: 0.12, clearProps: 'transform,opacity',
      scrollTrigger: { trigger: sec.querySelector('.h-why-bento'), start: 'top 82%' }
    });

    // Background shapes drift at different speeds while scrolling through the section
    gsap.utils.toArray(sec.querySelectorAll('[data-why-depth]')).forEach(function (el) {
      var d = parseFloat(el.getAttribute('data-why-depth')) || 0;
      gsap.fromTo(el, { y: -d * 220 }, { y: d * 220, ease: 'none', scrollTrigger: { trigger: sec, start: 'top bottom', end: 'bottom top', scrub: true } });
    });

    // Subtle 3D tilt that follows the pointer (desktop only)
    if (!finePointer) return;
    cards.forEach(function (card) {
      var raf = 0, px = 0, py = 0;
      function apply() {
        raf = 0;
        card.style.setProperty('--rx', (-py * 7).toFixed(2) + 'deg');
        card.style.setProperty('--ry', (px * 9).toFixed(2) + 'deg');
        card.style.setProperty('--px', px.toFixed(3));
        card.style.setProperty('--py', py.toFixed(3));
      }
      card.addEventListener('pointermove', function (e) {
        var r = card.getBoundingClientRect();
        px = (e.clientX - r.left) / r.width - 0.5;
        py = (e.clientY - r.top) / r.height - 0.5;
        card.classList.add('is-tilting');
        if (!raf) raf = requestAnimationFrame(apply);
      });
      card.addEventListener('pointerleave', function () {
        card.classList.remove('is-tilting');
        px = py = 0; apply();
      });
    });
  }

  /* ---------- Main: GSAP timelines ---------- */
  function animate() {
    var gsap = window.gsap, ST = window.ScrollTrigger;
    if (!gsap) { root.classList.remove('h-anim'); return; }   // CDN failed: show everything as-is
    if (ST) gsap.registerPlugin(ST);
    // Follow real time: if the tab is in the background during load, the intro still finishes on time
    // instead of crawling one tiny step per (throttled) frame.
    gsap.ticker.lagSmoothing(0);

    if (reduce) { root.classList.remove('h-anim'); return; }

    // Hero intro: text lines rise into place, then the stage pops in
    var tl = gsap.timeline({ defaults: { ease: 'power4.out' } });
    gsap.set('.h-intro, .h-intro-stage', { opacity: 1 });
    root.classList.remove('h-anim');
    tl.from('.h-pill-badge', { y: 20, opacity: 0, duration: .8 })
      .from('.h-line > .h-intro', { yPercent: 110, duration: 1.1, stagger: .12 }, '-=.5')
      .from('.h-lead', { y: 24, opacity: 0, duration: .9 }, '-=.7')
      .from('.h-claim', { y: 24, opacity: 0, scale: .97, duration: .9 }, '-=.7')
      .from('.h-trust li', { y: 12, opacity: 0, duration: .6, stagger: .08 }, '-=.6')
      .from('.h-intro-stage', { opacity: 0, scale: .9, y: 40, duration: 1.3, ease: 'expo.out' }, .25)
      .from('.h-float-icon', { scale: 0, opacity: 0, duration: .7, stagger: .07, ease: 'back.out(2)' }, .8)
      .from('.h-glass-card', { y: 30, opacity: 0, duration: .9, stagger: .12 }, 1);

    if (!ST) return;

    // Section headings & single blocks: fade + slide up
    gsap.utils.toArray('[data-reveal]').forEach(function (el) {
      gsap.from(el, { y: 40, opacity: 0, duration: 1, ease: 'power3.out', scrollTrigger: { trigger: el, start: 'top 88%' } });
    });
    // Groups: children cascade in
    gsap.utils.toArray('[data-stagger]').forEach(function (group) {
      gsap.from(group.children, { y: 50, opacity: 0, duration: .9, ease: 'power3.out', stagger: .1, scrollTrigger: { trigger: group, start: 'top 85%' } });
    });

    // Analytics bars grow; step line draws as you scroll
    gsap.from('.h-bars i', { scaleY: 0, duration: 1.1, ease: 'power3.out', stagger: .08, scrollTrigger: { trigger: '.h-bars', start: 'top 90%' } });
    stepsLine(gsap);

    // Counters start when the facts strip is visible
    document.querySelectorAll('[data-count]').forEach(function (el) {
      ST.create({ trigger: el, start: 'top 90%', once: true, onEnter: function () { countUp(el, gsap); } });
    });

    // Parallax: background orbs/shapes drift at different speeds while scrolling the hero
    gsap.utils.toArray('[data-parallax]').forEach(function (el) {
      var speed = parseFloat(el.getAttribute('data-parallax')) || 0;
      gsap.to(el, { yPercent: speed * 100, ease: 'none', scrollTrigger: { trigger: '.h-hero', start: 'top top', end: 'bottom top', scrub: true } });
    });
    // Theme cards float at slightly different speeds (desktop)
    if (window.matchMedia('(min-width: 992px)').matches) {
      gsap.utils.toArray('[data-parallax-card]').forEach(function (el) {
        var s = parseFloat(el.getAttribute('data-parallax-card')) || 0;
        gsap.to(el, { y: s * 400, ease: 'none', scrollTrigger: { trigger: '.h-themes', start: 'top bottom', end: 'bottom top', scrub: true } });
      });
    }

    stageTilt(gsap);
  }

  function start() {
    hideLoader();
    typing();
    spotlight();
    animate();
    why(window.gsap, window.ScrollTrigger);
  }

  // This file is loaded with `defer` after the deferred GSAP scripts, so they have already run:
  // start straight away (don't wait for images/fonts — that would delay the hero).
  start();
  // Safety net: never keep the loader up for long, even on slow connections.
  setTimeout(hideLoader, 900);
})();
