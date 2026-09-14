/**
 * iOS / Safari / WKWebView: forzar reproducción inline + bloquear Picture-in-Picture en TODO <video>.
 *
 * Equivale (a nivel web) a las dos opciones nativas que ya no podemos tocar en la app iOS:
 *   - allowsInlineMediaPlayback           = true
 *   - allowsPictureInPictureMediaPlayback = false
 *
 * Estrategia clave para iOS WKWebView (lo que evita el "se va a fullscreen al play"):
 *   1) Parchamos `HTMLMediaElement.prototype.play` SINCRONAMENTE en <head>, antes de que
 *      Plyr o cualquier código pueda invocarlo. En cada play() reaplicamos
 *      `playsinline / webkit-playsinline / x5-playsinline` y, mientras el usuario no
 *      haya interactuado con la página teniendo videos presentes, forzamos `muted=true`.
 *      iOS necesita o bien un gesto explícito o bien un video muted para reproducir
 *      inline; en cualquier otro caso lo manda a fullscreen.
 *   2) Bloqueamos el `play()` de los `<video>` que estén dentro de un reel oculto
 *      (`.reels_list.hidden`). Sin esto, el atributo HTML `autoplay` que arrastra cada
 *      reel precargado dispara 4 plays simultáneos al cargar /reels/ y se montan como
 *      ventanas fullscreen apiladas.
 *   3) Desmuteamos automáticamente los videos forzados a muted cuando el usuario
 *      interactúa por primera vez (touchstart/click/etc.) en la página.
 *   4) Bloqueamos PiP a nivel de prototipo (`webkitSetPresentationMode`,
 *      `requestPictureInPicture`) y por CSS sobre los controles de Plyr/Video.js.
 *
 * Debug avanzado (sin overlay): abrir DevTools y ejecutar
 *   `window.__ZUITCH_VIDEO_POLICY_DEBUG = true; location.reload();`
 * Para inspeccionar contadores en producción: `window.__zuitchVideoPolicyStats`.
 */
(function () {
    if (window.__zuitchIosVideoPolicyApplied) {
        return;
    }
    var ua = String((navigator && navigator.userAgent) || '');
    var isIOS = /iPhone|iPad|iPod/i.test(ua);
    var isIPadOS = (typeof navigator !== 'undefined'
        && navigator.platform === 'MacIntel'
        && (navigator.maxTouchPoints || 0) > 1);
    var isSafari = /Safari/i.test(ua)
        && !/Chrome|Chromium|CriOS|Edg|EdgA|FxiOS|Firefox|OPR|OPiOS|YaBrowser|Brave/i.test(ua);
    var isWKWebView = !!(window.webkit && window.webkit.messageHandlers);
    var shouldApply = isIOS || isIPadOS || isSafari || isWKWebView;
    if (!shouldApply) {
        return;
    }
    window.__zuitchIosVideoPolicyApplied = true;
    window.__zuitchIsAppleClient = true;
    window.__zuitchIsWKWebView = isWKWebView;

    var DEBUG = !!window.__ZUITCH_VIDEO_POLICY_DEBUG;
    try {
        var qsDbg = String(location.search || '');
        if (/[?&]zuitchVideoPolicyDebug=1\b/.test(qsDbg)) {
            DEBUG = true;
        }
    } catch (eDbgQs) {}
    function dbg() {
        if (!DEBUG) return;
        try {
            var args = ['[zuitchIosVideoPolicy]'];
            for (var i = 0; i < arguments.length; i++) {
                args.push(arguments[i]);
            }
            (console.log || function () {}).apply(console, args);
        } catch (e) {}
    }

    var stats = {
        videosTagged: 0,
        playIntercepts: 0,
        fsIntercepts: 0,
        pipBlocks: 0,
        loadstarts: 0,
        forcedMutes: 0,
        autoUnmutes: 0,
        hiddenReelBlocks: 0
    };
    window.__zuitchVideoPolicyStats = stats;

    /* ------------------------------------------------------------------- *
     *  Detección de reel oculto.
     *
     *  En /reels/ el DOM trae varios reels precargados: 1 visible y el
     *  resto con clase `.hidden`. Cada <video> trae el atributo `autoplay`.
     *  Si dejamos correr esos plays a la vez, el WebView con
     *  `allowsInlineMediaPlayback` restringido los apila como ventanas
     *  fullscreen unas sobre otras.
     *
     *  Quirúrgico: SOLO reels precargados que llevan ambas clases
     *  `.reels_list` + `.hidden`. El contenedor `.hidden_reels` es solo
     *  el padre que envuelve a todos los reels (visibles e invisibles),
     *  NO indica visibilidad. No usamos getComputedStyle aquí porque
     *  puede dar falsos positivos durante el parseo.
     * ------------------------------------------------------------------- */
    function isVideoEffectivelyHidden(v) {
        try {
            var el = v.parentElement;
            while (el && el !== document.documentElement) {
                if (el.classList
                    && el.classList.contains('reels_list')
                    && el.classList.contains('hidden')) {
                    return true;
                }
                el = el.parentElement;
            }
        } catch (e) {}
        return false;
    }

    /* ------------------------------------------------------------------- *
     *  Tracking de interacción de usuario (clave para iOS user-activation).
     *
     *  `sessionUserUnmuted`: empieza en `false`. Pasa a `true` la PRIMERA
     *  vez que el usuario interactúa con la página DESPUÉS de que ya hay
     *  <video> en el DOM. Mientras siga `false`, todos los play() se
     *  fuerzan a muted para evitar que iOS los mande a fullscreen
     *  (autoplay sin gesto + sin muted = fullscreen forzado en WKWebView).
     *  Una vez es `true`, dejamos pasar plays con sonido inline.
     * ------------------------------------------------------------------- */
    var sessionUserUnmuted = false;
    function markGesture() {
        try {
            if (!sessionUserUnmuted && document.querySelector('video')) {
                sessionUserUnmuted = true;
            }
        } catch (eS) {}
        try {
            var vids = document.querySelectorAll('video[data-zuitch-tempmute="1"]');
            for (var i = 0; i < vids.length; i++) {
                try {
                    vids[i].muted = false;
                    vids[i].defaultMuted = false;
                    vids[i].removeAttribute('data-zuitch-tempmute');
                    stats.autoUnmutes++;
                } catch (e) {}
            }
        } catch (e2) {}
    }
    try {
        var gestureEvts = ['touchstart', 'touchend', 'pointerdown', 'click', 'keydown'];
        for (var gi = 0; gi < gestureEvts.length; gi++) {
            document.addEventListener(gestureEvts[gi], markGesture, { capture: true, passive: true });
        }
    } catch (eGest) {}

    function addRootClass() {
        try {
            if (document.documentElement) {
                document.documentElement.classList.add('zuitch-ios-video-policy');
                if (isWKWebView) {
                    document.documentElement.classList.add('zuitch-wkwebview');
                }
            }
        } catch (e) {}
    }
    addRootClass();

    /* ------------------------------------------------------------------- *
     *  1) Helpers de aplicación de atributos
     * ------------------------------------------------------------------- */
    function setInlineAttrs(v) {
        if (!v || v.nodeType !== 1 || v.tagName !== 'VIDEO') {
            return;
        }
        try { v.setAttribute('playsinline', ''); } catch (e) {}
        try { v.setAttribute('webkit-playsinline', ''); } catch (e) {}
        try { v.setAttribute('x5-playsinline', ''); } catch (e) {}
        try { v.setAttribute('disablepictureinpicture', ''); } catch (e) {}
        try { v.disablePictureInPicture = true; } catch (e) {}
    }

    /* ------------------------------------------------------------------- *
     *  2) Parche síncrono de prototipos (lo más importante).
     *     Esto se instala ANTES de que se parsee cualquier <video> o se
     *     instancie Plyr porque este script se carga en <head>.
     * ------------------------------------------------------------------- */
    try {
        if (typeof HTMLMediaElement !== 'undefined' && HTMLMediaElement.prototype) {
            var mProto = HTMLMediaElement.prototype;

            if (typeof mProto.play === 'function' && !mProto.__zuitchOrigPlay) {
                mProto.__zuitchOrigPlay = mProto.play;
                mProto.play = function zuitchPatchedPlay() {
                    if (this && this.tagName === 'VIDEO') {
                        /* (a) Bloquear play() en reels OCULTOS (precargados).
                              Sin esto el autoplay HTML de los 4 reels se
                              dispara a la vez y se montan como ventanas
                              fullscreen unos sobre otros. */
                        if (isVideoEffectivelyHidden(this)) {
                            stats.hiddenReelBlocks++;
                            if (DEBUG) {
                                dbg('play() bloqueado: video en reel oculto', this);
                            }
                            try { this.pause(); } catch (eP) {}
                            return Promise.reject(typeof DOMException !== 'undefined'
                                ? new DOMException('Hidden reel — play blocked by policy', 'NotAllowedError')
                                : new Error('Hidden reel blocked'));
                        }

                        /* (b) Reaplicar atributos inline justo antes del play.
                              iOS decide AHORA si lo lleva a fullscreen. */
                        setInlineAttrs(this);
                        stats.playIntercepts++;

                        /* (c) Forzar muted=true mientras el usuario no haya
                              interactuado con la página (con videos presentes).
                              iOS exige o bien gesto activo o bien muted para
                              reproducir inline; sin nada de eso, fullscreen. */
                        try {
                            if (!sessionUserUnmuted && !this.muted) {
                                this.muted = true;
                                this.defaultMuted = true;
                                try { this.volume = 0; } catch (eV) {}
                                try { this.setAttribute('muted', ''); } catch (eA) {}
                                this.setAttribute('data-zuitch-tempmute', '1');
                                stats.forcedMutes++;
                                if (DEBUG) {
                                    dbg('play() forzando muted=true (sessionUserUnmuted=false)', this);
                                }
                            }
                        } catch (eMute) {}
                    }
                    try {
                        return mProto.__zuitchOrigPlay.apply(this, arguments);
                    } catch (e) {
                        return Promise.reject(e);
                    }
                };
            }
        }

        if (typeof HTMLVideoElement !== 'undefined' && HTMLVideoElement.prototype) {
            var vProto = HTMLVideoElement.prototype;

            /* webkitEnterFullscreen: reaplicar atributos antes de delegar
               (no impide fullscreen MANUAL del usuario, pero sí evita que
               un play automático pase por aquí sin el flag). */
            if (typeof vProto.webkitEnterFullscreen === 'function'
                && !vProto.__zuitchOrigWebkitEnterFullscreen) {
                vProto.__zuitchOrigWebkitEnterFullscreen = vProto.webkitEnterFullscreen;
                vProto.webkitEnterFullscreen = function () {
                    setInlineAttrs(this);
                    stats.fsIntercepts++;
                    if (DEBUG) {
                        dbg('webkitEnterFullscreen interceptado', this);
                    }
                    try {
                        return vProto.__zuitchOrigWebkitEnterFullscreen.apply(this, arguments);
                    } catch (e) {
                        return undefined;
                    }
                };
            }

            /* Bloqueo de PiP a nivel API.
               disablePictureInPicture solo bloquea la API estándar; iOS Safari
               tiene `webkitSetPresentationMode` privada que la esquiva. */
            if (typeof vProto.webkitSetPresentationMode === 'function'
                && !vProto.__zuitchOrigSetPresentationMode) {
                vProto.__zuitchOrigSetPresentationMode = vProto.webkitSetPresentationMode;
                vProto.webkitSetPresentationMode = function (mode) {
                    if (mode === 'picture-in-picture') {
                        stats.pipBlocks++;
                        if (DEBUG) {
                            dbg('webkitSetPresentationMode("picture-in-picture") bloqueado', this);
                        }
                        return undefined;
                    }
                    try {
                        return vProto.__zuitchOrigSetPresentationMode.apply(this, arguments);
                    } catch (e) {
                        return undefined;
                    }
                };
            }
            if (typeof vProto.webkitSupportsPresentationMode === 'function'
                && !vProto.__zuitchOrigSupportsPresentationMode) {
                vProto.__zuitchOrigSupportsPresentationMode = vProto.webkitSupportsPresentationMode;
                vProto.webkitSupportsPresentationMode = function (mode) {
                    if (mode === 'picture-in-picture') {
                        return false;
                    }
                    try {
                        return vProto.__zuitchOrigSupportsPresentationMode.apply(this, arguments);
                    } catch (e) {
                        return false;
                    }
                };
            }
            if (typeof vProto.requestPictureInPicture === 'function'
                && !vProto.__zuitchOrigRequestPictureInPicture) {
                vProto.__zuitchOrigRequestPictureInPicture = vProto.requestPictureInPicture;
                vProto.requestPictureInPicture = function () {
                    stats.pipBlocks++;
                    var DOMExc = (typeof DOMException !== 'undefined') ? DOMException : null;
                    var err = DOMExc
                        ? new DOMExc('Picture-in-Picture disabled by Zuitch policy', 'NotAllowedError')
                        : new Error('PiP disabled');
                    return Promise.reject(err);
                };
            }
        }
    } catch (eProto) {
        try {
            if (typeof console !== 'undefined' && console.error) {
                console.error('[zuitchIosVideoPolicy] Error parchando prototipos', eProto);
            }
        } catch (eLog) {}
    }

    /* ------------------------------------------------------------------- *
     *  3) Eventos PiP defensivos
     * ------------------------------------------------------------------- */
    function blockEnterPip(ev) {
        try { ev.preventDefault(); } catch (e) {}
        try {
            if (document.pictureInPictureElement
                && typeof document.exitPictureInPicture === 'function') {
                document.exitPictureInPicture().catch(function () {});
            }
        } catch (e2) {}
    }

    /* ------------------------------------------------------------------- *
     *  4) Aplicar atributos a cada <video>
     * ------------------------------------------------------------------- */
    function applyToVideo(v) {
        if (!v || v.nodeType !== 1 || v.tagName !== 'VIDEO') {
            return;
        }
        if (v.__zuitchIosPolicyBound) {
            return;
        }
        v.__zuitchIosPolicyBound = true;
        setInlineAttrs(v);
        stats.videosTagged++;
        /* Si en el momento de etiquetar el video ya está dentro de un
           reel oculto, quitar `autoplay` HTML para que el browser ni
           lo intente. La lógica de reels llamará a play() explícito
           cuando el reel se haga visible (y nuestro patch no lo bloquea
           porque ya no llevará la clase `.hidden`). */
        try {
            if (isVideoEffectivelyHidden(v) && v.hasAttribute('autoplay')) {
                v.removeAttribute('autoplay');
                try { v.autoplay = false; } catch (eA) {}
            }
        } catch (eAuto) {}
        try {
            v.addEventListener('enterpictureinpicture', blockEnterPip, true);
        } catch (e) {}
        try {
            v.addEventListener('webkitpresentationmodechanged', function () {
                try {
                    if (v.webkitPresentationMode === 'picture-in-picture'
                        && typeof v.webkitSetPresentationMode === 'function') {
                        v.webkitSetPresentationMode('inline');
                    }
                } catch (e) {}
            }, true);
        } catch (e) {}
        try {
            v.addEventListener('loadstart', function () {
                stats.loadstarts++;
                setInlineAttrs(v);
            }, true);
        } catch (e) {}
        if (DEBUG) {
            dbg('video etiquetado', v, {
                playsinline: v.hasAttribute('playsinline'),
                'webkit-playsinline': v.hasAttribute('webkit-playsinline'),
                disablePiP: v.disablePictureInPicture
            });
        }
    }

    function scan(root) {
        try {
            var scope = root && root.querySelectorAll ? root : document;
            var list = scope.querySelectorAll('video');
            for (var i = 0; i < list.length; i++) {
                applyToVideo(list[i]);
            }
        } catch (e) {}
    }

    /* ------------------------------------------------------------------- *
     *  5) CSS: ocultar botones PiP/fullscreen de players y controles nativos
     * ------------------------------------------------------------------- */
    function injectCss() {
        try {
            if (document.getElementById('zuitch-ios-video-policy-style')) {
                return;
            }
            var css =
                'html.zuitch-ios-video-policy .plyr__control[data-plyr="pip"],'
                + 'html.zuitch-ios-video-policy .vjs-picture-in-picture-control,'
                + 'html.zuitch-ios-video-policy .vjs-pip-control'
                + '{display:none !important;visibility:hidden !important;}'
                + 'html.zuitch-ios-video-policy video::-webkit-media-controls-fullscreen-button,'
                + 'html.zuitch-ios-video-policy video::-webkit-media-controls-picture-in-picture-button'
                + '{display:none !important;-webkit-appearance:none !important;}';
            var style = document.createElement('style');
            style.id = 'zuitch-ios-video-policy-style';
            style.type = 'text/css';
            style.appendChild(document.createTextNode(css));
            (document.head || document.documentElement).appendChild(style);
        } catch (e) {}
    }

    /* ------------------------------------------------------------------- *
     *  6) Observar mutaciones (videos por AJAX, prefetch reels, etc.)
     * ------------------------------------------------------------------- */
    function makeObserver() {
        try {
            return new MutationObserver(function (muts) {
                for (var i = 0; i < muts.length; i++) {
                    var added = muts[i].addedNodes;
                    if (!added) continue;
                    for (var j = 0; j < added.length; j++) {
                        var n = added[j];
                        if (!n) continue;
                        if (n.nodeType === 1 && n.tagName === 'VIDEO') {
                            applyToVideo(n);
                        } else if (n.querySelectorAll) {
                            var inner = n.querySelectorAll('video');
                            for (var k = 0; k < inner.length; k++) {
                                applyToVideo(inner[k]);
                            }
                        }
                    }
                }
            });
        } catch (e) {
            return null;
        }
    }

    /* Observer temprano sobre <html> para atrapar videos en cuanto se parseen. */
    if (document.documentElement) {
        try {
            var earlyObs = makeObserver();
            if (earlyObs) {
                earlyObs.observe(document.documentElement, { childList: true, subtree: true });
                window.__zuitchIosVideoPolicyEarlyObserver = earlyObs;
            }
        } catch (e) {}
    }

    /* loadstart en captura: cualquier video del documento que empiece a
       cargar reaplica atributos justo antes de la decisión de iOS. */
    try {
        document.addEventListener('loadstart', function (ev) {
            if (ev && ev.target && ev.target.tagName === 'VIDEO') {
                applyToVideo(ev.target);
                setInlineAttrs(ev.target);
            }
        }, true);
    } catch (e) {}

    /* ------------------------------------------------------------------- *
     *  7) Init: CSS + scan + listeners globales
     * ------------------------------------------------------------------- */
    var __zuitchDomBootstrapDone = false;
    function ensureDomBootstrap() {
        if (__zuitchDomBootstrapDone) {
            return;
        }
        __zuitchDomBootstrapDone = true;
        try {
            addRootClass();
            injectCss();
            scan(document);
        } catch (e) {}
    }
    var __zuitchGlobalListenersDone = false;
    function ensureGlobalListeners() {
        if (__zuitchGlobalListenersDone) {
            return;
        }
        __zuitchGlobalListenersDone = true;
        try {
            document.addEventListener('enterpictureinpicture', blockEnterPip, true);
        } catch (e) {}
    }
    function init() {
        ensureDomBootstrap();
        ensureGlobalListeners();
        // Overlay visual desactivado a propósito; el flag DEBUG sigue habilitando
        // logs de consola y `window.__zuitchVideoPolicyStats` para inspección.
        // Para reactivar el overlay, llamar manualmente a window.__zuitchMountVideoPolicyOverlay().
        if (DEBUG) {
            try { window.__zuitchMountVideoPolicyOverlay = mountDebugOverlay; } catch (eExp) {}
        }
    }

    if (document.readyState !== 'loading') {
        ensureDomBootstrap();
    } else {
        document.addEventListener('readystatechange', function zuitchVideoPolicyRs() {
            if (document.readyState !== 'loading') {
                ensureDomBootstrap();
                document.removeEventListener('readystatechange', zuitchVideoPolicyRs);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }

    /* ------------------------------------------------------------------- *
     *  8) Overlay de depuración (solo si DEBUG)
     *
     *  Activable con `?zuitchVideoPolicyDebug=1` en la URL o
     *  `window.__ZUITCH_VIDEO_POLICY_DEBUG = true` antes de cargar el script.
     * ------------------------------------------------------------------- */
    function mountDebugOverlay() {
        try {
            if (document.getElementById('zuitch-video-policy-debug')) {
                return;
            }
            var box = document.createElement('div');
            box.id = 'zuitch-video-policy-debug';
            box.style.cssText = [
                'position:fixed', 'left:6px', 'bottom:6px', 'z-index:2147483647',
                'background:rgba(0,0,0,.78)', 'color:#0f0', 'font:11px/1.3 monospace',
                'padding:6px 8px', 'border-radius:6px', 'max-width:62vw', 'pointer-events:auto',
                'border:1px solid rgba(0,255,0,.35)', 'white-space:pre-wrap'
            ].join(';');
            box.addEventListener('click', function () {
                try { box.style.display = (box.style.display === 'none') ? '' : 'none'; } catch (e) {}
            });
            (document.body || document.documentElement).appendChild(box);
            function paint() {
                if (!box.isConnected && document.body) {
                    document.body.appendChild(box);
                }
                var tmpMutedNow = 0;
                try {
                    tmpMutedNow = document.querySelectorAll('video[data-zuitch-tempmute="1"]').length;
                } catch (e) {}
                box.textContent =
                    'iOS-VideoPolicy DBG (toca para ocultar)'
                    + '\n  WKWebView=' + isWKWebView
                    + '  iOS=' + isIOS
                    + '  Safari=' + isSafari
                    + '\n  videos=' + stats.videosTagged
                    + '  play()=' + stats.playIntercepts
                    + '  loadstart=' + stats.loadstarts
                    + '\n  fs()=' + stats.fsIntercepts
                    + '  pipBlocks=' + stats.pipBlocks
                    + '\n  forcedMute=' + stats.forcedMutes
                    + '  autoUnmute=' + stats.autoUnmutes
                    + '  tempMutedNow=' + tmpMutedNow
                    + '\n  hiddenBlocks=' + stats.hiddenReelBlocks
                    + '\n  sessionUserUnmuted=' + sessionUserUnmuted
                    + '\n  ua=' + ua.slice(0, 90);
            }
            paint();
            setInterval(paint, 1000);
            window.__zuitchVideoPolicyDebugRepaint = paint;
        } catch (e) {}
    }
})();
