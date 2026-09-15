(function () {
  var POS_KEY = 'daas_fab_pos_v1';
  var DRAG_THRESHOLD = 6;
  var FAB_SIZE = 68;
  var HTML2CANVAS_SRC = 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js';
  var recentJsErrors = [];

  function pushJsError(entry) {
    recentJsErrors.push(entry);
    if (recentJsErrors.length > 10) recentJsErrors.shift();
  }

  window.addEventListener('error', function (e) {
    pushJsError({
      type: 'error',
      message: String(e.message || e.error || 'error'),
      source: e.filename || '',
      line: e.lineno || 0,
      col: e.colno || 0,
      at: new Date().toISOString()
    });
  });
  window.addEventListener('unhandledrejection', function (e) {
    var reason = e.reason;
    pushJsError({
      type: 'unhandledrejection',
      message: reason && reason.message ? String(reason.message) : String(reason || 'rejection'),
      at: new Date().toISOString()
    });
  });

  function escIdent(s) {
    if (window.CSS && typeof CSS.escape === 'function') return CSS.escape(s);
    return String(s).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
  }

  function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, function (c) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
    });
  }

  function cssPath(el) {
    if (!(el instanceof Element)) return '';
    if (el.id) return '#' + escIdent(el.id);
    var parts = [];
    var node = el;
    while (node && node.nodeType === 1 && parts.length < 6) {
      var part = node.tagName.toLowerCase();
      if (node.id) {
        parts.unshift('#' + escIdent(node.id));
        break;
      }
      if (node.classList && node.classList.length) {
        var cls = Array.from(node.classList)
          .filter(function (c) { return c && c.indexOf('rw-suggest-') !== 0; })
          .slice(0, 2)
          .map(function (c) { return '.' + escIdent(c); })
          .join('');
        part += cls;
      }
      var parent = node.parentElement;
      if (parent) {
        var siblings = Array.from(parent.children).filter(function (c) { return c.tagName === node.tagName; });
        if (siblings.length > 1) part += ':nth-of-type(' + (siblings.indexOf(node) + 1) + ')';
      }
      parts.unshift(part);
      node = parent;
      if (node && node === document.body) break;
    }
    return parts.join(' > ');
  }

  function parseBrowser(ua) {
    ua = ua || '';
    if (/Edg\//.test(ua)) return 'Edge';
    if (/Chrome\//.test(ua) && !/Edg\//.test(ua)) return 'Chrome';
    if (/Firefox\//.test(ua)) return 'Firefox';
    if (/Safari\//.test(ua) && !/Chrome\//.test(ua)) return 'Safari';
    return 'Unknown';
  }

  function parseOs(ua) {
    ua = ua || '';
    if (/Android/i.test(ua)) return 'Android';
    if (/iPhone|iPad|iPod/i.test(ua)) return 'iOS';
    if (/Windows/i.test(ua)) return 'Windows';
    if (/Mac OS X|Macintosh/i.test(ua)) return 'macOS';
    if (/Linux/i.test(ua)) return 'Linux';
    return 'Unknown';
  }

  function deviceType() {
    var w = window.innerWidth || 0;
    var touch = navigator.maxTouchPoints || 0;
    var ua = navigator.userAgent || '';
    if (/Mobi|Android.*Mobile|iPhone|iPod/i.test(ua) || (touch > 0 && w < 768)) return 'mobile';
    if (/iPad|Tablet|Android(?!.*Mobile)/i.test(ua) || (touch > 0 && w < 1100)) return 'tablet';
    return 'desktop';
  }

  function buildClientContext() {
    var ua = navigator.userAgent || '';
    var uad = navigator.userAgentData || null;
    var brands = [];
    if (uad && Array.isArray(uad.brands)) {
      brands = uad.brands.map(function (b) {
        return { brand: b.brand, version: b.version };
      });
    }
    return {
      device_type: deviceType(),
      platform: (uad && uad.platform) || navigator.platform || '',
      brands: brands,
      browser: parseBrowser(ua),
      os: parseOs(ua),
      screen: screen.width + 'x' + screen.height,
      screen_avail: screen.availWidth + 'x' + screen.availHeight,
      viewport: window.innerWidth + 'x' + window.innerHeight,
      device_pixel_ratio: window.devicePixelRatio || 1,
      language: navigator.language || '',
      timezone: (Intl.DateTimeFormat().resolvedOptions().timeZone) || '',
      touch_points: navigator.maxTouchPoints || 0,
      user_agent: ua,
      recent_js_errors: recentJsErrors.slice()
    };
  }

  function loadHtml2Canvas() {
    return new Promise(function (resolve, reject) {
      if (window.html2canvas) return resolve(window.html2canvas);
      var s = document.createElement('script');
      s.src = HTML2CANVAS_SRC;
      s.async = true;
      s.onload = function () {
        if (window.html2canvas) resolve(window.html2canvas);
        else reject(new Error('html2canvas no disponible'));
      };
      s.onerror = function () { reject(new Error('No se pudo cargar html2canvas')); };
      document.head.appendChild(s);
    });
  }

  function captureAutoScreenshot(root) {
    var prev = root.style.visibility;
    root.style.visibility = 'hidden';
    return loadHtml2Canvas()
      .then(function (html2canvas) {
        return html2canvas(document.body, {
          useCORS: true,
          allowTaint: true,
          logging: false,
          scale: Math.min(1, window.devicePixelRatio || 1),
          windowWidth: window.innerWidth,
          windowHeight: window.innerHeight
        });
      })
      .then(function (canvas) {
        return new Promise(function (resolve) {
          canvas.toBlob(function (blob) {
            resolve(blob);
          }, 'image/png', 0.85);
        });
      })
      .finally(function () {
        root.style.visibility = prev || '';
      });
  }

  function mount(root) {
    if (!root || root.dataset.mounted === '1') return;
    root.dataset.mounted = '1';

    var storeUrl = root.dataset.storeUrl || 'https://daas.alfabusiness.app/api/v1/orders';
    var csrf = root.dataset.csrf || document.querySelector('meta[name="csrf-token"]')?.content || '';
    var email = root.dataset.userEmail || '';
    var logoUrl = root.dataset.logoUrl || 'https://daas.alfabusiness.app/landing/assets/brand/alfa-mark-white.png';
    if (!logoUrl || logoUrl.indexOf('__DAAS_') === 0) {
      logoUrl = (document.currentScript && document.currentScript.src
        ? new URL('/landing/assets/brand/alfa-mark-white.png', document.currentScript.src).href
        : '/landing/assets/brand/alfa-mark-white.png');
    }
    var publicKey = root.dataset.publicKey || '';
    var companyExternalId = root.dataset.companyExternalId || '';
    var requesterExternalId = root.dataset.requesterExternalId || '';
    var cssUrl = root.dataset.cssUrl || 'https://daas.alfabusiness.app/css/daas-widget.css?v=1789341018';
    if (cssUrl && !document.querySelector('link[data-daas-widget-css]')) {
      var cssLink = document.createElement('link');
      cssLink.rel = 'stylesheet';
      cssLink.href = cssUrl;
      cssLink.setAttribute('data-daas-widget-css', '1');
      document.head.appendChild(cssLink);
    }

    root.classList.add('rw-suggest-root');
    if (!root.id) root.id = 'daas-widget-root';

    root.innerHTML =
      '<button type="button" class="rw-suggest-fab" id="rw-suggest-fab" title="Panel DaaS · AlfaBusiness" aria-expanded="false" aria-label="Abrir panel DaaS">' +
      '<img id="rw-suggest-logo" src="' + escapeHtml(logoUrl) + '" alt="" width="40" height="40" decoding="async">' +
      '<span class="rw-suggest-fab-fallback" aria-hidden="true">A</span>' +
      '<span class="rw-suggest-fab-badge" id="rw-suggest-fab-badge" hidden>0</span>' +
      '<svg id="rw-suggest-icon-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"></path></svg>' +
      '</button>' +
      '<div class="rw-suggest-banner" id="rw-suggest-banner">Haz clic en el elemento a cambiar · Esc para terminar</div>' +
      '<div class="rw-suggest-hl-layer" id="rw-suggest-hl-layer" aria-hidden="true"></div>' +
      '<div class="rw-suggest-panel" id="rw-suggest-panel" role="dialog" aria-label="Panel DaaS">' +
      '<div class="rw-suggest-head">' +
      '<div class="rw-suggest-head-main">' +
      '<div class="rw-suggest-brand"><span class="rw-suggest-brand-dot" aria-hidden="true"></span>DaaS AlfaBusiness</div>' +
      '<strong id="rw-suggest-title">Nueva orden</strong>' +
      '<small id="rw-suggest-url"></small>' +
      '<small id="rw-suggest-tagline" class="rw-suggest-tagline">Describe el cambio · adjunta pantallas</small>' +
      '</div>' +
      '<button type="button" class="rw-suggest-x" id="rw-suggest-x" title="Cerrar" aria-label="Cerrar">×</button>' +
      '</div>' +
      '<div class="rw-suggest-body">' +
      '<div><span class="rw-suggest-label">Tipo</span><div class="rw-suggest-modes">' +
      '<button type="button" class="rw-suggest-chip is-on" id="rw-suggest-type-mejora" data-type="suggestion">Mejora</button>' +
      '<button type="button" class="rw-suggest-chip is-bug" id="rw-suggest-type-bug" data-type="error">Bug</button>' +
      '</div></div>' +
      '<div><span class="rw-suggest-label">Alcance</span><div class="rw-suggest-modes">' +
      '<button type="button" class="rw-suggest-chip is-on" id="rw-suggest-mode-open">Abierta</button>' +
      '<button type="button" class="rw-suggest-chip" id="rw-suggest-mode-pick">Marcar elemento</button>' +
      '</div></div>' +
      '<div id="rw-suggest-elements" class="rw-suggest-elements" hidden></div>' +
      '<div><label class="rw-suggest-label" for="rw-suggest-prompt" id="rw-suggest-prompt-label">Tu orden</label>' +
      '<textarea id="rw-suggest-prompt" class="rw-suggest-textarea" rows="4" maxlength="5000" placeholder="Ej. Cambiar el título del hero y subir el contraste del botón…"></textarea></div>' +
      '<div><span class="rw-suggest-label">Adjuntos (hasta 5)</span>' +
      '<input id="rw-suggest-files" class="rw-suggest-file-hidden" type="file" accept="image/*,video/*,audio/*,application/pdf,.pdf,.mp4,.webm,.mov,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.odt,.ods,.mp3,.wav" multiple>' +
      '<div class="rw-suggest-drop" id="rw-suggest-drop" tabindex="0" role="button">' +
      '<strong>Pega, arrastra o elige un archivo</strong>' +
      '<span>Imágenes, video, PDF. Se añade captura automática de la página.</span>' +
      '</div>' +
      '<div class="rw-suggest-previews" id="rw-suggest-previews"></div></div>' +
      '<div class="rw-suggest-msg" id="rw-suggest-msg"></div>' +
      '</div>' +
      '<div class="rw-suggest-foot">' +
      '<div id="rw-suggest-account-banner" class="rw-suggest-account-banner" hidden></div>' +
      '<div class="rw-suggest-dispatch" role="group" aria-label="Modo de envío">' +
      '<button type="button" class="rw-suggest-chip is-on" id="rw-suggest-dispatch-now" data-dispatch="now">Enviar ahora</button>' +
      '<button type="button" class="rw-suggest-chip" id="rw-suggest-dispatch-queue" data-dispatch="queue">Agregar a cola</button>' +
      '</div>' +
      '<button type="button" class="rw-suggest-submit" id="rw-suggest-submit">Enviar orden</button>' +
      '<button type="button" class="rw-suggest-flush" id="rw-suggest-flush" hidden>Enviar cola (0)</button>' +
      (email ? '<p class="rw-suggest-hint">' + escapeHtml(email) + '</p>' : '') +
      '<p class="rw-suggest-powered">Powered by <a href="https://daas.alfabusiness.app/" target="_blank" rel="noopener">daas.alfabusiness.app</a></p>' +
      '</div></div>';

    var q = function (sel) { return root.querySelector(sel); };
    var fab = q('#rw-suggest-fab');
    var logoImg = q('#rw-suggest-logo');
    if (logoImg) {
      logoImg.addEventListener('error', function () {
        logoImg.style.display = 'none';
        fab.classList.add('is-logo-fallback');
      });
      if (logoImg.complete && logoImg.naturalWidth === 0) {
        logoImg.style.display = 'none';
        fab.classList.add('is-logo-fallback');
      }
    }
    var panel = q('#rw-suggest-panel');
    var banner = q('#rw-suggest-banner');
    var hlLayer = q('#rw-suggest-hl-layer');
    var fabBadge = q('#rw-suggest-fab-badge');
    var urlEl = q('#rw-suggest-url');
    var titleEl = q('#rw-suggest-title');
    var promptLabel = q('#rw-suggest-prompt-label');
    var promptEl = q('#rw-suggest-prompt');
    var filesEl = q('#rw-suggest-files');
    var dropEl = q('#rw-suggest-drop');
    var previews = q('#rw-suggest-previews');
    var elementsBox = q('#rw-suggest-elements');
    var msgEl = q('#rw-suggest-msg');
    var submitBtn = q('#rw-suggest-submit');
    var flushBtn = q('#rw-suggest-flush');
    var accountBanner = q('#rw-suggest-account-banner');
    var dispatchNow = q('#rw-suggest-dispatch-now');
    var dispatchQueue = q('#rw-suggest-dispatch-queue');
    var closeBtn = q('#rw-suggest-x');
    var iconClose = q('#rw-suggest-icon-close');
    var modeOpen = q('#rw-suggest-mode-open');
    var modePick = q('#rw-suggest-mode-pick');
    var typeMejora = q('#rw-suggest-type-mejora');
    var typeBug = q('#rw-suggest-type-bug');

    var open = false;
    var picking = false;
    var itemType = 'suggestion';
    var dispatchMode = 'now';
    var account = null;
    var elements = [];
    var files = [];
    var hoverEl = null;
    var onMove = null;
    var onClick = null;
    var onScroll = null;
    var sending = false;
    var dragState = null;
    var dragMoved = false;

    var queueKey = 'daas-draft-queue:' + (publicKey || 'local') + ':' + (companyExternalId || 'default');

    function accountUrl() {
      try {
        var u = new URL(storeUrl, window.location.href);
        u.pathname = u.pathname.replace(/\/api\/v1\/orders\/?$/, '/api/v1/widget/account');
        if (!/widget\/account/.test(u.pathname)) {
          u.pathname = '/api/v1/widget/account';
        }
        return u.origin + u.pathname;
      } catch (e) {
        return '/api/v1/widget/account';
      }
    }

    function loadQueue() {
      try {
        var raw = localStorage.getItem(queueKey);
        var arr = raw ? JSON.parse(raw) : [];
        return Array.isArray(arr) ? arr : [];
      } catch (e) { return []; }
    }

    function saveQueue(arr) {
      try { localStorage.setItem(queueKey, JSON.stringify(arr || [])); } catch (e) { /* ignore */ }
      refreshQueueUi();
    }

    function refreshQueueUi() {
      var n = loadQueue().length;
      if (flushBtn) {
        flushBtn.hidden = n === 0;
        flushBtn.textContent = 'Enviar cola (' + n + ')';
      }
    }

    function canSubmitNow() {
      if (!account || !account.flags) return true;
      return !!account.flags.can_submit;
    }

    function canQueueNow() {
      if (!account || !account.flags) return true;
      return !!account.flags.can_queue;
    }

    function renderAccountBanner() {
      if (!accountBanner) return;
      if (!account) {
        accountBanner.hidden = true;
        return;
      }
      var flags = account.flags || {};
      if (flags.requires_payment || !flags.can_submit) {
        var bill = (account.links && account.links.billing_url) || 'https://daas.alfabusiness.app/app/billing';
        accountBanner.hidden = false;
        accountBanner.innerHTML = '<strong>' + escapeHtml(account.summary || 'Revisa tu plan DaaS') +
          '</strong> <a href="' + escapeHtml(bill) + '" target="_blank" rel="noopener">Administrar plan →</a>';
      } else if (account.summary) {
        accountBanner.hidden = false;
        accountBanner.innerHTML = '<span>' + escapeHtml(account.summary) + '</span>';
      } else {
        accountBanner.hidden = true;
      }
      if (dispatchQueue) dispatchQueue.disabled = !canQueueNow();
      if (dispatchNow) dispatchNow.disabled = !canSubmitNow();
      if (submitBtn && dispatchMode === 'now') submitBtn.disabled = !canSubmitNow() && !sending;
    }

    function fetchAccount() {
      if (!publicKey) return;
      var url = accountUrl() + '?public_key=' + encodeURIComponent(publicKey) +
        (companyExternalId ? '&company_external_id=' + encodeURIComponent(companyExternalId) : '');
      fetch(url, { method: 'GET', credentials: 'omit', headers: { Accept: 'application/json' } })
        .then(function (r) { return r.json().catch(function () { return {}; }); })
        .then(function (data) {
          account = data.data || null;
          renderAccountBanner();
        })
        .catch(function () { /* ignore */ });
    }

    function setDispatch(mode) {
      dispatchMode = mode === 'queue' ? 'queue' : 'now';
      if (dispatchNow) dispatchNow.classList.toggle('is-on', dispatchMode === 'now');
      if (dispatchQueue) dispatchQueue.classList.toggle('is-on', dispatchMode === 'queue');
      if (submitBtn) {
        submitBtn.textContent = dispatchMode === 'queue'
          ? 'Agregar a cola'
          : (itemType === 'error' ? 'Enviar corrección' : 'Enviar orden');
      }
    }

    function placeFab(left, top) {
      var size = fab.offsetWidth || FAB_SIZE;
      var maxL = Math.max(8, window.innerWidth - size - 8);
      var maxT = Math.max(8, window.innerHeight - size - 8);
      var l = Math.min(maxL, Math.max(8, left));
      var t = Math.min(maxT, Math.max(8, top));
      fab.style.left = l + 'px';
      fab.style.top = t + 'px';
      fab.style.right = 'auto';
      fab.style.bottom = 'auto';
    }

    function positionPanel() {
      if (!open) return;
      var fabRect = fab.getBoundingClientRect();
      var pw = Math.min(368, window.innerWidth - 24);
      var ph = panel.offsetHeight || 420;
      var left = fabRect.left + fabRect.width / 2 - pw / 2;
      left = Math.max(12, Math.min(left, window.innerWidth - pw - 12));
      var top = fabRect.top - ph - 12;
      if (top < 12) top = fabRect.bottom + 12;
      if (top + ph > window.innerHeight - 8) top = Math.max(12, window.innerHeight - ph - 8);
      panel.style.left = left + 'px';
      panel.style.top = top + 'px';
      panel.style.right = 'auto';
      panel.style.bottom = 'auto';
      panel.style.width = pw + 'px';
    }

    function clampFab() {
      var rect = fab.getBoundingClientRect();
      placeFab(rect.left, rect.top);
      if (open) positionPanel();
    }

    function savePos() {
      try {
        var rect = fab.getBoundingClientRect();
        localStorage.setItem(POS_KEY, JSON.stringify({ left: rect.left, top: rect.top }));
      } catch (err) { /* ignore */ }
    }

    function restorePos() {
      try {
        var raw = localStorage.getItem(POS_KEY);
        if (!raw) return false;
        var pos = JSON.parse(raw);
        if (typeof pos.left === 'number' && typeof pos.top === 'number') {
          placeFab(pos.left, pos.top);
          return true;
        }
      } catch (err) { /* ignore */ }
      return false;
    }

    function setType(next) {
      itemType = next === 'error' ? 'error' : 'suggestion';
      typeMejora.classList.toggle('is-on', itemType === 'suggestion');
      typeBug.classList.toggle('is-on', itemType === 'error');
      titleEl.textContent = itemType === 'error' ? 'Reportar bug' : 'Nueva orden';
      promptLabel.textContent = itemType === 'error' ? 'Describe el bug' : 'Tu orden';
      promptEl.placeholder = itemType === 'error'
        ? 'Describe el error y cómo reproducirlo…'
        : 'Ej. Cambiar el título del hero y subir el contraste del botón…';
      if (!open) fab.title = 'Panel DaaS · AlfaBusiness';
      if (dispatchMode === 'queue') {
        submitBtn.textContent = 'Agregar a cola';
      } else {
        submitBtn.textContent = itemType === 'error' ? 'Enviar corrección' : 'Enviar orden';
      }
    }

    function setOpen(next) {
      open = !!next;
      panel.classList.toggle('is-open', open);
      fab.classList.toggle('is-open', open);
      fab.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) {
        urlEl.textContent = window.location.href;
        fab.title = 'Cerrar panel';
        positionPanel();
        fetchAccount();
        refreshQueueUi();
      } else {
        fab.title = 'Panel DaaS · AlfaBusiness';
      }
    }

    function closePanel() {
      if (picking) stopPick();
      setOpen(false);
    }

    function showMsg(text, ok) {
      msgEl.textContent = text || '';
      msgEl.className = 'rw-suggest-msg' + (text ? (ok ? ' is-ok' : ' is-err') : '');
    }

    function targetFromEvent(e) {
      var el = e.target;
      if (!(el instanceof Element)) return null;
      if (el.closest('#daas-widget-root, #rw-suggest-root, [data-daas-widget], .rw-suggest-root')) return null;
      return el;
    }

    function rectBox(el) {
      if (!el || !el.getBoundingClientRect) return null;
      var r = el.getBoundingClientRect();
      if (!r.width && !r.height) return null;
      return r;
    }

    function paintHlBox(node, rect, kind) {
      if (!node || !rect) {
        if (node) node.style.display = 'none';
        return;
      }
      var pad = 2;
      node.style.display = 'block';
      node.style.top = Math.max(0, rect.top - pad) + 'px';
      node.style.left = Math.max(0, rect.left - pad) + 'px';
      node.style.width = Math.max(0, rect.width + pad * 2) + 'px';
      node.style.height = Math.max(0, rect.height + pad * 2) + 'px';
      node.className = 'rw-suggest-hl' + (kind ? ' is-' + kind : '');
    }

    function refreshHighlights() {
      if (!hlLayer) return;
      var html = '';
      elements.forEach(function () {
        html += '<div class="rw-suggest-hl is-picked" style="display:none"></div>';
      });
      html += '<div class="rw-suggest-hl is-hover" id="rw-suggest-hl-hover" style="display:none"></div>';
      hlLayer.innerHTML = html;
      var boxes = hlLayer.querySelectorAll('.rw-suggest-hl.is-picked');
      elements.forEach(function (item, i) {
        var el = null;
        try { el = document.querySelector(item.selector); } catch (err) { el = null; }
        paintHlBox(boxes[i], rectBox(el), 'picked');
      });
      paintHlBox(hlLayer.querySelector('#rw-suggest-hl-hover'), rectBox(hoverEl), 'hover');
    }

    function updateFabBadge() {
      if (!fabBadge) return;
      if (!elements.length) {
        fabBadge.hidden = true;
        fabBadge.textContent = '0';
        return;
      }
      fabBadge.hidden = false;
      fabBadge.textContent = String(elements.length);
    }

    function clearPickedClasses() {
      document.querySelectorAll('.rw-suggest-picked').forEach(function (n) {
        n.classList.remove('rw-suggest-picked');
      });
      document.querySelectorAll('.rw-suggest-hover').forEach(function (n) {
        n.classList.remove('rw-suggest-hover');
      });
    }

    function renderElements() {
      updateFabBadge();
      refreshHighlights();
      if (!elementsBox) return;
      if (!elements.length) {
        elementsBox.hidden = true;
        elementsBox.innerHTML = '';
        return;
      }
      elementsBox.hidden = false;
      elementsBox.innerHTML =
        '<div class="rw-suggest-elements-head">' +
        '<span class="rw-suggest-label">Elementos marcados (' + elements.length + ')</span>' +
        '<button type="button" class="rw-suggest-elements-clear" id="rw-suggest-elements-clear">Limpiar</button>' +
        '</div>' +
        elements.map(function (el, i) {
          var label = (el.tag || 'el') + (el.text ? ' · ' + el.text.slice(0, 48) : '');
          var shortSel = el.selector.length > 64
            ? el.selector.slice(0, 28) + '…' + el.selector.slice(-28)
            : el.selector;
          return '<div class="rw-suggest-el"><div><div class="rw-suggest-el-title">' + escapeHtml(label) +
            '</div><code title="' + escapeHtml(el.selector) + '">' + escapeHtml(shortSel) +
            '</code></div><button type="button" data-remove="' + i + '" aria-label="Quitar">×</button></div>';
        }).join('');
      try {
        elementsBox.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      } catch (err) { /* ignore */ }
    }

    function capture(el) {
      var selector = cssPath(el);
      if (!selector) return;
      if (elements.some(function (x) { return x.selector === selector; })) return;
      if (elements.length >= 20) return;
      var text = (el.innerText || el.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 180);
      var html = (el.outerHTML || '').replace(/\s+/g, ' ').trim().slice(0, 1200);
      el.classList.add('rw-suggest-picked');
      elements.push({ selector: selector, tag: el.tagName.toLowerCase(), text: text, html_snippet: html });
      renderElements();
    }

    function finishPick(reopen) {
      stopPick();
      if (reopen) {
        setOpen(true);
        window.setTimeout(function () {
          if (elementsBox && !elementsBox.hidden) {
            try { elementsBox.scrollIntoView({ block: 'nearest' }); } catch (err) { /* ignore */ }
          }
          var body = panel && panel.querySelector('.rw-suggest-body');
          if (body) body.scrollTop = 0;
        }, 40);
      }
    }

    function startPick() {
      if (picking) return;
      picking = true;
      setOpen(false);
      banner.classList.add('is-on');
      document.body.classList.add('rw-suggest-picking');
      modePick.classList.add('is-on');
      modeOpen.classList.remove('is-on');
      refreshHighlights();
      onMove = function (e) {
        var el = targetFromEvent(e);
        if (!el || el === hoverEl) return;
        if (hoverEl) hoverEl.classList.remove('rw-suggest-hover');
        hoverEl = el;
        el.classList.add('rw-suggest-hover');
        paintHlBox(hlLayer && hlLayer.querySelector('#rw-suggest-hl-hover'), rectBox(el), 'hover');
      };
      onClick = function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        var el = targetFromEvent(e);
        if (!el) return;
        capture(el);
        // Stay in pick mode so the user can mark several; Esc / FAB ends picking.
        banner.textContent = 'Marcado (' + elements.length + '). Clic otro · Esc para volver al panel';
      };
      onScroll = function () { refreshHighlights(); };
      document.addEventListener('mousemove', onMove, true);
      document.addEventListener('click', onClick, true);
      window.addEventListener('scroll', onScroll, true);
      window.addEventListener('resize', onScroll);
    }

    function stopPick() {
      picking = false;
      banner.classList.remove('is-on');
      banner.textContent = 'Haz clic en el elemento a cambiar · Esc para terminar';
      document.body.classList.remove('rw-suggest-picking');
      modePick.classList.remove('is-on');
      modeOpen.classList.add('is-on');
      if (hoverEl) {
        hoverEl.classList.remove('rw-suggest-hover');
        hoverEl = null;
      }
      if (onMove) document.removeEventListener('mousemove', onMove, true);
      if (onClick) document.removeEventListener('click', onClick, true);
      if (onScroll) {
        window.removeEventListener('scroll', onScroll, true);
        window.removeEventListener('resize', onScroll);
      }
      onMove = onClick = onScroll = null;
      refreshHighlights();
    }

    function extForType(type) {
      var map = {
        'image/jpeg': 'jpg', 'image/png': 'png', 'image/webp': 'webp', 'image/gif': 'gif',
        'application/pdf': 'pdf', 'video/mp4': 'mp4', 'video/webm': 'webm', 'video/quicktime': 'mov',
        'audio/mpeg': 'mp3', 'audio/wav': 'wav', 'text/plain': 'txt', 'text/csv': 'csv',
        'application/zip': 'zip'
      };
      return map[type] || 'bin';
    }

    function fileKind(file) {
      var type = (file && file.type ? file.type : '').toLowerCase();
      var name = (file && file.name ? file.name : '').toLowerCase();
      if (type.indexOf('image/') === 0 || /\.(jpe?g|png|gif|webp|bmp)$/i.test(name)) return 'image';
      if (type.indexOf('video/') === 0 || /\.(mp4|m4v|webm|mov|ogv)$/i.test(name)) return 'video';
      if (type.indexOf('audio/') === 0 || /\.(mp3|wav|m4a)$/i.test(name)) return 'audio';
      if (type === 'application/pdf' || /\.pdf$/i.test(name)) return 'pdf';
      return 'file';
    }

    function maxBytesForKind(kind) {
      if (kind === 'video') return 50 * 1024 * 1024;
      if (kind === 'audio' || kind === 'pdf' || kind === 'file') return 20 * 1024 * 1024;
      return 8 * 1024 * 1024;
    }

    function isAllowedFile(file) {
      if (!file) return false;
      var type = (file.type || '').toLowerCase();
      var name = file.name || '';
      if (type.indexOf('image/') === 0 || type.indexOf('video/') === 0 || type.indexOf('audio/') === 0) return true;
      if ([
        'application/pdf', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/zip', 'text/plain', 'text/csv'
      ].indexOf(type) !== -1) return true;
      return /\.(jpe?g|png|gif|webp|bmp|pdf|mp4|m4v|webm|mov|ogv|mp3|wav|m4a|txt|csv|doc|docx|xls|xlsx|ppt|pptx|zip|odt|ods)$/i.test(name);
    }

    function namedFile(file, fallback) {
      var type = file.type || '';
      var name = (file.name || '').trim();
      if (!name || name === 'image.png' || name === 'blob' || name === 'image.jpg') {
        name = (fallback || 'archivo') + '-' + Date.now() + '.' + extForType(type || 'image/png');
      }
      if (file.name === name) return file;
      return new File([file], name, { type: type || 'application/octet-stream', lastModified: file.lastModified || Date.now() });
    }

    function dataUrlToFile(dataUrl, name) {
      var parts = String(dataUrl).split(',');
      var mimeMatch = parts[0] && parts[0].match(/data:(image\/[a-zA-Z0-9.+-]+)/);
      var mime = (mimeMatch && mimeMatch[1]) || 'image/png';
      var bin = atob(parts[1] || '');
      var arr = new Uint8Array(bin.length);
      for (var i = 0; i < bin.length; i++) arr[i] = bin.charCodeAt(i);
      return new File([arr], name || ('captura-' + Date.now() + '.' + extForType(mime)), { type: mime });
    }

    function filesFromHtml(html) {
      var out = [];
      if (!html) return out;
      var re = /<img[^>]+src=["'](data:image\/[^"']+)["']/gi;
      var m;
      while ((m = re.exec(html)) && out.length < 5) {
        try { out.push(dataUrlToFile(m[1])); } catch (err) { /* ignore */ }
      }
      return out;
    }

    function collectClipboardFiles(dt) {
      var out = [];
      var seen = [];
      function push(file) {
        if (!file || !isAllowedFile(file)) return;
        var key = file.size + ':' + file.type + ':' + (file.lastModified || 0);
        if (seen.indexOf(key) !== -1) return;
        seen.push(key);
        out.push(namedFile(file, fileKind(file) === 'image' ? 'captura' : 'archivo'));
      }
      if (dt.files && dt.files.length) Array.prototype.forEach.call(dt.files, push);
      if (dt.items) {
        for (var i = 0; i < dt.items.length; i++) {
          if (dt.items[i].kind === 'file') push(dt.items[i].getAsFile());
        }
      }
      if (!out.length) filesFromHtml(dt.getData('text/html') || '').forEach(push);
      return out;
    }

    function addFiles(list) {
      var incoming = Array.prototype.slice.call(list || []).filter(isAllowedFile);
      var rejected = Array.prototype.slice.call(list || []).filter(function (f) { return f && !isAllowedFile(f); });
      if (rejected.length && !incoming.length) {
        showMsg('Ese tipo de archivo no está permitido.', false);
        return 0;
      }
      if (!incoming.length) return 0;
      var room = 5 - files.length;
      if (room <= 0) {
        showMsg('Máximo 5 archivos.', false);
        return 0;
      }
      var added = 0;
      incoming.slice(0, room).forEach(function (file) {
        var kind = fileKind(file);
        var max = maxBytesForKind(kind);
        if (file.size > max) {
          showMsg('Un archivo supera ' + Math.round(max / (1024 * 1024)) + ' MB y se omitió.', false);
          return;
        }
        files.push(namedFile(file, kind === 'image' ? 'captura' : 'archivo'));
        added++;
      });
      renderPreviews();
      if (incoming.length > room) showMsg('Máximo 5 archivos.', true);
      return added;
    }

    function renderPreviews() {
      previews.innerHTML = '';
      files.forEach(function (file, i) {
        var wrap = document.createElement('div');
        wrap.className = 'rw-suggest-preview';
        var kind = fileKind(file);
        if (kind === 'image') {
          var img = document.createElement('img');
          img.alt = file.name || 'Captura';
          img.src = URL.createObjectURL(file);
          wrap.appendChild(img);
        } else if (kind === 'video') {
          var video = document.createElement('video');
          video.src = URL.createObjectURL(file);
          video.muted = true;
          video.preload = 'metadata';
          wrap.appendChild(video);
        } else {
          var chip = document.createElement('div');
          chip.className = 'rw-suggest-filechip';
          var label = document.createElement('em');
          label.textContent = ((file.name || '').split('.').pop() || kind).toUpperCase().slice(0, 8);
          var name = document.createElement('span');
          name.textContent = file.name || 'Archivo';
          chip.appendChild(label);
          chip.appendChild(name);
          wrap.appendChild(chip);
        }
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.setAttribute('data-remove-file', String(i));
        btn.setAttribute('aria-label', 'Quitar archivo');
        btn.textContent = '×';
        wrap.appendChild(btn);
        previews.appendChild(wrap);
      });
    }

    fab.addEventListener('pointerdown', function (e) {
      if (e.button != null && e.button !== 0) return;
      dragMoved = false;
      var rect = fab.getBoundingClientRect();
      dragState = { pointerId: e.pointerId, startX: e.clientX, startY: e.clientY, origLeft: rect.left, origTop: rect.top };
      try { fab.setPointerCapture(e.pointerId); } catch (err) { /* ignore */ }
      e.preventDefault();
    });
    fab.addEventListener('pointermove', function (e) {
      if (!dragState || e.pointerId !== dragState.pointerId) return;
      var dx = e.clientX - dragState.startX;
      var dy = e.clientY - dragState.startY;
      if (!dragMoved && Math.hypot(dx, dy) < DRAG_THRESHOLD) return;
      dragMoved = true;
      fab.classList.add('is-dragging');
      placeFab(dragState.origLeft + dx, dragState.origTop + dy);
      if (open) positionPanel();
    });
    function endDrag(e) {
      if (!dragState || e.pointerId !== dragState.pointerId) return;
      var wasDrag = dragMoved;
      fab.classList.remove('is-dragging');
      dragState = null;
      if (wasDrag) savePos();
      else if (picking) finishPick(true);
      else setOpen(!open);
    }
    fab.addEventListener('pointerup', endDrag);
    fab.addEventListener('pointercancel', endDrag);
    window.addEventListener('resize', clampFab);

    closeBtn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      closePanel();
    });

    modeOpen.addEventListener('click', function () { stopPick(); });
    modePick.addEventListener('click', function () { startPick(); });
    typeMejora.addEventListener('click', function () { setType('suggestion'); });
    typeBug.addEventListener('click', function () { setType('error'); });

    elementsBox.addEventListener('click', function (e) {
      if (e.target && e.target.id === 'rw-suggest-elements-clear') {
        elements = [];
        clearPickedClasses();
        renderElements();
        return;
      }
      var btn = e.target.closest('[data-remove]');
      if (!btn) return;
      var idx = parseInt(btn.getAttribute('data-remove'), 10);
      var removed = elements.splice(idx, 1)[0];
      if (removed && removed.selector) {
        try {
          var node = document.querySelector(removed.selector);
          if (node) node.classList.remove('rw-suggest-picked');
        } catch (err) { /* ignore */ }
      }
      renderElements();
    });

    filesEl.addEventListener('change', function () {
      addFiles(filesEl.files || []);
      filesEl.value = '';
    });
    dropEl.addEventListener('click', function () { filesEl.click(); });
    dropEl.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); filesEl.click(); }
    });
    dropEl.addEventListener('dragover', function (e) { e.preventDefault(); dropEl.classList.add('is-over'); });
    dropEl.addEventListener('dragleave', function () { dropEl.classList.remove('is-over'); });
    dropEl.addEventListener('drop', function (e) {
      e.preventDefault();
      dropEl.classList.remove('is-over');
      var n = addFiles((e.dataTransfer && e.dataTransfer.files) || []);
      if (n) showMsg(n === 1 ? 'Archivo añadido.' : n + ' archivos añadidos.', true);
    });
    previews.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-remove-file]');
      if (!btn) return;
      files.splice(parseInt(btn.getAttribute('data-remove-file'), 10), 1);
      renderPreviews();
    });

    document.addEventListener('paste', function (e) {
      if (!open) return;
      var dt = e.clipboardData;
      if (!dt) return;
      var imgs = collectClipboardFiles(dt);
      if (imgs.length) {
        e.preventDefault();
        var n = addFiles(imgs);
        if (n) showMsg(n === 1 ? 'Archivo pegado.' : n + ' archivos pegados.', true);
        return;
      }
      var inside = e.target && e.target.closest && e.target.closest('#rw-suggest-panel');
      if (!inside || e.target === promptEl) return;
      var text = (dt.getData('text/plain') || '').trim();
      if (!text) return;
      e.preventDefault();
      promptEl.value = promptEl.value ? promptEl.value.replace(/\s+$/, '') + '\n' + text : text;
      showMsg('Texto pegado en la orden.', true);
    }, true);

    submitBtn.addEventListener('click', function () {
      if (sending) return;
      showMsg('', false);
      var prompt = (promptEl.value || '').trim();
      if (!prompt) {
        showMsg(itemType === 'error' ? 'Describe el bug encontrado.' : 'Describe el cambio.', false);
        return;
      }
      if (dispatchMode === 'queue') {
        if (!canQueueNow()) {
          showMsg('Tu plan no permite encolar ahora. Revisa facturación DaaS.', false);
          return;
        }
        var q = loadQueue();
        q.push({
          type: itemType,
          prompt: prompt,
          url: window.location.href,
          page_title: document.title || '',
          selected_elements: elements.slice(),
          at: new Date().toISOString()
        });
        saveQueue(q);
        promptEl.value = '';
        elements = [];
        files = [];
        clearPickedClasses();
        renderElements();
        renderPreviews();
        showMsg('Agregado a la cola (' + q.length + '). Envía la cola cuando termines.', true);
        return;
      }
      if (!canSubmitNow()) {
        showMsg('Sin cupo o pago pendiente. Administra tu plan en DaaS.', false);
        return;
      }
      if (!storeUrl || (!publicKey && !csrf)) {
        showMsg('Configuración DaaS incompleta. Recarga la página.', false);
        return;
      }
      sending = true;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Capturando…';

      var ctx = buildClientContext();
      var shotPromise = captureAutoScreenshot(root).then(function (blob) {
        return blob;
      }).catch(function (err) {
        ctx.screenshot_error = String(err && err.message ? err.message : err);
        return null;
      });

      shotPromise.then(function (shotBlob) {
        submitBtn.textContent = 'Enviando…';
        return postOrder({
          type: itemType,
          prompt: prompt,
          url: window.location.href,
          page_title: document.title || '',
          selected_elements: elements,
          files: files,
          shotBlob: shotBlob,
          ctx: ctx
        });
      }).then(function (data) {
        showMsg(data.message || 'Orden enviada.', true);
        promptEl.value = '';
        elements = [];
        files = [];
        clearPickedClasses();
        renderElements();
        renderPreviews();
        fetchAccount();
        setTimeout(function () { setOpen(false); showMsg('', false); setType(itemType); }, 1800);
      }).catch(function (err) {
        showMsg(err.message || 'Error al enviar.', false);
      }).finally(function () {
        sending = false;
        submitBtn.disabled = false;
        setDispatch(dispatchMode);
        setType(itemType);
      });
    });

    function postOrder(payload) {
      var fd = new FormData();
      if (publicKey) fd.append('public_key', publicKey);
      if (companyExternalId) fd.append('company_external_id', companyExternalId);
      if (requesterExternalId) fd.append('requester_external_id', requesterExternalId);
      if (email) fd.append('requester_email', email);
      fd.append('type', payload.type || 'suggestion');
      fd.append('url', payload.url || window.location.href);
      fd.append('page_title', payload.page_title || '');
      fd.append('prompt', payload.prompt);
      fd.append('viewport', window.innerWidth + 'x' + window.innerHeight);
      fd.append('selected_elements', JSON.stringify(payload.selected_elements || []));
      fd.append('client_context', JSON.stringify(payload.ctx || buildClientContext()));
      (payload.files || []).forEach(function (file) { fd.append('attachments[]', file); });
      if (payload.shotBlob) {
        fd.append('auto_screenshot', payload.shotBlob, 'auto-screenshot.png');
      }
      var headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      };
      if (csrf && !publicKey) headers['X-CSRF-TOKEN'] = csrf;
      return fetch(storeUrl, {
        method: 'POST',
        credentials: publicKey ? 'omit' : 'same-origin',
        headers: headers,
        body: fd
      }).then(function (res) {
        return res.json().catch(function () { return {}; }).then(function (data) {
          if (!res.ok || !data.ok) {
            var first = data.message || (data.errors && Object.values(data.errors)[0] && Object.values(data.errors)[0][0]);
            throw new Error(first || 'No se pudo enviar la orden.');
          }
          return data;
        });
      });
    }

    if (dispatchNow) dispatchNow.addEventListener('click', function () { setDispatch('now'); });
    if (dispatchQueue) dispatchQueue.addEventListener('click', function () { setDispatch('queue'); });

    if (flushBtn) {
      flushBtn.addEventListener('click', function () {
        if (sending) return;
        var q = loadQueue();
        if (!q.length) return;
        if (!canSubmitNow()) {
          showMsg('Sin cupo o pago pendiente. No se puede enviar la cola.', false);
          return;
        }
        sending = true;
        flushBtn.disabled = true;
        submitBtn.disabled = true;
        var i = 0;
        function next() {
          if (i >= q.length) {
            saveQueue([]);
            sending = false;
            flushBtn.disabled = false;
            submitBtn.disabled = false;
            setDispatch(dispatchMode);
            fetchAccount();
            showMsg('Cola enviada (' + i + ' órdenes).', true);
            return;
          }
          flushBtn.textContent = 'Enviando ' + (i + 1) + '/' + q.length + '…';
          var item = q[i];
          postOrder({
            type: item.type,
            prompt: item.prompt,
            url: item.url,
            page_title: item.page_title,
            selected_elements: item.selected_elements || [],
            files: [],
            shotBlob: null,
            ctx: buildClientContext()
          }).then(function () {
            i += 1;
            next();
          }).catch(function (err) {
            // keep remaining including failed
            saveQueue(q.slice(i));
            sending = false;
            flushBtn.disabled = false;
            submitBtn.disabled = false;
            setDispatch(dispatchMode);
            showMsg('Error en ítem ' + (i + 1) + ': ' + (err.message || 'falló'), false);
          });
        }
        next();
      });
    }

    refreshQueueUi();

    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      if (picking) { finishPick(true); return; }
      if (open) closePanel();
    });

    if (!restorePos()) {
      placeFab(window.innerWidth - FAB_SIZE - 20, window.innerHeight - FAB_SIZE - 28);
    }
    setType('suggestion');
  }

  function boot() {
    var root = document.getElementById('daas-widget-root')
      || document.getElementById('rw-suggest-root')
      || document.querySelector('[data-daas-widget]');
    if (root) mount(root);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
