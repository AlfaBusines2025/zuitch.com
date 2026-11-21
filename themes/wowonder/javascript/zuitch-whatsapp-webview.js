/**
 * Android WebView: open WhatsApp via whatsapp:// deeplink instead of https://wa.me
 * (which stays inside the WebView). Does not use location.href = intent:… (hangs the app).
 */
(function () {
  'use strict';

  function isAndroidWebView() {
    try {
      if (typeof window.__ZUITCH_ANDROID_WEBVIEW !== 'undefined' && window.__ZUITCH_ANDROID_WEBVIEW) {
        return true;
      }
      if (window.__woReelsAndroidWebview) {
        return true;
      }
      var ua = navigator.userAgent || '';
      if (/Android/i.test(ua) && /\bwv\b/i.test(ua)) {
        return true;
      }
      if (/Android/i.test(ua) && typeof window.ZuitchAppBridge !== 'undefined' && window.ZuitchAppBridge) {
        return true;
      }
    } catch (e) {}
    return false;
  }

  function findAnchor(el) {
    var n = el;
    while (n && n !== document && n !== document.body) {
      if (n.nodeType === 1 && n.tagName === 'A' && n.getAttribute('href')) {
        return n;
      }
      n = n.parentNode;
    }
    return null;
  }

  function parseWhatsAppHref(href) {
    if (!href || typeof href !== 'string') {
      return null;
    }
    var raw = href.trim();
    if (!raw) {
      return null;
    }
    var lower = raw.toLowerCase();
    var isWa =
      lower.indexOf('wa.me/') !== -1 ||
      lower.indexOf('api.whatsapp.com/') !== -1 ||
      lower.indexOf('whatsapp.com/send') !== -1 ||
      lower.indexOf('whatsapp://') === 0;
    if (!isWa) {
      return null;
    }

    var phone = '';
    var text = '';
    try {
      var u;
      if (lower.indexOf('whatsapp://') === 0) {
        u = new URL(raw.replace(/^whatsapp:\/\//i, 'https://whatsapp.local/'));
      } else {
        u = new URL(raw, window.location.href);
      }
      phone = (u.searchParams.get('phone') || '').replace(/\D+/g, '');
      text = u.searchParams.get('text') || '';
      if (!phone) {
        var path = (u.pathname || '').replace(/^\/+/, '');
        var m = path.match(/^(\d{8,15})(?:\/|$|\?)/);
        if (m) {
          phone = m[1];
        } else if (/^wa\.me$/i.test(u.hostname || '') || /(^|\.)wa\.me$/i.test(u.hostname || '')) {
          var digits = path.replace(/\D+/g, '');
          if (digits.length >= 8 && digits.length <= 15) {
            phone = digits;
          }
        }
      }
    } catch (eParse) {
      var phoneM = raw.match(/[?&]phone=(\d{8,15})/i) || raw.match(/wa\.me\/(\d{8,15})/i);
      if (phoneM) {
        phone = phoneM[1];
      }
      var textM = raw.match(/[?&]text=([^&]*)/i);
      if (textM) {
        try {
          text = decodeURIComponent(textM[1].replace(/\+/g, ' '));
        } catch (eT) {
          text = textM[1];
        }
      }
    }

    if (!phone && !text) {
      return null;
    }
    return { phone: phone, text: text };
  }

  function buildDeeplink(parsed) {
    var parts = [];
    if (parsed.phone) {
      parts.push('phone=' + encodeURIComponent(parsed.phone));
    }
    if (parsed.text) {
      parts.push('text=' + encodeURIComponent(parsed.text));
    }
    return 'whatsapp://send' + (parts.length ? '?' + parts.join('&') : '');
  }

  function openViaBridge(url) {
    try {
      var b = window.ZuitchAppBridge;
      if (!b) {
        return false;
      }
      if (typeof b.openUrl === 'function') {
        b.openUrl(url);
        return true;
      }
      if (typeof b.openExternal === 'function') {
        b.openExternal(url);
        return true;
      }
      if (typeof b.openExternalUrl === 'function') {
        b.openExternalUrl(url);
        return true;
      }
    } catch (eB) {}
    return false;
  }

  function openDeeplink(url) {
    if (openViaBridge(url)) {
      return;
    }
    try {
      var iframe = document.createElement('iframe');
      iframe.setAttribute('aria-hidden', 'true');
      iframe.style.cssText = 'display:none;width:0;height:0;border:0;position:absolute;left:-9999px;';
      iframe.src = url;
      document.body.appendChild(iframe);
      setTimeout(function () {
        try {
          if (iframe.parentNode) {
            iframe.parentNode.removeChild(iframe);
          }
        } catch (eRm) {}
      }, 2500);
    } catch (eI) {}
  }

  function onClickCapture(ev) {
    if (!isAndroidWebView()) {
      return;
    }
    var a = findAnchor(ev.target);
    if (!a) {
      return;
    }
    var href = a.getAttribute('href') || a.href || '';
    var parsed = parseWhatsAppHref(href);
    if (!parsed) {
      return;
    }
    ev.preventDefault();
    if (typeof ev.stopPropagation === 'function') {
      ev.stopPropagation();
    }
    openDeeplink(buildDeeplink(parsed));
  }

  function install() {
    if (window.__zuitchWaWebviewInstalled) {
      return;
    }
    window.__zuitchWaWebviewInstalled = true;
    document.addEventListener('click', onClickCapture, true);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', install);
  } else {
    install();
  }
})();
