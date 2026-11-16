/*
 * Login side panel scene: glowing particles (canvas) + layered pointer parallax.
 * Runs only while the panel is visible (it is hidden on small screens) and the tab is in view.
 * prefers-reduced-motion: particles are drawn once, no parallax.
 */
(function () {
  'use strict';
  var root = document.querySelector('[data-nv]');
  if (!root) return;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var canvas = root.querySelector('[data-nv-particles]');
  var ctx = canvas && canvas.getContext('2d');
  var layers = Array.prototype.slice.call(root.querySelectorAll('[data-depth]')).map(function (el) {
    return { el: el, d: parseFloat(el.getAttribute('data-depth')) || 1 };
  });

  var W = 0, H = 0, dpr = 1, parts = [];
  var colors = ['#9FB2FF', '#B79CFF', '#C4B5FD', '#F0ABFC', '#7DA2FF'];
  var target = { x: 0, y: 0 }, cur = { x: 0, y: 0 }, lastMove = 0;

  function visible() { return root.offsetWidth > 0 && !document.hidden; }

  function resize() {
    if (!canvas) return;
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    W = root.offsetWidth; H = root.offsetHeight;
    canvas.width = Math.round(W * dpr); canvas.height = Math.round(H * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    var n = Math.round(Math.min(90, (W * H) / 9000));
    parts = [];
    for (var i = 0; i < n; i++) {
      // Denser towards the right, where the scene is
      var x = W * (0.25 + 0.75 * Math.pow(Math.random(), 0.6));
      parts.push({ x: x, y: Math.random() * H, r: 0.5 + Math.random() * 1.9, z: 0.3 + Math.random() * 1.2,
        vy: -(0.08 + Math.random() * 0.25), ph: Math.random() * Math.PI * 2, c: colors[i % colors.length] });
    }
  }

  function drawParticles(t) {
    if (!ctx) return;
    ctx.clearRect(0, 0, W, H);
    for (var i = 0; i < parts.length; i++) {
      var p = parts[i];
      if (!reduce) {
        p.y += p.vy * p.z;
        if (p.y < -10) { p.y = H + 10; }
      }
      var x = p.x + Math.sin(t / 2400 + p.ph) * 8 * p.z + cur.x * 14 * p.z;
      var y = p.y + cur.y * 10 * p.z;
      var a = 0.35 + 0.45 * (0.5 + 0.5 * Math.sin(t / 900 + p.ph));
      ctx.globalAlpha = a;
      ctx.fillStyle = p.c;
      ctx.shadowColor = p.c; ctx.shadowBlur = 8 * p.r;
      ctx.beginPath(); ctx.arc(x, y, p.r, 0, Math.PI * 2); ctx.fill();
    }
    ctx.globalAlpha = 1; ctx.shadowBlur = 0;
  }

  function frame(t) {
    if (visible()) {
      // Without a pointer, drift slowly on its own so the scene never looks frozen
      if (t - lastMove > 2500) { target.x = Math.sin(t / 5200) * 0.35; target.y = Math.cos(t / 6100) * 0.3; }
      cur.x += (target.x - cur.x) * 0.05; cur.y += (target.y - cur.y) * 0.05;
      for (var i = 0; i < layers.length; i++) {
        var L = layers[i];
        L.el.style.transform = 'translate3d(' + (cur.x * 16 * L.d).toFixed(2) + 'px,' + (cur.y * 12 * L.d).toFixed(2) + 'px,0)';
      }
      drawParticles(t);
    }
    requestAnimationFrame(frame);
  }

  resize();
  window.addEventListener('resize', resize);
  if (reduce) { drawParticles(0); return; }

  window.addEventListener('pointermove', function (e) {
    var r = root.getBoundingClientRect();
    if (!r.width) return;
    target.x = Math.max(-1, Math.min(1, (e.clientX - (r.left + r.width / 2)) / (r.width)));
    target.y = Math.max(-1, Math.min(1, (e.clientY - (r.top + r.height / 2)) / (r.height)));
    lastMove = performance.now();
  }, { passive: true });

  requestAnimationFrame(frame);
})();
