/**
 * Si script.js falla a mitad (IIFE de compartir reels), ZuitchOpenReelShareSheet nunca se define.
 * Este archivo va JUSTO DESPUÉS de script.js: solo actúa si la función sigue ausente.
 * UI alineada con #zuitch-reel-share-sheet (pastillas + rejilla social), sin intent: en WebView.
 */
(function () {
  window.__ZUITCH_REEL_FALLBACK_BUILD = 'pill-grid-v2';
  if (typeof window.ZuitchOpenReelShareSheet === 'function') {
    return;
  }

  var ZUITCH_SVG_SHARE_NODES = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="zuitch-pill-share-svg" aria-hidden="true"><path d="M18 16.08c-.76 0-1.44.3-1.96.85L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81A3 3 0 0021 5a3 3 0 00-3-3 3 3 0 00-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9a3 3 0 00-3 3 3 3 0 003 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.91 2.92 2.91s2.92-1.3 2.92-2.91A2.92 2.92 0 0018 16.08z"/></svg>';

  function zuitchFallbackIntent(text) {
    text = String(text || '');
    if (!text.length || !/Android/i.test(navigator.userAgent || '')) {
      return false;
    }
    var ua = navigator.userAgent || '';
    if (/\bwv\b/i.test(ua) || window.__woReelsAndroidWebview) {
      return false;
    }
    try {
      window.location.href = 'intent:#Intent;action=android.intent.action.SEND;type=text/plain;S.android.intent.extra.TEXT=' + encodeURIComponent(text) + ';end';
      return true;
    } catch (e) {
      return false;
    }
  }
  if (typeof window.zuitchTryAndroidSendPlainText !== 'function') {
    window.zuitchTryAndroidSendPlainText = zuitchFallbackIntent;
  }

  function zuitchFbSocialFlags() {
    var d = window.__ZUITCH_SOCIAL_SHARE;
    if (!d || typeof d !== 'object') {
      return { twitter: true, facebook: true, whatsapp: true, linkedin: true, telegram: true, pinterest: true };
    }
    return d;
  }

  function zuitchFbSocialRowHtml(shareUrl, shareTitle, flags) {
    var u = encodeURIComponent(shareUrl);
    var t = encodeURIComponent(shareTitle || '');
    var h = [];
    h.push('<div class="share_modal_social_icos zuitch-reel-share-social-inner">');
    if (flags.twitter) {
      h.push('<a class="social-btn-parent" href="https://twitter.com/intent/tweet?text=' + u + '" target="_blank" rel="noopener noreferrer">'
        + '<div class="social-btn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="feather" fill="#55acee"><path d="M5,3H19A2,2 0 0,1 21,5V19A2,2 0 0,1 19,21H5A2,2 0 0,1 3,19V5A2,2 0 0,1 5,3M17.71,9.33C18.19,8.93 18.75,8.45 19,7.92C18.59,8.13 18.1,8.26 17.56,8.33C18.06,7.97 18.47,7.5 18.68,6.86C18.16,7.14 17.63,7.38 16.97,7.5C15.42,5.63 11.71,7.15 12.37,9.95C9.76,9.79 8.17,8.61 6.85,7.16C6.1,8.38 6.75,10.23 7.64,10.74C7.18,10.71 6.83,10.57 6.5,10.41C6.54,11.95 7.39,12.69 8.58,13.09C8.22,13.16 7.82,13.18 7.44,13.12C7.81,14.19 8.58,14.86 9.9,15C9,15.76 7.34,16.29 6,16.08C7.15,16.81 8.46,17.39 10.28,17.31C14.69,17.11 17.64,13.95 17.71,9.33Z" /></svg></div> <span>Twitter</span></a>');
    }
    if (flags.facebook) {
      h.push('<a class="social-btn-parent" href="https://www.facebook.com/sharer/sharer.php?u=' + u + '" target="_blank" rel="noopener noreferrer">'
        + '<div class="social-btn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="feather" fill="#337ab7"><path d="M5,3H19A2,2 0 0,1 21,5V19A2,2 0 0,1 19,21H5A2,2 0 0,1 3,19V5A2,2 0 0,1 5,3M18,5H15.5A3.5,3.5 0 0,0 12,8.5V11H10V14H12V21H15V14H18V11H15V9A1,1 0 0,1 16,8H18V5Z" /></svg></div> <span>Facebook</span></a>');
    }
    if (flags.whatsapp) {
      h.push('<a class="social-btn-parent" href="https://api.whatsapp.com/send?text=' + u + '" data-action="share/whatsapp/share" target="_blank" rel="noopener noreferrer">'
        + '<div class="social-btn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="feather" fill="#04aa24"><path d="M16.75,13.96C17,14.09 17.16,14.16 17.21,14.26C17.27,14.37 17.25,14.87 17,15.44C16.8,16 15.76,16.54 15.3,16.56C14.84,16.58 14.83,16.92 12.34,15.83C9.85,14.74 8.35,12.08 8.23,11.91C8.11,11.74 7.27,10.53 7.31,9.3C7.36,8.08 8,7.5 8.26,7.26C8.5,7 8.77,6.97 8.94,7H9.41C9.56,7 9.77,6.94 9.96,7.45L10.65,9.32C10.71,9.45 10.75,9.6 10.66,9.76L10.39,10.17L10,10.59C9.88,10.71 9.74,10.84 9.88,11.09C10,11.35 10.5,12.18 11.2,12.87C12.11,13.75 12.91,14.04 13.15,14.17C13.39,14.31 13.54,14.29 13.69,14.13L14.5,13.19C14.69,12.94 14.85,13 15.08,13.08L16.75,13.96M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22C10.03,22 8.2,21.43 6.65,20.45L2,22L3.55,17.35C2.57,15.8 2,13.97 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12C4,13.72 4.54,15.31 5.46,16.61L4.5,19.5L7.39,18.54C8.69,19.46 10.28,20 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4Z" /></svg></div> <span>WhatsApp</span></a>');
    }
    if (flags.pinterest) {
      h.push('<a class="social-btn-parent" href="https://pinterest.com/pin/create/button/?url=' + u + '" target="_blank" rel="noopener noreferrer">'
        + '<div class="social-btn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="feather" fill="#cb2027"><path d="M13,16.2C12.2,16.2 11.43,15.86 10.88,15.28L9.93,18.5L9.86,18.69L9.83,18.67C9.64,19 9.29,19.2 8.9,19.2C8.29,19.2 7.8,18.71 7.8,18.1C7.8,18.05 7.81,18 7.81,17.95H7.8L7.85,17.77L9.7,12.21C9.7,12.21 9.5,11.59 9.5,10.73C9.5,9 10.42,8.5 11.16,8.5C11.91,8.5 12.58,8.76 12.58,9.81C12.58,11.15 11.69,11.84 11.69,12.81C11.69,13.55 12.29,14.16 13.03,14.16C15.37,14.16 16.2,12.4 16.2,10.75C16.2,8.57 14.32,6.8 12,6.8C9.68,6.8 7.8,8.57 7.8,10.75C7.8,11.42 8,12.09 8.34,12.68C8.43,12.84 8.5,13 8.5,13.2A1,1 0 0,1 7.5,14.2C7.13,14.2 6.79,14 6.62,13.7C6.08,12.81 5.8,11.79 5.8,10.75C5.8,7.47 8.58,4.8 12,4.8C15.42,4.8 18.2,7.47 18.2,10.75C18.2,13.37 16.57,16.2 13,16.2M20,2H4C2.89,2 2,2.89 2,4V20A2,2 0 0,0 4,22H20A2,2 0 0,0 22,20V4C22,2.89 21.1,2 20,2Z" /></svg></div> <span>Pinterest</span></a>');
    }
    if (flags.linkedin) {
      h.push('<a class="social-btn-parent" href="https://www.linkedin.com/shareArticle?mini=true&amp;url=' + u + '" target="_blank" rel="noopener noreferrer">'
        + '<div class="social-btn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="feather" fill="#007bb6"><path d="M19,3A2,2 0 0,1 21,5V19A2,2 0 0,1 19,21H5A2,2 0 0,1 3,19V5A2,2 0 0,1 5,3H19M18.5,18.5V13.2A3.26,3.26 0 0,0 15.24,9.94C14.39,9.94 13.4,10.46 12.92,11.24V10.13H10.13V18.5H12.92V13.57C12.92,12.8 13.54,12.17 14.31,12.17A1.4,1.4 0 0,1 15.71,13.57V18.5H18.5M6.88,8.56A1.68,1.68 0 0,0 8.56,6.88C8.56,5.95 7.81,5.19 6.88,5.19A1.69,1.69 0 0,0 5.19,6.88C5.19,7.81 5.95,8.56 6.88,8.56M8.27,18.5V10.13H5.5V18.5H8.27Z" /></svg></div> <span>LinkedIn</span></a>');
    }
    if (flags.telegram) {
      h.push('<a class="social-btn-parent" href="https://telegram.me/share/url?url=' + u + '&amp;text=' + t + '" target="_blank" rel="noopener noreferrer">'
        + '<div class="social-btn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="feather" fill="#239bcd"><path d="M9.78,18.65L10.06,14.42L17.74,7.5C18.08,7.19 17.67,7.04 17.22,7.31L7.74,13.3L3.64,12C2.76,11.75 2.75,11.14 3.84,10.7L19.81,4.54C20.54,4.21 21.24,4.72 20.96,5.84L18.24,18.65C18.05,19.56 17.5,19.78 16.74,19.36L12.6,16.3L10.61,18.23C10.38,18.46 10.19,18.65 9.78,18.65Z" /></svg></div> <span>Telegram</span></a>');
    }
    h.push('<a href="#" class="social-btn-parent zfb-social-copy" data-copy-mode="instagram">'
      + '<div class="social-btn"><i class="fa fa-instagram" style="font-size:20px;color:#c13584"></i></div> <span>Instagram</span></a>');
    h.push('<a href="#" class="social-btn-parent zfb-social-copy" data-copy-mode="tiktok">'
      + '<div class="social-btn"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="#000"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43V7.39a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.82z"/></svg></div> <span>TikTok</span></a>');
    h.push('</div>');
    return h.join('');
  }

  function zuitchFbQuickRowHtml() {
    var ua = navigator.userAgent || '';
    var androidNoShare = typeof navigator.share !== 'function' && /Android/i.test(ua);
    var isWv = /\bwv\b/i.test(ua) || window.__woReelsAndroidWebview;
    var showIntentPill = androidNoShare && !isWv;
    var intentPill = showIntentPill
      ? '<button type="button" class="zuitch-reel-share-pill zuitch-reel-share-pill-intent zfb-share-act" data-zfb="android_intent" title="Menú del sistema (Chrome Android)">'
      + '<i class="fa fa-android" aria-hidden="true"></i>'
      + '<span class="zuitch-reel-share-pill-lbl">Apps</span></button>'
      : '';
    return ''
      + '<div class="zuitch-reel-share-quick-row zuitch-reel-share-quick-row--with-intent' + (androidNoShare ? ' is-android-no-ns' : '') + '">'
      + '<button type="button" class="zuitch-reel-share-pill zfb-share-act" data-zfb="copy" title="Copiar enlace">'
      + '<i class="fa fa-link" aria-hidden="true"></i>'
      + '<span class="zuitch-reel-share-pill-lbl">Copiar enlace</span></button>'
      + '<button type="button" class="zuitch-reel-share-pill zfb-share-act" data-zfb="native" title="Compartir">'
      + '<span class="zuitch-reel-share-pill-ico-svg" aria-hidden="true">' + ZUITCH_SVG_SHARE_NODES + '</span>'
      + '<span class="zuitch-reel-share-pill-lbl">Compartir</span></button>'
      + intentPill
      + '</div>';
  }

  function zuitchFbZuitchBlockHtml(postId, ownerId) {
    if (!window.__ZUITCH_LOGGED_IN || !postId) {
      return '';
    }
    return ''
      + '<div class="zuitch-reel-share-zuitch-block">'
      + '<p class="zuitch-reel-share-zuitch-hint">Texto, menciones, línea de tiempo, página o grupo: usa el mismo formulario que en el feed.</p>'
      + '<button type="button" class="btn btn-main btn-block zfb-open-zuitch-share">Compartir en Zuitch</button>'
      + '</div>';
  }

  function zuitchFbCopyText(text) {
    text = String(text || '');
    if (navigator.clipboard && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(text).then(function () { return true; }).catch(function () { return false; });
    }
    try {
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly', '');
      ta.style.position = 'fixed';
      ta.style.left = '-9999px';
      document.body.appendChild(ta);
      ta.select();
      var ok = document.execCommand('copy');
      document.body.removeChild(ta);
      return Promise.resolve(ok);
    } catch (e) {
      return Promise.resolve(false);
    }
  }

  function zuitchFbBanner(root, msg) {
    var body = root.querySelector('.zuitch-reel-share-body');
    if (!body) {
      return;
    }
    var b = body.querySelector('.zuitch-reel-share-inline-alert');
    if (!b) {
      b = document.createElement('div');
      b.className = 'zuitch-reel-share-inline-alert';
      b.setAttribute('role', 'status');
      body.insertBefore(b, body.firstChild);
    }
    b.textContent = msg;
    b.classList.add('is-visible');
    clearTimeout(window.__zfbBannerTimer);
    window.__zfbBannerTimer = setTimeout(function () {
      b.classList.remove('is-visible');
      b.textContent = '';
    }, 2600);
  }

  window.ZuitchOpenReelShareSheet = function (triggerEl) {
    var el = triggerEl && triggerEl.nodeType ? triggerEl : (triggerEl && triggerEl[0]) ? triggerEl[0] : null;
    var shareUrl = el && el.getAttribute ? (el.getAttribute('data-share-url') || '') : '';
    var shareTitle = el && el.getAttribute ? (el.getAttribute('data-share-title') || '') : '';
    var postId = el && el.getAttribute ? (parseInt(el.getAttribute('data-post-id'), 10) || 0) : 0;
    var ownerId = el && el.getAttribute ? (parseInt(el.getAttribute('data-owner-id'), 10) || 0) : 0;
    if (!shareUrl) {
      shareUrl = window.location.href || '';
    }
    if (!shareTitle) {
      shareTitle = typeof document !== 'undefined' && document.title ? document.title : '';
    }

    var old = document.getElementById('zuitch-reel-share-fallback-ui');
    if (old && old.parentNode) {
      old.parentNode.removeChild(old);
    }

    var flags = zuitchFbSocialFlags();
    var quickHtml = zuitchFbQuickRowHtml();
    var socialHtml = zuitchFbSocialRowHtml(shareUrl, shareTitle, flags);
    var zuitchHtml = zuitchFbZuitchBlockHtml(postId, ownerId);

    var wrap = document.createElement('div');
    wrap.id = 'zuitch-reel-share-fallback-ui';
    wrap.className = 'zuitch-reel-share-fallback-root';
    wrap.setAttribute('role', 'dialog');
    wrap.setAttribute('aria-modal', 'true');
    wrap.setAttribute('aria-label', 'Compartir');

    wrap.innerHTML = ''
      + '<div class="modal-dialog zuitch-reel-share-dialog" role="document" style="position:relative;z-index:1">'
      + '  <div class="modal-content zuitch-reel-share-content">'
      + '    <div class="zuitch-reel-share-head">'
      + '      <span class="zuitch-reel-share-title">Compartir</span>'
      + '      <button type="button" class="close zuitch-reel-share-close zfb-share-close" aria-label="Close"><span aria-hidden="true">\u00d7</span></button>'
      + '    </div>'
      + '    <div class="modal-body zuitch-reel-share-body">'
      + '      <div class="zuitch-reel-share-logged" style="display:none;">'
      + '        <div class="zuitch-reel-share-search-wrap">'
      + '          <input type="search" class="form-control zuitch-reel-share-search" placeholder="Buscar contactos" autocomplete="off" />'
      + '        </div>'
      + '        <div class="zuitch-reel-share-contacts-grid"></div>'
      + '      </div>'
      + '      <div class="zuitch-reel-share-quick-actions">' + quickHtml + '</div>'
      + '      <div class="zuitch-fallback-share-social-wrap">' + socialHtml + '</div>'
      + '      <div class="zuitch-reel-share-zuitch-cta">' + zuitchHtml + '</div>'
      + '    </div>'
      + '  </div>'
      + '</div>';

    function close() {
      if (wrap.parentNode) {
        wrap.parentNode.removeChild(wrap);
      }
      document.removeEventListener('keydown', onKey, true);
    }

    function onKey(ev) {
      if (ev.key === 'Escape') {
        close();
      }
    }

    var payloadText = (shareTitle ? String(shareTitle) + '\n' : '') + shareUrl;

    wrap.querySelector('.zfb-share-close').addEventListener('click', function (e) {
      e.preventDefault();
      close();
    });

    wrap.addEventListener('click', function (ev) {
      if (ev.target === wrap) {
        close();
        return;
      }
      var actBtn = ev.target.closest && ev.target.closest('.zfb-share-act');
      if (actBtn) {
        ev.preventDefault();
        var act = actBtn.getAttribute('data-zfb');
        if (act === 'copy') {
          zuitchFbCopyText(shareUrl).then(function (ok) {
            zuitchFbBanner(wrap, ok ? 'El enlace se copió al portapapeles.' : 'No se pudo copiar el enlace.');
          });
        } else if (act === 'native') {
          if (navigator.share) {
            navigator.share({ title: shareTitle, url: shareUrl }).catch(function () {});
          } else {
            zuitchFbCopyText(shareUrl).then(function (ok) {
              zuitchFbBanner(wrap, ok ? 'El enlace se copió al portapapeles.' : 'No se pudo copiar el enlace.');
            });
          }
        } else if (act === 'android_intent') {
          var uaAi = navigator.userAgent || '';
          if (/\bwv\b/i.test(uaAi) || window.__woReelsAndroidWebview) {
            zuitchFbBanner(wrap, 'Dentro de la app no está disponible el menú del sistema. Usa Copiar o una red social.');
            return;
          }
          if (!zuitchFallbackIntent(payloadText)) {
            zuitchFbCopyText(shareUrl).then(function (ok) {
              zuitchFbBanner(wrap, ok ? 'No se pudo abrir el selector. Enlace copiado.' : 'No se pudo abrir apps ni copiar.');
            });
          }
        }
        return;
      }
      var copySoc = ev.target.closest && ev.target.closest('.zfb-social-copy');
      if (copySoc) {
        ev.preventDefault();
        var mode = copySoc.getAttribute('data-copy-mode') || '';
        zuitchFbCopyText(shareUrl).then(function (ok) {
          if (ok) {
            zuitchFbBanner(wrap, mode === 'tiktok'
              ? 'El enlace se copió. Ábrelo en la app de TikTok.'
              : 'El enlace se copió. Ábrelo en la app de Instagram.');
          } else {
            zuitchFbBanner(wrap, 'No se pudo copiar el enlace.');
          }
        });
        return;
      }
      var zBtn = ev.target.closest && ev.target.closest('.zfb-open-zuitch-share');
      if (zBtn) {
        ev.preventDefault();
        close();
        if (postId && typeof window.Wo_SharePostOn === 'function') {
          window.Wo_SharePostOn(postId, ownerId, 'timeline');
        }
      }
    });

    document.addEventListener('keydown', onKey, true);
    document.body.appendChild(wrap);
  };
  window.ZuitchOpenReelShareSheet.__zuitchReelShareUi = 'pill-grid-v2';
})();
