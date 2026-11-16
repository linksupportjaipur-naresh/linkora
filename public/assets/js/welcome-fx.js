/*
 * Dashboard welcome banner — "taps" flying into your link (canvas) + pointer parallax.
 * Each tap is a glowing comet that curves from the edge into the link core, then bursts into a ripple + sparks.
 * Runs only while the banner is visible and the tab is active; reduced motion → static starfield, no loop.
 */
(function () {
  'use strict';
  var card = document.querySelector('[data-wc]');
  if (!card) return;
  var canvas = card.querySelector('.wc-canvas'), core = card.querySelector('.wc-core');
  var ctx = canvas.getContext('2d');
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var colors = ['#FFFFFF', '#F0ABFC', '#C4B5FD', '#93C5FD', '#FDE68A', '#86EFAC'];

  var W = 0, H = 0, dpr = 1, target = { x: 0, y: 0 };
  var stars = [], taps = [], bursts = [], nextTap = 0, live = false, raf = 0;

  function rand(a, b) { return a + Math.random() * (b - a); }

  function measure() {
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    W = card.offsetWidth; H = card.offsetHeight;
    canvas.width = Math.round(W * dpr); canvas.height = Math.round(H * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    var c = card.getBoundingClientRect(), r = core.getBoundingClientRect();
    target.x = r.left - c.left + r.width / 2; target.y = r.top - c.top + r.height / 2;
    stars = [];
    for (var i = 0, n = Math.round(W * H / 2600); i < n; i++) {
      stars.push({ x: Math.random() * W, y: Math.random() * H, r: rand(.4, 1.4), p: Math.random() * 6.28, s: rand(.6, 1.6) });
    }
  }

  function spawn() {
    // start somewhere on the top, bottom or left edge, curve into the core
    var side = Math.random(), sx, sy;
    if (side < .4) { sx = rand(0, W * .75); sy = -10; }
    else if (side < .8) { sx = rand(0, W * .75); sy = H + 10; }
    else { sx = -10; sy = rand(0, H); }
    taps.push({
      sx: sx, sy: sy, cx: rand(W * .2, W * .8), cy: sy < 0 ? rand(H * .6, H * 1.2) : rand(-H * .2, H * .4),
      t: 0, dur: rand(1100, 1900), c: colors[(Math.random() * colors.length) | 0], trail: []
    });
  }

  function burst(x, y, c) {
    var parts = [];
    for (var i = 0; i < 10; i++) {
      var a = Math.random() * 6.28, v = rand(.6, 2.4);
      parts.push({ x: x, y: y, vx: Math.cos(a) * v, vy: Math.sin(a) * v, life: 1 });
    }
    bursts.push({ x: x, y: y, r: 8, a: .9, c: c, parts: parts });
    core.classList.remove('hit'); void core.offsetWidth; core.classList.add('hit');
  }

  function bez(p0, p1, p2, t) { var u = 1 - t; return u * u * p0 + 2 * u * t * p1 + t * t * p2; }
  function ease(t) { return t < .5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2; }

  function drawStars(t) {
    for (var i = 0; i < stars.length; i++) {
      var s = stars[i];
      ctx.globalAlpha = .25 + .45 * (.5 + .5 * Math.sin(t / 700 * s.s + s.p));
      ctx.fillStyle = '#fff';
      ctx.beginPath(); ctx.arc(s.x, s.y, s.r, 0, 6.283); ctx.fill();
    }
    ctx.globalAlpha = 1;
  }

  var last = 0;
  function frame(t) {
    raf = 0;
    if (!live || document.hidden) return;
    var dt = Math.min(50, t - (last || t)); last = t;
    ctx.clearRect(0, 0, W, H);
    drawStars(t);

    if (t > nextTap) { spawn(); if (Math.random() < .35) spawn(); nextTap = t + rand(380, 900); }

    ctx.globalCompositeOperation = 'lighter';
    for (var i = taps.length - 1; i >= 0; i--) {
      var p = taps[i];
      p.t += dt / p.dur;
      var k = ease(Math.min(1, p.t));
      var x = bez(p.sx, p.cx, target.x, k), y = bez(p.sy, p.cy, target.y, k);
      p.trail.push([x, y]); if (p.trail.length > 18) p.trail.shift();
      for (var j = 1; j < p.trail.length; j++) {
        var a = j / p.trail.length;
        ctx.strokeStyle = p.c; ctx.globalAlpha = a * .8; ctx.lineWidth = a * 3;
        ctx.beginPath(); ctx.moveTo(p.trail[j - 1][0], p.trail[j - 1][1]); ctx.lineTo(p.trail[j][0], p.trail[j][1]); ctx.stroke();
      }
      ctx.globalAlpha = 1; ctx.fillStyle = '#fff'; ctx.shadowColor = p.c; ctx.shadowBlur = 14;
      ctx.beginPath(); ctx.arc(x, y, 2.6, 0, 6.283); ctx.fill(); ctx.shadowBlur = 0;
      if (p.t >= 1) { burst(target.x, target.y, p.c); taps.splice(i, 1); }
    }

    for (var b = bursts.length - 1; b >= 0; b--) {
      var B = bursts[b];
      B.r += dt * .09; B.a -= dt * .0016;
      ctx.globalAlpha = Math.max(0, B.a); ctx.strokeStyle = B.c; ctx.lineWidth = 2;
      ctx.beginPath(); ctx.arc(B.x, B.y, B.r, 0, 6.283); ctx.stroke();
      for (var q = 0; q < B.parts.length; q++) {
        var s = B.parts[q];
        s.x += s.vx * dt * .06; s.y += s.vy * dt * .06; s.life -= dt * .0018;
        ctx.globalAlpha = Math.max(0, s.life); ctx.fillStyle = B.c;
        ctx.beginPath(); ctx.arc(s.x, s.y, 1.8, 0, 6.283); ctx.fill();
      }
      if (B.a <= 0) bursts.splice(b, 1);
    }
    ctx.globalAlpha = 1; ctx.globalCompositeOperation = 'source-over';

    raf = requestAnimationFrame(frame);
  }

  function start() { if (!raf && live && !reduce) { last = 0; raf = requestAnimationFrame(frame); } }

  // Pointer parallax (CSS reads --px / --py)
  if (!reduce && window.matchMedia('(hover: hover)').matches) {
    card.addEventListener('pointermove', function (e) {
      var r = card.getBoundingClientRect();
      card.style.setProperty('--px', (((e.clientX - r.left) / r.width - .5) * 26).toFixed(1) + 'px');
      card.style.setProperty('--py', (((e.clientY - r.top) / r.height - .5) * 20).toFixed(1) + 'px');
    });
    card.addEventListener('pointerleave', function () { card.style.setProperty('--px', '0px'); card.style.setProperty('--py', '0px'); });
  }

  measure();
  if (reduce) { drawStars(0); card.classList.add('is-live'); return; }

  window.addEventListener('resize', function () { measure(); });
  document.addEventListener('visibilitychange', start);
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (es) {
      live = es[0].isIntersecting;
      card.classList.toggle('is-live', live);
      start();
    }).observe(card);
  } else { live = true; card.classList.add('is-live'); start(); }
})();
