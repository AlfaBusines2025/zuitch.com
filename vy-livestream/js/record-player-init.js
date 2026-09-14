(function () {
  /**
   * Reproductor de live grabado: en Apple (iOS Safari / WKWebView / Safari escritorio con política Zuitch)
   * usamos <video> nativo con controls — Fluid + autoplay muted rompe audio con frecuencia.
   */
  function isVyLvUseNativeRecordedPlayer() {
    try {
      if (window.__zuitchIsWKWebView) {
        return true;
      }
    } catch (e0) {}
    try {
      if (window.__zuitchIsAppleClient) {
        return true;
      }
    } catch (e1) {}
    try {
      var ua = String((navigator && navigator.userAgent) || '');
      if (/iPhone|iPad|iPod/i.test(ua)) {
        return true;
      }
      if (typeof navigator !== 'undefined' && navigator.platform === 'MacIntel' && (navigator.maxTouchPoints || 0) > 1) {
        return true;
      }
    } catch (e2) {}
    return false;
  }

  function prepareNativeRecordedVideo(videoEl) {
    if (!videoEl) {
      return;
    }
    try {
      videoEl.removeAttribute('muted');
      videoEl.muted = false;
      videoEl.defaultMuted = false;
      videoEl.volume = 1;
      videoEl.setAttribute('playsinline', '');
      videoEl.setAttribute('webkit-playsinline', '');
      videoEl.setAttribute('controls', '');
      videoEl.controls = true;
      videoEl.removeAttribute('autoplay');
      videoEl.autoplay = false;
      videoEl.setAttribute('preload', 'metadata');
    } catch (e) {}
  }

  function initVyLvRecordPlayer(root) {
    if (!root || root.getAttribute('data-vy-lv-scheduled') === '1' || root.getAttribute('data-vy-lv-inited') === '1') {
      return;
    }
    var playerId = root.getAttribute('data-vy-lv-id');
    if (!playerId) {
      return;
    }
    var vid = document.getElementById('vy_lv_recordplayer_id_' + playerId);
    var poster = (vid && vid.getAttribute('poster')) || '';
    var useNative = isVyLvUseNativeRecordedPlayer();

    root.setAttribute('data-vy-lv-scheduled', '1');

    function runFluid() {
      if (root.getAttribute('data-vy-lv-inited') === '1') {
        return;
      }
      root.setAttribute('data-vy-lv-inited', '1');
      var loading = document.getElementById('vy_lv_player_loading_' + playerId);
      if (loading && loading.parentNode) {
        loading.parentNode.removeChild(loading);
      }

      if (useNative) {
        prepareNativeRecordedVideo(vid);
        root.style.display = '';
        root.setAttribute('data-vy-lv-native-player', '1');
        return;
      }

      if (typeof fluidPlayer !== 'function') {
        return;
      }

      fluidPlayer('vy_lv_recordplayer_id_' + playerId, {
        layoutControls: {
          primaryColor: 'red',
          controlBar: {
            autoHideTimeout: 3,
            animated: true,
            autoHide: true,
            playbackRates: ['x2', 'x1.5', 'x1', 'x0.5']
          },
          autoPlay: true,
          mute: true,
          allowTheatre: true,
          playPauseAnimation: false,
          playbackRateEnabled: true,
          allowDownload: false,
          playButtonShowing: true,
          fillToContainer: true,
          posterImage: poster
        },
        playerInitCallback: function () {}
      });
      root.style.display = '';
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', runFluid);
    } else {
      runFluid();
    }
  }

  function scanVyLvRecordPlayers() {
    var roots = document.querySelectorAll('.vy-lv-record-player-root[data-vy-lv-id]:not([data-vy-lv-scheduled="1"])');
    for (var i = 0; i < roots.length; i++) {
      initVyLvRecordPlayer(roots[i]);
    }
  }

  scanVyLvRecordPlayers();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scanVyLvRecordPlayers);
  }
})();
