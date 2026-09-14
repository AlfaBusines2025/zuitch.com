/**
 * Perfil: en iOS (Safari + WebView + Chrome iOS) reemplaza el iframe de Google Maps Embed
 * por una vista estática (Static Maps API) + enlace a Maps. En desktop/Android copia data-src a src.
 */
(function () {
  'use strict';

  function zuitchIsIOSDevice() {
    var ua = navigator.userAgent || '';
    if (/iPad|iPhone|iPod/i.test(ua)) return true;
    if (navigator.platform === 'MacIntel' && (navigator.maxTouchPoints || 0) > 1) return true;
    return false;
  }

  window.zuitchIsIOSDevice = zuitchIsIOSDevice;

  function zuitchExtractQueryParam(url, name) {
    try {
      var u = new URL(url, window.location.href);
      return u.searchParams.get(name) || '';
    } catch (e) {
      var re = new RegExp('[?&]' + name + '=([^&]*)');
      var m = String(url).match(re);
      return m ? decodeURIComponent(m[1].replace(/\+/g, ' ')) : '';
    }
  }

  function zuitchBuildStaticMapUrl(address, apiKey) {
    var enc = encodeURIComponent(address);
    return (
      'https://maps.googleapis.com/maps/api/staticmap?' +
      'center=' +
      enc +
      '&zoom=15&size=640x320&scale=2&markers=color:red%7C' +
      enc +
      '&key=' +
      encodeURIComponent(apiKey)
    );
  }

  function zuitchReplaceIframeForIOS(iframe, address, apiKey) {
    var mapsHref = 'https://www.google.com/maps?q=' + encodeURIComponent(address);
    var staticSrc = zuitchBuildStaticMapUrl(address, apiKey);
    var prevStyle = iframe.getAttribute('style') || '';

    var a = document.createElement('a');
    a.className = (iframe.className ? iframe.className + ' ' : '') + 'zuitch-map-static';
    a.setAttribute('href', mapsHref);
    a.setAttribute('target', '_blank');
    a.setAttribute('rel', 'noopener noreferrer');
    if (prevStyle) a.setAttribute('style', prevStyle);

    var img = document.createElement('img');
    img.className = 'zuitch-map-static-img';
    img.setAttribute('alt', address);
    img.setAttribute('loading', 'lazy');
    img.setAttribute('decoding', 'async');
    img.setAttribute('src', staticSrc);
    img.setAttribute(
      'style',
      'width:100%;max-width:100%;height:auto;border:0;border-radius:6px;display:block;'
    );
    img.addEventListener('error', function () {
      img.style.display = 'none';
      a.classList.add('zuitch-map-static-fallback');
    });

    var cta = document.createElement('span');
    cta.className = 'zuitch-map-static-cta';
    cta.textContent = 'Abrir en Maps';
    cta.setAttribute(
      'style',
      'display:block;margin-top:8px;font-size:14px;text-align:center;opacity:0.9;'
    );

    a.appendChild(img);
    a.appendChild(cta);

    if (iframe.parentNode) {
      iframe.parentNode.replaceChild(a, iframe);
    }
  }

  function zuitchHydrateOne(iframe) {
    if (iframe.getAttribute('data-zuitch-hydrated') === '1') return;
    var dataSrc = iframe.getAttribute('data-src');
    if (!dataSrc) return;

    if (!zuitchIsIOSDevice()) {
      iframe.src = dataSrc;
      iframe.removeAttribute('data-src');
      iframe.setAttribute('data-zuitch-hydrated', '1');
      return;
    }

    var address =
      iframe.getAttribute('data-zuitch-map-q') ||
      zuitchExtractQueryParam(dataSrc, 'q') ||
      '';
    var apiKey =
      iframe.getAttribute('data-zuitch-map-key') ||
      zuitchExtractQueryParam(dataSrc, 'key') ||
      '';

    if (!address || !apiKey) {
      iframe.src = dataSrc;
      iframe.removeAttribute('data-src');
      iframe.setAttribute('data-zuitch-hydrated', '1');
      return;
    }

    iframe.setAttribute('data-zuitch-hydrated', '1');
    zuitchReplaceIframeForIOS(iframe, address, apiKey);
  }

  function zuitchHydrateMaps() {
    var list = document.querySelectorAll('iframe.zuitch-map-embed[data-src]');
    for (var i = 0; i < list.length; i++) {
      zuitchHydrateOne(list[i]);
    }
  }

  window.zuitchHydrateMaps = zuitchHydrateMaps;

  function zuitchBootMapsObserver() {
    zuitchHydrateMaps();
    if (typeof MutationObserver === 'undefined') return;
    var t = null;
    var obs = new MutationObserver(function () {
      if (t) clearTimeout(t);
      t = setTimeout(function () {
        t = null;
        zuitchHydrateMaps();
      }, 100);
    });
    obs.observe(document.documentElement || document.body, { childList: true, subtree: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', zuitchBootMapsObserver);
  } else {
    zuitchBootMapsObserver();
  }
})();
