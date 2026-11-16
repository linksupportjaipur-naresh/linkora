/*
 * Linkora — profile photo picker with "Adjust your photo" dialog (move / zoom / rotate / flip inside a circle).
 * Needs: Cropper.js 1.6.x, Bootstrap 5 modal, and the markup in resources/views/dashboard/partials/avatar-crop-modal.blade.php.
 *
 *   LkAvatarCropper({
 *     input:     <input type="file">,      // the adjusted 640×640 JPEG replaces the picked file in this input
 *     adjustBtn: <button>|null,            // re-opens the dialog for the current photo
 *     savedUrl:  'https://…'|null,         // an already-saved (same-site) photo that can be adjusted
 *     onApplied: function (url) {},        // a new photo is ready in the input (url = preview)
 *     onError:   function (message) {},    // picked file was rejected ('' = clear the error)
 *   });
 *
 * Without Cropper.js / DataTransfer support the original file stays in the input (server limit: 2 MB).
 */
(function () {
  'use strict';

  window.LkAvatarCropper = function (opts) {
    var input = opts.input;
    var adjustBtn = opts.adjustBtn || null;
    var onApplied = opts.onApplied || function () {};
    var onError = opts.onError || function () {};
    var modalEl = document.getElementById('avatarCropModal');
    var img = document.getElementById('avatarCropImg');
    var zoom = document.getElementById('cropZoom');
    var loading = document.getElementById('cropLoading');
    var applyBtn = document.getElementById('cropApply');

    var canCrop = !!(window.Cropper && window.bootstrap && window.DataTransfer && modalEl) &&
      (function () { try { new DataTransfer(); return true; } catch (e) { return false; } })();
    var modal = canCrop ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
    var MAX_BYTES = (canCrop ? 10 : 2) * 1024 * 1024;
    var MAX_SIDE = canCrop ? 10000 : 6000;

    var cropper = null;
    var source = null;        // { url, name } of the original picture (never a crop of a crop)
    var lastData = null;      // crop settings to restore when the same picture is adjusted again
    var appliedFiles = null;  // adjusted photo currently in the input, so Cancel can put it back
    var applied = false, minRatio = 0, maxRatio = 1;

    function restoreInput() { if (appliedFiles) input.files = appliedFiles; else input.value = ''; }
    function reject(msg) { restoreInput(); onError(msg); }

    // ---- choosing a file ----
    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (!file) { restoreInput(); return; }
      if (!/^image\/(jpeg|png|webp)$/.test(file.type)) return reject('Please choose a JPG, PNG or WebP image.');
      if (file.size > MAX_BYTES) return reject('The photo must be smaller than ' + (MAX_BYTES / 1048576) + ' MB.');
      var url = URL.createObjectURL(file), probe = new Image();
      probe.onload = function () {
        if (probe.naturalWidth < 100 || probe.naturalHeight < 100) return reject('Please use a photo at least 100 × 100 pixels.');
        if (probe.naturalWidth > MAX_SIDE || probe.naturalHeight > MAX_SIDE) return reject('Please use a photo smaller than ' + MAX_SIDE + ' × ' + MAX_SIDE + ' pixels.');
        onError('');
        if (!canCrop) { appliedFiles = input.files; onApplied(url); return; }
        source = { url: url, name: file.name };
        lastData = null;
        open();
      };
      probe.onerror = function () { reject('This image could not be opened. Please choose another photo.'); };
      probe.src = url;
    });

    // ---- "Adjust photo": the photo picked this visit, or the saved uploaded one ----
    if (adjustBtn) {
      adjustBtn.addEventListener('click', function () {
        if (!canCrop) return;
        if (!source) {
          if (!opts.savedUrl) return;
          source = { url: opts.savedUrl, name: 'avatar.jpg' };
        }
        open();
      });
    }

    if (!canCrop) return { canCrop: false };

    function open() {
      applied = false;
      modalEl._lkOwner = api;
      loading.hidden = false;
      modal.show();
    }

    // ---- zoom helpers: the photo must always cover the circle ----
    function natural() {
      var cd = cropper.getCanvasData(), id = cropper.getImageData(), r = id.width / id.naturalWidth;
      return { w: cd.width / r, h: cd.height / r, ratio: r };
    }
    function fitCover(keepZoom) {
      var cb = cropper.getCropBoxData(), n = natural();
      minRatio = Math.max(cb.width / n.w, cb.height / n.h);
      maxRatio = minRatio * 5;
      if (!keepZoom || n.ratio < minRatio) cropper.zoomTo(minRatio);
      var cd = cropper.getCanvasData();
      cropper.setCanvasData({ left: cb.left + (cb.width - cd.width) / 2, top: cb.top + (cb.height - cd.height) / 2 });
      syncSlider();
    }
    function syncSlider() {
      if (!cropper) return;
      var r = natural().ratio;
      zoom.value = Math.round(Math.max(0, Math.min(1, (r - minRatio) / (maxRatio - minRatio))) * 100);
    }

    var api = {
      canCrop: true,
      shown: function () {
        if (cropper) cropper.destroy();
        img.src = source.url;
        cropper = new Cropper(img, {
          aspectRatio: 1, viewMode: 1, dragMode: 'move', autoCropArea: 0.84,
          cropBoxMovable: false, cropBoxResizable: false, toggleDragModeOnDblclick: false,
          guides: false, center: false, highlight: false, background: false, restore: false,
          responsive: true, checkCrossOrigin: false, preview: '.crop-prev', minContainerHeight: 220,
          ready: function () {
            loading.hidden = true;
            fitCover(false);
            if (lastData) { cropper.setData(lastData); syncSlider(); }
            zoom.focus({ preventScroll: true });
          },
          zoom: function (e) {
            if (e.detail.ratio < minRatio * 0.999 || e.detail.ratio > maxRatio * 1.001) { e.preventDefault(); return; }
            requestAnimationFrame(syncSlider);
          }
        });
      },
      hide: function () { if (!applied) restoreInput(); },
      hidden: function () { if (cropper) { cropper.destroy(); cropper = null; } },
      zoomInput: function () { if (cropper) cropper.zoomTo(minRatio + (maxRatio - minRatio) * (+zoom.value / 100)); },
      tool: function (act) {
        if (!cropper) return;
        if (act === 'rotate-left' || act === 'rotate-right') { cropper.rotate(act === 'rotate-left' ? -90 : 90); fitCover(true); }
        else if (act === 'flip') { cropper.scaleX(-(cropper.getData().scaleX || 1)); }
        else if (act === 'reset') { cropper.reset(); fitCover(false); }
      },
      key: function (e) {
        if (!cropper || e.target === zoom) return;
        var step = e.shiftKey ? 30 : 8, moves = { ArrowLeft: [-step, 0], ArrowRight: [step, 0], ArrowUp: [0, -step], ArrowDown: [0, step] };
        if (moves[e.key]) { e.preventDefault(); cropper.move(moves[e.key][0], moves[e.key][1]); }
        else if (e.key === '+' || e.key === '=') { e.preventDefault(); cropper.zoom(0.1); }
        else if (e.key === '-' || e.key === '_') { e.preventDefault(); cropper.zoom(-0.1); }
      },
      apply: function () {
        if (!cropper) return;
        applyBtn.disabled = true;
        var canvas = cropper.getCroppedCanvas({ width: 640, height: 640, fillColor: '#fff', imageSmoothingEnabled: true, imageSmoothingQuality: 'high' });
        canvas.toBlob(function (blob) {
          applyBtn.disabled = false;
          if (!blob) { onError('Sorry, this photo could not be processed. Please try another one.'); return; }
          var name = (source.name || 'avatar').replace(/\.[^.]+$/, '') + '.jpg';
          var dt = new DataTransfer();
          dt.items.add(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
          input.files = dt.files;
          appliedFiles = input.files;
          lastData = cropper.getData();
          applied = true;
          if (adjustBtn) adjustBtn.hidden = false;
          modal.hide();
          onError('');
          onApplied(URL.createObjectURL(blob));
        }, 'image/jpeg', 0.9);
      }
    };

    // The dialog is shared: wire its controls once and send events to whoever opened it.
    if (!modalEl._lkWired) {
      modalEl._lkWired = true;
      var owner = function () { return modalEl._lkOwner; };
      modalEl.addEventListener('shown.bs.modal', function () { owner() && owner().shown(); });
      modalEl.addEventListener('hide.bs.modal', function () { owner() && owner().hide(); });
      modalEl.addEventListener('hidden.bs.modal', function () { owner() && owner().hidden(); });
      zoom.addEventListener('input', function () { owner() && owner().zoomInput(); });
      modalEl.querySelectorAll('[data-crop]').forEach(function (b) {
        b.addEventListener('click', function () { owner() && owner().tool(b.getAttribute('data-crop')); });
      });
      modalEl.querySelectorAll('[data-crop-cancel]').forEach(function (b) {
        b.addEventListener('click', function () { bootstrap.Modal.getOrCreateInstance(modalEl).hide(); });
      });
      modalEl.addEventListener('keydown', function (e) { owner() && owner().key(e); });
      applyBtn.addEventListener('click', function () { owner() && owner().apply(); });
    }

    return api;
  };
})();
